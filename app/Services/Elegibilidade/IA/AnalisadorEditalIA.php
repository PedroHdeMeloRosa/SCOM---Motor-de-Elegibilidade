<?php

namespace App\Services\IA;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnalisadorEditalIA
{
    private const REGIOES_VALIDAS = [
        'SUDESTE', 'SUL', 'CENTRO_OESTE', 'NORDESTE', 'NORTE', 'INTERNACIONAL',
    ];

    private const BENEFICIOS_VALIDOS = [
        'ISENCAO_TAXA', 'COTA_RENDA', 'COTA_ESCOLA_PUBLICA', 'COTA_RACIAL', 'AMPLA_CONCORRENCIA',
    ];

    /**
     * @return array<string, mixed>
     */
    public function analisar(string $textoEdital): array
    {
        if (!config('scom.ai.enabled', true)) {
            throw new RuntimeException('A leitura assistida por IA está desabilitada neste ambiente. Utilize o preenchimento manual.');
        }

        $url = rtrim((string) config('scom.ai.url', 'http://127.0.0.1:11434'), '/');
        $modelo = (string) config('scom.ai.model', 'qwen2.5:3b');
        $timeout = max(5, (int) config('scom.ai.timeout', 60));

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->post("{$url}/api/chat", [
                    'model' => $modelo,
                    'stream' => false,
                    'format' => 'json',
                    'options' => [
                        'temperature' => 0.1,
                    ],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->montarSystemPrompt()],
                        ['role' => 'user', 'content' => "Extraia os dados estruturados do edital abaixo:\n\n{$textoEdital}"],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('O serviço de IA local (Ollama) está indisponível no momento. Você pode preencher o edital manualmente.', 0, $e);
        }

        if (!$response->successful()) {
            throw new RuntimeException('A IA local retornou erro (HTTP ' . $response->status() . '). Verifique se o modelo "' . $modelo . '" está instalado no Ollama.');
        }

        $conteudo = $response->json('message.content') ?? $response->json('response');
        if (!is_string($conteudo) || trim($conteudo) === '') {
            throw new RuntimeException('A IA local retornou uma resposta vazia.');
        }

        $bruto = json_decode($conteudo, true);
        if (!is_array($bruto)) {
            throw new RuntimeException('A IA local não retornou um JSON válido para preenchimento.');
        }

        return $this->sanitizarSugestao($bruto);
    }

    private function montarSystemPrompt(): string
    {
        return <<<'PROMPT'
Você é um assistente de extração de dados de editais universitários brasileiros para o sistema SCOM.
Retorne EXCLUSIVAMENTE um objeto JSON válido (sem markdown, sem comentários) seguindo exatamente esta estrutura:
{
  "instituicao": string|null,
  "nome_processo": string|null,
  "cidade": string|null,
  "estado": "UF com 2 letras maiúsculas"|null,
  "regiao": "SUDESTE"|"SUL"|"CENTRO_OESTE"|"NORDESTE"|"NORTE"|"INTERNACIONAL"|null,
  "ano_referencia": integer|null,
  "custo_medio_republica": number|null,
  "tem_moradia_estudantil": boolean|null,
  "link_documento": "url http/https"|null,
  "regra": {
    "tipo_beneficio": "ISENCAO_TAXA"|"COTA_RENDA"|"COTA_ESCOLA_PUBLICA"|"COTA_RACIAL"|"AMPLA_CONCORRENCIA"|null,
    "renda_maxima_per_capita": number|null,
    "exige_escola_publica": boolean,
    "exige_etnia": boolean,
    "exige_pcd": boolean,
    "nota_corte_estimada": integer|null,
    "prazo_inicio": "AAAA-MM-DD"|null,
    "prazo_fim": "AAAA-MM-DD"|null,
    "documentos_exigidos": string|null
  }
}
Regras obrigatórias:
- Nunca invente informações ausentes no texto; use null quando não encontrar.
- Datas devem estar estritamente no formato AAAA-MM-DD.
- Valores monetários devem ser números puros (ex: 2118.00, sem "R$").
PROMPT;
    }

    /**
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    private function sanitizarSugestao(array $dados): array
    {
        $regraBruta = is_array($dados['regra'] ?? null) ? $dados['regra'] : [];

        $estado = $this->textoLimpo($dados['estado'] ?? null, 2);
        $estado = ($estado !== null && preg_match('/^[A-Za-z]{2}$/', $estado)) ? strtoupper($estado) : null;

        $regiao = strtoupper((string) ($dados['regra_regiao'] ?? $dados['regiao'] ?? ''));
        $regiao = in_array($regiao, self::REGIOES_VALIDAS, true) ? $regiao : null;

        $ano = is_numeric($dados['ano_referencia'] ?? null) ? (int) $dados['ano_referencia'] : null;
        if ($ano !== null && ($ano < 2000 || $ano > 2155)) {
            $ano = null;
        }

        $link = $this->textoLimpo($dados['link_documento'] ?? null, 255);
        if ($link !== null && (!filter_var($link, FILTER_VALIDATE_URL) || !in_array(parse_url($link, PHP_URL_SCHEME), ['http', 'https'], true))) {
            $link = null;
        }

        $tipoBeneficio = strtoupper((string) ($regraBruta['tipo_beneficio'] ?? ''));
        $tipoBeneficio = in_array($tipoBeneficio, self::BENEFICIOS_VALIDOS, true) ? $tipoBeneficio : null;

        $notaCorte = is_numeric($regraBruta['nota_corte_estimada'] ?? null) ? (int) round((float) $regraBruta['nota_corte_estimada']) : null;
        if ($notaCorte !== null && ($notaCorte < 0 || $notaCorte > 1000)) {
            $notaCorte = null;
        }

        return [
            'instituicao' => $this->textoLimpo($dados['instituicao'] ?? null, 100),
            'nome_processo' => $this->textoLimpo($dados['nome_processo'] ?? null, 150),
            'cidade' => $this->textoLimpo($dados['cidade'] ?? null, 100),
            'estado' => $estado,
            'regiao' => $regiao,
            'ano_referencia' => $ano,
            'custo_medio_republica' => $this->numeroPositivo($dados['custo_medio_republica'] ?? null),
            'tem_moradia_estudantil' => is_bool($dados['tem_moradia_estudantil'] ?? null) ? $dados['tem_moradia_estudantil'] : null,
            'link_documento' => $link,
            'regra' => [
                'tipo_beneficio' => $tipoBeneficio,
                'renda_maxima_per_capita' => $this->numeroPositivo($regraBruta['renda_maxima_per_capita'] ?? null),
                'exige_escola_publica' => (bool) ($regraBruta['exige_escola_publica'] ?? false),
                'exige_etnia' => (bool) ($regraBruta['exige_etnia'] ?? false),
                'exige_pcd' => (bool) ($regraBruta['exige_pcd'] ?? false),
                'nota_corte_estimada' => $notaCorte,
                'prazo_inicio' => $this->dataIso($regraBruta['prazo_inicio'] ?? null),
                'prazo_fim' => $this->dataIso($regraBruta['prazo_fim'] ?? null),
                'documentos_exigidos' => $this->textoLimpo($regraBruta['documentos_exigidos'] ?? null, 10000),
            ],
        ];
    }

    private function textoLimpo(mixed $valor, int $max): ?string
    {
        if (!is_string($valor)) {
            return null;
        }
        $limpo = trim($valor);
        return $limpo === '' ? null : mb_substr($limpo, 0, $max);
    }

    private function numeroPositivo(mixed $valor): ?float
    {
        if (!is_numeric($valor)) {
            return null;
        }
        $num = round((float) $valor, 2);
        return $num >= 0 ? $num : null;
    }

    private function dataIso(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }
        $valor = trim($valor);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }
        [$ano, $mes, $dia] = array_map('intval', explode('-', $valor));
        return checkdate($mes, $dia, $ano) ? $valor : null;
    }
}