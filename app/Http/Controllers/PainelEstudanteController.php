<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Edital;
use Illuminate\Support\Facades\Auth;

class PainelEstudanteController extends Controller
{
    public function obterDados()
    {
        $user = Auth::user();
        if (!$user) { return response()->json(['sucesso' => false, 'mensagem' => 'Sessão expirada.']); }

        $editais = Edital::all();
        // Adicionamos o contador de 'ativas'
        $kpis = ['verdes' => 0, 'amarelas' => 0, 'vermelhas' => 0, 'ativas' => 0];
        
        // Pega a data de hoje no formato Y-m-d
        $hoje = date('Y-m-d'); 

        $oportunidades = $editais->map(function($edital) use ($user, &$kpis, $hoje) {
            
            // VERIFICAÇÃO DE TEMPO (A NOVA REGRA MESTRA)
            $isExpirado = $edital->prazo_limite < $hoje;

            if ($isExpirado) {
                $probabilidade = 'EXPIRADO';
                $diagnostico = 'Prazo expirado. Este processo seletivo já foi encerrado.';
                // Não conta nas KPIs coloridas nem nas 'ativas'
            } else {
                $kpis['ativas']++; // Só conta se estiver no futuro/hoje
                
                $probabilidade = 'ALTA';
                $motivosAmarelos = [];
                $motivosVermelhos = [];
                $motivosInformativos = [];

                $cidadeUser = mb_strtolower(trim($user->cidade_origem ?? 'Sorocaba'));
                $estadoUser = mb_strtoupper(trim($user->estado_origem ?? 'SP'));
                $cidadeEdital = mb_strtolower(trim($edital->cidade));
                $estadoEdital = mb_strtoupper(trim($edital->estado));

                $temMoradiaGratuita = in_array($edital->tipo_vaga, ['Moradia', 'Alojamento']);
                $precisaAuxilio = (bool) $user->precisa_auxilio_moradia;

                // 1. REGRA DE DESLOCAMENTO / MOBILIDADE
                if ($user->nivel_mobilidade === 'LOCAL') {
                    if ($cidadeEdital !== $cidadeUser || $estadoEdital !== $estadoUser) {
                        $motivosVermelhos[] = "O edital exige mudança para {$edital->cidade}-{$estadoEdital}, mas você optou por não sair de sua cidade.";
                    }
                } elseif ($user->nivel_mobilidade === 'ESTADUAL') {
                    if ($estadoEdital !== $estadoUser) {
                        $motivosVermelhos[] = "A universidade fica em {$edital->cidade}-{$estadoEdital}, fora da sua preferência estadual.";
                    }
                }

                // 2. REGRA DE ENSINO MÉDIO
                if ($edital->tipo_vaga === 'Cota Escola Pública' && $user->tipo_escola !== 'PUBLICA') {
                    $motivosVermelhos[] = "Edital exclusivo para Escola Pública. Você declarou ensino {$user->tipo_escola}.";
                }

                // 3. REGRA DE ORÇAMENTO
                $orcamento = (float) ($user->orcamento_moradia ?? 0);
                $custoCidade = (float) $edital->custo_moradia_estimado;

                if ($orcamento > 0 && $custoCidade > $orcamento && !$temMoradiaGratuita && ($cidadeEdital !== $cidadeUser || $estadoEdital !== $estadoUser)) {
                    if ($custoCidade > ($orcamento * 1.5)) {
                        $motivosVermelhos[] = "O custo de vida (R$ " . number_format($custoCidade, 2, ',', '.') . ") supera em mais de 50% seu orçamento máximo.";
                    } else {
                        $motivosAmarelos[] = "O custo médio (R$ " . number_format($custoCidade, 2, ',', '.') . ") ultrapassa seu orçamento.";
                    }
                }

                // 4. MOTOR BIFURCADO SOCIOECONÔMICO
                $renda = (float) ($user->renda_per_capita ?? 0);
                $vagasSociais = ['Moradia', 'Alojamento', 'Alimentação', 'Isenção', 'Cota Renda'];
                $isVagaSocial = in_array($edital->tipo_vaga, $vagasSociais);
                $rendaFormatada = number_format($renda, 2, ',', '.');

                if ($precisaAuxilio) {
                    if (!$temMoradiaGratuita && ($cidadeEdital !== $cidadeUser || $estadoEdital !== $estadoUser)) {
                        $motivosVermelhos[] = "Você depende de moradia gratuita, mas este edital oferece apenas {$edital->tipo_vaga}.";
                    }
                    if ($isVagaSocial) {
                        if ($renda > 2118) {
                            $motivosVermelhos[] = "Sua renda (R$ {$rendaFormatada}) excede o teto legal de vulnerabilidade (1,5 SM).";
                        } elseif ($renda > 1412) {
                            $motivosAmarelos[] = "Sua renda (R$ {$rendaFormatada}) está no limite da fila de prioridade.";
                        }
                    }
                } else {
                    if ($isVagaSocial && $renda > 2118) {
                        $motivosInformativos[] = "Sua renda (R$ {$rendaFormatada}) excede o teto desta bolsa social, mas a ida para a universidade segue viável com recursos próprios.";
                    }
                }

                if (count($motivosVermelhos) > 0) {
                    $probabilidade = 'BAIXA';
                    $diagnostico = 'Incompatível: ' . implode(' ', $motivosVermelhos);
                    $kpis['vermelhas']++;
                } elseif (count($motivosAmarelos) > 0) {
                    $probabilidade = 'MEDIA';
                    $diagnostico = 'Atenção: ' . implode(' ', $motivosAmarelos) . ' ' . implode(' ', $motivosInformativos);
                    $kpis['amarelas']++;
                } else {
                    $probabilidade = 'ALTA';
                    $diagnostico = 'Viável: ' . (count($motivosInformativos) > 0 ? implode(' ', $motivosInformativos) : 'Seu perfil atende plenamente aos requisitos.');
                    $kpis['verdes']++;
                }
            }

            return [
                'id_edital' => $edital->id,
                'instituicao' => $edital->instituicao,
                'cidade' => $edital->cidade,
                'estado' => $edital->estado,
                'latitude' => $edital->latitude,
                'longitude' => $edital->longitude,
                'nome_processo' => $edital->titulo,
                'tipo_beneficio' => $edital->tipo_vaga,
                'prazo_limite' => $edital->prazo_limite,
                'prazo_formatado' => date('d/m/Y', strtotime($edital->prazo_limite)),
                'custo_moradia_estimado' => (float) $edital->custo_moradia_estimado,
                'tem_moradia' => in_array($edital->tipo_vaga, ['Moradia', 'Alojamento']),
                'probabilidade' => $probabilidade, // Pode ser ALTA, MEDIA, BAIXA ou EXPIRADO
                'diagnostico' => $diagnostico,
                'distancia_km' => $this->calcularDistanciaKm($user->lat_origem, $user->lng_origem, $edital->latitude, $edital->longitude),
                'escopo_geografico' => $edital->escopo_geografico
            ];
        });

        return response()->json([
            'sucesso' => true,
            'usuario' => $user->name,
            'perfil' => $user,
            'oportunidades' => $oportunidades,
            'kpis' => [
                'total_universidades' => $kpis['ativas'], // Agora só mostra as do Futuro!
                'verdes' => $kpis['verdes'],
                'amarelas' => $kpis['amarelas'],
                'vermelhas' => $kpis['vermelhas']
            ]
        ]);
    }

    public function salvarFicha(Request $request)
    {
        $request->validate([
            'renda_per_capita' => 'required|numeric|min:0',
            'tipo_escola' => 'required|string',
            'etnia' => 'required|string',
            'nota_media_enem' => 'required|numeric|min:400|max:1000',
            'cep' => 'required|string',
            'estado_origem' => 'required|string|size:2',
            'cidade_origem' => 'required|string',
            'nivel_mobilidade' => 'required|string',
            'orcamento_moradia' => 'required|numeric|min:0',
        ]);

        $user = \App\Models\User::find(Auth::id());
        
        if ($user) {
            $user->renda_per_capita = $request->renda_per_capita;
            $user->tipo_escola = $request->tipo_escola;
            $user->etnia = $request->etnia;
            $user->nota_media_enem = $request->nota_media_enem;
            $user->cep = $request->cep;
            $user->cidade_origem = $request->cidade_origem;
            $user->estado_origem = $request->estado_origem;
            $user->lat_origem = $request->lat_origem ?: -23.5015;
            $user->lng_origem = $request->lng_origem ?: -47.4581;
            $user->nivel_mobilidade = $request->nivel_mobilidade;
            $user->orcamento_moradia = $request->orcamento_moradia;
            $user->interesse_republica = $request->interesse_republica ? 1 : 0;
            $user->precisa_auxilio_moradia = $request->precisa_auxilio_moradia ? 1 : 0;
            $user->pcd = $request->pcd ? 1 : 0;
            $user->save();
        }

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Ficha socioeconômica salva! Os mapas e probabilidades foram atualizados.'
        ]);
    }

    private function calcularDistanciaKm($lat1, $lon1, $lat2, $lon2)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;

        $raioTerra = 6371; // Quilómetros

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($raioTerra * $c, 1); // Retorna em KM, com 1 casa decimal
    }
}
