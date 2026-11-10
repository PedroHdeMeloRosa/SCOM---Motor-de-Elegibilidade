// assets/js/painel_estudante.js

let todasOportunidades = [];
let dadosPerfil = {};
let chartPizza = null;
let chartBarras = null;

// Variáveis do Mapa Principal (Dashboard)
let mapaLeaflet = null;
let camadaElementosMapa = null;
window.rotasDashboard = {}; // Guarda as rotas para o recurso de ligar/desligar

// Variáveis do Mapa do Histórico
let mapaHistorico = null;
let camadaHistorico = null;

document.addEventListener('DOMContentLoaded', () => {
    carregarDadosPainel();

    const formFicha = document.getElementById('form-ficha-completa');
    if (formFicha) {
        formFicha.addEventListener('submit', salvarFichaSocioeconomica);
    }
});

// Alternância entre as Abas do Painel
function trocarAba(idAba) {
    document.querySelectorAll('.secao-aba').forEach(sec => sec.classList.remove('ativa'));
    document.querySelectorAll('.aba-btn').forEach(btn => btn.classList.remove('ativa'));

    document.getElementById(idAba).classList.add('ativa');
    const btnCorrespondente = document.getElementById('btn-' + idAba);
    if (btnCorrespondente) btnCorrespondente.classList.add('ativa');

    // Recalcula o tamanho dos mapas quando as abas mudam (Correção de glitch visual do Leaflet)
    if (idAba === 'aba-dashboard' && mapaLeaflet) {
        setTimeout(() => mapaLeaflet.invalidateSize(), 200);
    }
    if (idAba === 'aba-historico') {
        if (!mapaHistorico) inicializarMapaHistorico();
        setTimeout(() => mapaHistorico.invalidateSize(), 200);
    }
}

async function carregarDadosPainel() {
    try {
        const response = await fetch('../api/controllers/painel_aluno.php');
        const dados = await response.json();

        if (!dados.sucesso) throw new Error(dados.mensagem);

        todasOportunidades = dados.oportunidades;
        dadosPerfil = dados.perfil;

        document.getElementById('nome-estudante').textContent = `Olá, ${dados.usuario}!`;
        document.getElementById('badge-origem').textContent = `📍 Origem: ${dadosPerfil.cidade_origem} - ${dadosPerfil.estado_origem} (Raio 2km)`;
        document.getElementById('kpi-universidades').textContent = dados.kpis.total_universidades;
        document.getElementById('kpi-verdes').textContent = dados.kpis.verdes;
        document.getElementById('kpi-amarelas').textContent = dados.kpis.amarelas;
        document.getElementById('kpi-vermelhas').textContent = dados.kpis.vermelhas;

        preencherFormularioPerfil(dadosPerfil);
        renderizarGraficos(dados.kpis, todasOportunidades);

        // Processa as 8 melhores oportunidades
        const pesoOrdem = { 'ALTA': 1, 'MEDIA': 2, 'BAIXA': 3 };
        const top8 = [...todasOportunidades].sort((a, b) => pesoOrdem[a.probabilidade] - pesoOrdem[b.probabilidade]).slice(0, 8);
        
        // Renderiza as Top 8 no mapa e nos cards
        renderizarMapaBrasil(dadosPerfil, top8);
        renderizarTop8Cards(top8);
        
        aplicarFiltrosHistorico();

    } catch (erro) {
        console.error("Erro ao carregar painel:", erro);
    }
}

/* =======================================================================
   MAPA PRINCIPAL (DASHBOARD) - COM TOGGLE DE ROTAS
   ======================================================================= */
function renderizarMapaBrasil(perfil, top8Oportunidades) {
    const latOrigem = parseFloat(perfil.lat_origem);
    const lngOrigem = parseFloat(perfil.lng_origem);

    if (!mapaLeaflet) {
        mapaLeaflet = L.map('mapa-brasil').setView([latOrigem, lngOrigem], 6);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap | UNILEGI GeoEngine',
            maxZoom: 18
        }).addTo(mapaLeaflet);
        camadaElementosMapa = L.layerGroup().addTo(mapaLeaflet);
    } else {
        camadaElementosMapa.clearLayers();
        mapaLeaflet.setView([latOrigem, lngOrigem], 6);
    }

    // Ponto Central (Residência) - Fica solto na camada principal (não ocultável)
    L.circle([latOrigem, lngOrigem], {
        color: '#004488', fillColor: '#004488', fillOpacity: 0.35, radius: 2000, weight: 3
    }).bindPopup(`<b>🏠 ${perfil.cidade_origem}</b>`).addTo(camadaElementosMapa);

    const coresProb = { 'ALTA': '#28a745', 'MEDIA': '#ffc107', 'BAIXA': '#dc3545' };
    window.rotasDashboard = {}; // Limpa memória

    top8Oportunidades.forEach(op => {
        const cor = coresProb[op.probabilidade] || '#004488';

        const circuloDest = L.circle([op.latitude, op.longitude], {
            color: cor, fillColor: cor, fillOpacity: 0.45, radius: 2000, weight: 3
        }).bindPopup(`<b>🎓 ${op.instituicao} (${op.cidade})</b><br>Chance: ${op.probabilidade}`);

        const pontosArco = gerarPontosArco([latOrigem, lngOrigem], [op.latitude, op.longitude]);
        const arcoLinha = L.polyline(pontosArco, { color: cor, weight: 2.5, className: 'arco-voo' });

        // Agrupa Destino + Arco e salva na memória referenciado pelo ID do edital
        const grupoRota = L.layerGroup([circuloDest, arcoLinha]);
        grupoRota.addTo(camadaElementosMapa); // Adiciona ao mapa por padrão
        
        window.rotasDashboard[op.id_edital] = grupoRota;
    });
}

// Ligar/Desligar Rota no Dashboard
function alternarRotaDashboard(id_edital, btnElement) {
    const grupo = window.rotasDashboard[id_edital];
    if (!grupo) return;

    if (mapaLeaflet.hasLayer(grupo)) {
        mapaLeaflet.removeLayer(grupo);
        btnElement.classList.add('rota-oculta');
        btnElement.innerHTML = '👁️‍🗨️ Rota Oculta';
    } else {
        grupo.addTo(mapaLeaflet);
        btnElement.classList.remove('rota-oculta');
        btnElement.innerHTML = '👁️ Ocultar Rota';
    }
}

/* =======================================================================
   MAPA SECUNDÁRIO (HISTÓRICO) - AUTO-FIT
   ======================================================================= */
function inicializarMapaHistorico() {
    mapaHistorico = L.map('mapa-brasil-historico').setView([-15.79, -47.88], 4); // Centro do Brasil por padrão
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap | UNILEGI GeoEngine',
        maxZoom: 18
    }).addTo(mapaHistorico);
    camadaHistorico = L.layerGroup().addTo(mapaHistorico);
}

// Focar rota exclusiva
function focarRotaHistorico(id_edital) {
    const op = todasOportunidades.find(o => o.id_edital === id_edital);
    if (!op) return;

    camadaHistorico.clearLayers();

    const latOrigem = parseFloat(dadosPerfil.lat_origem);
    const lngOrigem = parseFloat(dadosPerfil.lng_origem);
    const latDest = parseFloat(op.latitude);
    const lngDest = parseFloat(op.longitude);

    // Origem
    L.circle([latOrigem, lngOrigem], { color: '#004488', fillColor: '#004488', fillOpacity: 0.5, radius: 2000 }).addTo(camadaHistorico);
    
    // Destino
    const coresProb = { 'ALTA': '#28a745', 'MEDIA': '#ffc107', 'BAIXA': '#dc3545' };
    const cor = coresProb[op.probabilidade];
    L.circle([latDest, lngDest], { color: cor, fillColor: cor, fillOpacity: 0.5, radius: 2000 }).addTo(camadaHistorico);

    // Arco
    const pontosArco = gerarPontosArco([latOrigem, lngOrigem], [latDest, lngDest]);
    L.polyline(pontosArco, { color: cor, weight: 3, className: 'arco-voo' }).addTo(camadaHistorico);

    // Mágica do Auto-Fit (Calcula o retângulo perfeito englobando os 2 pontos)
    const limites = L.latLngBounds([latOrigem, lngOrigem], [latDest, lngDest]);
    mapaHistorico.fitBounds(limites, { padding: [50, 50], maxZoom: 10 });

    // Rola a tela suavemente para o mapa
    document.getElementById('mapa-container-historico').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/* =======================================================================
   GERAÇÃO DE CARDS E AUXILIARES
   ======================================================================= */
function renderizarTop8Cards(top8) {
    const container = document.getElementById('grid-top-8');
    container.innerHTML = top8.map(op => montarCardHTML(op, 'dashboard')).join('');
}

function aplicarFiltrosHistorico() {
    const chance = document.getElementById('filtro-chance').value;
    const escopo = document.getElementById('filtro-escopo').value;
    const prazo = document.getElementById('filtro-prazo').value;
    const orcamentoMax = parseFloat(document.getElementById('filtro-orcamento').value);

    const filtradas = todasOportunidades.filter(op => {
        if (chance !== 'TODAS' && op.probabilidade !== chance) return false;
        if (escopo !== 'TODOS' && op.escopo_mobilidade !== escopo) return false;
        if (prazo && op.prazo_fim > prazo) return false;
        if (!isNaN(orcamentoMax) && op.custo_medio_republica > orcamentoMax) return false;
        return true;
    });

    const container = document.getElementById('grid-historico-completo');
    if (filtradas.length === 0) {
        container.innerHTML = `<div class="estado-vazio"><p>Nenhum edital encontrado para esta combinação de filtros.</p></div>`;
    } else {
        container.innerHTML = filtradas.map(op => montarCardHTML(op, 'historico')).join('');
    }
}

// O mesmo Card reage diferente dependendo de onde foi injetado (Contexto)
function montarCardHTML(op, contexto) {
    const classeTag = op.probabilidade === 'ALTA' ? 'tag-alta' : (op.probabilidade === 'MEDIA' ? 'tag-media' : 'tag-baixa');
    const textoTag = op.probabilidade === 'ALTA' ? 'Alta Compatibilidade' : (op.probabilidade === 'MEDIA' ? 'Atenção aos Critérios' : 'Baixa Compatibilidade');

    let botaoMapa = '';
    if (contexto === 'dashboard') {
        botaoMapa = `<button onclick="alternarRotaDashboard(${op.id_edital}, this)" class="btn-toggle-rota">👁️ Ocultar Rota</button>`;
    } else {
        botaoMapa = `<button onclick="focarRotaHistorico(${op.id_edital})" class="btn-foco-rota">🗺️ Ver Rota no Mapa</button>`;
    }

    return `
        <div class="card-oportunidade-rico borda-${op.probabilidade}">
            <div>
                <div class="card-topo">
                    <span class="instituicao-badge">${op.instituicao} • ${op.cidade}/${op.estado}</span>
                    <span class="tag-probabilidade ${classeTag}">${textoTag}</span>
                </div>
                <h3>${op.nome_processo}</h3>
                <p class="meta-info">🎯 <strong>Modalidade:</strong> ${formatarBeneficio(op.tipo_beneficio)}</p>
                <p class="meta-info">📅 <strong>Prazo Final:</strong> ${op.prazo_formatado}</p>
                <p class="meta-info">🏠 <strong>República Média:</strong> R$ ${op.custo_medio_republica.toFixed(2)} ${op.tem_moradia ? '(Possui Moradia Gratuita)' : ''}</p>
                <div class="diagnostico-box">
                    <strong>Análise do Motor:</strong> ${op.diagnostico}
                </div>
            </div>
            <div class="card-rodape">
                ${botaoMapa}
                <a href="${op.link_documento}" class="link-edital">Resumo e Edital →</a>
            </div>
        </div>
    `;
}

function gerarPontosArco(origem, destino) {
    const lat1 = origem[0], lng1 = origem[1];
    const lat2 = destino[0], lng2 = destino[1];
    const latMeio = (lat1 + lat2) / 2;
    const lngMeio = (lng1 + lng2) / 2;
    const deslocamentoCurva = Math.abs(lng2 - lng1) * 0.25 + 0.6;
    
    const pontos = [];
    for (let i = 0; i <= 35; i++) {
        const t = i / 35;
        const lat = (1 - t) * (1 - t) * lat1 + 2 * (1 - t) * t * (latMeio + deslocamentoCurva) + t * t * lat2;
        const lng = (1 - t) * (1 - t) * lng1 + 2 * (1 - t) * t * lngMeio + t * t * lng2;
        pontos.push([lat, lng]);
    }
    return pontos;
}

function formatarBeneficio(codigo) {
    const mapa = { 'ISENCAO_TAXA': 'Isenção de Taxa', 'COTA_RENDA': 'Cota Renda', 'COTA_ESCOLA_PUBLICA': 'Cota Escola Pública', 'COTA_RACIAL': 'Cota Étnico-Racial (PPI)', 'AMPLA_CONCORRENCIA': 'Ampla Concorrência' };
    return mapa[codigo] || codigo;
}

function renderizarGraficos(kpis, oportunidades) {
    const ctxPizza = document.getElementById('graficoPizza').getContext('2d');
    const ctxBarras = document.getElementById('graficoBarras').getContext('2d');

    if (chartPizza) chartPizza.destroy();
    if (chartBarras) chartBarras.destroy();

    chartPizza = new Chart(ctxPizza, {
        type: 'doughnut',
        data: {
            labels: ['Alta Chance', 'Atenção', 'Improvável'],
            datasets: [{ data: [kpis.verdes, kpis.amarelas, kpis.vermelhas], backgroundColor: ['#28a745', '#ffc107', '#dc3545'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    const contagemBeneficios = {};
    oportunidades.forEach(op => {
        const nomeFormatado = formatarBeneficio(op.tipo_beneficio);
        contagemBeneficios[nomeFormatado] = (contagemBeneficios[nomeFormatado] || 0) + 1;
    });

    chartBarras = new Chart(ctxBarras, {
        type: 'bar',
        data: {
            labels: Object.keys(contagemBeneficios),
            datasets: [{ label: 'Qtd. Editais', data: Object.values(contagemBeneficios), backgroundColor: '#004488', borderRadius: 6 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
}

function preencherFormularioPerfil(p) {
    document.getElementById('renda_per_capita').value = p.renda_per_capita;
    document.getElementById('tipo_escola').value = p.tipo_escola;
    document.getElementById('etnia').value = p.etnia;
    document.getElementById('nota_media_enem').value = p.nota_media_enem;
    document.getElementById('cep').value = p.cep;
    document.getElementById('cidade_origem').value = p.cidade_origem;
    document.getElementById('nivel_mobilidade').value = p.nivel_mobilidade;
    document.getElementById('orcamento_moradia').value = p.orcamento_moradia;
    document.getElementById('interesse_republica').checked = Boolean(Number(p.interesse_republica));
    document.getElementById('precisa_auxilio_moradia').checked = Boolean(Number(p.precisa_auxilio_moradia));
    document.getElementById('pcd').checked = Boolean(Number(p.pcd));
}

async function salvarFichaSocioeconomica(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-salvar-ficha');
    const alerta = document.getElementById('alerta-ficha');
    btn.textContent = "Recalculando Motor...";
    btn.disabled = true;

    const payload = {
        renda_per_capita: document.getElementById('renda_per_capita').value,
        tipo_escola: document.getElementById('tipo_escola').value,
        etnia: document.getElementById('etnia').value,
        nota_media_enem: document.getElementById('nota_media_enem').value,
        cep: document.getElementById('cep').value,
        cidade_origem: document.getElementById('cidade_origem').value,
        nivel_mobilidade: document.getElementById('nivel_mobilidade').value,
        orcamento_moradia: document.getElementById('orcamento_moradia').value,
        interesse_republica: document.getElementById('interesse_republica').checked,
        precisa_auxilio_moradia: document.getElementById('precisa_auxilio_moradia').checked,
        pcd: document.getElementById('pcd').checked
    };

    try {
        const res = await fetch('../api/controllers/painel_aluno.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        if ((await res.json()).sucesso) {
            alerta.style.display = 'block';
            alerta.style.backgroundColor = '#d4edda';
            alerta.style.color = '#155724';
            alerta.textContent = "✔ Ficha salva! Rotas e probabilidades foram recalculadas.";
            await carregarDadosPainel();
            setTimeout(() => { alerta.style.display = 'none'; trocarAba('aba-dashboard'); }, 1400);
        }
    } finally {
        btn.textContent = "Salvar Ficha e Recalcular Rotas";
        btn.disabled = false;
    }
}

async function fazerLogout() {
    try { await fetch('../api/controllers/logout.php', { method: 'POST' }); } finally { window.location.href = '../index.html'; }
}