<?php

namespace App\Http\Controllers\Diretor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class GestaoUsuarioController extends Controller
{
    public function index(): View
    {
        $usuarios = User::query()
            ->orderBy('tipo_perfil')
            ->orderBy('nome')
            ->paginate(25);

        return view('editor.dashboard_diretor', compact('usuarios'));
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

        $user->update($dados);

        Log::info('scom.conta.atualizada', [
            'cadastro_id' => $user->id,
            'ator_id' => Auth::id(),
            'tipo_perfil' => $dados['tipo_perfil'],
            'status_conta' => $dados['status_conta'],
        ]);

        return back()->with('status', 'Permissões e situação da conta atualizadas.');
    }
}
