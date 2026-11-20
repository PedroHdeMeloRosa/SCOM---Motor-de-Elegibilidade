<?php

namespace App\Services\Elegibilidade;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Avalia somente os critérios objetivos registrados em regras_elegibilidade.
 * Mobilidade, moradia e custo de vida são informações logísticas, não critérios
 * oficiais de elegibilidade, e devem ser apresentados separadamente.
 */
class AvaliadorElegibilidade
{
    private const RACAS_PPI = ['PRETA', 'PARDA', 'INDIGENA'];

    /**
     * @param array<string, mixed> $perfil
     * @param array<string, mixed> $regra
     * @param string|DateTimeInterface|null $hoje
     * @return array{
     *   status: string,
     *   diagnostico: string,
     *   motivos: array<int, string>,
     *   pendencias: array<int, string>,
     *   alertas: array<int, string>
     * }
     */
    public function avaliar(array $perfil, array $regra, string|DateTimeInterface|null $hoje = null): array
    {
        $dataHoje = $hoje instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($hoje)->setTime(0, 0)
            : new DateTimeImmutable(($hoje ?: 'today'));
        $dataHoje = $dataHoje->setTime(0, 0);

        if (($regra['status_regra'] ?? null) !== 'ATIVA') {
            return $this->resultado('INATIVA', ['A regra não está ativa.']);
        }

        try {
            $inicio = $this->dataObrigatoria($regra['prazo_inicio'] ?? null, 'prazo_inicio');
            $fim = $this->dataObrigatoria($regra['prazo_fim'] ?? null, 'prazo_fim');
        } catch (InvalidArgumentException $exception) {
            return $this->resultado('REGRA_INVALIDA', [
                'A regra cadastrada possui datas inválidas e precisa ser revisada pela coordenação.',
            ]);
        }

        if ($fim < $inicio) {
            return $this->resultado('REGRA_INVALIDA', [
                'O prazo final da regra é anterior ao prazo inicial. A coordenação precisa corrigir o cadastro.',
            ]);
        }

        $tipoBeneficio = $regra['tipo_beneficio'] ?? null;
        $tiposPermitidos = ['ISENCAO_TAXA', 'COTA_RENDA', 'COTA_ESCOLA_PUBLICA', 'COTA_RACIAL', 'AMPLA_CONCORRENCIA'];
        if (!in_array($tipoBeneficio, $tiposPermitidos, true)) {
            return $this->resultado('REGRA_INVALIDA', [
                'A modalidade cadastrada não é reconhecida pelo sistema e precisa ser revisada pela coordenação.',
            ]);
        }

        if ($tipoBeneficio === 'COTA_RENDA' && ($regra['renda_maxima_per_capita'] ?? null) === null) {
            return $this->resultado('REGRA_INVALIDA', [
                'A modalidade de cota de renda não possui limite de renda cadastrado.',
            ]);
        }
        if ($tipoBeneficio === 'COTA_ESCOLA_PUBLICA' && !(bool) ($regra['exige_escola_publica'] ?? false)) {
            return $this->resultado('REGRA_INVALIDA', [
                'A modalidade de escola pública não está marcada como exigindo escola pública.',
            ]);
        }
        if ($tipoBeneficio === 'COTA_RACIAL' && !(bool) ($regra['exige_etnia'] ?? false)) {
            return $this->resultado('REGRA_INVALIDA', [
                'A modalidade étnico-racial não possui o critério racial habilitado.',
            ]);
        }

        if ($dataHoje < $inicio) {
            return $this->resultado('AGUARDANDO_ABERTURA', [
                'As inscrições começam em '.$inicio->format('d/m/Y').'.',
            ]);
        }

        if ($dataHoje > $fim) {
            return $this->resultado('ENCERRADO', [
                'O prazo terminou em '.$fim->format('d/m/Y').'.',
            ]);
        }

        $motivos = [];
        $pendencias = [];
        $alertas = [];

        if ((bool) ($regra['exige_escola_publica'] ?? false)) {
            $tipoEscola = $perfil['tipo_escola'] ?? null;
            if ($this->vazio($tipoEscola)) {
                $pendencias[] = 'Informe o tipo de escola em que cursou o ensino médio.';
            } elseif ($tipoEscola !== 'PUBLICA') {
                $motivos[] = 'Esta regra exige ensino médio cursado integralmente em escola pública.';
            }
        }

        $rendaMaxima = $regra['renda_maxima_per_capita'] ?? null;
        if ($rendaMaxima !== null) {
            $renda = $perfil['renda_per_capita'] ?? null;
            if ($this->vazio($renda) || !is_numeric($renda)) {
                $pendencias[] = 'Informe sua renda familiar per capita para verificar o limite da regra.';
            } elseif ((float) $renda > (float) $rendaMaxima) {
                $motivos[] = sprintf(
                    'A renda per capita informada (R$ %s) supera o limite da regra (R$ %s).',
                    number_format((float) $renda, 2, ',', '.'),
                    number_format((float) $rendaMaxima, 2, ',', '.')
                );
            }
        }

        if ((bool) ($regra['exige_etnia'] ?? false)) {
            $racaCor = strtoupper(trim((string) ($perfil['raca_cor'] ?? '')));
            if ($racaCor === '' || $racaCor === 'NAO_INFORMAR') {
                $pendencias[] = 'A autodeclaração de raça/cor é necessária para avaliar esta modalidade.';
            } elseif (!in_array($racaCor, self::RACAS_PPI, true)) {
                $motivos[] = 'A modalidade exige autodeclaração nas categorias PRETA, PARDA ou INDÍGENA.';
            }
        }

        if ((bool) ($regra['exige_pcd'] ?? false)) {
            if (!array_key_exists('pcd', $perfil) || $perfil['pcd'] === null || $perfil['pcd'] === '') {
                $pendencias[] = 'Informe se você é pessoa com deficiência (PcD) para avaliar esta modalidade.';
            } elseif (!(bool) $perfil['pcd']) {
                $motivos[] = 'Esta regra exige a condição de pessoa com deficiência (PcD), que não foi declarada na ficha.';
            }
        }

        // nota_corte_estimada é uma referência informativa do banco legado, não
        // uma condição formal de elegibilidade definida por um campo próprio.
        $notaCorte = $regra['nota_corte_estimada'] ?? null;
        $notaAluno = $perfil['nota_media_enem'] ?? null;
        if ($notaCorte !== null && is_numeric($notaAluno) && (float) $notaAluno < (float) $notaCorte) {
            $alertas[] = sprintf(
                'Sua nota está abaixo da nota de corte estimada de %s pontos. Essa referência não substitui os critérios oficiais do edital.',
                number_format((float) $notaCorte, 0, ',', '.')
            );
        }

        if ($motivos !== []) {
            return [
                'status' => 'INCOMPATIVEL',
                'diagnostico' => 'Não atende a um ou mais critérios cadastrados: '.implode(' ', $motivos),
                'motivos' => $motivos,
                'pendencias' => $pendencias,
                'alertas' => $alertas,
            ];
        }

        if ($pendencias !== []) {
            return [
                'status' => 'PENDENTE',
                'diagnostico' => 'Não foi possível concluir a triagem. Complete as informações indicadas: '.implode(' ', $pendencias),
                'motivos' => [],
                'pendencias' => $pendencias,
                'alertas' => $alertas,
            ];
        }

        return [
            'status' => 'COMPATIVEL',
            'diagnostico' => 'Os dados informados atendem aos critérios cadastrados nesta regra. Confira os documentos e o edital oficial; esta triagem não garante aprovação.',
            'motivos' => [],
            'pendencias' => [],
            'alertas' => $alertas,
        ];
    }

    private function dataObrigatoria(mixed $valor, string $campo): DateTimeImmutable
    {
        if ($valor instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($valor)->setTime(0, 0);
        }

        if (!is_string($valor) || trim($valor) === '') {
            throw new InvalidArgumentException("O campo {$campo} é obrigatório para avaliar a regra.");
        }

        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        $erros = DateTimeImmutable::getLastErrors();

        if (!$data || ($erros !== false && ($erros['warning_count'] > 0 || $erros['error_count'] > 0))) {
            throw new InvalidArgumentException("O campo {$campo} precisa estar no formato AAAA-MM-DD.");
        }

        return $data;
    }

    private function vazio(mixed $valor): bool
    {
        return $valor === null || (is_string($valor) && trim($valor) === '');
    }

    /** @param array<int, string> $mensagens */
    private function resultado(string $status, array $mensagens): array
    {
        return [
            'status' => $status,
            'diagnostico' => implode(' ', $mensagens),
            'motivos' => [],
            'pendencias' => [],
            'alertas' => [],
        ];
    }
}
