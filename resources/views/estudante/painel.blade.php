<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="scom-user-id" content="{{ auth()->id() ?? '' }}">
    <title>UNILEGI - Painel do Estudante</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/UNIELEGI logo.jpg') }}?v=2">
<link rel="shortcut icon" type="image/jpeg" href="{{ asset('assets/img/UNIELEGI logo.jpg') }}?v=2">

    <!-- Motor Vite (Bootstrap e Alpine) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- CSS Global e CSS do Painel -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/painel.css') }}">

    <!-- Leaflet CSS (Mapa Interativo) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Flatpickr (Super Calendário) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js" defer></script>

    <!-- Bibliotecas JS: Chart.js e Leaflet -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>

    <!-- Script Controlador do Painel -->
    <script src="{{ asset('assets/js/painel_estudante.js') }}" defer></script>
</head>

<body>

    <!-- Navbar Superior -->
    <header class="navbar">
        <div class="logo">
            <img src="{{ asset('assets/img/UNIELEGI LOGO 2026.svg') }}" alt="Logo UNILEGI" class="logo-img" style="height: 45px;">
            <span>UNILEGI | Estudante</span>
        </div>
        <nav>
            <ul>
                <li><a href="#" onclick="trocarAba('aba-dashboard')">Visão Geral & Mapa</a></li>
                <li><a href="#" onclick="trocarAba('aba-historico')">Histórico & Filtros</a></li>
                <li><a href="#" onclick="trocarAba('aba-ficha')">Minha Ficha Socioeconômica</a></li>
                <li><button onclick="fazerLogout()" class="btn-primario"
                        style="background:#dc3545; color:white; padding: 6px 15px;">Sair</button></li>
            </ul>
        </nav>
    </header>

    <main class="painel-wrapper">

        <!-- Botões de Alternância Rápida -->
        <div class="abas-navegacao">
            <button class="aba-btn ativa" id="btn-aba-dashboard" onclick="trocarAba('aba-dashboard')">📊 Dashboard &
                Mapa de Rotas</button>
            <button class="aba-btn" id="btn-aba-historico" onclick="trocarAba('aba-historico')">🔎 Histórico Completo &
                Filtros</button>
            <button class="aba-btn" id="btn-aba-ficha" onclick="trocarAba('aba-ficha')">📝 Preencher Ficha
                Socioeconômica</button>
        </div>

        <!-- =================================================================
             ABA 1: DASHBOARD INTERATIVO + MAPA + 8 OPORTUNIDADES PROMISSORAS
             ================================================================= -->
        <section id="aba-dashboard" class="secao-aba ativa">

            <div class="caixa-dashboard">
                <div class="topo-boas-vindas">
                    <div>
                        <h2 id="nome-estudante">Olá, Estudante!</h2>
                        <p>Aqui está a triagem do seu perfil conforme as regras cadastradas e seus prazos.</p>
                    </div>
                    <span class="badge-localizacao" id="badge-origem">📍 Origem: Carregando...</span>
                </div>

                <!-- Indicadores Numéricos (KPIs) -->
                <div class="kpis-grid">
                    <div class="kpi-card">
                        <div class="kpi-titulo">Editais avaliados</div>
                        <div class="kpi-valor" id="kpi-universidades">0</div>
                    </div>
                    <div class="kpi-card verde">
                        <div class="kpi-titulo">Compatíveis</div>
                        <div class="kpi-valor" id="kpi-verdes">0</div>
                    </div>
                    <div class="kpi-card amarelo">
                        <div class="kpi-titulo">Dados pendentes</div>
                        <div class="kpi-valor" id="kpi-amarelas">0</div>
                    </div>
                    <div class="kpi-card vermelho">
                        <div class="kpi-titulo">Incompatíveis</div>
                        <div class="kpi-valor" id="kpi-vermelhas">0</div>
                    </div>
                </div>

                <!-- Gráficos Lado a Lado -->
                <div class="graficos-grid">
                    <div class="grafico-box">
                        <h3>Distribuição por Compatibilidade</h3>
                        <div class="canvas-container">
                            <canvas id="graficoPizza"></canvas>
                        </div>
                    </div>
                    <div class="grafico-box">
                        <h3>Modalidades de Benefício e Cotas</h3>
                        <div class="canvas-container">
                            <canvas id="graficoBarras"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Mapa Interativo -->
                <div class="mapa-section">
                    <div class="mapa-header">
                        <strong>🗺️ Radar Geográfico de Oportunidades (Distância Aproximada em Linha Reta)</strong>
                        <div class="legenda-mapa">
                            <span class="legenda-item"><i class="ponto-cor" style="background:#004488;"></i> Sua
                                Residência</span>
                            <span class="legenda-item"><i class="ponto-cor" style="background:#28a745;"></i> Alta
                                Compatibilidade</span>
                            <span class="legenda-item"><i class="ponto-cor" style="background:#ffc107;"></i> Dados pendentes</span>
                            <span class="legenda-item"><i class="ponto-cor" style="background:#dc3545;"></i>
                                Incompatível</span>
                        </div>
                    </div>
                    <div id="mapa-brasil"></div>
                </div>
            </div>

            <!-- SEGUNDO CORPO: Oportunidades compatíveis com sua ficha -->
            <div class="secao-cards-recentes">
                <div class="cabecalho-secao">
                    <div>
                        <h2 style="color: var(--azul-escuro);">Oportunidades compatíveis com sua ficha</h2>
                        <p style="color: var(--texto-mutado); font-size: 0.95rem;">Ordenadas pelos critérios cadastrados e pela vigência das regras. O resultado é uma triagem informativa, não uma garantia de aprovação.</p>
                    </div>
                    <button class="btn-primario" onclick="trocarAba('aba-historico')"
                        style="background: var(--azul-claro); color: var(--azul-primario);">Ver Todos os Editais
                        →</button>
                </div>

                <div id="grid-top-8" class="grid-cards">
                    <!-- Injetado dinamicamente via JS -->
                </div>
            </div>
        </section>

        <!-- =================================================================
             ABA 2: HISTÓRICO COMPLETO COM FILTROS AVANÇADOS
             ================================================================= -->
        <section id="aba-historico" class="secao-aba">
            <div class="secao-cards-recentes">
                <h2 style="color: var(--azul-escuro); margin-bottom: 15px;">Histórico Completo de Editais e Filtros</h2>

                <div id="mapa-container-historico" class="mapa-section" style="margin-bottom: 25px;">
                    <div class="mapa-header" style="background-color: var(--azul-primario);">
                        <strong>🗺️ Inspetor de Conexão Geográfica</strong>
                        <span style="font-size: 0.85rem;">Clique em "Ver no Mapa" em qualquer edital abaixo para focar no
                            trajeto.</span>
                    </div>
                    <div id="mapa-brasil-historico" style="width: 100%; height: 350px; background-color: #eef5fc;">
                    </div>
                </div>

                <div class="barra-filtros">
                    <div>
                        <label for="filtro-chance">Compatibilidade</label>
                        <select id="filtro-chance" onchange="aplicarFiltrosHistorico()">
                            <option value="TODAS">Todas as classificações</option>
                            <option value="COMPATIVEL">🟢 Compatível</option>
                            <option value="PENDENTE">🟡 Dados pendentes</option>
                            <option value="INCOMPATIVEL">🔴 Incompatível</option>
                            <option value="AGUARDANDO_ABERTURA">🔵 Aguardando abertura</option>
                            <option value="ENCERRADO">⚫ Encerrado</option>
                        </select>
                    </div>
                    <div>
                        <label for="filtro-escopo">Alcance / Região</label>
                        <select id="filtro-escopo" onchange="aplicarFiltrosHistorico()">
                            <option value="TODOS">Qualquer Distância</option>
                            <option value="LOCAL">Mesma Cidade (Local)</option>
                            <option value="ESTADUAL">Dentro do Estado (Estadual)</option>
                            <option value="INTERESTADUAL">Outro Estado (Interestadual)</option>
                            <option value="INTERNACIONAL">Internacional</option>
                        </select>
                    </div>
                    <div style="position: relative;">
                        <label for="filtro-prazo">Prazo Limite Até</label>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <input type="text" id="filtro-prazo" placeholder="Selecione no calendário..." style="background-color: white; cursor: pointer;">
                            <button id="btn-limpar-data" title="Remover filtro de data" style="background: #dc3545; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: none; font-weight: bold;">✕</button>
                        </div>
                        <div id="legenda-datas" style="font-size: 0.75rem; color: #64748b; margin-top: 4px; display: none;"></div>
                    </div>
                    <div>
                        <label for="filtro-orcamento">Custo Máx. República (R$)</label>
                        <input type="number" id="filtro-orcamento" placeholder="Ex: 900"
                            oninput="aplicarFiltrosHistorico()">
                    </div>
                </div>

                <div id="grid-historico-completo" class="grid-cards">
                    <!-- Injetado dinamicamente via JS -->
                </div>
            </div>
        </section>

        <!-- =================================================================
             ABA 3: FICHA SOCIOECONÔMICA E DE MOBILIDADE COMPLETA
             ================================================================= -->
        <section id="aba-ficha" class="secao-aba">
            <div class="caixa-dashboard" style="max-width: 850px; margin: 0 auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h2 style="color: var(--azul-escuro); margin: 0;">Ficha Socioeconômica, Acadêmica e de Mobilidade</h2>
                    <button type="button" class="btn-primario" onclick="trocarAba('aba-dashboard')" style="background: #64748b; color: white; padding: 8px 16px;">
                        ← Voltar ao Mapa
                    </button>
                </div>
                <p style="color: var(--texto-mutado); margin-bottom: 20px;">
                    Os campos marcados com <span style="color: #dc3545; font-weight: bold;">*</span> são obrigatórios. Digite o seu <strong>CEP</strong> para preencher Estado e Cidade automaticamente, ou selecione apenas <strong>Estado e Cidade</strong> (sem precisar de CEP).
                </p>

                <div id="alerta-ficha" style="display:none; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;"></div>

                <form id="form-ficha-completa">

                    <h3 class="form-secao-titulo">1. Perfil Socioeconômico e Escolar</h3>
                    <div class="form-linha">
                        <div class="form-group">
                            <label for="renda_per_capita">Renda Familiar Per Capita (R$) <span style="color:#dc3545">*</span></label>
                            <input type="number" id="renda_per_capita" name="renda_per_capita" step="0.01" min="0" required placeholder="Ex: 850.00">
                        </div>
                        <div class="form-group">
                            <label for="tipo_escola">Ensino Médio Cursado em: <span style="color:#dc3545">*</span></label>
                            <select id="tipo_escola" name="tipo_escola" required>
                                <option value="" disabled selected>Selecione uma opção...</option>
                                <option value="PUBLICA">Integralmente em Escola Pública</option>
                                <option value="PRIVADA_BOLSA">Escola Privada (Com Bolsa Integral)</option>
                                <option value="PRIVADA">Escola Privada (Sem Bolsa)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-linha">
                        <div class="form-group">
                            <label for="raca_cor">Autodeclaração de raça/cor <span style="color:#dc3545">*</span></label>
                            <select id="raca_cor" name="raca_cor" required>
                                <option value="" disabled selected>Selecione uma opção...</option>
                                <option value="BRANCA">Branca</option>
                                <option value="PRETA">Preta</option>
                                <option value="PARDA">Parda</option>
                                <option value="AMARELA">Amarela</option>
                                <option value="INDIGENA">Indígena</option>
                                <option value="NAO_INFORMAR">Prefiro não informar</option>
                            </select>
                            <small>Essa informação é armazenada criptografada e só é utilizada para verificar critérios pertinentes.</small>
                        </div>
                        <div class="form-group">
                            <label for="nota_media_enem">Média Estimada (ENEM, Provão Paulista ou Vestibular) <span style="color:#dc3545">*</span></label>
                            <input type="number" id="nota_media_enem" name="nota_media_enem" min="0" max="1000" step="0.1" required placeholder="Ex: 720">
                        </div>
                    </div>

                    <h3 class="form-secao-titulo">2. Geografia e Disposição de Mudança</h3>
                    <div class="form-linha">
                        <div class="form-group">
                            <label for="cep">CEP <span style="color: var(--texto-mutado); font-weight: normal; font-size: 0.8rem;">(Opcional — busca Estado/Cidade)</span></label>
                            <input type="text" id="cep" name="cep" maxlength="9" placeholder="Ex: 18013-004" onblur="buscarCep()">
                        </div>

                        <div class="form-group">
                            <label for="estado_origem">Estado (UF) <span style="color:#dc3545">*</span></label>
                            <select id="estado_origem" name="estado_origem" required onchange="aoMudarEstado(this.value)">
                                <option value="" disabled selected>UF...</option>
                                <option value="AC">Acre (AC)</option>
                                <option value="AL">Alagoas (AL)</option>
                                <option value="AP">Amapá (AP)</option>
                                <option value="AM">Amazonas (AM)</option>
                                <option value="BA">Bahia (BA)</option>
                                <option value="CE">Ceará (CE)</option>
                                <option value="DF">Distrito Federal (DF)</option>
                                <option value="ES">Espírito Santo (ES)</option>
                                <option value="GO">Goiás (GO)</option>
                                <option value="MA">Maranhão (MA)</option>
                                <option value="MT">Mato Grosso (MT)</option>
                                <option value="MS">Mato Grosso do Sul (MS)</option>
                                <option value="MG">Minas Gerais (MG)</option>
                                <option value="PA">Pará (PA)</option>
                                <option value="PB">Paraíba (PB)</option>
                                <option value="PR">Paraná (PR)</option>
                                <option value="PE">Pernambuco (PE)</option>
                                <option value="PI">Piauí (PI)</option>
                                <option value="RJ">Rio de Janeiro (RJ)</option>
                                <option value="RN">Rio Grande do Norte (RN)</option>
                                <option value="RS">Rio Grande do Sul (RS)</option>
                                <option value="RO">Rondônia (RO)</option>
                                <option value="RR">Roraima (RR)</option>
                                <option value="SC">Santa Catarina (SC)</option>
                                <option value="SP">São Paulo (SP)</option>
                                <option value="SE">Sergipe (SE)</option>
                                <option value="TO">Tocantins (TO)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="cidade_origem">Cidade Atual (Ponto de Partida) <span style="color:#dc3545">*</span></label>
                            <select id="cidade_origem" name="cidade_origem" required onchange="aoSelecionarCidade()">
                                <option value="" disabled selected>Selecione o Estado primeiro...</option>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" id="lat_origem" name="lat_origem">
                    <input type="hidden" id="lng_origem" name="lng_origem">

                    <div class="form-linha">
                        <div class="form-group">
                            <label for="nivel_mobilidade">Até que nível você estaria disposto a se mudar? <span style="color:#dc3545">*</span></label>
                            <select id="nivel_mobilidade" name="nivel_mobilidade" required>
                                <option value="" disabled selected>Selecione sua preferência...</option>
                                <option value="LOCAL">Não posso me mudar (Apenas minha cidade)</option>
                                <option value="ESTADUAL">Dentro do meu Estado (Estadual)</option>
                                <option value="INTERESTADUAL">Qualquer Estado do Brasil (Interestadual)</option>
                                <option value="INTERNACIONAL">Aceito oportunidades no Exterior (Internacional)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="orcamento_moradia">Orçamento Mensal para Moradia/República (R$) <span style="color:#dc3545">*</span></label>
                            <input type="number" id="orcamento_moradia" name="orcamento_moradia" step="10" min="0" required placeholder="Ex: 800.00">
                        </div>
                    </div>

                    <h3 class="form-secao-titulo">3. Condições Específicas e Permanência Estudantil</h3>
                    <div class="checkbox-grupo">
                        <label class="checkbox-label">
                            <input type="checkbox" id="interesse_republica" name="interesse_republica">
                            Tenho interesse em morar em Repúblicas Estudantis
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="precisa_auxilio_moradia" name="precisa_auxilio_moradia">
                            Dependo obrigatoriamente de Auxílio Moradia / Alojamento Gratuito
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="pcd" name="pcd">
                            Sou Pessoa com Deficiência (PcD)
                        </label>
                    </div>

                    <button type="submit" id="btn-salvar-ficha" class="btn-primario btn-full" style="margin-top: 25px;">
                        💾 Salvar Dados e Rever Oportunidades
                    </button>
                </form>
            </div>
        </section>

    </main>

</body>

</html>