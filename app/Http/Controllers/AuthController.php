<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Exibe a tela de login (ou redireciona para o painel correto se já estiver autenticado).
     */
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

    /**
     * Processa a tentativa de login validando no banco SQL (tabela cadastros).
     */
    public function autenticar(Request $request)
    {
        $credenciais = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Informe o seu endereço de e-mail.',
            'email.email' => 'Informe um formato de e-mail válido.',
            'password.required' => 'Informe a sua senha.',
        ]);

        $emailNormalizado = Str::lower(trim($credenciais['email']));
        $chaveRateLimit = $emailNormalizado . '|' . $request->ip();

        // Camada 1: Limitação de tentativas (Máximo de 5 tentativas por minuto)
        if (RateLimiter::tooManyAttempts($chaveRateLimit, 5)) {
            $segundos = RateLimiter::availableIn($chaveRateLimit);

            return back()->withErrors([
                'email' => "Muitas tentativas de login. Por segurança, aguarde {$segundos} segundos antes de tentar novamente.",
            ])->onlyInput('email');
        }

        // Camada 2: Consulta real à tabela 'cadastros' via Eloquent
        $usuario = User::where('email', $emailNormalizado)->first();

        // Camada 3: Verificação criptográfica do Bcrypt (senha_hash)
        if (!$usuario || !Hash::check($credenciais['password'], $usuario->senha_hash)) {
            RateLimiter::hit($chaveRateLimit, 60);

            return back()->withErrors([
                'email' => 'As credenciais estão incorretas ou a conta não está autorizada.',
            ])->onlyInput('email');
        }

        // Camada 4: Verificação de status da conta (Bloqueio imediato de contas BANIDAS)
        if ($usuario->status_conta === 'BANIDA') {
            RateLimiter::hit($chaveRateLimit, 60);

            return back()->withErrors([
                'email' => 'Sua conta foi suspensa pela administração.',
            ])->onlyInput('email');
        }

        if ($usuario->status_conta !== 'ATIVA') {
            RateLimiter::hit($chaveRateLimit, 60);

            return back()->withErrors([
                'email' => 'Sua conta não está ativa no momento.',
            ])->onlyInput('email');
        }

        // Camada 5: Validação prévia de perfil RBAC antes de iniciar sessão
        $destino = $this->obterRotaDestinoPorPerfil($usuario->tipo_perfil);

        if ($destino === null) {
            return back()->withErrors([
                'email' => 'O perfil da conta não possui autorização válida no sistema.',
            ])->onlyInput('email');
        }

        // Login confirmado: limpa o contador de falhas e regenera o ID da sessão (Anti-Session Fixation)
        RateLimiter::clear($chaveRateLimit);
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended($destino);
    }

    /**
     * Exibe a tela de cadastro.
     */
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

    /**
     * Processa o registro de uma nova conta de estudante.
     */
    public function registrar(Request $request)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:cadastros,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'nome.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.unique' => 'Este e-mail já está cadastrado em nossa base.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 6 caracteres.',
            'password.confirmed' => 'As senhas não conferem. Digite a mesma senha nos dois campos.',
        ]);

        $usuario = User::create([
            'nome' => trim($dados['nome']),
            'email' => Str::lower(trim($dados['email'])),
            'senha_hash' => Hash::make($dados['password']),
            'tipo_perfil' => 'ESTUDANTE',
            'status_conta' => 'ATIVA',
        ]);

        // Autentica o usuário recém-criado imediatamente
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('estudante.painel');
    }

    /**
     * Encerra a sessão de forma segura (suporta tanto chamada Fetch/JSON quanto Form POST).
     */
    public function logout(Request $request)
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

    /**
     * Mapeia o destino oficial de acordo com o perfil RBAC.
     */
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