<?php

namespace App\Http\Controllers\Diretor;

use App\Http\Controllers\Controller;
use App\Models\Edital;
use App\Models\LogAuditoria;
use App\Models\PerfilEstudante;
use App\Models\RegraElegibilidade;
use App\Models\User;
use App\Services\Elegibilidade\AvaliadorElegibilidade;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GestaoUsuarioController extends Controller
{
    public function index(Request $request, AvaliadorElegibilidade $avaliador): View
    {
        $busca = trim((string) $request->query('busca', ''));
        $perfil = strtoupper(trim((string) $request->query('perfil', '')));
        $status = strtoupper(trim((string) $request->query('status', '')));
        $ficha = strtoupper(trim((string) $request->query('ficha', '')));
        $metricaEquipe = strtoupper(trim((string) $request->query('metrica_equipe', 'PONTOS')));
        $ordemPontos = strtolower(trim((string) $request->query('ordem_pontos', 'desc')));
        $abaAtiva = (string) $request->query('aba', 'aba-visao-geral');

        if (!in_array($perfil, ['ESTUDANTE', 'COORDENADOR', 'DIRETOR'], true)) {
            $perfil = '';
        }
        if (!in_array($status, ['ATIVA', 'BANIDA'], true)) {
            $status = '';
        }
        if (!in_array($ficha, ['COM_FICHA', 'SEM_FICHA'], true)) {
            $ficha = '';
        }
        $metricasValidas = [
            'PONTOS',
            'EDITAL_CRIADO',
            'EDITAL_EDITADO',
            'EDITAL_PUBLICADO',
            'REGRA_RETIFICADA',
            'EDITAL_ARQUIVADO',
        ];
        if (!in_array($metricaEquipe, $metricasValidas, true)) {
            $metricaEquipe = 'PONTOS';
        }
        if (!in_array($ordemPontos, ['asc', 'desc'], true)) {
            $ordemPontos = 'desc';
        }

        // 1. Estudantes (com ficha socioeconômica carregada)
        $estudantesQuery = User::with('perfil')->where('tipo_perfil', 'ESTUDANTE');
        if ($perfil !== '' && $perfil !== 'ESTUDANTE') {
            $estudantesQuery->whereRaw('1 = 0');
        }
        if ($busca !== '') {
            $estudantesQuery->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%");
            });
        }
        if ($status !== '') {
            $estudantesQuery->where('status_conta', $status);
        }
        if ($ficha === 'COM_FICHA') {
            $estudantesQuery->has('perfil');
        } elseif ($ficha === 'SEM_FICHA') {
            $estudantesQuery->doesntHave('perfil');
        }
        $estudantes = $estudantesQuery->orderBy('nome')->get();

        // 2. Equipe (Diretores e Coordenadores)
        $equipeQuery = User::whereIn('tipo_perfil', ['DIRETOR', 'COORDENADOR']);
        if ($perfil === 'ESTUDANTE') {
            $equipeQuery->whereRaw('1 = 0');
        } elseif (in_array($perfil, ['DIRETOR', 'COORDENADOR'], true)) {
            $equipeQuery->where('tipo_perfil', $perfil);
        }
        if ($busca !== '') {
            $equipeQuery->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%");
            });
        }
        if ($status !== '') {
            $equipeQuery->where('status_conta', $status);
        }
        $equipe = $equipeQuery->get();

        // 3. Placar individual e pontuação geral dos Coordenadores
        $acoesCoordenacao = [
            'EDITAL_CRIADO' => 10,
            'EDITAL_EDITADO' => 5,
            'EDITAL_PUBLICADO' => 15,
            'REGRA_RETIFICADA' => 12,
            'EDITAL_ARQUIVADO' => 5,
        ];

        $logsCoordenadores = LogAuditoria::whereIn('acao', array_keys($acoesCoordenacao))->get();
        $estatisticasPorUsuario = [];

        foreach ($logsCoordenadores as $log) {
            $uid = (int) $log->ator_id;
            if (!isset($estatisticasPorUsuario[$uid])) {
                $estatisticasPorUsuario[$uid] = [
                    'EDITAL_CRIADO' => 0,
                    'EDITAL_EDITADO' => 0,
                    'EDITAL_PUBLICADO' => 0,
                    'REGRA_RETIFICADA' => 0,
                    'EDITAL_ARQUIVADO' => 0,
                    'total_acoes' => 0,
                    'pontos' => 0,
                ];
            }
            $estatisticasPorUsuario[$uid][$log->acao]++;
            $estatisticasPorUsuario[$uid]['total_acoes']++;
            $estatisticasPorUsuario[$uid]['pontos'] += $acoesCoordenacao[$log->acao];
        }

        // Ordena equipe: Diretores no topo, depois Coordenadores pela métrica selecionada
        $chaveOrdenacao = $metricaEquipe === 'PONTOS' ? 'pontos' : $metricaEquipe;
        $equipe = $equipe->sort(function ($a, $b) use ($estatisticasPorUsuario, $ordemPontos, $chaveOrdenacao) {
            if ($a->tipo_perfil !== $b->tipo_perfil) {
                return $a->tipo_perfil === 'DIRETOR' ? -1 : 1;
            }
            if ($a->tipo_perfil === 'COORDENADOR') {
                $valA = $estatisticasPorUsuario[$a->id][$chaveOrdenacao] ?? 0;
                $valB = $estatisticasPorUsuario[$b->id][$chaveOrdenacao] ?? 0;
                if ($valA !== $valB) {
                    return $ordemPontos === 'asc' ? ($valA <=> $valB) : ($valB <=> $valA);
                }
            }
            return strcasecmp($a->nome, $b->nome);
        })->values();

        // Dados para os gráficos da Equipe: Top 5 (barras) + Todos (pizza alterável)
        $coordenadoresOrdenadosPontos = User::where('tipo_perfil', 'COORDENADOR')
            ->get()
            ->sortByDesc(fn ($c) => $estatisticasPorUsuario[$c->id]['pontos'] ?? 0)
            ->values();

        $top5 = $coordenadoresOrdenadosPontos->take(5);

        $dadosGraficoEquipe = [
            'top5' => [
                'labels' => $top5->pluck('nome')->values()->all(),
                'criados' => $top5->map(fn ($c) => $estatisticasPorUsuario[$c->id]['EDITAL_CRIADO'] ?? 0)->values()->all(),
                'editados' => $top5->map(fn ($c) => $estatisticasPorUsuario[$c->id]['EDITAL_EDITADO'] ?? 0)->values()->all(),
                'publicados' => $top5->map(fn ($c) => $estatisticasPorUsuario[$c->id]['EDITAL_PUBLICADO'] ?? 0)->values()->all(),
                'retificados' => $top5->map(fn ($c) => $estatisticasPorUsuario[$c->id]['REGRA_RETIFICADA'] ?? 0)->values()->all(),
                'arquivados' => $top5->map(fn ($c) => $estatisticasPorUsuario[$c->id]['EDITAL_ARQUIVADO'] ?? 0)->values()->all(),
            ],
            'pizza' => $coordenadoresOrdenadosPontos->map(function ($c) use ($estatisticasPorUsuario) {
                $s = $estatisticasPorUsuario[$c->id] ?? [
                    'EDITAL_CRIADO' => 0, 'EDITAL_EDITADO' => 0, 'EDITAL_PUBLICADO' => 0,
                    'REGRA_RETIFICADA' => 0, 'EDITAL_ARQUIVADO' => 0, 'total_acoes' => 0, 'pontos' => 0,
                ];
                return [
                    'nome' => $c->nome,
                    'pontos' => $s['pontos'],
                    'total_acoes' => $s['total_acoes'],
                    'EDITAL_CRIADO' => $s['EDITAL_CRIADO'],
                    'EDITAL_EDITADO' => $s['EDITAL_EDITADO'],
                    'EDITAL_PUBLICADO' => $s['EDITAL_PUBLICADO'],
                    'REGRA_RETIFICADA' => $s['REGRA_RETIFICADA'],
                    'EDITAL_ARQUIVADO' => $s['EDITAL_ARQUIVADO'],
                ];
            })->values()->all(),
        ];

        // 4. Motor próprio: quantos alunos têm pelo menos 1 vaga PROVÁVEL (COMPATIVEL)
        $regrasPublicadas = RegraElegibilidade::where('status_regra', 'ATIVA')
            ->whereHas('edital', fn ($q) => $q->where('status_publicacao', 'PUBLICADO'))
            ->get();

        $perfisAtivos = PerfilEstudante::whereHas('usuario', fn ($q) => $q->where('tipo_perfil', 'ESTUDANTE')->where('status_conta', 'ATIVA'))->get();
        $alunosElegiveisProvaveis = 0;

        foreach ($perfisAtivos as $perfilEstudante) {
            foreach ($regrasPublicadas as $regra) {
                $resultado = $avaliador->avaliar($perfilEstudante->toArray(), $regra->toArray());
                if (($resultado['status'] ?? null) === 'COMPATIVEL') {
                    $alunosElegiveisProvaveis++;
                    break;
                }
            }
        }

        // 5. Gráfico: Entradas de alunos (histograma) + Editais postados (marcadores) nos últimos 14 dias
        $labelsDias = [];
        $alunosPorDia = [];
        $editaisPorDia = [];
        $mapaDatas = [];

        for ($i = 13; $i >= 0; $i--) {
            $dia = Carbon::today()->subDays($i);
            $chave = $dia->format('Y-m-d');
            $mapaDatas[$chave] = count($labelsDias);
            $labelsDias[] = $dia->format('d/m');
            $alunosPorDia[] = 0;
            $editaisPorDia[] = 0;
        }

        $inicioJanela = Carbon::today()->subDays(13)->startOfDay();

        User::where('tipo_perfil', 'ESTUDANTE')
            ->where('criado_em', '>=', $inicioJanela)
            ->get(['criado_em'])
            ->each(function ($u) use ($mapaDatas, &$alunosPorDia) {
                if (!$u->criado_em) return;
                $chave = Carbon::parse($u->criado_em)->format('Y-m-d');
                if (isset($mapaDatas[$chave])) {
                    $alunosPorDia[$mapaDatas[$chave]]++;
                }
            });

        Edital::whereNotNull('criado_em')
            ->where('criado_em', '>=', $inicioJanela)
            ->get(['criado_em'])
            ->each(function ($e) use ($mapaDatas, &$editaisPorDia) {
                $chave = Carbon::parse($e->criado_em)->format('Y-m-d');
                if (isset($mapaDatas[$chave])) {
                    $editaisPorDia[$mapaDatas[$chave]]++;
                }
            });

        $dadosGrafico = [
            'labels' => $labelsDias,
            'alunos' => $alunosPorDia,
            'editais' => $editaisPorDia,
        ];

        $kpis = [
            'estudantes_total' => User::where('tipo_perfil', 'ESTUDANTE')->count(),
            'fichas_preenchidas' => PerfilEstudante::count(),
            'alunos_elegiveis_provaveis' => $alunosElegiveisProvaveis,
            'coordenadores_ativos' => User::where('tipo_perfil', 'COORDENADOR')->where('status_conta', 'ATIVA')->count(),
            'contas_banidas' => User::where('status_conta', 'BANIDA')->count(),
            'editais_publicados' => Edital::where('status_publicacao', 'PUBLICADO')->count(),
            'editais_rascunho' => Edital::where('status_publicacao', 'RASCUNHO')->count(),
            'editais_arquivados' => Edital::where('status_publicacao', 'ARQUIVADO')->count(),
        ];

        $logsAuditoria = LogAuditoria::with('ator')
            ->orderByDesc('criado_em')
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $sugestoesNomes = User::orderBy('nome')->pluck('nome')->unique()->values();

        $filtros = [
            'busca' => $busca,
            'perfil' => $perfil,
            'status' => $status,
            'ficha' => $ficha,
            'metrica_equipe' => $metricaEquipe,
            'ordem_pontos' => $ordemPontos,
            'aba' => $abaAtiva,
        ];

        return view('editor.dashboard_diretor', compact(
            'estudantes',
            'equipe',
            'estatisticasPorUsuario',
            'dadosGraficoEquipe',
            'kpis',
            'dadosGrafico',
            'logsAuditoria',
            'sugestoesNomes',
            'filtros'
        ));
    }

    public function atualizar(Request $request, User $user): RedirectResponse
    {
        if ((int) $user->id === (int) Auth::id()) {
            return back()->withErrors(['usuario' => 'Você não pode alterar o próprio perfil ou bloquear a própria conta.']);
        }

        if ($user->tipo_perfil === 'DIRETOR') {
            return back()->withErrors(['usuario' => 'Contas de diretoria não podem ser alteradas por esta tela.']);
        }

        $dados = $request->validate([
            'tipo_perfil' => ['required', Rule::in(['ESTUDANTE', 'COORDENADOR'])],
            'status_conta' => ['required', Rule::in(['ATIVA', 'BANIDA'])],
        ]);

        $perfilAnterior = $user->tipo_perfil;
        $statusAnterior = $user->status_conta;

        $user->update($dados);

        $detalhes = sprintf(
            'Alterou %s (%s): perfil %s → %s | status %s → %s.',
            $user->nome,
            $user->email,
            $perfilAnterior,
            $dados['tipo_perfil'],
            $statusAnterior,
            $dados['status_conta']
        );

        LogAuditoria::create([
            'ator_id' => Auth::id(),
            'acao' => 'CONTA_ATUALIZADA',
            'entidade_tipo' => 'USUARIO',
            'entidade_id' => $user->id,
            'detalhes' => $detalhes,
            'criado_em' => now(),
        ]);

        Log::info('scom.conta.atualizada', [
            'cadastro_id' => $user->id,
            'ator_id' => Auth::id(),
            'tipo_perfil' => $dados['tipo_perfil'],
            'status_conta' => $dados['status_conta'],
        ]);

        return back()->with('status', 'Permissões e situação da conta atualizadas.');
    }
}