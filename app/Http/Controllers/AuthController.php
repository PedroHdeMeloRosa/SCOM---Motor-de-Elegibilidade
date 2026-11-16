<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function autenticar(Request $request)
    {
        $credenciais = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $autenticado = Auth::attempt([
            'email' => $credenciais['email'],
            'password' => $credenciais['password'],
            'status_conta' => 'ATIVA',
        ]);

        if (!$autenticado) {
            return back()->withErrors([
                'email' => 'As credenciais estão incorretas ou a conta não está autorizada.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $usuario = Auth::user();

        $destino = match ($usuario->tipo_perfil) {
            'DIRETOR' => '/diretoria/dashboard',
            'COORDENADOR' => '/coordenacao/dashboard',
            'ESTUDANTE' => route('estudante.painel'),
            default => null,
        };

        if ($destino === null) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'O perfil da conta não possui autorização válida.',
            ])->onlyInput('email');
        }

        return redirect()->intended($destino);
    }
}