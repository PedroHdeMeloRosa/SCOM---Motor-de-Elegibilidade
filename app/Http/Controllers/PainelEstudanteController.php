<?php

namespace App\Http\Controllers;

use App\Models\RegraElegibilidade;
use App\Models\User;
use App\Services\Elegibilidade\AvaliadorElegibilidade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PainelEstudanteController extends Controller
{
    public function obterDados(AvaliadorElegibilidade $avaliador): JsonResponse
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Autenticação necessária.',
            ], 401);
        }

        $perfil = $user->perfil;
        $perfilDados = $perfil ? $this->dadosPublicaveisDoPerfil($perfil) : [];
        $fichaCompleta = $perfil !== null && $this->fichaCompleta($perfilDados);

        if (!$fichaCompleta) {
            return response()->json([
                'sucesso' => true,
                'usuario' => $user->nome,
                'perfil' => $perfilDados,
                'ficha_completa' => false,
                'oportunidades' => [],
                'kpis' => $this->kpisVazios(),
            ]);
        }

        $regras = RegraElegibilidade::with('edital')
            ->where('status_regra', 'ATIVA')
            ->whereHas('edital', fn ($query) => $query->where('status_publicacao', 'PUBLICADO'))
            ->orderBy('prazo_fim')
            ->get();

        $kpis = [
            'verdes' => 0,
            'amarelas' => 0,
            'vermelhas' => 0,
        ];
        $hoje = now()->toDateString();

        $oportunidades = $regras->map(function (RegraElegibilidade $regra) use ($perfil, $perfilDados, $avaliador, $hoje, &$kpis) {
            $edital = $regra->edital;
            if (!$edital) {
                return null;
            }

            $avaliacao = $avaliador->avaliar(
                $perfilDados,
                $regra->toArray(),
                $hoje
            );

            $escopo = $this->determinarEscopoGeografico($perfil, $edital);
            $alertasLogisticos = $this->avaliarLogistica($perfil, $edital, $escopo);
            $status = $avaliacao['status'];
            $classeVisual = match ($status) {
                'COMPATIVEL' => 'ALTA',
                'INCOMPATIVEL' => 'BAIXA',
                'ENCERRADO' => 'EXPIRADO',
                default => 'MEDIA',
            };

            if ($status === 'COMPATIVEL') {
                $kpis['verdes']++;
            } elseif ($status === 'PENDENTE') {
                $kpis['amarelas']++;
            } elseif ($status === 'INCOMPATIVEL') {
                $kpis['vermelhas']++;
            }

            $latitudeOrigem = $perfil->lat_origem;
            $longitudeOrigem = $perfil->lng_origem;

            return [
                'id' => (int) $regra->id,
                'id_regra' => (int) $regra->id,
                'id_edital' => (int) $edital->id,
                'instituicao' => $edital->instituicao,
                'nome_processo' => $edital->nome_processo,
                'titulo' => $edital->nome_processo,
                'cidade' => $edital->cidade,
                'estado' => $edital->estado,
                'regiao' => $edital->regiao,
                'escopo_geografico' => $escopo,
                'escopo_mobilidade' => $escopo,
                'latitude' => $edital->latitude,
                'longitude' => $edital->longitude,
                'custo_medio_republica' => $edital->custo_medio_republica,
                'custo_moradia_estimado' => $edital->custo_medio_republica,
                'tem_moradia' => $edital->tem_moradia_estudantil === null ? null : (bool) $edital->tem_moradia_estudantil,
                'tipo_beneficio' => $regra->tipo_beneficio,
                'tipo_vaga' => $regra->tipo_beneficio,
                'nota_corte_estimada' => $regra->nota_corte_estimada,
                'renda_maxima_per_capita' => $regra->renda_maxima_per_capita,
                'prazo_inicio' => $regra->prazo_inicio,
                'prazo_fim' => $regra->prazo_fim,
                'prazo_limite' => $regra->prazo_fim,
                'prazo_formatado' => $regra->prazo_fim ? date('d/m/Y', strtotime($regra->prazo_fim)) : null,
                'documentos_exigidos' => $regra->documentos_exigidos,
                'link_documento' => $edital->link_documento,
                'url_detalhe' => route('estudante.editais.show', $edital),
                'status_elegibilidade' => $status,
                'classe_visual' => $classeVisual,
                'diagnostico' => $avaliacao['diagnostico'],
                'motivos' => $avaliacao['motivos'],
                'pendencias' => $avaliacao['pendencias'],
                'alertas' => $avaliacao['alertas'],
                'alertas_logisticos' => $alertasLogisticos,
                'distancia_km' => $this->calcularDistanciaKm(
                    $latitudeOrigem,
                    $longitudeOrigem,
                    $edital->latitude,
                    $edital->longitude
                ),
            ];
        })->filter()->values();

        return response()->json([
            'sucesso' => true,
            'usuario' => $user->nome,
            'perfil' => $perfilDados,
            'ficha_completa' => true,
            'oportunidades' => $oportunidades,
            'kpis' => [
                'total_universidades' => $oportunidades->pluck('id_edital')->unique()->count(),
                'verdes' => $kpis['verdes'],
                'amarelas' => $kpis['amarelas'],
                'vermelhas' => $kpis['vermelhas'],
            ],
        ]);
    }

    public function salvarFicha(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Autenticação necessária.',
            ], 401);
        }

        $dados = $request->validate([
            'renda_per_capita' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'tipo_escola' => ['required', Rule::in(['PUBLICA', 'PRIVADA_BOLSA', 'PRIVADA'])],
            'raca_cor' => ['required', Rule::in(['BRANCA', 'PRETA', 'PARDA', 'AMARELA', 'INDIGENA', 'NAO_INFORMAR'])],
            'nota_media_enem' => ['required', 'numeric', 'min:0', 'max:1000'],
            'cep' => ['nullable', 'string', 'max:15'],
            'estado_origem' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'cidade_origem' => ['required', 'string', 'max:100'],
            'lat_origem' => ['nullable', 'numeric', 'between:-90,90'],
            'lng_origem' => ['nullable', 'numeric', 'between:-180,180'],
            'nivel_mobilidade' => ['required', Rule::in(['LOCAL', 'ESTADUAL', 'INTERESTADUAL', 'INTERNACIONAL'])],
            'orcamento_moradia' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'interesse_republica' => ['sometimes', 'boolean'],
            'precisa_auxilio_moradia' => ['sometimes', 'boolean'],
            'pcd' => ['sometimes', 'boolean'],
        ]);

        $user->perfil()->updateOrCreate(
            ['id_usuario' => $user->id],
            [
                'renda_per_capita' => $dados['renda_per_capita'],
                'tipo_escola' => $dados['tipo_escola'],
                'raca_cor' => $dados['raca_cor'],
                'nota_media_enem' => (int) round((float) $dados['nota_media_enem']),
                'cep' => isset($dados['cep']) ? trim($dados['cep']) : null,
                'cidade_origem' => trim($dados['cidade_origem']),
                'estado_origem' => mb_strtoupper(trim($dados['estado_origem'])),
                'lat_origem' => $dados['lat_origem'] ?? null,
                'lng_origem' => $dados['lng_origem'] ?? null,
                'nivel_mobilidade' => $dados['nivel_mobilidade'],
                'orcamento_moradia' => $dados['orcamento_moradia'],
                'interesse_republica' => $request->boolean('interesse_republica'),
                'precisa_auxilio_moradia' => $request->boolean('precisa_auxilio_moradia'),
                'pcd' => $request->boolean('pcd'),
                'atualizado_em' => now(),
            ]
        );

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Ficha socioeconômica salva. As oportunidades foram reavaliadas com os critérios cadastrados.',
        ]);
    }

    private function dadosPublicaveisDoPerfil($perfil): array
    {
        return [
            'cep' => $perfil->cep,
            'cidade_origem' => $perfil->cidade_origem,
            'estado_origem' => $perfil->estado_origem,
            'lat_origem' => $perfil->lat_origem,
            'lng_origem' => $perfil->lng_origem,
            'renda_per_capita' => $perfil->renda_per_capita,
            'tipo_escola' => $perfil->tipo_escola,
            'raca_cor' => $perfil->raca_cor,
            'pcd' => (bool) $perfil->pcd,
            'nota_media_enem' => $perfil->nota_media_enem,
            'nivel_mobilidade' => $perfil->nivel_mobilidade,
            'orcamento_moradia' => $perfil->orcamento_moradia,
            'interesse_republica' => (bool) $perfil->interesse_republica,
            'precisa_auxilio_moradia' => (bool) $perfil->precisa_auxilio_moradia,
        ];
    }

    private function fichaCompleta(array $perfil): bool
    {
        foreach ([
            'renda_per_capita', 'tipo_escola', 'raca_cor', 'nota_media_enem',
            'cidade_origem', 'estado_origem', 'nivel_mobilidade', 'orcamento_moradia',
        ] as $campo) {
            if (!array_key_exists($campo, $perfil) || $perfil[$campo] === null || $perfil[$campo] === '') {
                return false;
            }
        }

        return true;
    }

    private function determinarEscopoGeografico($perfil, $edital): string
    {
        $cidadeOrigem = mb_strtolower(trim((string) $perfil->cidade_origem));
        $estadoOrigem = mb_strtoupper(trim((string) $perfil->estado_origem));
        $cidadeEdital = mb_strtolower(trim((string) $edital->cidade));
        $estadoEdital = mb_strtoupper(trim((string) $edital->estado));

        if ($edital->regiao === 'INTERNACIONAL') {
            return 'INTERNACIONAL';
        }
        if ($estadoEdital !== $estadoOrigem) {
            return 'INTERESTADUAL';
        }
        if ($cidadeEdital !== $cidadeOrigem) {
            return 'ESTADUAL';
        }

        return 'LOCAL';
    }

    private function avaliarLogistica($perfil, $edital, string $escopo): array
    {
        if ($escopo === 'LOCAL') {
            return [];
        }

        $alertas = [];
        $ordem = ['LOCAL' => 1, 'ESTADUAL' => 2, 'INTERESTADUAL' => 3, 'INTERNACIONAL' => 4];
        $nivelAluno = $ordem[$perfil->nivel_mobilidade] ?? null;
        $nivelOportunidade = $ordem[$escopo] ?? null;

        if ($nivelAluno !== null && $nivelOportunidade !== null && $nivelOportunidade > $nivelAluno) {
            $alertas[] = 'A localização está além do nível de mobilidade informado na ficha.';
        }

        $moradiaInformada = $edital->tem_moradia_estudantil;
        if ((bool) $perfil->precisa_auxilio_moradia) {
            if ($moradiaInformada === null) {
                $alertas[] = 'A disponibilidade de moradia estudantil ainda não foi informada para este edital.';
            } elseif (!(bool) $moradiaInformada) {
                $alertas[] = 'O edital informa que não oferece moradia estudantil, embora a ficha indique essa necessidade.';
            }
        }

        if ((bool) $perfil->interesse_republica || (bool) $perfil->precisa_auxilio_moradia) {
            $custo = $edital->custo_medio_republica;
            $orcamento = $perfil->orcamento_moradia;
            if ($custo !== null && $orcamento !== null && (float) $custo > (float) $orcamento && $moradiaInformada !== true) {
                $alertas[] = 'O custo médio de república informado para a cidade supera o orçamento de moradia da ficha.';
            }
        }

        return $alertas;
    }

    private function calcularDistanciaKm($lat1, $lon1, $lat2, $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        $lat1 = (float) $lat1;
        $lon1 = (float) $lon1;
        $lat2 = (float) $lat2;
        $lon2 = (float) $lon2;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(6371 * $c, 1);
    }

    private function kpisVazios(): array
    {
        return [
            'total_universidades' => 0,
            'verdes' => 0,
            'amarelas' => 0,
            'vermelhas' => 0,
        ];
    }
}
