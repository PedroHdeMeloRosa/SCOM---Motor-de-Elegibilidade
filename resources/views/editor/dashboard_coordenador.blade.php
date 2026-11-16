<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNILEGI - Coordenação</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/UNIELEGI LOGO 2026.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .admin-navbar { background-color: var(--azul-primario); color: white; }
        .admin-navbar .logo, .admin-navbar a { color: white; }
        .painel-container { padding: 40px 5%; max-width: 1200px; margin: 0 auto; }
        .btn-sair-admin { background: transparent; border: 1px solid white; color: white; padding: 6px 15px; border-radius: 4px; cursor: pointer; transition: 0.3s; }
        .btn-sair-admin:hover { background: #dc3545; border-color: #dc3545; }
        .badge-coord { background-color: #17a2b8; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; vertical-align: middle; margin-left: 10px; }
    </style>
</head>
<body>
    <header class="navbar admin-navbar">
        <div class="logo">
            <img src="{{ asset('assets/img/UNIELEGI LOGO 2026.svg') }}" alt="Logo UNILEGI" class="logo-img" style="filter: brightness(0) invert(1); height: 40px;">
            <span>UNILEGI <span class="badge-coord">COORDENADOR</span></span>
        </div>
        <nav>
            <ul>
                <li><span>Olá, {{ auth()->user()->nome }}</span></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn-sair-admin">Sair do Painel</button>
                    </form>
                </li>
            </ul>
        </nav>
    </header>

    <main class="painel-container">
        <h2>Painel de Coordenação</h2>
        <hr style="border: 0; height: 1px; background: #ccc; margin: 20px 0;">
        <p>Bem-vindo, <strong>{{ auth()->user()->nome }}</strong>. Autenticação RBAC de Coordenador validada com sucesso.</p>
    </main>
</body>
</html>