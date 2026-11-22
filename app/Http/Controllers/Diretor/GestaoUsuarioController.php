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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GestaoUsuarioController extends Controller
{
    private const ACOES_COORDENACAO = [
        'EDITAL_CRIADO' => 10,
        'EDITAL_EDITADO' => 5,
        'EDITAL_PUBLICADO' => 15,
        'REGRA_RETIFICADA' => 12,
        'EDITAL_ARQUIVADO' => 5,
    ];

    public function index(Request $request, AvaliadorElegibilidade $avaliador): View
    {
        $filtros = $this->extrairFiltros($request);

        $estudantes = $this->buscarEstudantes($filtros);
        $estatisticasPorUsuario = $this->calcularEstatisticasEquipe();
        $equipe = $this->buscarEquipe($filtros, $estatisticasPorUsuario);

        $dadosGraficoEquipe = $this->montarDadosGraficoEquipe($estatisticasPorUsuario);
        $alunosElegiveisProvaveis = $this->contarAlunosElegiveis($avaliador);
        $dadosGrafico = $this->montarDadosGraficoDiario();

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

        $ultimasAtividades = LogAuditoria::with('ator')
            ->orderByDesc('criado_em')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $logsAuditoria = $this->buscarLogsAuditoria($filtros['data_auditoria']);
        $sugestoesNomes = User::orderBy('nome')->pluck('nome')->unique()->values();
        $acoesCoordenacao = self::ACOES_COORDENACAO;

        return view('editor.dashboard_diretor', compact(
            'estudantes',
            'equipe',
            'estatisticasPorUsuario',
            'acoesCoordenacao',
            'dadosGraficoEquipe',
            'kpis',
            'dadosGrafico',
            'ultimasAtividades',
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

    private function extrairFiltros(Request $request): array
    {
        $abasValidas = ['aba-visao-geral', 'aba-estudantes', 'aba-equipe', 'aba-auditoria'];
        $aba = (string) $request->query('aba', 'aba-visao-geral');
        if (!in_array($aba, $abasValidas, true)) {
            $aba = 'aba-visao-geral';
        }

        $perfil = strtoupper(trim((string) $request->query('perfil', '')));
        if (!in_array($perfil, ['ESTUDANTE', 'COORDENADOR', 'DIRETOR'], true)) {
            $perfil = '';
        }

        $status = strtoupper(trim((string) $request->query('status', '')));
        if (!in_array($status, ['ATIVA', 'BANIDA'], true)) {
            $status = '';
        }

        $ficha = strtoupper(trim((string) $request->query('ficha', '')));
        if (!in_array($ficha, ['COM_FICHA', 'SEM_FICHA'], true)) {
            $ficha = '';
        }

        $ordenarEstudantes = strtolower(trim((string) $request->query('ordenar_estudantes', 'criado_em')));
        if (!in_array($ordenarEstudantes, ['criado_em', 'nome'], true)) {
            $ordenarEstudantes = 'criado_em';
        }

        $direcaoEstudantes = strtolower(trim((string) $request->query('direcao_estudantes', 'desc')));
        $direcaoEstudantes = $direcaoEstudantes === 'asc' ? 'asc' : 'desc';

        $metricasValidas = array_merge(['PONTOS', 'NOME', 'CRIADO_EM'], array_keys(self::ACOES_COORDENACAO));
        $metricaEquipe = strtoupper(trim((string) $request->query('metrica_equipe', 'PONTOS')));
        if (!in_array($metricaEquipe, $metricasValidas, true)) {
            $metricaEquipe = 'PONTOS';
        }

        $ordemPontos = strtolower(trim((string) $request->query('ordem_pontos', 'desc')));
        $ordemPontos = $ordemPontos === 'asc' ? 'asc' : 'desc';

        $dataAuditoria = trim((string) $request->query('data_auditoria', ''));
        if ($dataAuditoria !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataAuditoria)) {
            $dataAuditoria = '';
        }

        return [
            'aba' => $aba,
            'busca' => trim((string) $request->query('busca', '')),
            'prefixo_estudante' => trim((string) $request->query('prefixo_estudante', '')),
            'prefixo_equipe' => trim((string) $request->query('prefixo_equipe', '')),
            'perfil' => $perfil,
            'status' => $status,
            'ficha' => $ficha,
            'ordenar_estudantes' => $ordenarEstudantes,
            'direcao_estudantes' => $direcaoEstudantes,
            'metrica_equipe' => $metricaEquipe,
            'ordem_pontos' => $ordemPontos,
            'data_auditoria' => $dataAuditoria,
        ];
    }

    private function buscarEstudantes(array $filtros): Collection
    {
        $query = User::withExists('perfil')->where('tipo_perfil', 'ESTUDANTE');

        if ($filtros['perfil'] !== '' && $filtros['perfil'] !== 'ESTUDANTE') {
            return collect();
        }

        if ($filtros['busca'] !== '') {
            $busca = $filtros['busca'];
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%");
            });
        }

        if ($filtros['prefixo_estudante'] !== '') {
            $query->where('nome', 'like', $filtros['prefixo_estudante'] . '%');
        }

        if ($filtros['status'] !== '') {
            $query->where('status_conta', $filtros['status']);
        }

        if ($filtros['ficha'] === 'COM_FICHA') {
            $query->has('perfil');
        } elseif ($filtros['ficha'] === 'SEM_FICHA') {
            $query->doesntHave('perfil');
        }

        $query->orderBy($filtros['ordenar_estudantes'], $filtros['direcao_estudantes']);
        if ($filtros['ordenar_estudantes'] !== 'nome') {
            $query->orderBy('nome');
        }

        return $query->get();
    }

    private function buscarEquipe(array $filtros, array $estatisticasPorUsuario): Collection
    {
        if ($filtros['perfil'] === 'ESTUDANTE') {
            return collect();
        }

        $query = User::whereIn('tipo_perfil', ['DIRETOR', 'COORDENADOR']);

        if (in_array($filtros['perfil'], ['DIRETOR', 'COORDENADOR'], true)) {
            $query->where('tipo_perfil', $filtros['perfil']);
        }

        if ($filtros['busca'] !== '') {
            $busca = $filtros['busca'];
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%");
            });
        }

        if ($filtros['prefixo_equipe'] !== '') {
            $query->where('nome', 'like', $filtros['prefixo_equipe'] . '%');
        }

        if ($filtros['status'] !== '') {
            $query->where('status_conta', $filtros['status']);
        }

        $equipe = $query->get();
        $metrica = $filtros['metrica_equipe'];
        $asc = $filtros['ordem_pontos'] === 'asc';
        $chaveEstatistica = $metrica === 'PONTOS' ? 'pontos' : $metrica;

        return $equipe->sort(function ($a, $b) use ($estatisticasPorUsuario, $metrica, $asc, $chaveEstatistica) {
            if ($a->tipo_perfil !== $b->tipo_perfil) {
                return $a->tipo_perfil === 'DIRETOR' ? -1 : 1;
            }

            if ($metrica === 'NOME') {
                $cmp = strcasecmp($a->nome, $b->nome);
                return $asc ? $cmp : -$cmp;
            }

            if ($metrica === 'CRIADO_EM') {
                $tA = $a->criado_em ? Carbon::parse($a->criado_em)->timestamp : 0;
                $tB = $b->criado_em ? Carbon::parse($b->criado_em)->timestamp : 0;
                if ($tA !== $tB) {
                    return $asc ? ($tA <=> $tB) : ($tB <=> $tA);
                }
                return strcasecmp($a->nome, $b->nome);
            }

            if ($a->tipo_perfil === 'COORDENADOR') {
                $valA = $estatisticasPorUsuario[$a->id][$chaveEstatistica] ?? 0;
                $valB = $estatisticasPorUsuario[$b->id][$chaveEstatistica] ?? 0;
                if ($valA !== $valB) {
                    return $asc ? ($valA <=> $valB) : ($valB <=> $valA);
                }
            }

            return strcasecmp($a->nome, $b->nome);
        })->values();
    }

    private function calcularEstatisticasEquipe(): array
    {
        $logs = LogAuditoria::whereIn('acao', array_keys(self::ACOES_COORDENACAO))->get();
        $estatisticas = [];

        foreach ($logs as $log) {
            $uid = (int) $log->ator_id;
            if (!isset($estatisticas[$uid])) {
                $estatisticas[$uid] = [
                    'EDITAL_CRIADO' => 0,
                    'EDITAL_EDITADO' => 0,
                    'EDITAL_PUBLICADO' => 0,
                    'REGRA_RETIFICADA' => 0,
                    'EDITAL_ARQUIVADO' => 0,
                    'total_acoes' => 0,
                    'pontos' => 0,
                ];
            }
            $estatisticas[$uid][$log->acao]++;
            $estatisticas[$uid]['total_acoes']++;
            $estatisticas[$uid]['pontos'] += self::ACOES_COORDENACAO[$log->acao];
        }

        return $estatisticas;
    }

    private function montarDadosGraficoEquipe(array $estatisticasPorUsuario): array
    {
        $coordenadores = User::where('tipo_perfil', 'COORDENADOR')
            ->get()
            ->sortByDesc(fn($c) => $estatisticasPorUsuario[$c->id]['pontos'] ?? 0)
            ->values();

        $top5 = $coordenadores->take(5);

        return [
            'top5' => [
                'labels' => $top5->pluck('nome')->values()->all(),
                'criados' => $top5->map(fn($c) => $estatisticasPorUsuario[$c->id]['EDITAL_CRIADO'] ?? 0)->values()->all(),
                'editados' => $top5->map(fn($c) => $estatisticasPorUsuario[$c->id]['EDITAL_EDITADO'] ?? 0)->values()->all(),
                'publicados' => $top5->map(fn($c) => $estatisticasPorUsuario[$c->id]['EDITAL_PUBLICADO'] ?? 0)->values()->all(),
                'retificados' => $top5->map(fn($c) => $estatisticasPorUsuario[$c->id]['REGRA_RETIFICADA'] ?? 0)->values()->all(),
                'arquivados' => $top5->map(fn($c) => $estatisticasPorUsuario[$c->id]['EDITAL_ARQUIVADO'] ?? 0)->values()->all(),
            ],
            'pizza' => $coordenadores->map(function ($c) use ($estatisticasPorUsuario) {
                $s = $estatisticasPorUsuario[$c->id] ?? [
                    'EDITAL_CRIADO' => 0,
                    'EDITAL_EDITADO' => 0,
                    'EDITAL_PUBLICADO' => 0,
                    'REGRA_RETIFICADA' => 0,
                    'EDITAL_ARQUIVADO' => 0,
                    'total_acoes' => 0,
                    'pontos' => 0,
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
    }

    private function contarAlunosElegiveis(AvaliadorElegibilidade $avaliador): int
    {
        $regrasPublicadas = RegraElegibilidade::where('status_regra', 'ATIVA')
            ->whereHas('edital', fn($q) => $q->where('status_publicacao', 'PUBLICADO'))
            ->get();

        $perfisAtivos = PerfilEstudante::whereHas('usuario', function ($q) {
            $q->where('tipo_perfil', 'ESTUDANTE')->where('status_conta', 'ATIVA');
        })->get();

        $elegiveis = 0;
        foreach ($perfisAtivos as $perfilEstudante) {
            foreach ($regrasPublicadas as $regra) {
                $resultado = $avaliador->avaliar($perfilEstudante->toArray(), $regra->toArray());
                if (($resultado['status'] ?? null) === 'COMPATIVEL') {
                    $elegiveis++;
                    break;
                }
            }
        }

        return $elegiveis;
    }

    private function montarDadosGraficoDiario(): array
    {
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
                if (!$u->criado_em) {
                    return;
                }
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

        return [
            'labels' => $labelsDias,
            'alunos' => $alunosPorDia,
            'editais' => $editaisPorDia,
        ];
    }

    private function buscarLogsAuditoria(string $dataAuditoria): Collection
    {
        $query = LogAuditoria::with('ator')
            ->orderByDesc('criado_em')
            ->orderByDesc('id');

        if ($dataAuditoria !== '') {
            $query->whereDate('criado_em', $dataAuditoria);
        } else {
            $query->limit(60);
        }

        return $query->get();
    }
}