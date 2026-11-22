<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Verifica se o usuário está autenticado, com conta ATIVA e possui um dos perfis permitidos.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$perfisPermitidos): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Autenticação necessária.',
                ], 401);
            }

            return redirect()->route('login');
        }

        $usuario = Auth::user();

        // Se a conta foi banida após o login, encerra a sessão imediatamente
        if ($usuario->status_conta !== 'ATIVA') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Sua conta foi suspensa pela administração.',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Sua conta foi suspensa pela administração.',
            ]);
        }

        // Verifica se o tipo_perfil do usuário está na lista de perfis autorizados da rota
        if (!empty($perfisPermitidos) && !in_array($usuario->tipo_perfil, $perfisPermitidos, true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Acesso negado para o seu perfil de usuário.',
                ], 403);
            }

            abort(403, 'Acesso não autorizado para o seu perfil.');
        }

        $response = $next($request);

        return $response->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}