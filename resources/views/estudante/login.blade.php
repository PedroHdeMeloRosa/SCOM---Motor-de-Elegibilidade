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
        /* Estilos exclusivos para centralizar o Login na tela inteira */
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
            padding: 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            margin-bottom: 15px;
            display: block; /* Modificado para funcionar com o Blade */
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="login-logo">
            <img src="{{ asset('assets/img/UNIELEGI LOGO 2026.svg') }}" alt="Logo UNILEGI">
            <span>UNILEGI</span>
        </div>

        <!-- O Laravel injetará erros de autenticação aqui automaticamente -->
        @if ($errors->any())
            <div class="alerta-erro">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- O formulário apontará para a rota POST de login que criaremos -->
        <form method="POST" action="{{ route('login') }}">
            <!-- Token de Segurança Obrigatório do Laravel -->
            @csrf

            <div class="form-group">
                <label for="email">E-mail</label>
                <!-- Mantém o email preenchido se a senha estiver errada -->
                <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="aluno@unesp.br">
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <!-- Laravel espera o campo 'password' em inglês por padrão -->
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-primario" style="width: 100%;">Acessar Plataforma</button>

            <div class="links-uteis">
                <a href="#">Criar conta</a>
                <a href="#">Esqueceu a senha?</a>
            </div>
        </form>
    </div>

</body>
</html>