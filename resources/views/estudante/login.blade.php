<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNILEGI - Acesso</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/UNIELEGI logo.jpg') }}">
    
    <!-- CSS Global -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    
    <!-- Motor Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: var(--cinza-fundo);
        }

        .login-container {
            background: var(--branco);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border-top: 5px solid var(--azul-primario);
        }

        .login-logo {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--azul-escuro);
        }

        .login-logo img {
            height: 40px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .links-uteis {
            margin-top: 15px;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
        }

        .links-uteis a {
            color: var(--azul-primario);
            text-decoration: none;
        }

        .alerta-erro {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c2c7;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            margin-bottom: 15px;
            display: block;
            text-align: left;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="login-logo">
            <img src="{{ asset('assets/img/UNIELEGI LOGO 2026.svg') }}" alt="Logo UNILEGI">
            <span>UNILEGI</span>
        </div>

        @if ($errors->any())
            <div class="alerta-erro" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.autenticar') }}" id="form-login-laravel">
            @csrf

            <div class="form-group">
                <label for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    maxlength="150"
                    autocomplete="email"
                    autofocus
                    placeholder="aluno@unesp.br"
                >
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                >
            </div>

            <button type="submit" id="btn-entrar" class="btn-primario" style="width: 100%; background-color: var(--azul-primario); color: var(--branco);">
                Acessar Plataforma
            </button>

            <div class="links-uteis">
                <a href="{{ route('home') }}">← Voltar ao Início</a>
                <a href="#">Esqueceu a senha?</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('form-login-laravel').addEventListener('submit', function () {
            const btn = document.getElementById('btn-entrar');
            btn.textContent = 'Autenticando...';
            btn.disabled = true;
        });
    </script>
</body>
</html>