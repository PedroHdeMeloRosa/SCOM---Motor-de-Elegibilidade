<?php

namespace App\Http\Controllers\Coordenador;

use App\Http\Controllers\Controller;
use App\Models\Edital;
use App\Models\HistoricoRetificacao;
use App\Models\RegraElegibilidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GestaoEditalController extends Controller
{
    public function index(): View
    {
        $editais = Edital::with(['regras' => fn ($query) => $query->with('retificacoesOrigem.regraNova')->orderByDesc('versao')])
            ->orderByDesc('ano_referencia')
            ->orderBy('instituicao')
            ->paginate(15);

        return view('editor.dashboard_coordenador', compact('editais'));
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

        DB::transaction(function () use ($dados): void {
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
            ]);
        });

        return back()->with('status', 'Edital criado como rascunho. Revise as informações antes de publicar.');
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
        ]);

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
        ]);

        Log::info('scom.edital.atualizado', [
            'edital_id' => $edital->id,
            'coordenador_id' => auth()->id(),
        ]);

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

        return back()->with('status', 'Edital publicado.');
    }

    public function arquivar(Edital $edital): RedirectResponse
    {
        $edital->update(['status_publicacao' => 'ARQUIVADO']);

        Log::info('scom.edital.arquivado', ['edital_id' => $edital->id, 'coordenador_id' => auth()->id()]);

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
        });

        return back()->with('status', 'Regra retificada. A versão anterior foi preservada no histórico.');
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
}
