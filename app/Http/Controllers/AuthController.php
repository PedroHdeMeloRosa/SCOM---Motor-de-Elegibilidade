<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function autenticar(Request $request)
    {
        $credenciais = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credenciais)) {
            $request->session()->regenerate();
            
            // Lógica RBAC (Role-Based Access Control)
            $perfil = Auth::user()->tipo_perfil;

            if ($perfil === 'DIRETOR') {
                return redirect()->intended('/diretoria/dashboard');
            } elseif ($perfil === 'COORDENADOR') {
                return redirect()->intended('/coordenacao/dashboard');
            } else {
                // Estudante vai para o painel do aluno
                return redirect()->route('estudante.painel');
            }
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas estão incorretas ou a conta não existe.',
        ])->onlyInput('email');
    }
}