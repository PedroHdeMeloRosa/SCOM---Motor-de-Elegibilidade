<?php

namespace App\Http\Controllers\Coordenador;

use App\Http\Controllers\Controller;
use App\Models\Edital;
use App\Models\HistoricoRetificacao;
use App\Models\LogAuditoria;
use App\Models\RegraElegibilidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GestaoEditalController extends Controller
{
    private const PESOS_PONTUACAO = [
        'EDITAL_CRIADO' => 10,
        'EDITAL_EDITADO' => 5,
        'EDITAL_PUBLICADO' => 15,
        'REGRA_RETIFICADA' => 12,
        'EDITAL_ARQUIVADO' => 5,
    ];

    public function index(Request $request): View
    {
        $filtros = $this->extrairFiltros($request);
        $userId = (int) auth()->id();

        // IDs de editais em que o coordenador logado atuou (para filtro "Minhas postagens")
        $meusEditaisIds = LogAuditoria::where('entidade_tipo', 'EDITAL')
            ->where('ator_id', $userId)
            ->whereNotNull('entidade_id')
            ->distinct()
            ->pluck('entidade_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $query = Edital::with([
            'regras' => fn ($q) => $q->with('retificacoesOrigem.regraNova')->orderByDesc('versao'),
        ]);

        if ($filtros['busca'] !== '') {
            $busca = $filtros['busca'];
            $query->where(function ($q) use ($busca) {
                $q->where('instituicao', 'like', "%{$busca}%")
                    ->orWhere('nome_processo', 'like', "%{$busca}%")
                    ->orWhere('cidade', 'like', "%{$busca}%");
            });
        }

        if ($filtros['status'] !== '') {
            $query->where('status_publicacao', $filtros['status']);
        }

        if ($filtros['autoria'] === 'MEUS') {
            if ($meusEditaisIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $meusEditaisIds);
            }
        }

        match ($filtros['ordenar']) {
            'antigos' => $query->orderBy('criado_em')->orderBy('id'),
            'ano' => $query->orderByDesc('ano_referencia')->orderBy('instituicao'),
            'instituicao' => $query->orderBy('instituicao')->orderByDesc('id'),
            default => $query->orderByDesc('criado_em')->orderByDesc('id'),
        };

        $editais = $query->paginate(15)->withQueryString();

        // Contadores globais por status
        $contadores = [
            'todos' => Edital::count(),
            'rascunho' => Edital::where('status_publicacao', 'RASCUNHO')->count(),
            'publicado' => Edital::where('status_publicacao', 'PUBLICADO')->count(),
            'arquivado' => Edital::where('status_publicacao', 'ARQUIVADO')->count(),
            'meus' => count($meusEditaisIds),
        ];

        // Autoria (quem criou e última ação) para os editais exibidos na página atual
        $idsPagina = $editais->pluck('id')->all();
        $autoriaPorEdital = $this->mapearAutoriaEditais($idsPagina);

        // Placar e histórico pessoal do coordenador logado
        [$meuPlacar, $minhaAtividade] = $this->carregarAtividadePessoal($userId);
        $pesosPontuacao = self::PESOS_PONTUACAO;

        return view('editor.dashboard_coordenador', compact(
            'editais',
            'filtros',
            'contadores',
            'autoriaPorEdital',
            'meuPlacar',
            'minhaAtividade',
            'pesosPontuacao'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'instituicao' => ['required', 'string', 'max:100'],
            'nome_processo' => ['required', 'string', 'max:150'],
            'cidade' => ['required', 'string', 'max:100'],
            'estado' => ['required', 'string', 'size:2'],
            'regiao' => ['required', Rule::in(['SUDESTE', 'SUL', 'CENTRO_OESTE', 'NORDESTE', 'NORTE', 'INTERNACIONAL'])],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'custo_medio_republica' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'tem_moradia_estudantil' => ['required', 'boolean'],
            'ano_referencia' => ['required', 'integer', 'between:2000,2155'],
            'link_documento' => ['nullable', 'url:http,https', 'max:255'],
            'arquivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'regra.tipo_beneficio' => ['required', Rule::in(['ISENCAO_TAXA', 'COTA_RENDA', 'COTA_ESCOLA_PUBLICA', 'COTA_RACIAL', 'AMPLA_CONCORRENCIA'])],
            'regra.renda_maxima_per_capita' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'regra.exige_escola_publica' => ['required', 'boolean'],
            'regra.exige_etnia' => ['required', 'boolean'],
            'regra.exige_pcd' => ['required', 'boolean'],
            'regra.nota_corte_estimada' => ['nullable', 'integer', 'between:0,1000'],
            'regra.prazo_inicio' => ['required', 'date'],
            'regra.prazo_fim' => ['required', 'date', 'after_or_equal:regra.prazo_inicio'],
            'regra.documentos_exigidos' => ['nullable', 'string', 'max:10000'],
        ]);

        $this->validarCoerenciaDaRegra($dados['regra']);

        $caminhoPdf = $request->hasFile('arquivo_pdf')
            ? $request->file('arquivo_pdf')->store('editais', 'local')
            : null;

        DB::transaction(function () use ($dados, $caminhoPdf): void {
            $edital = Edital::create([
                'instituicao' => trim($dados['instituicao']),
                'nome_processo' => trim($dados['nome_processo']),
                'cidade' => trim($dados['cidade']),
                'estado' => strtoupper(trim($dados['estado'])),
                'regiao' => $dados['regiao'],
                'latitude' => $dados['latitude'],
                'longitude' => $dados['longitude'],
                'custo_medio_republica' => $dados['custo_medio_republica'] ?? null,
                'tem_moradia_estudantil' => $dados['tem_moradia_estudantil'],
                'ano_referencia' => $dados['ano_referencia'],
                'link_documento' => $dados['link_documento'] ?? null,
                'arquivo_pdf' => $caminhoPdf,
                'status_publicacao' => 'RASCUNHO',
            ]);

            $edital->regras()->create($this->dadosDaRegra($dados['regra']) + [
                'status_regra' => 'ATIVA',
                'versao' => 1,
            ]);

            Log::info('scom.edital.criado', [
                'edital_id' => $edital->id,
                'coordenador_id' => auth()->id(),
                'status_publicacao' => 'RASCUNHO',
                'tem_pdf' => $caminhoPdf !== null,
            ]);

            $this->registrarAuditoria(
                'EDITAL_CRIADO',
                $edital->id,
                "Criou rascunho: {$edital->instituicao} — {$edital->nome_processo}."
            );
        });

        return back()->with('status', 'Edital criado como rascunho. Ele já aparece no topo da aba Gestão de Editais para revisão e publicação.');
    }

    public function atualizar(Request $request, Edital $edital): RedirectResponse
    {
        $dados = $request->validate([
            'instituicao' => ['required', 'string', 'max:100'],
            'nome_processo' => ['required', 'string', 'max:150'],
            'cidade' => ['required', 'string', 'max:100'],
            'estado' => ['required', 'string', 'size:2'],
            'regiao' => ['required', Rule::in(['SUDESTE', 'SUL', 'CENTRO_OESTE', 'NORDESTE', 'NORTE', 'INTERNACIONAL'])],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'custo_medio_republica' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'tem_moradia_estudantil' => ['required', 'boolean'],
            'ano_referencia' => ['required', 'integer', 'between:2000,2155'],
            'link_documento' => ['nullable', 'url:http,https', 'max:255'],
            'arquivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $caminhoPdf = $edital->arquivo_pdf;
        if ($request->hasFile('arquivo_pdf')) {
            $caminhoPdf = $request->file('arquivo_pdf')->store('editais', 'local');
        }

        $edital->update([
            'instituicao' => trim($dados['instituicao']),
            'nome_processo' => trim($dados['nome_processo']),
            'cidade' => trim($dados['cidade']),
            'estado' => strtoupper(trim($dados['estado'])),
            'regiao' => $dados['regiao'],
            'latitude' => $dados['latitude'],
            'longitude' => $dados['longitude'],
            'custo_medio_republica' => $dados['custo_medio_republica'] ?? null,
            'tem_moradia_estudantil' => $dados['tem_moradia_estudantil'],
            'ano_referencia' => $dados['ano_referencia'],
            'link_documento' => $dados['link_documento'] ?? null,
            'arquivo_pdf' => $caminhoPdf,
        ]);

        Log::info('scom.edital.atualizado', [
            'edital_id' => $edital->id,
            'coordenador_id' => auth()->id(),
        ]);

        $this->registrarAuditoria(
            'EDITAL_EDITADO',
            $edital->id,
            "Editou edital: {$edital->instituicao} — {$edital->nome_processo}."
        );

        return back()->with('status', 'Dados do edital atualizados.');
    }

    public function publicar(Edital $edital): RedirectResponse
    {
        $temRegraAtiva = $edital->regras()->where('status_regra', 'ATIVA')->exists();
        if (!$temRegraAtiva) {
            return back()->withErrors(['edital' => 'Não é possível publicar um edital sem pelo menos uma regra ativa.']);
        }

        $edital->update(['status_publicacao' => 'PUBLICADO']);

        Log::info('scom.edital.publicado', ['edital_id' => $edital->id, 'coordenador_id' => auth()->id()]);

        $this->registrarAuditoria(
            'EDITAL_PUBLICADO',
            $edital->id,
            "Publicou edital: {$edital->instituicao} — {$edital->nome_processo}."
        );

        return back()->with('status', 'Edital publicado.');
    }

    public function arquivar(Edital $edital): RedirectResponse
    {
        $edital->update(['status_publicacao' => 'ARQUIVADO']);

        Log::info('scom.edital.arquivado', ['edital_id' => $edital->id, 'coordenador_id' => auth()->id()]);

        $this->registrarAuditoria(
            'EDITAL_ARQUIVADO',
            $edital->id,
            "Arquivou edital: {$edital->instituicao} — {$edital->nome_processo}."
        );

        return back()->with('status', 'Edital arquivado. Os dados e o histórico foram preservados.');
    }

    public function retificarRegra(Request $request, RegraElegibilidade $regra): RedirectResponse
    {
        $dados = $request->validate([
            'motivo_retificacao' => ['required', 'string', 'min:10', 'max:255'],
            'tipo_beneficio' => ['required', Rule::in(['ISENCAO_TAXA', 'COTA_RENDA', 'COTA_ESCOLA_PUBLICA', 'COTA_RACIAL', 'AMPLA_CONCORRENCIA'])],
            'renda_maxima_per_capita' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'exige_escola_publica' => ['required', 'boolean'],
            'exige_etnia' => ['required', 'boolean'],
            'exige_pcd' => ['required', 'boolean'],
            'nota_corte_estimada' => ['nullable', 'integer', 'between:0,1000'],
            'prazo_inicio' => ['required', 'date'],
            'prazo_fim' => ['required', 'date', 'after_or_equal:prazo_inicio'],
            'documentos_exigidos' => ['nullable', 'string', 'max:10000'],
        ]);

        $this->validarCoerenciaDaRegra($dados);

        DB::transaction(function () use ($regra, $dados): void {
            $regra = RegraElegibilidade::query()->whereKey($regra->id)->lockForUpdate()->firstOrFail();
            if ($regra->status_regra !== 'ATIVA') {
                throw ValidationException::withMessages(['regra' => 'Somente uma regra ativa pode ser retificada.']);
            }
            $regra->update(['status_regra' => 'INATIVA']);

            $novaRegra = $regra->edital->regras()->create($this->dadosDaRegra($dados) + [
                'status_regra' => 'ATIVA',
                'versao' => ((int) $regra->versao) + 1,
            ]);

            HistoricoRetificacao::create([
                'id_regra_original' => $regra->id,
                'id_regra_nova' => $novaRegra->id,
                'motivo_retificacao' => trim($dados['motivo_retificacao']),
                'data_retificacao' => now(),
            ]);

            Log::info('scom.regra.retificada', [
                'regra_original_id' => $regra->id,
                'regra_nova_id' => $novaRegra->id,
                'edital_id' => $regra->id_edital,
                'coordenador_id' => auth()->id(),
            ]);

            $this->registrarAuditoria(
                'REGRA_RETIFICADA',
                $regra->id_edital,
                "Retificou regra (v{$novaRegra->versao}): " . trim($dados['motivo_retificacao'])
            );
        });

        return back()->with('status', 'Regra retificada. A versão anterior foi preservada no histórico.');
    }

    private function extrairFiltros(Request $request): array
    {
        $temFiltroLista = $request->hasAny(['busca', 'status', 'autoria', 'ordenar', 'page']);
        $abaPadrao = $temFiltroLista ? 'aba-editais' : 'aba-novo';

        $abasValidas = ['aba-novo', 'aba-editais', 'aba-atividade'];
        $aba = (string) $request->query('aba', $abaPadrao);
        if (!in_array($aba, $abasValidas, true)) {
            $aba = $abaPadrao;
        }

        $status = strtoupper(trim((string) $request->query('status', '')));
        if (!in_array($status, ['RASCUNHO', 'PUBLICADO', 'ARQUIVADO'], true)) {
            $status = '';
        }

        $autoria = strtoupper(trim((string) $request->query('autoria', 'TODOS')));
        if (!in_array($autoria, ['TODOS', 'MEUS'], true)) {
            $autoria = 'TODOS';
        }

        $ordenar = strtolower(trim((string) $request->query('ordenar', 'recentes')));
        if (!in_array($ordenar, ['recentes', 'antigos', 'ano', 'instituicao'], true)) {
            $ordenar = 'recentes';
        }

        return [
            'aba' => $aba,
            'busca' => trim((string) $request->query('busca', '')),
            'status' => $status,
            'autoria' => $autoria,
            'ordenar' => $ordenar,
        ];
    }

    /**
     * @param array<int, int> $idsEditais
     * @return array<int, array<string, mixed>>
     */
    private function mapearAutoriaEditais(array $idsEditais): array
    {
        if ($idsEditais === []) {
            return [];
        }

        $logs = LogAuditoria::with('ator')
            ->where('entidade_tipo', 'EDITAL')
            ->whereIn('entidade_id', $idsEditais)
            ->orderBy('criado_em')
            ->orderBy('id')
            ->get();

        $mapa = [];
        foreach ($logs as $log) {
            $id = (int) $log->entidade_id;
            $nomeAtor = $log->ator?->nome ?? 'Sistema';

            if (!isset($mapa[$id])) {
                $mapa[$id] = [
                    'criador' => $nomeAtor,
                    'criador_id' => (int) $log->ator_id,
                    'ultima_acao' => $log->acao,
                    'ultimo_ator' => $nomeAtor,
                    'ultima_data' => $log->criado_em,
                ];
            }

            if ($log->acao === 'EDITAL_CRIADO') {
                $mapa[$id]['criador'] = $nomeAtor;
                $mapa[$id]['criador_id'] = (int) $log->ator_id;
            }

            $mapa[$id]['ultima_acao'] = $log->acao;
            $mapa[$id]['ultimo_ator'] = $nomeAtor;
            $mapa[$id]['ultima_data'] = $log->criado_em;
        }

        return $mapa;
    }

    /**
     * @return array{0: array<string, int>, 1: \Illuminate\Support\Collection}
     */
    private function carregarAtividadePessoal(int $userId): array
    {
        $logs = LogAuditoria::where('ator_id', $userId)
            ->whereIn('acao', array_keys(self::PESOS_PONTUACAO))
            ->orderByDesc('criado_em')
            ->orderByDesc('id')
            ->get();

        $placar = [
            'EDITAL_CRIADO' => 0,
            'EDITAL_EDITADO' => 0,
            'EDITAL_PUBLICADO' => 0,
            'REGRA_RETIFICADA' => 0,
            'EDITAL_ARQUIVADO' => 0,
            'total_acoes' => 0,
            'pontos' => 0,
        ];

        foreach ($logs as $log) {
            $placar[$log->acao]++;
            $placar['total_acoes']++;
            $placar['pontos'] += self::PESOS_PONTUACAO[$log->acao];
        }

        return [$placar, $logs->take(40)];
    }

    private function validarCoerenciaDaRegra(array $dados): void
    {
        $erros = [];
        $tipo = $dados['tipo_beneficio'] ?? null;

        if ($tipo === 'COTA_RENDA' && ($dados['renda_maxima_per_capita'] ?? null) === null) {
            $erros['regra.renda_maxima_per_capita'] = 'Uma modalidade de cota de renda precisa informar o limite per capita.';
        }
        if ($tipo === 'COTA_ESCOLA_PUBLICA' && !(bool) ($dados['exige_escola_publica'] ?? false)) {
            $erros['regra.exige_escola_publica'] = 'A modalidade de escola pública precisa exigir essa condição.';
        }
        if ($tipo === 'COTA_RACIAL' && !(bool) ($dados['exige_etnia'] ?? false)) {
            $erros['regra.exige_etnia'] = 'A modalidade étnico-racial precisa habilitar o critério racial.';
        }

        if ($erros !== []) {
            throw ValidationException::withMessages($erros);
        }
    }

    private function dadosDaRegra(array $dados): array
    {
        return [
            'tipo_beneficio' => $dados['tipo_beneficio'],
            'renda_maxima_per_capita' => $dados['renda_maxima_per_capita'] ?? null,
            'exige_escola_publica' => $dados['exige_escola_publica'],
            'exige_etnia' => $dados['exige_etnia'],
            'exige_pcd' => $dados['exige_pcd'],
            'nota_corte_estimada' => $dados['nota_corte_estimada'] ?? null,
            'prazo_inicio' => $dados['prazo_inicio'],
            'prazo_fim' => $dados['prazo_fim'],
            'documentos_exigidos' => $dados['documentos_exigidos'] ?? null,
        ];
    }

    private function registrarAuditoria(string $acao, int $editalId, string $detalhes): void
    {
        LogAuditoria::create([
            'ator_id' => auth()->id(),
            'acao' => $acao,
            'entidade_tipo' => 'EDITAL',
            'entidade_id' => $editalId,
            'detalhes' => $detalhes,
            'criado_em' => now(),
        ]);
    }
}