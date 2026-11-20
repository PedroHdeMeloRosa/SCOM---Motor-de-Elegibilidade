<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SCOM - Gestão de usuários</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: #f5f7fa; color: #172033; }
        .painel { max-width: 1100px; margin: 30px auto; padding: 0 18px; }
        .cartao { background: #fff; border: 1px solid #dce2ea; border-radius: 10px; padding: 20px; margin-bottom: 18px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; } th, td { padding: 11px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        select { padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; margin: 3px; }
        button { padding: 8px 12px; border: 0; border-radius: 6px; cursor: pointer; background: #174a80; color: #fff; }
        .mensagem { padding: 12px; border-radius: 6px; margin-bottom: 15px; background: #e5f7eb; }
        .erro { color: #a11; margin: 8px 0; }
        .cabecalho { background: #172f50; color: #fff; padding: 16px 4%; display: flex; justify-content: space-between; align-items: center; gap: 15px; }
        .cabecalho form { margin: 0; } .cabecalho button { background: transparent; border: 1px solid #fff; }
    </style>
</head>
<body>
<header class="cabecalho">
    <div><strong>SCOM · Diretoria</strong><br><small>Administração de contas e permissões</small></div>
    <div>{{ auth()->user()->nome }}
        <form method="POST" action="{{ route('logout') }}" style="display:inline">@csrf<button type="submit">Sair</button></form>
    </div>
</header>
<main class="painel">
    @if (session('status')) <div class="mensagem" role="status">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="cartao erro" role="alert"><ul>@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div> @endif
    <section class="cartao">
        <h1>Contas cadastradas</h1>
        <p>O cadastro público cria apenas estudantes. A diretoria pode promover estudantes a coordenadores, rebaixá-los e ativar ou banir contas. Contas de diretoria não são alteradas nesta tela.</p>
        <table>
            <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th><th>Ação</th></tr></thead>
            <tbody>
            @forelse ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->nome }}</td>
                    <td>{{ $usuario->email }}</td>
                    <td>{{ $usuario->tipo_perfil }}</td>
                    <td>{{ $usuario->status_conta }}</td>
                    <td>
                        @if ($usuario->tipo_perfil !== 'DIRETOR' && $usuario->id !== auth()->id())
                            <form method="POST" action="{{ route('diretoria.usuarios.atualizar', $usuario) }}">
                                @csrf @method('PATCH')
                                <select name="tipo_perfil" aria-label="Perfil de {{ $usuario->nome }}">
                                    <option value="ESTUDANTE" @selected($usuario->tipo_perfil === 'ESTUDANTE')>ESTUDANTE</option>
                                    <option value="COORDENADOR" @selected($usuario->tipo_perfil === 'COORDENADOR')>COORDENADOR</option>
                                </select>
                                <select name="status_conta" aria-label="Status de {{ $usuario->nome }}">
                                    <option value="ATIVA" @selected($usuario->status_conta === 'ATIVA')>ATIVA</option>
                                    <option value="BANIDA" @selected($usuario->status_conta === 'BANIDA')>BANIDA</option>
                                </select>
                                <button type="submit">Salvar</button>
                            </form>
                        @else
                            <small>Conta protegida</small>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhuma conta cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $usuarios->links() }}
    </section>
</main>
</body>
</html>
