<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SCOM · Painel da Diretoria</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/UNIELEGI logo.jpg') }}?v=2">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('assets/img/favicon-uniele.jpg') }}?v=2">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --azul-topo: #172f50;
            --vermelho-detalhe: #c8102e;
            --azul-primario: #004488;
            --azul-hover: #003366;
            --cinza-fundo: #f4f7fb;
            --borda: #dce2ea;
            --texto-principal: #172033;
            --texto-suave: #5a677d;
        }

        body {
            background: var(--cinza-fundo);
            color: var(--texto-principal);
            margin: 0;
        }

        /* Cabeçalho com faixa vermelha e logo da Diretoria */
        .cabecalho {
            background: var(--azul-topo);
            border-bottom: 4px solid var(--vermelho-detalhe);
            color: #fff;
            padding: 14px 4%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.14);
        }

        .cabecalho-marca {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .cabecalho-logo {
            height: 60px;
            width: auto;
            background: transparent;
            padding: 0;
            object-fit: contain;
        }

        .cabecalho-marca strong {
            font-size: 1.15rem;
            letter-spacing: 0.3px;
            display: block;
        }

        .cabecalho-marca small {
            color: #cbd5e1;
            font-size: 0.82rem;
        }

        .cabecalho-usuario {
            display: flex;
            align-items: center;
            gap: 14px;
            font-weight: 500;
        }

        .btn-sair {
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.7);
            padding: 7px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.15s, color 0.15s;
        }

        .btn-sair:hover {
            background: rgba(255, 255, 255, 0.18) !important;
            color: #fff !important;
        }

        .painel {
            max-width: 1200px;
            margin: 26px auto;
            padding: 0 18px 50px;
        }

        /* Abas */
        .barra-abas {
            display: flex;
            gap: 10px;
            border-bottom: 1px solid var(--borda);
            padding-bottom: 12px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .aba-btn {
            background: #fff;
            color: var(--texto-principal);
            border: 1px solid var(--borda);
            padding: 10px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.92rem;
            transition: all 0.15s;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .aba-btn:hover {
            background: #eff6ff !important;
            border-color: var(--azul-primario) !important;
            color: var(--azul-primario) !important;
        }

        .aba-btn.ativa,
        .aba-btn.ativa:hover {
            background: var(--azul-primario) !important;
            color: #fff !important;
            border-color: var(--azul-primario) !important;
        }

        .secao-aba {
            display: none;
        }

        .secao-aba.ativa {
            display: block;
        }

        /* Alertas */
        .alerta-sucesso {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: 500;
        }

        .alerta-erro {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .alerta-erro ul {
            margin: 0;
            padding-left: 18px;
        }

        /* KPIs */
        .grid-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .card-kpi {
            background: #fff;
            border: 1px solid var(--borda);
            border-radius: 12px;
            padding: 18px 20px;
            border-top: 4px solid var(--azul-primario);
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.05);
        }

        .card-kpi.kpi-verde {
            border-top-color: #16a34a;
        }

        .card-kpi.kpi-amarelo {
            border-top-color: #d97706;
        }

        .card-kpi.kpi-vermelho {
            border-top-color: #dc2626;
        }

        .kpi-rotulo {
            font-size: 0.78rem;
            color: var(--texto-suave);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .kpi-valor {
            font-size: 1.95rem;
            font-weight: 700;
            margin: 6px 0 4px;
            color: var(--texto-principal);
        }

        .kpi-sub {
            font-size: 0.82rem;
            color: var(--texto-suave);
        }

        /* Cartões e Grid secundário da Visão Geral */
        .cartao {
            background: #fff;
            border: 1px solid var(--borda);
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.05);
        }

        .cartao h2 {
            margin: 0 0 4px;
            font-size: 1.2rem;
            color: var(--azul-topo);
        }

        .cartao p.subtitulo {
            margin: 0 0 16px;
            font-size: 0.88rem;
            color: var(--texto-suave);
        }

        .grid-duplo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 20px;
        }

        .funil-item {
            margin-bottom: 14px;
        }

        .funil-topo {
            display: flex;
            justify-content: space-between;
            font-size: 0.86rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .funil-trilha {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }

        .funil-barra {
            height: 100%;
            border-radius: 999px;
            transition: width 0.3s;
        }

        /* Gráficos */
        .grafico-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .grafico-equipe-container {
            position: relative;
            height: 260px;
            width: 100%;
            margin-bottom: 20px;
        }

        /* Barra de filtros */
        .barra-filtros {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            background: #f8fafc;
            border: 1px solid var(--borda);
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .barra-filtros input[type="text"],
        .barra-filtros select {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.88rem;
            background: #fff;
        }

        .barra-filtros input[type="text"] {
            flex: 1;
            min-width: 210px;
        }

        .btn-primario {
            background: var(--azul-primario);
            color: #fff;
            border: 0;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.88rem;
            transition: background 0.15s, color 0.15s;
        }

        .btn-primario:hover {
            background: var(--azul-hover) !important;
            color: #fff !important;
        }

        .btn-secundario {
            background: #fff;
            color: var(--texto-principal);
            border: 1px solid #cbd5e1;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: background 0.15s, color 0.15s;
        }

        .btn-secundario:hover {
            background: #e2e8f0 !important;
            color: var(--texto-principal) !important;
        }

        /* Cards Flutuantes */
        .lista-cards {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .card-membro {
            background: #fff;
            border: 1px solid var(--borda);
            border-left: 6px solid #004488;
            border-radius: 14px;
            padding: 16px 20px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .card-membro:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.1);
        }

        .card-membro.nivel-estudante {
            border-left-color: #2563eb;
        }

        .card-membro.nivel-coordenador {
            border-left-color: #eab308;
        }

        .card-membro.nivel-diretor {
            border-left-color: #dc2626;
        }

        .card-membro-topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }

        .membro-info {
            flex: 1;
            min-width: 240px;
        }

        .membro-cabecalho-linha {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 4px;
        }

        .membro-nome {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--texto-principal);
        }

        .membro-meta {
            font-size: 0.84rem;
            color: var(--texto-suave);
        }

        /* Badges estáticas e Badges Interativas (selects embutidos) */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .badge-diretor {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-ativa {
            background: #dcfce7;
            color: #166534;
        }

        .form-inline-badges {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            margin: 0;
        }

        .badge-select {
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: filter 0.15s;
        }

        .badge-select:hover {
            filter: brightness(0.95);
        }

        .badge-select.sel-estudante {
            background: #dbeafe;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .badge-select.sel-coordenador {
            background: #fef9c3;
            color: #a16207;
            border-color: #fde047;
        }

        .badge-select.sel-ativa {
            background: #dcfce7;
            color: #166534;
            border-color: #86efac;
        }

        .badge-select.sel-banida {
            background: #fecaca;
            color: #991b1b;
            border-color: #fca5a5;
        }

        .conta-protegida {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            color: #991b1b;
            background: #fef2f2;
            border: 1px dashed #fca5a5;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        /* Ficha expansível do Estudante */
        .btn-expandir {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            padding: 7px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.84rem;
            font-weight: 600;
            transition: background 0.15s, color 0.15s;
        }

        .btn-expandir:hover {
            background: #dbeafe !important;
            color: #1d4ed8 !important;
        }

        .detalhes-aluno {
            display: none;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed var(--borda);
        }

        .detalhes-aluno.aberto {
            display: block;
        }

        .grid-ficha-aluno {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 10px;
            background: #f8fafc;
            padding: 14px;
            border-radius: 10px;
            font-size: 0.86rem;
        }

        .grid-ficha-aluno div strong {
            color: var(--azul-topo);
            display: block;
            font-size: 0.76rem;
            text-transform: uppercase;
        }

        /* Placar estilo videogame dos Coordenadores */
        .placar-coord {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px dashed #fde68a;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .stat-pill {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .stat-pill.destaque-filtro {
            background: #fef08a;
            border-color: #ca8a04;
            color: #713f12;
            box-shadow: 0 0 0 2px rgba(234, 179, 8, 0.25);
        }

        .stat-pill.pontos-total {
            background: #eab308;
            border-color: #ca8a04;
            color: #fff;
            font-weight: 800;
            margin-left: auto;
        }

        /* Auditoria */
        .lista-auditoria {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .item-auditoria {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .item-auditoria:last-child {
            border-bottom: 0;
        }

        .audit-acao {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 700;
            background: #eef2ff;
            color: #3730a3;
            padding: 2px 8px;
            border-radius: 6px;
            margin-bottom: 4px;
        }

        .audit-detalhes {
            font-size: 0.88rem;
            color: var(--texto-principal);
            margin: 2px 0;
        }

        .audit-meta {
            font-size: 0.78rem;
            color: var(--texto-suave);
            white-space: nowrap;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-overlay.aberto {
            display: flex;
        }

        .modal-box {
            background: #fff;
            border-radius: 12px;
            max-width: 440px;
            width: 100%;
            padding: 22px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
        }

        .modal-box h3 {
            margin: 0 0 8px;
            color: var(--azul-topo);
        }

        .modal-box p {
            margin: 0 0 18px;
            color: var(--texto-suave);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .modal-acoes {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
    </style>
</head>

<body>

    <header class="cabecalho">
        <div class="cabecalho-marca">
            <img src="{{ asset('assets/img/UNIELEGI LOGO 2026 diretor.svg') }}" alt="Logo Diretoria UNIELEGI" class="cabecalho-logo">
            <div>
                <strong>SCOM · Diretoria</strong>
                <small>Governança, inteligência de vagas e auditoria</small>
            </div>
        </div>
        <div class="cabecalho-usuario">
            <span>{{ auth()->user()->nome }}</span>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn-sair">Sair</button>
            </form>
        </div>
    </header>

    <main class="painel">
        @if (session('status'))
        <div class="alerta-sucesso" role="status">✅ {{ session('status') }}</div>
        @endif

        @if ($errors->any())
        <div class="alerta-erro" role="alert">
            <ul>
                @foreach ($errors->all() as $erro)
                <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <datalist id="lista-sugestoes-nomes">
            @foreach ($sugestoesNomes as $nomeSugerido)
            <option value="{{ $nomeSugerido }}">
            @endforeach
        </datalist>

        <nav class="barra-abas" aria-label="Seções da Diretoria">
            <button type="button" id="btn-aba-visao-geral" class="aba-btn {{ $filtros['aba'] === 'aba-visao-geral' ? 'ativa' : '' }}" onclick="trocarAbaDiretoria('aba-visao-geral')">
                📊 Visão Geral &amp; Gráficos
            </button>
            <button type="button" id="btn-aba-estudantes" class="aba-btn {{ $filtros['aba'] === 'aba-estudantes' ? 'ativa' : '' }}" onclick="trocarAbaDiretoria('aba-estudantes')">
                🎓 Estudantes ({{ $estudantes->count() }})
            </button>
            <button type="button" id="btn-aba-equipe" class="aba-btn {{ $filtros['aba'] === 'aba-equipe' ? 'ativa' : '' }}" onclick="trocarAbaDiretoria('aba-equipe')">
                🏆 Equipe &amp; Pontuação ({{ $equipe->count() }})
            </button>
            <button type="button" id="btn-aba-auditoria" class="aba-btn {{ $filtros['aba'] === 'aba-auditoria' ? 'ativa' : '' }}" onclick="trocarAbaDiretoria('aba-auditoria')">
                📋 Auditoria ({{ $logsAuditoria->count() }})
            </button>
        </nav>

        {{-- ABA 1: VISÃO GERAL & GRÁFICOS --}}
        <section id="aba-visao-geral" class="secao-aba {{ $filtros['aba'] === 'aba-visao-geral' ? 'ativa' : '' }}">
            <div class="grid-kpis">
                <div class="card-kpi">
                    <div class="kpi-rotulo">Estudantes</div>
                    <div class="kpi-valor">{{ $kpis['estudantes_total'] }}</div>
                    <div class="kpi-sub">{{ $kpis['fichas_preenchidas'] }} com ficha preenchida</div>
                </div>
                <div class="card-kpi kpi-verde">
                    <div class="kpi-rotulo">Elegíveis (Vagas Prováveis)</div>
                    <div class="kpi-valor">{{ $kpis['alunos_elegiveis_provaveis'] }}</div>
                    <div class="kpi-sub">Alunos com ao menos 1 vaga compatível</div>
                </div>
                <div class="card-kpi kpi-amarelo">
                    <div class="kpi-rotulo">Coordenação Ativa</div>
                    <div class="kpi-valor">{{ $kpis['coordenadores_ativos'] }}</div>
                    <div class="kpi-sub">{{ $kpis['editais_publicados'] }} publicados · {{ $kpis['editais_rascunho'] }} rascunhos</div>
                </div>
                <div class="card-kpi kpi-vermelho">
                    <div class="kpi-rotulo">Contas Suspensas</div>
                    <div class="kpi-valor">{{ $kpis['contas_banidas'] }}</div>
                    <div class="kpi-sub">Acesso bloqueado (BANIDA)</div>
                </div>
            </div>

            <div class="cartao">
                <h2>Adições de Alunos vs. Editais Postados (Últimos 14 dias)</h2>
                <p class="subtitulo">Barras azuis mostram o volume diário de novos estudantes; marcadores vermelhos destacam os editais cadastrados no mesmo período.</p>
                <div class="grafico-container">
                    <canvas id="graficoDiretoria"></canvas>
                </div>
            </div>

            @php
            $baseEstudantes = max(1, (int) $kpis['estudantes_total']);
            $pctFichas = min(100, round(($kpis['fichas_preenchidas'] / $baseEstudantes) * 100));
            $pctElegiveis = min(100, round(($kpis['alunos_elegiveis_provaveis'] / $baseEstudantes) * 100));
            $totalEditais = max(1, $kpis['editais_publicados'] + $kpis['editais_rascunho'] + $kpis['editais_arquivados']);
            $pctPublicados = min(100, round(($kpis['editais_publicados'] / $totalEditais) * 100));
            @endphp

            <div class="grid-duplo">
                <div class="cartao">
                    <h2>Funil de Engajamento &amp; Cobertura</h2>
                    <p class="subtitulo">Acompanhe a conversão de estudantes cadastrados até a elegibilidade real em editais publicados.</p>

                    <div class="funil-item">
                        <div class="funil-topo">
                            <span>1. Estudantes Cadastrados</span>
                            <span>{{ $kpis['estudantes_total'] }} (100%)</span>
                        </div>
                        <div class="funil-trilha">
                            <div class="funil-barra" style="width: 100%; background: #2563eb;"></div>
                        </div>
                    </div>

                    <div class="funil-item">
                        <div class="funil-topo">
                            <span>2. Com Ficha Socioeconômica</span>
                            <span>{{ $kpis['fichas_preenchidas'] }} ({{ $pctFichas }}%)</span>
                        </div>
                        <div class="funil-trilha">
                            <div class="funil-barra" data-width="{{ $pctFichas }}" style="background: #0284c7;"></div>
                        </div>
                    </div>

                    <div class="funil-item">
                        <div class="funil-topo">
                            <span>3. Elegíveis a Vagas Prováveis</span>
                            <span>{{ $kpis['alunos_elegiveis_provaveis'] }} ({{ $pctElegiveis }}%)</span>
                        </div>
                        <div class="funil-trilha">
                            <div class="funil-barra" data-width="{{ $pctElegiveis }}" style="background: #16a34a;"></div>
                        </div>
                    </div>

                    <div class="funil-item" style="margin-bottom: 0;">
                        <div class="funil-topo">
                            <span>Editais Publicados (vs. Total)</span>
                            <span>{{ $kpis['editais_publicados'] }}/{{ $kpis['editais_publicados'] + $kpis['editais_rascunho'] + $kpis['editais_arquivados'] }} ({{ $pctPublicados }}%)</span>
                        </div>
                        <div class="funil-trilha">
                            <div class="funil-barra" data-width="{{ $pctPublicados }}" style="background: #eab308;"></div>
                        </div>
                    </div>
                </div>

                <div class="cartao">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                        <h2 style="margin:0;">Últimas Atividades Críticas</h2>
                        <button type="button" class="btn-secundario" style="padding: 5px 10px; font-size: 0.8rem;" onclick="trocarAbaDiretoria('aba-auditoria')">Ver tudo →</button>
                    </div>
                    <p class="subtitulo">Resumo das 5 movimentações mais recentes na plataforma.</p>

                    @if ($logsAuditoria->isEmpty())
                    <p style="color: var(--texto-suave); font-size: 0.88rem;">Nenhuma atividade registrada ainda.</p>
                    @else
                    <ul class="lista-auditoria">
                        @foreach ($logsAuditoria->take(5) as $log)
                        <li class="item-auditoria" style="padding: 9px 0;">
                            <div>
                                <span class="audit-acao">{{ $log->acao }}</span>
                                <p class="audit-detalhes">{{ \Illuminate\Support\Str::limit($log->detalhes, 95) }}</p>
                            </div>
                            <span class="audit-meta">{{ $log->criado_em ? $log->criado_em->format('d/m H:i') : '—' }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>
        </section>

        {{-- ABA 2: ESTUDANTES (CARDS AZUIS + AÇÕES EMBUTIDAS NAS BADGES) --}}
        <section id="aba-estudantes" class="secao-aba {{ $filtros['aba'] === 'aba-estudantes' ? 'ativa' : '' }}">
            <div class="cartao">
                <h2>Gestão de Estudantes</h2>
                <p class="subtitulo">Clique diretamente nas badges de perfil ou status para alterar sem sair da sua posição. Use "Ver Ficha" para dados socioeconômicos.</p>

                <form method="GET" action="{{ route('diretoria.dashboard') }}" class="barra-filtros">
                    <input type="hidden" name="aba" value="aba-estudantes">
                    <input type="text" name="busca" list="lista-sugestoes-nomes" value="{{ $filtros['busca'] }}" placeholder="Buscar estudante por nome ou e-mail..." aria-label="Buscar estudante">
                    <select name="status" aria-label="Filtrar status">
                        <option value="">Todos os status</option>
                        <option value="ATIVA" @selected($filtros['status']==='ATIVA' )>Ativa</option>
                        <option value="BANIDA" @selected($filtros['status']==='BANIDA' )>Banida</option>
                    </select>
                    <select name="ficha" aria-label="Filtrar por ficha">
                        <option value="">Com ou sem ficha</option>
                        <option value="COM_FICHA" @selected($filtros['ficha']==='COM_FICHA' )>Ficha preenchida</option>
                        <option value="SEM_FICHA" @selected($filtros['ficha']==='SEM_FICHA' )>Sem ficha</option>
                    </select>
                    <button type="submit" class="btn-primario">Filtrar</button>
                    <a href="{{ route('diretoria.dashboard', ['aba' => 'aba-estudantes']) }}" class="btn-secundario">Limpar</a>
                </form>

                <div class="lista-cards">
                    @forelse ($estudantes as $aluno)
                    @php $p = $aluno->perfil; @endphp
                    <div class="card-membro nivel-estudante">
                        <div class="card-membro-topo">
                            <div class="membro-info">
                                <div class="membro-cabecalho-linha">
                                    <span class="membro-nome">{{ $aluno->nome }}</span>
                                    <form method="POST"
                                        action="{{ route('diretoria.usuarios.atualizar', $aluno) }}"
                                        class="form-inline-badges form-atualizar-conta"
                                        data-nome="{{ $aluno->nome }}"
                                        data-aba="aba-estudantes">
                                        @csrf @method('PATCH')
                                        <select name="tipo_perfil"
                                            class="badge-select sel-estudante select-auto-confirm"
                                            data-original="{{ $aluno->tipo_perfil }}"
                                            aria-label="Perfil de {{ $aluno->nome }}">
                                            <option value="ESTUDANTE" selected>ESTUDANTE</option>
                                            <option value="COORDENADOR">COORDENADOR</option>
                                        </select>
                                        <select name="status_conta"
                                            class="badge-select {{ $aluno->status_conta === 'ATIVA' ? 'sel-ativa' : 'sel-banida' }} select-auto-confirm"
                                            data-original="{{ $aluno->status_conta }}"
                                            aria-label="Status de {{ $aluno->nome }}">
                                            <option value="ATIVA" @selected($aluno->status_conta === 'ATIVA')>ATIVA</option>
                                            <option value="BANIDA" @selected($aluno->status_conta === 'BANIDA')>BANIDA</option>
                                        </select>
                                    </form>
                                </div>
                                <div class="membro-meta">
                                    {{ $aluno->email }} · Cadastro: {{ $aluno->criado_em ? \Carbon\Carbon::parse($aluno->criado_em)->format('d/m/Y') : '—' }}
                                </div>
                            </div>

                            <div>
                                <button type="button" class="btn-expandir" data-aluno-id="{{ $aluno->id }}" onclick="toggleDetalhesAluno(this.dataset.alunoId)">
                                    📄 Ver Ficha
                                </button>
                            </div>
                        </div>

                        <div id="detalhes-aluno-{{ $aluno->id }}" class="detalhes-aluno">
                            @if ($p)
                            <div class="grid-ficha-aluno">
                                <div><strong>Contato (E-mail)</strong>{{ $aluno->email }}</div>
                                <div><strong>Origem / CEP</strong>{{ $p->cidade_origem ?? '—' }}/{{ $p->estado_origem ?? '—' }} {{ $p->cep ? "({$p->cep})" : '' }}</div>
                                <div><strong>Renda Per Capita</strong>{{ $p->renda_per_capita !== null ? 'R$ ' . number_format((float) $p->renda_per_capita, 2, ',', '.') : 'Não informada' }}</div>
                                <div><strong>Escola / ENEM</strong>{{ $p->tipo_escola ?? '—' }} · {{ $p->nota_media_enem ? $p->nota_media_enem . ' pts' : 'Sem nota' }}</div>
                                <div><strong>Mobilidade / Orçamento</strong>{{ $p->nivel_mobilidade ?? '—' }} · {{ $p->orcamento_moradia !== null ? 'R$ ' . number_format((float) $p->orcamento_moradia, 2, ',', '.') : '—' }}</div>
                                <div><strong>Permanência / PcD</strong>República: {{ $p->interesse_republica ? 'Sim' : 'Não' }} · Auxílio: {{ $p->precisa_auxilio_moradia ? 'Sim' : 'Não' }} · PcD: {{ $p->pcd ? 'Sim' : 'Não' }}</div>
                            </div>
                            @else
                            <p style="margin:0; font-size:0.86rem; color: var(--texto-suave);">
                                Este estudante ainda não preencheu a ficha socioeconômica. Contato: <strong>{{ $aluno->email }}</strong>.
                            </p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p style="text-align:center; color: var(--texto-suave); padding: 20px;">Nenhum estudante encontrado para os filtros aplicados.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ABA 3: EQUIPE & PONTUAÇÃO (GRÁFICO + FILTRO POR AÇÃO INDIVIDUAL) --}}
        <section id="aba-equipe" class="secao-aba {{ $filtros['aba'] === 'aba-equipe' ? 'ativa' : '' }}">
            <div class="grid-duplo" style="margin-bottom: 20px;">
                <div class="cartao" style="margin-bottom: 0;">
                    <h2>Top 5 Coordenadores Mais Ativos</h2>
                    <p class="subtitulo">Ranking dos 5 membros com maior pontuação geral, detalhando o tipo de ação.</p>
                    <div class="grafico-equipe-container" style="margin-bottom: 0;">
                        <canvas id="graficoEquipe"></canvas>
                    </div>
                </div>

                <div class="cartao" style="margin-bottom: 0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:6px;">
                        <h2 style="margin:0;">Proporção por Editor</h2>
                        <select id="seletor-pizza-equipe" aria-label="Selecionar métrica do gráfico de pizza"
                            style="padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.84rem; background: #f8fafc; font-weight: 600;">
                            <option value="pontos">⭐ Pontuação Geral (PTS)</option>
                            <option value="EDITAL_CRIADO">➕ Editais Criados</option>
                            <option value="EDITAL_EDITADO">✏️ Editais Editados</option>
                            <option value="EDITAL_PUBLICADO">🚀 Editais Publicados</option>
                            <option value="REGRA_RETIFICADA">🔄 Regras Retificadas</option>
                            <option value="EDITAL_ARQUIVADO">📦 Editais Arquivados</option>
                        </select>
                    </div>
                    <p class="subtitulo">Passe o mouse sobre uma fatia para destacar o editor e ver ações e pontuação.</p>
                    <div class="grafico-equipe-container" style="margin-bottom: 0;">
                        <canvas id="graficoPizzaEquipe"></canvas>
                    </div>
                </div>
            </div>

            <div class="cartao">
                <h2>Equipe Administrativa &amp; Pontuação</h2>
                <p class="subtitulo">Diretores aparecem em vermelho (protegidos) e Coordenadores em amarelo. Clique nas badges para alterar cargo ou status.</p>

                <form method="GET" action="{{ route('diretoria.dashboard') }}" class="barra-filtros">
                    <input type="hidden" name="aba" value="aba-equipe">
                    <input type="text" name="busca" list="lista-sugestoes-nomes" value="{{ $filtros['busca'] }}" placeholder="Buscar membro por nome ou e-mail..." aria-label="Buscar membro">
                    <select name="perfil" aria-label="Filtrar cargo">
                        <option value="">Diretores e Coordenadores</option>
                        <option value="DIRETOR" @selected($filtros['perfil']==='DIRETOR' )>Apenas Diretores</option>
                        <option value="COORDENADOR" @selected($filtros['perfil']==='COORDENADOR' )>Apenas Coordenadores</option>
                    </select>
                    <select name="metrica_equipe" aria-label="Filtrar por métrica">
                        <option value="PONTOS" @selected($filtros['metrica_equipe']==='PONTOS' )>Métrica: Pontuação Geral (PTS)</option>
                        <option value="EDITAL_CRIADO" @selected($filtros['metrica_equipe']==='EDITAL_CRIADO' )>Métrica: Editais Criados</option>
                        <option value="EDITAL_EDITADO" @selected($filtros['metrica_equipe']==='EDITAL_EDITADO' )>Métrica: Editais Editados</option>
                        <option value="EDITAL_PUBLICADO" @selected($filtros['metrica_equipe']==='EDITAL_PUBLICADO' )>Métrica: Editais Publicados</option>
                        <option value="REGRA_RETIFICADA" @selected($filtros['metrica_equipe']==='REGRA_RETIFICADA' )>Métrica: Regras Retificadas</option>
                        <option value="EDITAL_ARQUIVADO" @selected($filtros['metrica_equipe']==='EDITAL_ARQUIVADO' )>Métrica: Editais Arquivados</option>
                    </select>
                    <select name="ordem_pontos" aria-label="Ordenação">
                        <option value="desc" @selected($filtros['ordem_pontos']==='desc' )>Maior → Menor</option>
                        <option value="asc" @selected($filtros['ordem_pontos']==='asc' )>Menor → Maior</option>
                    </select>
                    <button type="submit" class="btn-primario">Aplicar</button>
                    <a href="{{ route('diretoria.dashboard', ['aba' => 'aba-equipe']) }}" class="btn-secundario">Limpar</a>
                </form>

                <div class="lista-cards">
                    @forelse ($equipe as $membro)
                    @php
                    $isDiretor = $membro->tipo_perfil === 'DIRETOR';
                    $stats = $estatisticasPorUsuario[$membro->id] ?? [
                    'EDITAL_CRIADO' => 0, 'EDITAL_EDITADO' => 0, 'EDITAL_PUBLICADO' => 0,
                    'REGRA_RETIFICADA' => 0, 'EDITAL_ARQUIVADO' => 0, 'total_acoes' => 0, 'pontos' => 0
                    ];
                    @endphp
                    <div class="card-membro {{ $isDiretor ? 'nivel-diretor' : 'nivel-coordenador' }}">
                        <div class="card-membro-topo">
                            <div class="membro-info">
                                <div class="membro-cabecalho-linha">
                                    <span class="membro-nome">{{ $membro->nome }}</span>
                                    @if (!$isDiretor && $membro->id !== auth()->id())
                                    <form method="POST"
                                        action="{{ route('diretoria.usuarios.atualizar', $membro) }}"
                                        class="form-inline-badges form-atualizar-conta"
                                        data-nome="{{ $membro->nome }}"
                                        data-aba="aba-equipe">
                                        @csrf @method('PATCH')
                                        <select name="tipo_perfil"
                                            class="badge-select sel-coordenador select-auto-confirm"
                                            data-original="{{ $membro->tipo_perfil }}"
                                            aria-label="Perfil de {{ $membro->nome }}">
                                            <option value="ESTUDANTE">ESTUDANTE</option>
                                            <option value="COORDENADOR" selected>COORDENADOR</option>
                                        </select>
                                        <select name="status_conta"
                                            class="badge-select {{ $membro->status_conta === 'ATIVA' ? 'sel-ativa' : 'sel-banida' }} select-auto-confirm"
                                            data-original="{{ $membro->status_conta }}"
                                            aria-label="Status de {{ $membro->nome }}">
                                            <option value="ATIVA" @selected($membro->status_conta === 'ATIVA')>ATIVA</option>
                                            <option value="BANIDA" @selected($membro->status_conta === 'BANIDA')>BANIDA</option>
                                        </select>
                                    </form>
                                    @else
                                    <span class="badge badge-diretor">{{ $membro->tipo_perfil }}</span>
                                    <span class="badge badge-ativa">{{ $membro->status_conta }}</span>
                                    @endif
                                </div>
                                <div class="membro-meta">
                                    {{ $membro->email }} · Cadastro: {{ $membro->criado_em ? \Carbon\Carbon::parse($membro->criado_em)->format('d/m/Y') : '—' }}
                                </div>
                            </div>

                            @if ($isDiretor || $membro->id === auth()->id())
                            <span class="conta-protegida">🔒 Conta protegida</span>
                            @endif
                        </div>

                        @if (!$isDiretor)
                        <div class="placar-coord">
                            <span class="stat-pill {{ $filtros['metrica_equipe'] === 'EDITAL_CRIADO' ? 'destaque-filtro' : '' }}">➕ Criados: {{ $stats['EDITAL_CRIADO'] }}</span>
                            <span class="stat-pill {{ $filtros['metrica_equipe'] === 'EDITAL_EDITADO' ? 'destaque-filtro' : '' }}">✏️ Editados: {{ $stats['EDITAL_EDITADO'] }}</span>
                            <span class="stat-pill {{ $filtros['metrica_equipe'] === 'EDITAL_PUBLICADO' ? 'destaque-filtro' : '' }}">🚀 Publicados: {{ $stats['EDITAL_PUBLICADO'] }}</span>
                            <span class="stat-pill {{ $filtros['metrica_equipe'] === 'REGRA_RETIFICADA' ? 'destaque-filtro' : '' }}">🔄 Retificados: {{ $stats['REGRA_RETIFICADA'] }}</span>
                            <span class="stat-pill {{ $filtros['metrica_equipe'] === 'EDITAL_ARQUIVADO' ? 'destaque-filtro' : '' }}">📦 Arquivados: {{ $stats['EDITAL_ARQUIVADO'] }}</span>
                            <span class="stat-pill pontos-total">⭐ {{ $stats['pontos'] }} PTS</span>
                        </div>
                        @endif
                    </div>
                    @empty
                    <p style="text-align:center; color: var(--texto-suave); padding: 20px;">Nenhum membro encontrado para os filtros aplicados.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ABA 4: AUDITORIA --}}
        <section id="aba-auditoria" class="secao-aba {{ $filtros['aba'] === 'aba-auditoria' ? 'ativa' : '' }}">
            <div class="cartao">
                <h2>Registro de Auditoria</h2>
                <p class="subtitulo">Últimas 40 ações administrativas e editoriais registradas no sistema.</p>

                @if ($logsAuditoria->isEmpty())
                <p style="color: var(--texto-suave); text-align: center; padding: 24px 0;">Nenhuma ação registrada até o momento.</p>
                @else
                <ul class="lista-auditoria">
                    @foreach ($logsAuditoria as $log)
                    <li class="item-auditoria">
                        <div>
                            <span class="audit-acao">{{ $log->acao }}</span>
                            <p class="audit-detalhes">{{ $log->detalhes }}</p>
                            <small style="color: var(--texto-suave);">Responsável: <strong>{{ $log->ator?->nome ?? 'Sistema' }}</strong></small>
                        </div>
                        <span class="audit-meta">{{ $log->criado_em ? $log->criado_em->format('d/m/Y H:i') : '—' }}</span>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </section>
    </main>

    {{-- MODAL DE CONFIRMAÇÃO --}}
    <div id="modal-confirmacao" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
        <div class="modal-box">
            <h3 id="modal-titulo">Confirmar alteração de conta</h3>
            <p id="modal-descricao"></p>
            <div class="modal-acoes">
                <button type="button" class="btn-secundario" onclick="fecharModal(true)">Cancelar</button>
                <button type="button" id="btn-confirmar-modal" class="btn-primario">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        let formularioPendente = null;
        let selectAlteradoPendente = null;

        function trocarAbaDiretoria(idAba) {
            document.querySelectorAll('.secao-aba').forEach(el => el.classList.remove('ativa'));
            document.querySelectorAll('.aba-btn').forEach(btn => btn.classList.remove('ativa'));
            const alvo = document.getElementById(idAba);
            const btnAlvo = document.getElementById('btn-' + idAba);
            if (alvo) alvo.classList.add('ativa');
            if (btnAlvo) btnAlvo.classList.add('ativa');
        }

        function toggleDetalhesAluno(id) {
            const box = document.getElementById('detalhes-aluno-' + id);
            if (box) box.classList.toggle('aberto');
        }

        function fecharModal(reverter = false) {
            if (reverter && selectAlteradoPendente) {
                selectAlteradoPendente.value = selectAlteradoPendente.dataset.original;
            }
            document.getElementById('modal-confirmacao').classList.remove('aberto');
            formularioPendente = null;
            selectAlteradoPendente = null;
        }

        // Ações integradas direto nas badges (dispara modal ao mudar o select)
        document.querySelectorAll('.select-auto-confirm').forEach(select => {
            select.addEventListener('change', function() {
                const form = this.closest('.form-atualizar-conta');
                if (!form) return;
                formularioPendente = form;
                selectAlteradoPendente = this;

                const nome = form.dataset.nome;
                const perfil = form.querySelector('select[name="tipo_perfil"]').value;
                const status = form.querySelector('select[name="status_conta"]').value;
                document.getElementById('modal-descricao').textContent =
                    `Deseja atualizar a conta de "${nome}" para perfil ${perfil} com status ${status}? Esta ação será registrada na auditoria.`;
                document.getElementById('modal-confirmacao').classList.add('aberto');
            });
        });

        document.getElementById('btn-confirmar-modal').addEventListener('click', function() {
            if (!formularioPendente) return;
            sessionStorage.setItem('diretoria_aba', formularioPendente.dataset.aba || 'aba-visao-geral');
            sessionStorage.setItem('diretoria_scroll', String(window.scrollY));
            formularioPendente.submit();
        });

        // Restaura aba, rolagem e aplica larguras das barras do funil
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.funil-barra[data-width]').forEach(barra => {
                barra.style.width = `${barra.dataset.width}%`;
            });

            const abaSalva = sessionStorage.getItem('diretoria_aba');
            const scrollSalvo = sessionStorage.getItem('diretoria_scroll');
            if (abaSalva) {
                trocarAbaDiretoria(abaSalva);
                sessionStorage.removeItem('diretoria_aba');
            }
            if (scrollSalvo !== null) {
                window.scrollTo({
                    top: parseInt(scrollSalvo, 10) || 0,
                    behavior: 'instant'
                });
                sessionStorage.removeItem('diretoria_scroll');
            }
        });

        // Gráfico 1: Histograma de Alunos + Editais Postados
        const dadosGrafico = @json($dadosGrafico);
        const ctx = document.getElementById('graficoDiretoria');
        if (ctx && typeof Chart !== 'undefined') {
            new Chart(ctx, {
                data: {
                    labels: dadosGrafico.labels,
                    datasets: [{
                            type: 'bar',
                            label: 'Novos Alunos',
                            data: dadosGrafico.alunos,
                            backgroundColor: 'rgba(37, 99, 235, 0.78)',
                            borderColor: '#1d4ed8',
                            borderWidth: 1,
                            borderRadius: 6,
                            yAxisID: 'y',
                            order: 2
                        },
                        {
                            type: 'line',
                            label: 'Editais Postados',
                            data: dadosGrafico.editais,
                            borderColor: '#c8102e',
                            backgroundColor: '#c8102e',
                            borderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            tension: 0.25,
                            yAxisID: 'y1',
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Novos Alunos'
                            },
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Editais Postados'
                            },
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Gráfico 2A: Top 5 Coordenadores Mais Ativos (Barras Empilhadas)
        const dadosEquipe = @json($dadosGraficoEquipe);
        const ctxEquipe = document.getElementById('graficoEquipe');
        if (ctxEquipe && typeof Chart !== 'undefined') {
            const top5 = dadosEquipe.top5 || {
                labels: [],
                criados: [],
                editados: [],
                publicados: [],
                retificados: [],
                arquivados: []
            };
            new Chart(ctxEquipe, {
                type: 'bar',
                data: {
                    labels: top5.labels.length ? top5.labels : ['Sem coordenadores'],
                    datasets: [{
                            label: 'Criados',
                            data: top5.criados,
                            backgroundColor: '#60a5fa',
                            borderRadius: 4
                        },
                        {
                            label: 'Editados',
                            data: top5.editados,
                            backgroundColor: '#fbbf24',
                            borderRadius: 4
                        },
                        {
                            label: 'Publicados',
                            data: top5.publicados,
                            backgroundColor: '#22c55e',
                            borderRadius: 4
                        },
                        {
                            label: 'Retificados',
                            data: top5.retificados,
                            backgroundColor: '#a855f7',
                            borderRadius: 4
                        },
                        {
                            label: 'Arquivados',
                            data: top5.arquivados,
                            backgroundColor: '#94a3b8',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Gráfico 2B: Pizza Alterável estilo Google Sheets (com destaque e tooltip completo)
        const ctxPizza = document.getElementById('graficoPizzaEquipe');
        const seletorPizza = document.getElementById('seletor-pizza-equipe');
        const paletaPizza = ['#2563eb', '#eab308', '#16a34a', '#dc2626', '#9333ea', '#0891b2', '#ea580c', '#4f46e5'];
        let instanciaPizza = null;

        function renderizarPizzaEquipe(metrica) {
            if (!ctxPizza || typeof Chart === 'undefined') return;
            const lista = dadosEquipe.pizza || [];
            const filtrados = lista.filter(item => (item[metrica] || 0) > 0);
            const temDados = filtrados.length > 0;

            const labels = temDados ? filtrados.map(i => i.nome) : ['Sem registros nesta métrica'];
            const valores = temDados ? filtrados.map(i => i[metrica]) : [1];
            const cores = temDados ? filtrados.map((_, idx) => paletaPizza[idx % paletaPizza.length]) : ['#e2e8f0'];

            if (instanciaPizza) instanciaPizza.destroy();

            instanciaPizza = new Chart(ctxPizza, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: valores,
                        backgroundColor: cores,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 16
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '48%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            enabled: temDados,
                            callbacks: {
                                title: (items) => filtrados[items[0].dataIndex]?.nome || '',
                                label: (item) => {
                                    const editor = filtrados[item.dataIndex];
                                    if (!editor) return '';
                                    const totalMetrica = valores.reduce((a, b) => a + b, 0) || 1;
                                    const pct = ((item.raw / totalMetrica) * 100).toFixed(1);
                                    const rotuloMetrica = seletorPizza.options[seletorPizza.selectedIndex].text.replace(/^[^\s]+\s/, '');
                                    return [
                                        ` ${rotuloMetrica}: ${item.raw} (${pct}%)`,
                                        ` Total de ações: ${editor.total_acoes}`,
                                        ` Pontuação geral: ${editor.pontos} PTS`
                                    ];
                                }
                            }
                        }
                    }
                }
            });
        }

        if (seletorPizza) {
            renderizarPizzaEquipe(seletorPizza.value);
            seletorPizza.addEventListener('change', (e) => renderizarPizzaEquipe(e.target.value));
        }
    </script>

</body>

</html>