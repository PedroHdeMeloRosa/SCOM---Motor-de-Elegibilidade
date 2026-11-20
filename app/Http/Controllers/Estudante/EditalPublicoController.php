<?php

namespace App\Http\Controllers\Estudante;

use App\Http\Controllers\Controller;
use App\Models\Edital;
use Illuminate\View\View;

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
}
