<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function exibirLogin()
    {
        if (Auth::check()) {
            $destino = $this->obterRotaDestinoPorPerfil(Auth::user()->tipo_perfil);
            if ($destino !== null) {
                return redirect()->to($destino);
            }
        }

        return view('estudante.login');
    }

    public function autenticar(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $credenciais = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => [
                'required', 'string', 'max:72',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && strlen($value) > 72) {
                        $fail('A senha excede o limite seguro de 72 bytes para o algoritmo bcrypt legado.');
                    }
                },
            ],
        ]);

        $chaveIdentidade = 'login:conta:'.hash('sha256', $credenciais['email'].'|'.$request->ip());
        $chaveIp = 'login:ip:'.hash('sha256', (string) $request->ip());

        if (RateLimiter::tooManyAttempts($chaveIdentidade, 5) || RateLimiter::tooManyAttempts($chaveIp, 30)) {
            $espera = max(
                RateLimiter::availableIn($chaveIdentidade),
                RateLimiter::availableIn($chaveIp)
            );

            throw ValidationException::withMessages([
                'email' => "Muitas tentativas de acesso. Aguarde {$espera} segundos e tente novamente.",
            ]);
        }

        $usuario = User::query()->where('email', $credenciais['email'])->first();
        $hashVerificado = $usuario?->senha_hash
            ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $senhaValida = Hash::check($credenciais['password'], $hashVerificado);
        $contaAtiva = $usuario !== null && $usuario->status_conta === 'ATIVA';
        $destino = $usuario ? $this->obterRotaDestinoPorPerfil($usuario->tipo_perfil) : null;

        if (!$senhaValida || !$contaAtiva || $destino === null) {
            Log::warning('scom.auth.login_falhou', [
                'email_hash' => hash('sha256', $credenciais['email']),
                'ip' => $request->ip(),
                'motivo' => $usuario === null ? 'cadastro_inexistente' : (!$senhaValida ? 'senha_incorreta' : (!$contaAtiva ? 'conta_inativa' : 'perfil_invalido')),
            ]);

            RateLimiter::hit($chaveIdentidade, 60);
            RateLimiter::hit($chaveIp, 60);

            // Mensagem única evita revelar se o e-mail existe ou se a conta está banida.
            return back()->withErrors([
                'email' => 'Não foi possível autenticar com os dados informados.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($chaveIdentidade);
        RateLimiter::clear($chaveIp);
        Log::info('scom.auth.login_sucesso', [
            'cadastro_id' => $usuario->id,
            'tipo_perfil' => $usuario->tipo_perfil,
            'ip' => $request->ip(),
        ]);
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->to($destino);
    }

    public function exibirCadastro()
    {
        if (Auth::check()) {
            $destino = $this->obterRotaDestinoPorPerfil(Auth::user()->tipo_perfil);
            if ($destino !== null) {
                return redirect()->to($destino);
            }
        }

        return view('estudante.cadastro');
    }

    public function registrar(Request $request)
    {
        $chaveCadastro = 'cadastro:ip:'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($chaveCadastro, 5)) {
            $espera = RateLimiter::availableIn($chaveCadastro);
            throw ValidationException::withMessages([
                'email' => "Muitas tentativas de cadastro. Aguarde {$espera} segundos e tente novamente.",
            ]);
        }
        RateLimiter::hit($chaveCadastro, 600);

        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'nome' => trim((string) $request->input('nome')),
        ]);

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('cadastros', 'email')],
            'password' => [
                'required', 'string', 'min:12', 'max:72', 'confirmed',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && strlen($value) > 72) {
                        $fail('A senha não pode ultrapassar 72 bytes.');
                    }
                },
            ],
        ], [
            'nome.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.min' => 'A senha deve ter pelo menos 12 caracteres.',
            'password.max' => 'A senha não pode ultrapassar 72 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
        ]);

        $usuario = User::create([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'senha_hash' => Hash::make($dados['password']),
            // O papel é definido no servidor. Nunca aceitar tipo_perfil enviado pelo formulário.
            'tipo_perfil' => 'ESTUDANTE',
            'status_conta' => 'ATIVA',
        ]);

        Log::info('scom.auth.cadastro_estudante', [
            'cadastro_id' => $usuario->id,
            'ip' => $request->ip(),
        ]);

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('estudante.painel');
    }

    public function logout(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Sessão encerrada com sucesso.',
            ]);
        }

        return redirect()->route('login');
    }

    private function obterRotaDestinoPorPerfil(?string $tipoPerfil): ?string
    {
        return match ($tipoPerfil) {
            'DIRETOR' => route('diretoria.dashboard'),
            'COORDENADOR' => route('coordenacao.dashboard'),
            'ESTUDANTE' => route('estudante.painel'),
            default => null,
        };
    }
}
