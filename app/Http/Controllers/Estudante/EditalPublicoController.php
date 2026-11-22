<?php

namespace App\Http\Controllers\Estudante;

use App\Http\Controllers\Controller;
use App\Models\Edital;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditalPublicoController extends Controller
{
    public function show(Edital $edital): View
    {
        abort_unless($edital->status_publicacao === 'PUBLICADO', 404);

        $regras = $edital->regras()
            ->where('status_regra', 'ATIVA')
            ->orderByDesc('versao')
            ->get();

        return view('estudante.edital', compact('edital', 'regras'));
    }

    public function baixarPdf(Edital $edital): StreamedResponse
    {
        abort_unless($edital->status_publicacao === 'PUBLICADO', 404);
        abort_unless($edital->arquivo_pdf && Storage::disk('local')->exists($edital->arquivo_pdf), 404);

        $nomeArquivo = Str::slug($edital->instituicao . '-' . $edital->nome_processo) . '.pdf';

       return Storage::disk('local')->response($edital->arquivo_pdf, $nomeArquivo, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nomeArquivo . '"',
        ]);
    }
}