<?php

namespace App\Http\Controllers\Coordenador;

use App\Http\Controllers\Controller;
use App\Services\IA\AnalisadorEditalIA;
use App\Services\IA\ExtratorTextoEdital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ImportacaoEditalIAController extends Controller
{
    public function escanear(
        Request $request,
        ExtratorTextoEdital $extrator,
        AnalisadorEditalIA $analisador
    ): JsonResponse {
        $dados = $request->validate([
            'arquivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'texto_edital' => ['nullable', 'string', 'max:50000'],
        ], [
            'arquivo_pdf.mimes' => 'Envie apenas arquivos no formato PDF.',
            'arquivo_pdf.max' => 'O arquivo PDF não pode ultrapassar 10 MB.',
            'texto_edital.max' => 'O texto colado não pode ultrapassar 50.000 caracteres.',
        ]);

        $arquivo = $request->file('arquivo_pdf');
        $textoInformado = trim((string) ($dados['texto_edital'] ?? ''));

        if ($arquivo === null && $textoInformado === '') {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Envie um arquivo PDF ou cole o texto do edital para iniciar a análise.',
            ], 422);
        }

        try {
            $textoLimpo = $arquivo !== null
                ? $extrator->extrairDePdf($arquivo)
                : $extrator->limparTexto($textoInformado);
        } catch (RuntimeException $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage(),
            ], 422);
        }

        try {
            $sugestao = $analisador->analisar($textoLimpo);
        } catch (RuntimeException $e) {
            Log::warning('scom.ia.escanear_falhou', [
                'coordenador_id' => auth()->id(),
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage(),
            ], 503);
        }

        Log::info('scom.ia.edital_escaneado', [
            'coordenador_id' => auth()->id(),
            'origem' => $arquivo !== null ? 'pdf' : 'texto',
            'caracteres_lidos' => mb_strlen($textoLimpo),
        ]);

        return response()->json([
            'sucesso' => true,
            'dados' => $sugestao,
            'texto_extraido' => $textoLimpo,
            'aviso' => 'Sugestão gerada por IA local. Revise os trechos grifados à esquerda antes de salvar como rascunho.',
        ]);
    }
}