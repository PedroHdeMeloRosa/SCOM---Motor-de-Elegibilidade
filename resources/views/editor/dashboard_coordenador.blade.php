<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SCOM · Painel da Coordenação</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/favicon-uniele.jpg') }}?v=2">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('assets/img/favicon-uniele.jpg') }}?v=2">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --azul-topo: #172f50;
            --amarelo-detalhe: #eab308;
            --amarelo-escuro: #a16207;
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

        /* Cabeçalho institucional com faixa amarela e logo da Coordenação */
        .cabecalho {
            background: var(--azul-topo);
            border-bottom: 4px solid var(--amarelo-detalhe);
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

        .pill-pontos-topo {
            background: rgba(234, 179, 8, 0.2);
            border: 1px solid var(--amarelo-detalhe);
            color: #fef08a;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
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
            max-width: 1440px;
            margin: 24px auto;
            padding: 0 20px 55px;
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
            margin: 6px 0 0;
            padding-left: 18px;
        }

        /* Cartões e Botões */
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

        .btn-primario {
            background: var(--azul-primario);
            color: #fff;
            border: 0;
            padding: 9px 16px;
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

        .btn-sucesso {
            background: #16a34a;
            color: #fff;
            border: 0;
            padding: 7px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.84rem;
            transition: background 0.15s, color 0.15s;
        }

        .btn-sucesso:hover {
            background: #15803d !important;
            color: #fff !important;
        }

        .btn-secundario {
            background: #fff;
            color: var(--texto-principal);
            border: 1px solid #cbd5e1;
            padding: 7px 14px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 600;
            transition: background 0.15s, color 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-secundario:hover {
            background: #e2e8f0 !important;
            color: var(--texto-principal) !important;
        }

        .btn-arquivar {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 7px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.84rem;
            transition: background 0.15s, color 0.15s;
        }

        .btn-arquivar:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }

        /* Layout Lado a Lado (Split-Screen amplo para leitura confortável) */
        .split-novo-edital {
            display: grid;
            grid-template-columns: minmax(440px, 1.25fr) 1fr;
            gap: 22px;
            align-items: start;
        }

        .painel-ia-sticky {
            position: sticky;
            top: 18px;
        }

        .card-ia {
            background: linear-gradient(180deg, #f0f7ff 0%, #ffffff 100%);
            border: 1px solid #bfdbfe;
            border-top: 4px solid #2563eb;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 3px 12px rgba(37, 99, 235, 0.07);
        }

        .preview-pdf-box,
        .texto-grifado-box {
            display: none;
            margin-top: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .preview-pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            padding: 8px 12px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--azul-topo);
        }

        .preview-pdf-frame {
            width: 100%;
            height: 620px;
            border: 0;
            display: block;
        }

        .texto-grifado-conteudo {
            max-height: 540px;
            overflow-y: auto;
            padding: 14px;
            font-size: 0.85rem;
            line-height: 1.65;
            white-space: pre-wrap;
            color: #1e293b;
        }

        .grifo-data { background: #fef08a; color: #713f12; padding: 1px 4px; border-radius: 4px; font-weight: 700; }
        .grifo-valor { background: #bbf7d0; color: #14532d; padding: 1px 4px; border-radius: 4px; font-weight: 700; }
        .grifo-criterio { background: #bfdbfe; color: #1e3a8a; padding: 1px 4px; border-radius: 4px; font-weight: 700; }
        .grifo-entidade { background: #e9d5ff; color: #581c87; padding: 1px 4px; border-radius: 4px; font-weight: 700; }

        /* Formulário */
        .secao-form-titulo {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--azul-topo);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 18px 0 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
        }

        .secao-form-titulo:first-of-type {
            margin-top: 4px;
        }

        .grade {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
            gap: 12px;
        }

        .campo label {
            display: block;
            font-weight: 600;
            font-size: 0.84rem;
            margin-bottom: 4px;
            color: var(--texto-principal);
        }

        .campo input,
        .campo select,
        .campo textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.88rem;
            background: #fff;
            transition: border-color 0.15s, background-color 0.15s;
        }

        .campo input:focus,
        .campo select:focus,
        .campo textarea:focus {
            outline: none;
            border-color: var(--azul-primario);
            box-shadow: 0 0 0 3px rgba(0, 68, 136, 0.1);
        }

        .grupo-checks {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            background: #f8fafc;
            border: 1px solid var(--borda);
            border-radius: 10px;
            padding: 12px 14px;
            margin: 12px 0;
        }

        .check-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 0.86rem;
            font-weight: 600;
            cursor: pointer;
            margin: 0;
        }

        .check-item input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        /* Filtros e Pills de Status */
        .pills-status {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }

        .pill-filtro {
            padding: 6px 14px;
            border-radius: 999px;
            border: 1px solid var(--borda);
            background: #fff;
            color: var(--texto-principal);
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s;
        }

        .pill-filtro:hover {
            background: #f1f5f9 !important;
            color: var(--texto-principal) !important;
        }

        .pill-filtro.ativo {
            background: var(--azul-topo) !important;
            color: #fff !important;
            border-color: var(--azul-topo) !important;
        }

        .pill-filtro.pill-rascunho.ativo {
            background: #ca8a04 !important;
            border-color: #ca8a04 !important;
        }

        .pill-filtro.pill-publicado.ativo {
            background: #16a34a !important;
            border-color: #16a34a !important;
        }

        .pill-filtro.pill-arquivado.ativo {
            background: #475569 !important;
            border-color: #475569 !important;
        }

        .barra-filtros {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            background: #f8fafc;
            border: 1px solid var(--borda);
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 18px;
        }

        .barra-filtros input[type="text"],
        .barra-filtros select {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.86rem;
            background: #fff;
        }

        .barra-filtros input[type="text"] {
            flex: 1;
            min-width: 220px;
        }

        /* Cards de Editais */
        .lista-editais {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .card-edital {
            background: #fff;
            border: 1px solid var(--borda);
            border-left: 6px solid #64748b;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.05);
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .card-edital:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
        }

        .card-edital.status-PUBLICADO {
            border-left-color: #16a34a;
        }

        .card-edital.status-RASCUNHO {
            border-left-color: #eab308;
        }

        .card-edital.status-ARQUIVADO {
            border-left-color: #94a3b8;
            background: #f8fafc;
        }

        .edital-topo {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 10px;
        }

        .edital-titulo {
            font-size: 1.08rem;
            font-weight: 700;
            color: var(--azul-topo);
            margin: 0 0 4px;
        }

        .edital-meta {
            font-size: 0.84rem;
            color: var(--texto-suave);
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.4px;
        }

        .badge-PUBLICADO {
            background: #dcfce7;
            color: #166534;
        }

        .badge-RASCUNHO {
            background: #fef9c3;
            color: #854d0e;
        }

        .badge-ARQUIVADO {
            background: #e2e8f0;
            color: #334155;
        }

        .badge-prazo-ok {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-prazo-expirado {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-autor {
            background: #f1f5f9;
            color: #334155;
            font-weight: 600;
        }

        .badge-pdf {
            background: #e0e7ff;
            color: #3730a3;
        }

        .acoes-edital {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .regra-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
            margin-top: 12px;
        }

        .regra-box.inativa {
            background: #f1f5f9;
            opacity: 0.85;
        }

        .details-limpo {
            margin-top: 10px;
        }

        .details-limpo summary {
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--azul-primario);
            user-select: none;
        }

        .details-corpo {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed var(--borda);
        }

        /* KPIs e Placar Pessoal */
        .grid-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .card-kpi {
            background: #fff;
            border: 1px solid var(--borda);
            border-top: 4px solid var(--amarelo-detalhe);
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.04);
        }

        .card-kpi.kpi-azul {
            border-top-color: var(--azul-primario);
        }

        .card-kpi.kpi-verde {
            border-top-color: #16a34a;
        }

        .kpi-rotulo {
            font-size: 0.74rem;
            font-weight: 700;
            color: var(--texto-suave);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .kpi-valor {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--texto-principal);
            margin: 4px 0 2px;
        }

        .kpi-sub {
            font-size: 0.8rem;
            color: var(--texto-suave);
        }

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
            padding: 11px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .item-auditoria:last-child {
            border-bottom: 0;
        }

        .audit-acao {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 700;
            background: #fef9c3;
            color: #854d0e;
            padding: 2px 8px;
            border-radius: 6px;
            margin-bottom: 4px;
        }

        @media (max-width: 1024px) {
            .split-novo-edital {
                grid-template-columns: 1fr;
            }

            .painel-ia-sticky {
                position: static;
            }
        }
    </style>
</head>

<body>

    <header class="cabecalho">
        <div class="cabecalho-marca">
            <img src="{{ asset('assets/img/UNIELEGI LOGO 2026 coordenador.svg') }}" alt="Logo Coordenação UNIELEGI" class="cabecalho-logo">
            <div>
                <strong>SCOM · Coordenação</strong>
                <small>Cadastro assistido por IA, curadoria de editais e retificação de regras</small>
            </div>
        </div>
        <div class="cabecalho-usuario">
            <span class="pill-pontos-topo" title="Sua pontuação editorial">⭐ {{ $meuPlacar['pontos'] }} PTS</span>
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
            <strong>Não foi possível concluir:</strong>
            <ul>
                @foreach ($errors->all() as $erro)
                <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @php
        $abaInicial = $errors->any() ? 'aba-novo' : $filtros['aba'];
        @endphp

        <nav class="barra-abas" aria-label="Seções da Coordenação">
            <button type="button" id="btn-aba-novo" class="aba-btn {{ $abaInicial === 'aba-novo' ? 'ativa' : '' }}" onclick="trocarAbaCoord('aba-novo')">
                📝 Novo Edital &amp; Leitor IA
            </button>
            <button type="button" id="btn-aba-editais" class="aba-btn {{ $abaInicial === 'aba-editais' ? 'ativa' : '' }}" onclick="trocarAbaCoord('aba-editais')">
                📂 Gestão de Editais ({{ $contadores['todos'] }})
            </button>
            <button type="button" id="btn-aba-atividade" class="aba-btn {{ $abaInicial === 'aba-atividade' ? 'ativa' : '' }}" onclick="trocarAbaCoord('aba-atividade')">
                🏆 Minha Atividade &amp; Placar ({{ $meuPlacar['total_acoes'] }})
            </button>
        </nav>

        {{-- ABA 1: NOVO EDITAL & LEITOR IA (LADO A LADO) --}}
        <section id="aba-novo" class="secao-aba {{ $abaInicial === 'aba-novo' ? 'ativa' : '' }}">
            <div class="split-novo-edital">

                {{-- COLUNA ESQUERDA: LEITOR IA + PREVIEW PDF + TEXTO GRIFADO --}}
                <div class="painel-ia-sticky">
                    <div class="card-ia">
                        <h2 style="margin:0 0 4px; font-size:1.12rem; color:#1e3a8a;">🤖 Leitor Assistido por IA Local</h2>
                        <p style="margin:0 0 14px; font-size:0.86rem; color:#334155;">
                            Envie o PDF do edital <strong>ou</strong> cole o texto abaixo. Os pontos-chave (datas, valores e critérios) serão grifados aqui para conferência rápida ao lado do formulário.
                        </p>

                        <div class="campo" style="margin-bottom: 12px;">
                            <label for="ia_arquivo_pdf">Arquivo PDF do edital (até 10 MB)</label>
                            <input id="ia_arquivo_pdf" type="file" accept="application/pdf" onchange="atualizarPreviewPdf(this)">
                        </div>

                        <div class="campo" style="margin-bottom: 12px;">
                            <label for="ia_texto_edital">Ou cole o trecho principal do edital</label>
                            <textarea id="ia_texto_edital" rows="5" placeholder="Cole aqui instituição, prazos, renda máxima, cotas e documentos exigidos..."></textarea>
                        </div>

                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <button type="button" id="btn-escanear-ia" class="btn-primario" onclick="escanearEditalComIA()">
                                🤖 Escanear e Preencher Formulário
                            </button>
                            <small id="ia-status-texto" style="color:#475569;"></small>
                        </div>

                        <div id="ia-progresso-box" style="display:none; margin-top:12px;">
                            <div style="display:flex; justify-content:space-between; font-size:0.82rem; color:#1e3a8a; margin-bottom:4px; font-weight:600;">
                                <span id="ia-progresso-etapa">Iniciando leitura...</span>
                                <span id="ia-progresso-tempo">00:00</span>
                            </div>
                            <div style="width:100%; height:10px; background:#dbeafe; border-radius:999px; overflow:hidden;">
                                <div id="ia-progresso-barra" style="width:5%; height:100%; background:linear-gradient(90deg, #2563eb, #0ea5e9); border-radius:999px; transition:width 0.4s ease;"></div>
                            </div>
                        </div>

                        <div id="ia-feedback" style="display:none; margin-top:12px; padding:10px 12px; border-radius:8px; font-size:0.86rem; font-weight:500;"></div>

                        {{-- Texto Extraído com Grifos Coloridos --}}
                        <div id="texto-grifado-box" class="texto-grifado-box">
                            <div class="preview-pdf-header">
                                <span>🖍️ Pontos-Chave Grifados</span>
                                <span style="font-size:0.72rem; font-weight:600; display:flex; gap:5px; flex-wrap:wrap;">
                                    <span class="grifo-data">Datas</span>
                                    <span class="grifo-valor">Valores</span>
                                    <span class="grifo-criterio">Critérios</span>
                                    <span class="grifo-entidade">Inst./Cidade</span>
                                </span>
                            </div>
                            <div id="texto-grifado-conteudo" class="texto-grifado-conteudo"></div>
                        </div>

                        {{-- Visualizador lateral do PDF selecionado --}}
                        <div id="preview-pdf-box" class="preview-pdf-box">
                            <div class="preview-pdf-header">
                                <span id="preview-pdf-nome">📄 Pré-visualização do PDF</span>
                                <button type="button" class="btn-secundario" style="padding:3px 8px; font-size:0.75rem;" onclick="limparPreviewPdf()">Fechar</button>
                            </div>
                            <iframe id="preview-pdf-frame" class="preview-pdf-frame" title="Pré-visualização do PDF do edital"></iframe>
                        </div>
                    </div>
                </div>

                {{-- COLUNA DIREITA: FORMULÁRIO DO EDITAL --}}
                <div class="cartao" style="margin-bottom: 0;">
                    <h2>Novo Edital</h2>
                    <p class="subtitulo">Todo edital começa como <strong>RASCUNHO</strong> para revisão. Os campos preenchidos pela IA ficam destacados em azul.</p>

                    <form method="POST" action="{{ route('coordenacao.editais.store') }}" enctype="multipart/form-data" class="form-preservar-posicao" data-aba-destino="aba-editais">
                        @csrf

                        <div class="secao-form-titulo">1. Instituição &amp; Localização</div>
                        <div class="grade">
                            <div class="campo">
                                <label for="instituicao">Instituição *</label>
                                <input id="instituicao" name="instituicao" maxlength="100" required value="{{ old('instituicao') }}" placeholder="Ex: UNESP">
                            </div>
                            <div class="campo">
                                <label for="nome_processo">Nome do processo *</label>
                                <input id="nome_processo" name="nome_processo" maxlength="150" required value="{{ old('nome_processo') }}" placeholder="Ex: Vestibular 2027 - Isenção">
                            </div>
                            <div class="campo">
                                <label for="cidade">Cidade do campus *</label>
                                <input id="cidade" name="cidade" maxlength="100" required value="{{ old('cidade') }}" placeholder="Ex: Sorocaba">
                            </div>
                            <div class="campo">
                                <label for="estado">Estado (UF) *</label>
                                <input id="estado" name="estado" maxlength="2" minlength="2" required value="{{ old('estado') }}" placeholder="SP">
                            </div>
                            <div class="campo">
                                <label for="regiao">Região *</label>
                                <select id="regiao" name="regiao" required>
                                    @foreach (['SUDESTE','SUL','CENTRO_OESTE','NORDESTE','NORTE','INTERNACIONAL'] as $item)
                                    <option value="{{ $item }}" @selected(old('regiao') === $item)>{{ $item }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="campo">
                                <label for="ano_referencia">Ano de referência *</label>
                                <input id="ano_referencia" name="ano_referencia" type="number" min="2000" max="2155" required value="{{ old('ano_referencia', date('Y')) }}">
                            </div>
                            <div class="campo">
                                <label for="latitude">Latitude *</label>
                                <input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" required value="{{ old('latitude') }}" placeholder="-23.501500">
                            </div>
                            <div class="campo">
                                <label for="longitude">Longitude *</label>
                                <input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" required value="{{ old('longitude') }}" placeholder="-47.452600">
                            </div>
                        </div>

                        <div class="secao-form-titulo">2. Logística &amp; Documento Oficial</div>
                        <div class="grade">
                            <div class="campo">
                                <label for="custo_medio_republica">Custo médio de república (R$)</label>
                                <input id="custo_medio_republica" name="custo_medio_republica" type="number" min="0" step="0.01" value="{{ old('custo_medio_republica') }}" placeholder="750.00">
                            </div>
                            <div class="campo">
                                <label for="tem_moradia_estudantil">Moradia estudantil</label>
                                <select id="tem_moradia_estudantil" name="tem_moradia_estudantil">
                                    <option value="0" @selected(old('tem_moradia_estudantil') === '0')>Não</option>
                                    <option value="1" @selected(old('tem_moradia_estudantil') === '1')>Sim</option>
                                </select>
                            </div>
                            <div class="campo" style="grid-column: 1 / -1;">
                                <label for="link_documento">Link do documento oficial (http/https)</label>
                                <input id="link_documento" name="link_documento" maxlength="255" value="{{ old('link_documento') }}" placeholder="https://...">
                            </div>
                            <div class="campo" style="grid-column: 1 / -1;">
                                <label for="arquivo_pdf_form">Cópia do Edital em PDF (repositório para download do estudante)</label>
                                <input id="arquivo_pdf_form" name="arquivo_pdf" type="file" accept="application/pdf">
                                <small style="color:var(--texto-suave); font-size:0.78rem;">Se você selecionou um PDF no Leitor IA ao lado, ele é anexado aqui automaticamente.</small>
                            </div>
                        </div>

                        <div class="secao-form-titulo">3. Primeira Regra de Elegibilidade</div>
                        <div class="grade">
                            <div class="campo">
                                <label for="tipo_beneficio">Tipo de benefício *</label>
                                <select id="tipo_beneficio" name="regra[tipo_beneficio]" required>
                                    @foreach (['ISENCAO_TAXA','COTA_RENDA','COTA_ESCOLA_PUBLICA','COTA_RACIAL','AMPLA_CONCORRENCIA'] as $item)
                                    <option value="{{ $item }}" @selected(old('regra.tipo_beneficio') === $item)>{{ $item }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="campo">
                                <label for="renda_maxima_per_capita">Renda máxima per capita (R$)</label>
                                <input id="renda_maxima_per_capita" name="regra[renda_maxima_per_capita]" type="number" min="0" step="0.01" value="{{ old('regra.renda_maxima_per_capita') }}" placeholder="2118.00">
                            </div>
                            <div class="campo">
                                <label for="nota_corte_estimada">Nota de corte estimada</label>
                                <input id="nota_corte_estimada" name="regra[nota_corte_estimada]" type="number" min="0" max="1000" value="{{ old('regra.nota_corte_estimada') }}" placeholder="620">
                            </div>
                            <div class="campo">
                                <label for="prazo_inicio">Início das inscrições *</label>
                                <input id="prazo_inicio" name="regra[prazo_inicio]" type="date" required value="{{ old('regra.prazo_inicio') }}">
                            </div>
                            <div class="campo">
                                <label for="prazo_fim">Fim das inscrições *</label>
                                <input id="prazo_fim" name="regra[prazo_fim]" type="date" required value="{{ old('regra.prazo_fim') }}">
                            </div>
                        </div>

                        <div class="grupo-checks">
                            <label class="check-item">
                                <input type="hidden" name="regra[exige_escola_publica]" value="0">
                                <input type="checkbox" name="regra[exige_escola_publica]" value="1" @checked(old('regra.exige_escola_publica'))>
                                Exige escola pública
                            </label>
                            <label class="check-item">
                                <input type="hidden" name="regra[exige_etnia]" value="0">
                                <input type="checkbox" name="regra[exige_etnia]" value="1" @checked(old('regra.exige_etnia'))>
                                Exige critério étnico-racial PPI
                            </label>
                            <label class="check-item">
                                <input type="hidden" name="regra[exige_pcd]" value="0">
                                <input type="checkbox" name="regra[exige_pcd]" value="1" @checked(old('regra.exige_pcd'))>
                                Exige condição PcD
                            </label>
                        </div>

                        <div class="campo" style="margin-bottom: 16px;">
                            <label for="documentos_exigidos">Documentos exigidos</label>
                            <textarea id="documentos_exigidos" name="regra[documentos_exigidos]" rows="3" placeholder="Ex: RG, CPF, histórico escolar, comprovantes de renda familiar...">{{ old('regra.documentos_exigidos') }}</textarea>
                        </div>

                        <div style="display:flex; justify-content:flex-end; gap:10px;">
                            <button type="reset" class="btn-secundario">Limpar campos</button>
                            <button type="submit" class="btn-primario">💾 Salvar edital como rascunho</button>
                        </div>
                    </form>
                </div>

            </div>
        </section>

        {{-- ABA 2: GESTÃO DE EDITAIS --}}
        <section id="aba-editais" class="secao-aba {{ $abaInicial === 'aba-editais' ? 'ativa' : '' }}">
            <div class="cartao">
                <h2>Gestão de Editais</h2>
                <p class="subtitulo">Acompanhe o status visual (Amarelo = Rascunho, Verde = Publicado, Cinza = Arquivado), filtre por autoria e retifique regras sem perder sua posição na página.</p>

                <div class="pills-status">
                    <a href="{{ route('coordenacao.dashboard', ['aba' => 'aba-editais', 'ordenar' => $filtros['ordenar']]) }}"
                        class="pill-filtro {{ $filtros['status'] === '' && $filtros['autoria'] === 'TODOS' ? 'ativo' : '' }}">
                        Todos ({{ $contadores['todos'] }})
                    </a>
                    <a href="{{ route('coordenacao.dashboard', ['aba' => 'aba-editais', 'status' => 'RASCUNHO', 'ordenar' => $filtros['ordenar']]) }}"
                        class="pill-filtro pill-rascunho {{ $filtros['status'] === 'RASCUNHO' ? 'ativo' : '' }}">
                        🟡 Rascunhos ({{ $contadores['rascunho'] }})
                    </a>
                    <a href="{{ route('coordenacao.dashboard', ['aba' => 'aba-editais', 'status' => 'PUBLICADO', 'ordenar' => $filtros['ordenar']]) }}"
                        class="pill-filtro pill-publicado {{ $filtros['status'] === 'PUBLICADO' ? 'ativo' : '' }}">
                        🟢 Publicados ({{ $contadores['publicado'] }})
                    </a>
                    <a href="{{ route('coordenacao.dashboard', ['aba' => 'aba-editais', 'status' => 'ARQUIVADO', 'ordenar' => $filtros['ordenar']]) }}"
                        class="pill-filtro pill-arquivado {{ $filtros['status'] === 'ARQUIVADO' ? 'ativo' : '' }}">
                        ⚫ Arquivados ({{ $contadores['arquivado'] }})
                    </a>
                    <a href="{{ route('coordenacao.dashboard', ['aba' => 'aba-editais', 'autoria' => 'MEUS', 'ordenar' => $filtros['ordenar']]) }}"
                        class="pill-filtro {{ $filtros['autoria'] === 'MEUS' ? 'ativo' : '' }}">
                        👤 Minhas postagens ({{ $contadores['meus'] }})
                    </a>
                </div>

                <form method="GET" action="{{ route('coordenacao.dashboard') }}" class="barra-filtros">
                    <input type="hidden" name="aba" value="aba-editais">
                    <input type="text" name="busca" value="{{ $filtros['busca'] }}" placeholder="Buscar por instituição, processo ou cidade..." aria-label="Buscar edital">
                    <select name="status" aria-label="Filtrar status">
                        <option value="">Todos os status</option>
                        <option value="RASCUNHO" @selected($filtros['status'] === 'RASCUNHO')>Rascunho</option>
                        <option value="PUBLICADO" @selected($filtros['status'] === 'PUBLICADO')>Publicado</option>
                        <option value="ARQUIVADO" @selected($filtros['status'] === 'ARQUIVADO')>Arquivado</option>
                    </select>
                    <select name="autoria" aria-label="Filtrar autoria">
                        <option value="TODOS" @selected($filtros['autoria'] === 'TODOS')>Toda a equipe</option>
                        <option value="MEUS" @selected($filtros['autoria'] === 'MEUS')>Apenas minhas postagens</option>
                    </select>
                    <select name="ordenar" aria-label="Ordenar editais">
                        <option value="recentes" @selected($filtros['ordenar'] === 'recentes')>Ordenar: Mais recentes primeiro</option>
                        <option value="antigos" @selected($filtros['ordenar'] === 'antigos')>Ordenar: Mais antigos primeiro</option>
                        <option value="ano" @selected($filtros['ordenar'] === 'ano')>Ordenar: Ano de referência</option>
                        <option value="instituicao" @selected($filtros['ordenar'] === 'instituicao')>Ordenar: Instituição (A–Z)</option>
                    </select>
                    <button type="submit" class="btn-primario">Filtrar</button>
                    <a href="{{ route('coordenacao.dashboard', ['aba' => 'aba-editais']) }}" class="btn-secundario">Limpar</a>
                </form>

                <div class="lista-editais">
                    @php $hojeIso = now()->toDateString(); @endphp

                    @forelse ($editais as $edital)
                    @php
                    $infoAutor = $autoriaPorEdital[$edital->id] ?? null;
                    $regrasAtivas = $edital->regras->where('status_regra', 'ATIVA');
                    $regrasInativas = $edital->regras->where('status_regra', '!=', 'ATIVA');
                    @endphp
                    <article class="card-edital status-{{ $edital->status_publicacao }}">
                        <div class="edital-topo">
                            <div>
                                <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:4px;">
                                    <span class="badge badge-{{ $edital->status_publicacao }}">{{ $edital->status_publicacao }}</span>
                                    @if ($edital->arquivo_pdf)
                                    <span class="badge badge-pdf">📄 PDF Anexado</span>
                                    @endif
                                    <h3 class="edital-titulo" style="margin:0;">{{ $edital->instituicao }} · {{ $edital->nome_processo }}</h3>
                                </div>
                                <div class="edital-meta">
                                    <span>📍 {{ $edital->cidade }}/{{ $edital->estado }} ({{ $edital->regiao }})</span>
                                    <span>📅 Ano {{ $edital->ano_referencia }}</span>
                                    @if ($infoAutor)
                                    <span class="badge badge-autor">
                                        👤 Por {{ $infoAutor['criador'] }}
                                        @if ($infoAutor['ultima_data'])
                                        · Última ação: {{ $infoAutor['ultimo_ator'] }} ({{ $infoAutor['ultima_data']->format('d/m H:i') }})
                                        @endif
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <div class="acoes-edital">
                                @if ($edital->status_publicacao !== 'PUBLICADO')
                                <form method="POST" action="{{ route('coordenacao.editais.publicar', $edital) }}" class="form-preservar-posicao" data-aba-destino="aba-editais" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn-sucesso">
                                        {{ $edital->status_publicacao === 'ARQUIVADO' ? '🔄 Republicar' : '🚀 Publicar' }}
                                    </button>
                                </form>
                                @endif

                                @if ($edital->status_publicacao !== 'ARQUIVADO')
                                <form method="POST" action="{{ route('coordenacao.editais.arquivar', $edital) }}" class="form-preservar-posicao" data-aba-destino="aba-editais" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn-arquivar">📦 Arquivar</button>
                                </form>
                                @endif
                            </div>
                        </div>

                        <details class="details-limpo">
                            <summary>✏️ Editar dados do edital</summary>
                            <div class="details-corpo">
                                <form method="POST" action="{{ route('coordenacao.editais.atualizar', $edital) }}" enctype="multipart/form-data" class="form-preservar-posicao" data-aba-destino="aba-editais">
                                    @csrf @method('PATCH')
                                    <div class="grade">
                                        <div class="campo"><label>Instituição</label><input name="instituicao" maxlength="100" required value="{{ $edital->instituicao }}"></div>
                                        <div class="campo"><label>Nome do processo</label><input name="nome_processo" maxlength="150" required value="{{ $edital->nome_processo }}"></div>
                                        <div class="campo"><label>Cidade</label><input name="cidade" maxlength="100" required value="{{ $edital->cidade }}"></div>
                                        <div class="campo"><label>Estado</label><input name="estado" minlength="2" maxlength="2" required value="{{ $edital->estado }}"></div>
                                        <div class="campo"><label>Região</label><select name="regiao" required>@foreach (['SUDESTE','SUL','CENTRO_OESTE','NORDESTE','NORTE','INTERNACIONAL'] as $item)<option value="{{ $item }}" @selected($edital->regiao === $item)>{{ $item }}</option>@endforeach</select></div>
                                        <div class="campo"><label>Latitude</label><input name="latitude" type="number" step="any" min="-90" max="90" required value="{{ $edital->latitude }}"></div>
                                        <div class="campo"><label>Longitude</label><input name="longitude" type="number" step="any" min="-180" max="180" required value="{{ $edital->longitude }}"></div>
                                        <div class="campo"><label>Ano</label><input name="ano_referencia" type="number" min="2000" max="2155" required value="{{ $edital->ano_referencia }}"></div>
                                        <div class="campo"><label>Custo de república</label><input name="custo_medio_republica" type="number" min="0" step="0.01" value="{{ $edital->custo_medio_republica }}"></div>
                                        <div class="campo"><label>Moradia estudantil</label><select name="tem_moradia_estudantil"><option value="0" @selected(!$edital->tem_moradia_estudantil)>Não</option><option value="1" @selected($edital->tem_moradia_estudantil)>Sim</option></select></div>
                                        <div class="campo"><label>Link/documento</label><input name="link_documento" maxlength="255" value="{{ $edital->link_documento }}"></div>
                                        <div class="campo"><label>Substituir/anexar PDF</label><input name="arquivo_pdf" type="file" accept="application/pdf"></div>
                                    </div>
                                    <div style="margin-top:12px;">
                                        <button type="submit" class="btn-primario">Salvar alterações do edital</button>
                                    </div>
                                </form>
                            </div>
                        </details>

                        {{-- Regras Ativas --}}
                        @foreach ($regrasAtivas as $regra)
                        @php $prazoExpirado = $regra->prazo_fim && $regra->prazo_fim < $hojeIso; @endphp
                        <div class="regra-box">
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                                <strong>Regra #{{ $regra->id }} · {{ $regra->tipo_beneficio }} · v{{ $regra->versao }} (ATIVA)</strong>
                                <span class="badge {{ $prazoExpirado ? 'badge-prazo-expirado' : 'badge-prazo-ok' }}">
                                    {{ $prazoExpirado ? '⏰ Prazo encerrado' : '📅 Vigência' }}: {{ $regra->prazo_inicio }} até {{ $regra->prazo_fim }}
                                </span>
                            </div>

                            <details class="details-limpo">
                                <summary>🔄 Retificar regra (cria nova versão e preserva a anterior)</summary>
                                <div class="details-corpo">
                                    <form method="POST" action="{{ route('coordenacao.regras.retificar', $regra) }}" class="form-preservar-posicao" data-aba-destino="aba-editais">
                                        @csrf
                                        <div class="campo" style="margin-bottom:10px;">
                                            <label>Motivo da retificação *</label>
                                            <input name="motivo_retificacao" minlength="10" maxlength="255" required placeholder="Descreva o adendo ou correção oficial...">
                                        </div>
                                        <div class="grade">
                                            <div class="campo"><label>Tipo de benefício</label><select name="tipo_beneficio" required>@foreach (['ISENCAO_TAXA','COTA_RENDA','COTA_ESCOLA_PUBLICA','COTA_RACIAL','AMPLA_CONCORRENCIA'] as $item)<option value="{{ $item }}" @selected($regra->tipo_beneficio === $item)>{{ $item }}</option>@endforeach</select></div>
                                            <div class="campo"><label>Renda máxima</label><input name="renda_maxima_per_capita" type="number" min="0" step="0.01" value="{{ $regra->renda_maxima_per_capita }}"></div>
                                            <div class="campo"><label>Nota estimada</label><input name="nota_corte_estimada" type="number" min="0" max="1000" value="{{ $regra->nota_corte_estimada }}"></div>
                                            <div class="campo"><label>Início</label><input name="prazo_inicio" type="date" required value="{{ $regra->prazo_inicio }}"></div>
                                            <div class="campo"><label>Fim</label><input name="prazo_fim" type="date" required value="{{ $regra->prazo_fim }}"></div>
                                        </div>
                                        <div class="grupo-checks">
                                            <label class="check-item"><input type="hidden" name="exige_escola_publica" value="0"><input type="checkbox" name="exige_escola_publica" value="1" @checked($regra->exige_escola_publica)> Exige escola pública</label>
                                            <label class="check-item"><input type="hidden" name="exige_etnia" value="0"><input type="checkbox" name="exige_etnia" value="1" @checked($regra->exige_etnia)> Exige critério étnico-racial PPI</label>
                                            <label class="check-item"><input type="hidden" name="exige_pcd" value="0"><input type="checkbox" name="exige_pcd" value="1" @checked($regra->exige_pcd)> Exige condição PcD</label>
                                        </div>
                                        <div class="campo" style="margin-bottom:10px;">
                                            <label>Documentos exigidos</label>
                                            <textarea name="documentos_exigidos" rows="2">{{ $regra->documentos_exigidos }}</textarea>
                                        </div>
                                        <button type="submit" class="btn-primario">Criar nova versão</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                        @endforeach

                        {{-- Histórico de Regras Inativas --}}
                        @if ($regrasInativas->isNotEmpty())
                        <details class="details-limpo">
                            <summary style="color:#64748b;">📜 Histórico de versões anteriores ({{ $regrasInativas->count() }})</summary>
                            @foreach ($regrasInativas as $regraAntiga)
                            <div class="regra-box inativa">
                                <strong>Regra #{{ $regraAntiga->id }} · {{ $regraAntiga->tipo_beneficio }} · v{{ $regraAntiga->versao }} ({{ $regraAntiga->status_regra }})</strong>
                                <div style="font-size:0.84rem; color:#475569; margin-top:4px;">Vigência: {{ $regraAntiga->prazo_inicio }} até {{ $regraAntiga->prazo_fim }}</div>
                                @foreach ($regraAntiga->retificacoesOrigem as $historico)
                                <small style="display:block; margin-top:4px; color:#334155;">
                                    Retificada em {{ optional($historico->data_retificacao)->format('d/m/Y H:i') }} → nova regra #{{ $historico->id_regra_nova }}. Motivo: {{ $historico->motivo_retificacao }}
                                </small>
                                @endforeach
                            </div>
                            @endforeach
                        </details>
                        @endif
                    </article>
                    @empty
                    <p style="text-align:center; color:var(--texto-suave); padding:28px 0;">Nenhum edital encontrado para os filtros aplicados.</p>
                    @endforelse
                </div>

                <div style="margin-top: 18px;">
                    {{ $editais->links() }}
                </div>
            </div>
        </section>

        {{-- ABA 3: MINHA ATIVIDADE & PLACAR --}}
        <section id="aba-atividade" class="secao-aba {{ $abaInicial === 'aba-atividade' ? 'ativa' : '' }}">
            <div class="grid-kpis">
                <div class="card-kpi">
                    <div class="kpi-rotulo">Minha Pontuação</div>
                    <div class="kpi-valor">⭐ {{ $meuPlacar['pontos'] }}</div>
                    <div class="kpi-sub">{{ $meuPlacar['total_acoes'] }} ações registradas</div>
                </div>
                <div class="card-kpi kpi-azul">
                    <div class="kpi-rotulo">Editais Criados</div>
                    <div class="kpi-valor">{{ $meuPlacar['EDITAL_CRIADO'] }}</div>
                    <div class="kpi-sub">+{{ $pesosPontuacao['EDITAL_CRIADO'] }} PTS cada</div>
                </div>
                <div class="card-kpi kpi-verde">
                    <div class="kpi-rotulo">Publicados</div>
                    <div class="kpi-valor">{{ $meuPlacar['EDITAL_PUBLICADO'] }}</div>
                    <div class="kpi-sub">+{{ $pesosPontuacao['EDITAL_PUBLICADO'] }} PTS cada</div>
                </div>
                <div class="card-kpi kpi-azul">
                    <div class="kpi-rotulo">Retificações</div>
                    <div class="kpi-valor">{{ $meuPlacar['REGRA_RETIFICADA'] }}</div>
                    <div class="kpi-sub">+{{ $pesosPontuacao['REGRA_RETIFICADA'] }} PTS cada</div>
                </div>
                <div class="card-kpi">
                    <div class="kpi-rotulo">Edições / Arquiv.</div>
                    <div class="kpi-valor">{{ $meuPlacar['EDITAL_EDITADO'] + $meuPlacar['EDITAL_ARQUIVADO'] }}</div>
                    <div class="kpi-sub">{{ $meuPlacar['EDITAL_EDITADO'] }} ed. · {{ $meuPlacar['EDITAL_ARQUIVADO'] }} arq. (+{{ $pesosPontuacao['EDITAL_EDITADO'] }} PTS)</div>
                </div>
            </div>

            <div class="cartao">
                <h2>Meu Histórico de Ações</h2>
                <p class="subtitulo">Últimas 40 movimentações registradas em sua conta na auditoria editorial.</p>

                @if ($minhaAtividade->isEmpty())
                <p style="color:var(--texto-suave); text-align:center; padding:20px 0;">Você ainda não registrou ações editoriais.</p>
                @else
                <ul class="lista-auditoria">
                    @foreach ($minhaAtividade as $log)
                    <li class="item-auditoria">
                        <div>
                            <span class="audit-acao">{{ $log->acao }} (+{{ $pesosPontuacao[$log->acao] ?? 0 }} PTS)</span>
                            <p style="margin:2px 0; font-size:0.88rem;">{{ $log->detalhes }}</p>
                        </div>
                        <span style="font-size:0.78rem; color:var(--texto-suave); white-space:nowrap;">
                            {{ $log->criado_em ? $log->criado_em->format('d/m/Y H:i') : '—' }}
                        </span>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </section>
    </main>

    <script>
        let previewPdfUrl = null;

        function trocarAbaCoord(idAba) {
            document.querySelectorAll('.secao-aba').forEach(el => el.classList.remove('ativa'));
            document.querySelectorAll('.aba-btn').forEach(btn => btn.classList.remove('ativa'));
            const alvo = document.getElementById(idAba);
            const btnAlvo = document.getElementById('btn-' + idAba);
            if (alvo) alvo.classList.add('ativa');
            if (btnAlvo) btnAlvo.classList.add('ativa');
        }

        function atualizarPreviewPdf(input) {
            const box = document.getElementById('preview-pdf-box');
            const frame = document.getElementById('preview-pdf-frame');
            const nomeEl = document.getElementById('preview-pdf-nome');
            const inputFormPdf = document.getElementById('arquivo_pdf_form');

            if (previewPdfUrl) {
                URL.revokeObjectURL(previewPdfUrl);
                previewPdfUrl = null;
            }

            if (input.files && input.files[0]) {
                const arquivo = input.files[0];
                previewPdfUrl = URL.createObjectURL(arquivo);
                frame.src = previewPdfUrl;
                nomeEl.textContent = '📄 ' + arquivo.name;
                box.style.display = 'block';

                // Sincroniza o mesmo PDF para o formulário de salvamento automaticamente
                if (inputFormPdf) {
                    const dt = new DataTransfer();
                    dt.items.add(arquivo);
                    inputFormPdf.files = dt.files;
                }
            } else {
                box.style.display = 'none';
                frame.src = '';
            }
        }

        function limparPreviewPdf() {
            const box = document.getElementById('preview-pdf-box');
            const frame = document.getElementById('preview-pdf-frame');
            if (previewPdfUrl) {
                URL.revokeObjectURL(previewPdfUrl);
                previewPdfUrl = null;
            }
            frame.src = '';
            box.style.display = 'none';
        }

        function escapeHtmlCoord(str) {
            return String(str ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            })[c]);
        }

        function renderizarTextoGrifado(texto, dados) {
            const box = document.getElementById('texto-grifado-box');
            const alvo = document.getElementById('texto-grifado-conteudo');
            if (!box || !alvo) return;

            let html = escapeHtmlCoord(texto);

            // 1. Datas (dd/mm/aaaa, aaaa-mm-dd ou "dia de mês")
            html = html.replace(
                /(\b\d{1,2}\/\d{1,2}\/\d{2,4}\b|\b\d{4}-\d{2}-\d{2}\b|\b\d{1,2}\s+de\s+(?:janeiro|fevereiro|março|marco|abril|maio|junho|julho|agosto|setembro|outubro|novembro|dezembro)(?:\s+de\s+\d{4})?\b)/gi,
                '<mark class="grifo-data">$1</mark>'
            );

            // 2. Valores monetários e salários mínimos
            html = html.replace(
                /(R\$\s?[\d.,]+|\b\d+(?:[.,]\d+)?\s*sal[áa]rios?\s+m[íi]nimos?\b)/gi,
                '<mark class="grifo-valor">$1</mark>'
            );

            // 3. Critérios de elegibilidade
            html = html.replace(
                /\b(isen[çc][ãa]o|renda\s+per\s+capita|escola\s+p[úu]blica|autodeclara[çc][ãa]o|PPI|PcD|pessoa\s+com\s+defici[êe]ncia|nota\s+de\s+corte|moradia\s+estudantil|documentos?\s+exigidos?)\b/gi,
                '<mark class="grifo-criterio">$1</mark>'
            );

            // 4. Instituição e Cidade extraídas pela IA
            [dados.instituicao, dados.cidade]
                .filter(v => typeof v === 'string' && v.trim().length >= 3)
                .forEach(termo => {
                    const seguro = escapeHtmlCoord(termo.trim()).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    html = html.replace(new RegExp(`(${seguro})`, 'gi'), '<mark class="grifo-entidade">$1</mark>');
                });

            alvo.innerHTML = html;
            box.style.display = 'block';
        }

        // Preserva aba e posição de rolagem ao publicar, arquivar, editar ou retificar
        document.querySelectorAll('.form-preservar-posicao').forEach(form => {
            form.addEventListener('submit', () => {
                const abaDestino = form.dataset.abaDestino || 'aba-editais';
                sessionStorage.setItem('coord_aba', abaDestino);
                sessionStorage.setItem('coord_scroll', String(window.scrollY));
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const temErros = Boolean(document.querySelector('.alerta-erro'));
            if (!temErros) {
                const abaSalva = sessionStorage.getItem('coord_aba');
                const scrollSalvo = sessionStorage.getItem('coord_scroll');
                if (abaSalva) {
                    trocarAbaCoord(abaSalva);
                    sessionStorage.removeItem('coord_aba');
                }
                if (scrollSalvo !== null) {
                    window.scrollTo({
                        top: parseInt(scrollSalvo, 10) || 0,
                        behavior: 'instant'
                    });
                    sessionStorage.removeItem('coord_scroll');
                }
            }
        });

        async function escanearEditalComIA() {
            const btn = document.getElementById('btn-escanear-ia');
            const statusTxt = document.getElementById('ia-status-texto');
            const feedback = document.getElementById('ia-feedback');
            const inputPdf = document.getElementById('ia_arquivo_pdf');
            const inputTexto = document.getElementById('ia_texto_edital');

            const progressoBox = document.getElementById('ia-progresso-box');
            const progressoBarra = document.getElementById('ia-progresso-barra');
            const progressoEtapa = document.getElementById('ia-progresso-etapa');
            const progressoTempo = document.getElementById('ia-progresso-tempo');

            const formData = new FormData();
            const temPdf = Boolean(inputPdf.files && inputPdf.files[0]);
            if (temPdf) {
                formData.append('arquivo_pdf', inputPdf.files[0]);
            }
            if (inputTexto.value.trim() !== '') {
                formData.append('texto_edital', inputTexto.value.trim());
            }

            btn.disabled = true;
            btn.textContent = '⏳ Analisando edital...';
            statusTxt.textContent = '';
            feedback.style.display = 'none';

            let segundos = 0;
            let percentual = 8;
            const etapas = temPdf
                ? ['Lendo páginas do PDF...', 'Limpando texto extraído...', 'Consultando IA local (Ollama)...', 'Extraindo critérios e prazos...', 'Sanitizando JSON...']
                : ['Preparando texto...', 'Consultando IA local (Ollama)...', 'Extraindo critérios e prazos...', 'Sanitizando JSON...'];

            progressoBox.style.display = 'block';
            progressoBarra.style.background = 'linear-gradient(90deg, #2563eb, #0ea5e9)';
            progressoBarra.style.width = percentual + '%';
            progressoEtapa.textContent = etapas[0];
            progressoTempo.textContent = '00:00';

            const timer = setInterval(() => {
                segundos++;
                const mm = String(Math.floor(segundos / 60)).padStart(2, '0');
                const ss = String(segundos % 60).padStart(2, '0');
                progressoTempo.textContent = `${mm}:${ss}`;

                if (percentual < 92) {
                    percentual += percentual < 50 ? 4 : (percentual < 80 ? 1.5 : 0.4);
                    progressoBarra.style.width = Math.min(92, percentual).toFixed(1) + '%';
                }
                const idxEtapa = Math.min(etapas.length - 1, Math.floor(segundos / 4));
                progressoEtapa.textContent = etapas[idxEtapa];
            }, 1000);

            try {
                const res = await fetch("{{ route('coordenacao.editais.escanear-ia') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const json = await res.json();

                if (!res.ok || !json.sucesso) {
                    throw new Error(json.mensagem || json.message || 'Não foi possível analisar o edital.');
                }

                clearInterval(timer);
                progressoBarra.style.width = '100%';
                progressoBarra.style.background = '#16a34a';
                progressoEtapa.textContent = 'Análise concluída!';

                preencherFormularioComIA(json.dados || {});
                if (json.texto_extraido) {
                    renderizarTextoGrifado(json.texto_extraido, json.dados || {});
                }

                feedback.style.display = 'block';
                feedback.style.background = '#dcfce7';
                feedback.style.color = '#166534';
                feedback.textContent = '✅ ' + (json.aviso || 'Campos preenchidos. Revise antes de salvar.');
            } catch (erro) {
                clearInterval(timer);
                progressoBarra.style.width = '100%';
                progressoBarra.style.background = '#dc2626';
                progressoEtapa.textContent = 'Falha na análise.';

                feedback.style.display = 'block';
                feedback.style.background = '#fee2e2';
                feedback.style.color = '#991b1b';
                feedback.textContent = '⚠️ ' + erro.message;
            } finally {
                btn.disabled = false;
                btn.textContent = '🤖 Escanear e Preencher Formulário';
            }
        }

        function preencherFormularioComIA(dados) {
            const aplicarValor = (id, valor) => {
                if (valor === null || valor === undefined || valor === '') return;
                const el = document.getElementById(id);
                if (!el) return;
                el.value = valor;
                el.style.borderColor = '#2563eb';
                el.style.backgroundColor = '#eff6ff';
            };

            aplicarValor('instituicao', dados.instituicao);
            aplicarValor('nome_processo', dados.nome_processo);
            aplicarValor('cidade', dados.cidade);
            aplicarValor('estado', dados.estado);
            aplicarValor('regiao', dados.regiao);
            aplicarValor('ano_referencia', dados.ano_referencia);
            aplicarValor('custo_medio_republica', dados.custo_medio_republica);
            aplicarValor('link_documento', dados.link_documento);

            if (dados.tem_moradia_estudantil !== null && dados.tem_moradia_estudantil !== undefined) {
                aplicarValor('tem_moradia_estudantil', dados.tem_moradia_estudantil ? '1' : '0');
            }

            const regra = dados.regra || {};
            aplicarValor('tipo_beneficio', regra.tipo_beneficio);
            aplicarValor('renda_maxima_per_capita', regra.renda_maxima_per_capita);
            aplicarValor('nota_corte_estimada', regra.nota_corte_estimada);
            aplicarValor('prazo_inicio', regra.prazo_inicio);
            aplicarValor('prazo_fim', regra.prazo_fim);
            aplicarValor('documentos_exigidos', regra.documentos_exigidos);

            const aplicarCheckbox = (nome, marcado) => {
                const cb = document.querySelector(`input[type="checkbox"][name="${nome}"]`);
                if (cb) cb.checked = Boolean(marcado);
            };

            aplicarCheckbox('regra[exige_escola_publica]', regra.exige_escola_publica);
            aplicarCheckbox('regra[exige_etnia]', regra.exige_etnia);
            aplicarCheckbox('regra[exige_pcd]', regra.exige_pcd);

            if (dados.cidade && dados.estado) {
                buscarCoordenadasCampus(dados.cidade, dados.estado);
            }
        }

        async function buscarCoordenadasCampus(cidade, estado) {
            const inputLat = document.getElementById('latitude');
            const inputLng = document.getElementById('longitude');
            if (!inputLat || !inputLng) return;

            try {
                const query = `${cidade}, ${estado}, Brazil`;
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`);
                const locais = await res.json();

                if (Array.isArray(locais) && locais.length > 0) {
                    inputLat.value = Number(locais[0].lat).toFixed(6);
                    inputLng.value = Number(locais[0].lon).toFixed(6);
                    inputLat.style.borderColor = '#2563eb';
                    inputLat.style.backgroundColor = '#eff6ff';
                    inputLng.style.borderColor = '#2563eb';
                    inputLng.style.backgroundColor = '#eff6ff';
                }
            } catch (e) {
                console.warn('Não foi possível obter coordenadas automaticamente:', e);
            }
        }
    </script>

</body>

</html>