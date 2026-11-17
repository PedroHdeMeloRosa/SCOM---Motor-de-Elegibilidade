<?php

namespace App\Http\Controllers;

use App\Models\RegraElegibilidade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PainelEstudanteController extends Controller
{
    public function obterDados()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 401);
        }

        $perfil = $user->perfil;

        // 1. BARREIRA DE CONTA VAZIA: Não inventa cidade nem roda o motor sem ficha preenchida
        if (!$perfil) {
            return response()->json([
                'sucesso' => true,
                'usuario' => $user->nome,
                'perfil' => (object) [],
                'ficha_completa' => false,
                'oportunidades' => [],
                'kpis' => [
                    'total_universidades' => 0,
                    'verdes' => 0,
                    'amarelas' => 0,
                    'vermelhas' => 0,
                ],
            ]);
        }

        // 2. BUSCA DAS REGRAS OFICIAIS ATIVAS COM SEUS RESPECTIVOS EDITAIS
        $regras = RegraElegibilidade::with('edital')
            ->where('status_regra', 'ATIVA')
            ->orderBy('prazo_fim', 'asc')
            ->get();

        $kpis = ['verdes' => 0, 'amarelas' => 0, 'vermelhas' => 0, 'ativas' => 0];
        $hoje = date('Y-m-d');

        $oportunidades = $regras->map(function (RegraElegibilidade $regra) use ($perfil, &$kpis, $hoje) {
            $edital = $regra->edital;

            if (!$edital) {
                return null;
            }

            $cidadeUser = mb_strtolower(trim((string) $perfil->cidade_origem));
            $estadoUser = mb_strtoupper(trim((string) $perfil->estado_origem));
            $cidadeEdital = mb_strtolower(trim((string) $edital->cidade));
            $estadoEdital = mb_strtoupper(trim((string) $edital->estado));

            // Determina o escopo geográfico real comparando a origem do aluno com o campus do edital
            $escopoVaga = 'LOCAL';
            if ($edital->regiao === 'INTERNACIONAL') {
                $escopoVaga = 'INTERNACIONAL';
            } elseif ($estadoEdital !== $estadoUser) {
                $escopoVaga = 'INTERESTADUAL';
            } elseif ($cidadeEdital !== $cidadeUser) {
                $escopoVaga = 'ESTADUAL';
            }

            $isExpirado = $regra->prazo_fim < $hoje;

            if ($isExpirado) {
                $probabilidade = 'EXPIRADO';
                $diagnostico = 'Prazo encerrado em ' . date('d/m/Y', strtotime($regra->prazo_fim)) . '. Este processo seletivo já foi finalizado.';
            } else {
                $kpis['ativas']++;

                $motivosVermelhos = [];
                $motivosAmarelos = [];

                $rendaAluno = (float) $perfil->renda_per_capita;
                $notaAluno = (float) $perfil->nota_media_enem;
                $orcamentoAluno = (float) $perfil->orcamento_moradia;
                $precisaAuxilio = (bool) $perfil->precisa_auxilio_moradia;
                $temMoradiaGratuita = (bool) $edital->tem_moradia_estudantil;
                $custoRepublica = (float) $edital->custo_medio_republica;

                // CRITÉRIO 1: Exigência de Escola Pública (da tabela regras_elegibilidade)
                if ($regra->exige_escola_publica && $perfil->tipo_escola !== 'PUBLICA') {
                    $motivosVermelhos[] = 'Exige ensino médio integral em escola pública.';
                }

                // CRITÉRIO 2: Teto de Renda Per Capita (da tabela regras_elegibilidade)
                if ($regra->renda_maxima_per_capita !== null) {
                    $tetoRenda = (float) $regra->renda_maxima_per_capita;
                    $tetoFormatado = number_format($tetoRenda, 2, ',', '.');
                    $rendaFormatada = number_format($rendaAluno, 2, ',', '.');

                    if ($rendaAluno > $tetoRenda) {
                        $motivosVermelhos[] = "Sua renda per capita (R$ {$rendaFormatada}) excede o limite máximo desta modalidade (R$ {$tetoFormatado}).";
                    } elseif ($rendaAluno >= ($tetoRenda * 0.90)) {
                        $motivosAmarelos[] = "Renda per capita (R$ {$rendaFormatada}) muito próxima ao teto limite (R$ {$tetoFormatado}).";
                    }
                }

                // CRITÉRIO 3: Autodeclaração Étnico-Racial PPI (da tabela regras_elegibilidade)
                if ($regra->exige_etnia) {
                    $etniasElegiveisPPI = ['PPI', 'PRETA', 'PARDA', 'INDIGENA'];
                    if (!in_array($perfil->etnia, $etniasElegiveisPPI, true)) {
                        $motivosVermelhos[] = 'Vaga exclusiva para candidatos autodeclarados Pretos, Pardos ou Indígenas (PPI).';
                    }
                }

                // CRITÉRIO 4: Nota de Corte Estimada (ENEM / Provão Paulista / Vestibular)
                $notaCorte = (float) $regra->nota_corte_estimada;
                if ($notaCorte > 0 && $notaAluno < $notaCorte) {
                    $diferencaNota = $notaCorte - $notaAluno;
                    if ($diferencaNota > 80) {
                        $motivosVermelhos[] = "Sua média ({$notaAluno} pts) está muito abaixo da nota de corte estimada ({$notaCorte} pts).";
                    } else {
                        $motivosAmarelos[] = "Sua média ({$notaAluno} pts) está abaixo da nota de corte estimada ({$notaCorte} pts); acompanhe as listas de chamada.";
                    }
                }

                // CRITÉRIO 5: Disposição de Mobilidade Geográfica
                $niveisOrdem = ['LOCAL' => 1, 'ESTADUAL' => 2, 'INTERESTADUAL' => 3, 'INTERNACIONAL' => 4];
                $nivelAluno = $niveisOrdem[$perfil->nivel_mobilidade] ?? 1;
                $nivelVaga = $niveisOrdem[$escopoVaga] ?? 1;

                if ($nivelVaga > $nivelAluno) {
                    $motivosAmarelos[] = "Localidade ({$edital->cidade}/{$edital->estado}) fora do seu raio de mudança preferido ({$perfil->nivel_mobilidade}).";
                }

                // CRITÉRIO 6: Custos de Permanência e Moradia Estudantil (para vagas fora da cidade do aluno)
                if ($escopoVaga !== 'LOCAL') {
                    if ($precisaAuxilio && !$temMoradiaGratuita) {
                        $motivosVermelhos[] = "Você declarou depender de moradia gratuita, mas o campus {$edital->cidade} não oferece alojamento estudantil.";
                    } elseif ($custoRepublica > $orcamentoAluno) {
                        $custoFormatado = number_format($custoRepublica, 2, ',', '.');
                        if (!$temMoradiaGratuita) {
                            $motivosVermelhos[] = "Custo médio de república local (R$ {$custoFormatado}) supera seu orçamento e não há moradia gratuita no campus.";
                        } else {
                            $motivosAmarelos[] = "Custo de república (R$ {$custoFormatado}) acima do seu orçamento; será necessário solicitar vaga na Moradia Estudantil.";
                        }
                    }
                }

                // CLASSIFICAÇÃO FINAL DE ELEGIBILIDADE
                if (count($motivosVermelhos) > 0) {
                    $probabilidade = 'BAIXA';
                    $diagnostico = 'Incompatível: ' . implode(' ', $motivosVermelhos);
                    $kpis['vermelhas']++;
                } elseif (count($motivosAmarelos) > 0) {
                    $probabilidade = 'MEDIA';
                    $diagnostico = 'Atenção: ' . implode(' ', $motivosAmarelos);
                    $kpis['amarelas']++;
                } else {
                    $probabilidade = 'ALTA';
                    $diagnostico = 'Viável: Seu perfil socioeconômico, acadêmico e de mobilidade atende integralmente aos requisitos desta regra!';
                    $kpis['verdes']++;
                }
            }

            return [
                'id' => $regra->id,
                'id_regra' => $regra->id,
                'id_edital' => $edital->id,
                'instituicao' => $edital->instituicao,
                'nome_processo' => $edital->nome_processo,
                'titulo' => $edital->nome_processo,
                'cidade' => $edital->cidade,
                'estado' => $edital->estado,
                'regiao' => $edital->regiao,
                'escopo_geografico' => $escopoVaga,
                'escopo_mobilidade' => $escopoVaga,
                'latitude' => (float) $edital->latitude,
                'longitude' => (float) $edital->longitude,
                'custo_medio_republica' => (float) $edital->custo_medio_republica,
                'custo_moradia_estimado' => (float) $edital->custo_medio_republica,
                'tem_moradia' => (bool) $edital->tem_moradia_estudantil,
                'tipo_beneficio' => $regra->tipo_beneficio,
                'tipo_vaga' => $regra->tipo_beneficio,
                'nota_corte_estimada' => (int) $regra->nota_corte_estimada,
                'renda_maxima_per_capita' => $regra->renda_maxima_per_capita !== null ? (float) $regra->renda_maxima_per_capita : null,
                'prazo_inicio' => $regra->prazo_inicio,
                'prazo_fim' => $regra->prazo_fim,
                'prazo_limite' => $regra->prazo_fim,
                'prazo_formatado' => date('d/m/Y', strtotime($regra->prazo_fim)),
                'documentos_exigidos' => $regra->documentos_exigidos,
                'link_documento' => $edital->link_documento,
                'probabilidade' => $probabilidade,
                'diagnostico' => $diagnostico,
                'distancia_km' => $this->calcularDistanciaKm(
                    $perfil->lat_origem,
                    $perfil->lng_origem,
                    $edital->latitude,
                    $edital->longitude
                ),
            ];
        })->filter()->values();

        return response()->json([
            'sucesso' => true,
            'usuario' => $user->nome,
            'perfil' => $perfil,
            'ficha_completa' => true,
            'oportunidades' => $oportunidades,
            'kpis' => [
                'total_universidades' => $kpis['ativas'],
                'verdes' => $kpis['verdes'],
                'amarelas' => $kpis['amarelas'],
                'vermelhas' => $kpis['vermelhas'],
            ],
        ]);
    }

    public function salvarFicha(Request $request)
    {
        $request->validate([
            'renda_per_capita' => 'required|numeric|min:0',
            'tipo_escola' => 'required|string|in:PUBLICA,PRIVADA_BOLSA,PRIVADA',
            'etnia' => 'required|string',
            'nota_media_enem' => 'required|numeric|min:0|max:1000',
            'cep' => 'nullable|string|max:15', // CEP agora é opcional!
            'estado_origem' => 'required|string|size:2',
            'cidade_origem' => 'required|string|max:100',
            'nivel_mobilidade' => 'required|string|in:LOCAL,ESTADUAL,INTERESTADUAL,INTERNACIONAL',
            'orcamento_moradia' => 'required|numeric|min:0',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $user->perfil()->updateOrCreate(
            ['id_usuario' => $user->id],
            [
                'renda_per_capita' => $request->renda_per_capita,
                'tipo_escola' => $request->tipo_escola,
                'etnia' => $request->etnia,
                'nota_media_enem' => (int) round((float) $request->nota_media_enem),
                'cep' => $request->filled('cep') ? trim($request->cep) : null,
                'cidade_origem' => trim($request->cidade_origem),
                'estado_origem' => mb_strtoupper(trim($request->estado_origem)),
                'lat_origem' => $request->lat_origem !== null && $request->lat_origem !== '' ? (float) $request->lat_origem : 0.0,
                'lng_origem' => $request->lng_origem !== null && $request->lng_origem !== '' ? (float) $request->lng_origem : 0.0,
                'nivel_mobilidade' => $request->nivel_mobilidade,
                'orcamento_moradia' => $request->orcamento_moradia,
                'interesse_republica' => $request->boolean('interesse_republica') ? 1 : 0,
                'precisa_auxilio_moradia' => $request->boolean('precisa_auxilio_moradia') ? 1 : 0,
                'pcd' => $request->boolean('pcd') ? 1 : 0,
            ]
        );

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Ficha socioeconômica salva! Os mapas e probabilidades foram recalculados pelas regras oficiais.',
        ]);
    }

    private function calcularDistanciaKm($lat1, $lon1, $lat2, $lon2): float
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
            return 0.0;
        }

        $raioTerra = 6371;

        $dLat = deg2rad((float) $lat2 - (float) $lat1);
        $dLon = deg2rad((float) $lon2 - (float) $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($raioTerra * $c, 1);
    }
}