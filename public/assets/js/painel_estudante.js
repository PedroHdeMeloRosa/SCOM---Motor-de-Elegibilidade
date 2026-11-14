// ==============================================================================
// VARIÁVEIS GLOBAIS E ESTADOS
// ==============================================================================
let todasOportunidades = [];
let dadosPerfil = {};
let chartPizza = null;
let chartBarras = null;
let mapaLeaflet = null;
let camadaElementosMapa = null;
window.rotasDashboard = {};
let mapaHistorico = null;
let camadaHistorico = null;

let fichaModificada = false;
let buscandoLocalizacao = false;
let superCalendario = null;
let limitesGeraisMapa = null;
let circuloRadarOrigem = null;
let intervaloRadar = null;
let rotaEmDestaqueAtual = null;

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : "";
}

// ==============================================================================
// INICIALIZAÇÃO
// ==============================================================================
document.addEventListener("DOMContentLoaded", () => {
    carregarDadosPainel();

    const formFicha = document.getElementById("form-ficha-completa");
    if (formFicha) {
        formFicha.addEventListener("submit", salvarFichaSocioeconomica);
        formFicha.addEventListener("input", () => { fichaModificada = true; });
        formFicha.addEventListener("change", () => { fichaModificada = true; });

        const cepInput = document.getElementById("cep");
        if (cepInput) {
            cepInput.addEventListener("keydown", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    buscarCep();
                }
            });
        }
    }
});

function trocarAba(idAba) {
    const abaFichaAtiva = document.getElementById("aba-ficha").classList.contains("ativa");

    if (abaFichaAtiva && idAba !== "aba-ficha" && fichaModificada) {
        if (!confirm("⚠️ Você alterou dados na Ficha Socioeconômica e ainda não salvou.\n\nDeseja realmente sair desta página sem salvar as alterações?")) return;
        fichaModificada = false;
        preencherFormularioPerfil(dadosPerfil);
    }

    document.querySelectorAll(".secao-aba").forEach(aba => aba.classList.remove("ativa"));
    document.querySelectorAll(".aba-btn").forEach(btn => btn.classList.remove("ativa"));

    const secaoAlvo = document.getElementById(idAba);
    if (secaoAlvo) secaoAlvo.classList.add("ativa");

    const btnAtivo = document.getElementById("btn-" + idAba);
    if (btnAtivo) btnAtivo.classList.add("ativa");

    if (idAba === "aba-dashboard" && mapaLeaflet) setTimeout(() => mapaLeaflet.invalidateSize(), 200);
    if (idAba === "aba-historico") {
        if (!mapaHistorico) inicializarMapaHistorico();
        setTimeout(() => mapaHistorico.invalidateSize(), 200);
    }
}

// ==============================================================================
// COMUNICAÇÃO COM A API E RENDERIZAÇÃO INICIAL
// ==============================================================================
async function carregarDadosPainel() {
    try {
        const resposta = await fetch("/api/estudante/painel");
        const json = await resposta.json();

        if (!json.sucesso) throw new Error(json.mensagem);

        todasOportunidades = json.oportunidades;
        dadosPerfil = json.perfil;

        document.getElementById("nome-estudante").textContent = `Olá, ${json.usuario}!`;
        document.getElementById("badge-origem").textContent = `📍 Origem: ${dadosPerfil.cidade_origem} - ${dadosPerfil.estado_origem} (Raio 2km)`;
        document.getElementById("kpi-universidades").textContent = json.kpis.total_universidades;
        document.getElementById("kpi-verdes").textContent = json.kpis.verdes;
        document.getElementById("kpi-amarelas").textContent = json.kpis.amarelas;
        document.getElementById("kpi-vermelhas").textContent = json.kpis.vermelhas;

        preencherFormularioPerfil(dadosPerfil);
        renderizarGraficos(json.kpis, todasOportunidades);

        // TOP 8 DO DASHBOARD: Filtra expirados, ordena por prioridade e depois por data
        const pesosProb = { ALTA: 1, MEDIA: 2, BAIXA: 3 };
        const vagasAtivas = todasOportunidades.filter(v => v.probabilidade !== 'EXPIRADO');
        
        const top8 = [...vagasAtivas].sort((a, b) => {
            if (pesosProb[a.probabilidade] !== pesosProb[b.probabilidade]) {
                return pesosProb[a.probabilidade] - pesosProb[b.probabilidade];
            }
            return new Date(a.prazo_limite || a.prazo_fim) - new Date(b.prazo_limite || b.prazo_fim);
        }).slice(0, 8);

        renderizarMapaBrasil(dadosPerfil, top8); // Mostra apenas as top 8 no mapa
        renderizarTop8Cards(top8);
        aplicarFiltrosHistorico(); // Renderiza o histórico com os grupos
        inicializarSuperCalendario();

    } catch (erro) {
        console.error("Erro ao carregar painel:", erro);
    }
}

async function salvarFichaSocioeconomica(evento) {
    evento.preventDefault();
    if (buscandoLocalizacao) return;

    const form = document.getElementById("form-ficha-completa");
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const btnSalvar = document.getElementById("btn-salvar-ficha");
    const alerta = document.getElementById("alerta-ficha");
    btnSalvar.textContent = "⏳ Recalculando Motor...";
    btnSalvar.disabled = true;

    const dadosFicha = {
        renda_per_capita: document.getElementById("renda_per_capita").value,
        tipo_escola: document.getElementById("tipo_escola").value,
        etnia: document.getElementById("etnia").value,
        nota_media_enem: document.getElementById("nota_media_enem").value,
        cep: document.getElementById("cep").value,
        cidade_origem: document.getElementById("cidade_origem").value,
        estado_origem: document.getElementById("estado_origem").value,
        lat_origem: document.getElementById("lat_origem").value,
        lng_origem: document.getElementById("lng_origem").value,
        nivel_mobilidade: document.getElementById("nivel_mobilidade").value,
        orcamento_moradia: document.getElementById("orcamento_moradia").value,
        interesse_republica: document.getElementById("interesse_republica").checked,
        precisa_auxilio_moradia: document.getElementById("precisa_auxilio_moradia").checked,
        pcd: document.getElementById("pcd").checked
    };

    try {
        const resposta = await fetch("/api/estudante/ficha", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": getCsrfToken() },
            body: JSON.stringify(dadosFicha)
        });

        const json = await resposta.json();

        if (json.sucesso) {
            fichaModificada = false;
            alerta.style.display = "block";
            alerta.style.backgroundColor = "#dcfce7";
            alerta.style.color = "#166534";
            alerta.textContent = "✅ " + json.mensagem + " Clique em 'Voltar ao Mapa' para ver os resultados.";
            await carregarDadosPainel();
        }
    } catch (erro) {
        alerta.style.display = "block";
        alerta.style.backgroundColor = "#fee2e2";
        alerta.style.color = "#991b1b";
        alerta.textContent = "❌ Ocorreu um erro ao salvar a ficha. Tente novamente.";
    } finally {
        btnSalvar.textContent = "💾 Salvar Ficha e Recalcular Rotas (Permanece nesta tela)";
        btnSalvar.disabled = false;
    }
}

async function fazerLogout() {
    try { await fetch("/logout", { method: "POST", headers: { "X-CSRF-TOKEN": getCsrfToken() } }); }
    finally { window.location.href = "/login"; }
}

// ==============================================================================
// BUSCA DE ENDEREÇO (IBGE E VIA CEP)
// ==============================================================================
async function preencherFormularioPerfil(perfil) {
    if (!perfil) return;
    document.getElementById("renda_per_capita").value = perfil.renda_per_capita || "";
    document.getElementById("tipo_escola").value = perfil.tipo_escola || "";
    document.getElementById("etnia").value = perfil.etnia || "";
    document.getElementById("nota_media_enem").value = perfil.nota_media_enem || "";
    document.getElementById("cep").value = perfil.cep || "";

    if (perfil.estado_origem) {
        document.getElementById("estado_origem").value = perfil.estado_origem;
        await carregarCidadesPorUF(perfil.estado_origem, perfil.cidade_origem);
    }

    document.getElementById("lat_origem").value = perfil.lat_origem || "";
    document.getElementById("lng_origem").value = perfil.lng_origem || "";
    document.getElementById("nivel_mobilidade").value = perfil.nivel_mobilidade || "";
    document.getElementById("orcamento_moradia").value = perfil.orcamento_moradia || "";
    document.getElementById("interesse_republica").checked = Boolean(Number(perfil.interesse_republica));
    document.getElementById("precisa_auxilio_moradia").checked = Boolean(Number(perfil.precisa_auxilio_moradia));
    document.getElementById("pcd").checked = Boolean(Number(perfil.pcd));
    fichaModificada = false;
}

async function carregarCidadesPorUF(uf, cidadePreSelecionada = null) {
    const selectCidade = document.getElementById("cidade_origem");
    if (!uf) return;
    selectCidade.innerHTML = `<option value="" disabled selected>Carregando cidades...</option>`;
    try {
        const res = await fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${uf}/municipios?orderBy=nome`);
        const municipios = await res.json();
        selectCidade.innerHTML = `<option value="" disabled selected>Selecione a cidade...</option>`;
        municipios.forEach(m => {
            const option = document.createElement("option");
            option.value = m.nome;
            option.textContent = m.nome;
            if (cidadePreSelecionada && m.nome.toLowerCase() === cidadePreSelecionada.toLowerCase()) option.selected = true;
            selectCidade.appendChild(option);
        });
    } catch (e) {
        console.error("Erro IBGE:", e);
    }
}

async function aoMudarEstado(uf) {
    await carregarCidadesPorUF(uf);
    document.getElementById("cep").value = "";
    fichaModificada = true;
}

async function aoSelecionarCidade() {
    const uf = document.getElementById("estado_origem").value;
    const cidade = document.getElementById("cidade_origem").value;
    const cepInput = document.getElementById("cep");
    const btnSalvar = document.getElementById("btn-salvar-ficha");

    if (!uf || !cidade) return;
    buscandoLocalizacao = true;
    btnSalvar.disabled = true;
    btnSalvar.textContent = "⏳ Sincronizando GPS...";
    cepInput.value = "Buscando CEP...";

    try {
        const query = `${cidade}, ${uf}, Brazil`;
        const resGeo = await fetch(`https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=1&q=${encodeURIComponent(query)}`);
        const dadosGeo = await resGeo.json();
        let cepEncontrado = "";

        if (dadosGeo.length > 0) {
            document.getElementById("lat_origem").value = dadosGeo[0].lat;
            document.getElementById("lng_origem").value = dadosGeo[0].lon;
            if (dadosGeo[0].address && dadosGeo[0].address.postcode) cepEncontrado = dadosGeo[0].address.postcode;
        }

        if (!cepEncontrado) {
            try {
                const resVia = await fetch(`https://viacep.com.br/ws/${uf}/${encodeURIComponent(cidade)}/Centro/json/`);
                const listaCeps = await resVia.json();
                if (Array.isArray(listaCeps) && listaCeps.length > 0 && listaCeps[0].cep) cepEncontrado = listaCeps[0].cep;
            } catch (_) { }
        }
        cepInput.value = cepEncontrado || "00000-000";
        fichaModificada = true;
    } catch (erro) {
        cepInput.value = "00000-000";
    } finally {
        buscandoLocalizacao = false;
        btnSalvar.disabled = false;
        btnSalvar.textContent = "💾 Salvar Ficha e Recalcular Rotas (Permanece nesta tela)";
    }
}

async function buscarCep() {
    const cepInput = document.getElementById("cep");
    const cepLimpo = cepInput.value.replace(/\D/g, "");
    if (cepLimpo.length !== 8 || cepLimpo === "00000000") return;

    const btnSalvar = document.getElementById("btn-salvar-ficha");
    buscandoLocalizacao = true;
    btnSalvar.disabled = true;
    btnSalvar.textContent = "⏳ Buscando endereço...";

    try {
        const resViaCep = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
        const dadosCep = await resViaCep.json();
        if (dadosCep.erro) throw new Error("CEP inválido");

        document.getElementById("estado_origem").value = dadosCep.uf;
        await carregarCidadesPorUF(dadosCep.uf, dadosCep.localidade);
        cepInput.value = dadosCep.cep || cepLimpo;

        const query = `${dadosCep.logradouro}, ${dadosCep.localidade}, ${dadosCep.uf}, Brazil`;
        const n = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
        const r = await n.json();

        if (r.length > 0) {
            document.getElementById("lat_origem").value = r[0].lat;
            document.getElementById("lng_origem").value = r[0].lon;
        } else {
            const e = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(dadosCep.localidade + ", " + dadosCep.uf + ", Brazil")}`);
            const a = await e.json();
            if (a.length > 0) {
                document.getElementById("lat_origem").value = a[0].lat;
                document.getElementById("lng_origem").value = a[0].lon;
            }
        }
        fichaModificada = true;
    } catch (a) {
        cepInput.value = "";
        alert("CEP não encontrado. Selecione diretamente na lista de Estados e Cidades ao lado!");
    } finally {
        buscandoLocalizacao = false;
        btnSalvar.disabled = false;
        btnSalvar.textContent = "💾 Salvar Ficha e Recalcular Rotas (Permanece nesta tela)";
    }
}

// ==============================================================================
// MAPA PRINCIPAL (DASHBOARD)
// ==============================================================================
function renderizarMapaBrasil(perfil, oportunidades) {
    const lat = parseFloat(perfil.lat_origem) || -23.5015;
    const lng = parseFloat(perfil.lng_origem) || -47.4581;

    if (mapaLeaflet) {
        camadaElementosMapa.clearLayers();
    } else {
        mapaLeaflet = L.map("mapa-brasil");
        L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 18 }).addTo(mapaLeaflet);
        camadaElementosMapa = L.layerGroup().addTo(mapaLeaflet);

        const ExpandirControl = L.Control.extend({
            options: { position: 'topright' },
            onAdd: function () {
                const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                container.innerHTML = '<button class="btn-expandir-mapa" title="Alternar Tela Cheia">⛶</button>';
                container.onclick = function (e) {
                    e.stopPropagation();
                    document.getElementById('mapa-brasil').classList.toggle('mapa-fullscreen');
                    setTimeout(() => mapaLeaflet.invalidateSize(), 300);
                }
                return container;
            }
        });
        mapaLeaflet.addControl(new ExpandirControl());
        mapaLeaflet.on('zoomend', atualizarRadarOrigem);
    }

    limitesGeraisMapa = L.latLngBounds([[lat, lng]]);

    const iconePulso = L.divIcon({ className: 'icone-origem-pulso', iconSize: [16, 16] });
    L.marker([lat, lng], { icon: iconePulso, zIndexOffset: 1000 })
        .bindPopup(`<b>🏠 Ponto de Partida: ${perfil.cidade_origem}</b>`).addTo(camadaElementosMapa);

    circuloRadarOrigem = L.circle([lat, lng], {
        color: "#004488", fillColor: "#004488", fillOpacity: 0.2, radius: 25000, weight: 1, interactive: false
    }).addTo(camadaElementosMapa);

    const cores = { ALTA: "#28a745", MEDIA: "#ffc107", BAIXA: "#dc3545", EXPIRADO: "#6c757d" };
    window.rotasDashboard = {};

    oportunidades.forEach(vaga => {
        const cor = cores[vaga.probabilidade] || "#004488";
        const latVaga = parseFloat(vaga.latitude) || 0;
        const lngVaga = parseFloat(vaga.longitude) || 0;

        const pino = L.circleMarker([latVaga, lngVaga], { color: cor, fillColor: cor, fillOpacity: 0.8, radius: 6, weight: 2 })
            .bindPopup(`<b>🎓 ${vaga.instituicao} (${vaga.cidade})</b><br>Chance: ${vaga.probabilidade}`);

        const arco = L.polyline(gerarPontosArco([lat, lng], [latVaga, lngVaga]), { color: cor, weight: 2.5, opacity: 0.8, className: "arco-voo" });

        const grupo = L.layerGroup([pino, arco]);
        grupo.addTo(camadaElementosMapa);

        limitesGeraisMapa.extend([latVaga, lngVaga]);

        window.rotasDashboard[vaga.id || vaga.id_edital] = {
            camada: grupo, latDestino: latVaga, lngDestino: lngVaga, latOrigem: lat, lngOrigem: lng
        };
    });

    if (oportunidades.length > 0) {
        mapaLeaflet.fitBounds(limitesGeraisMapa, { padding: [40, 40], maxZoom: 8 });
    } else {
        mapaLeaflet.setView([lat, lng], 6);
    }
    atualizarRadarOrigem();
}

function atualizarRadarOrigem() {
    if (!circuloRadarOrigem) return;
    const zoomAtual = mapaLeaflet.getZoom();
    clearInterval(intervaloRadar);
    
    if (zoomAtual <= 7) {
        let direcao = 1;
        circuloRadarOrigem.setStyle({ opacity: 0.6, fillOpacity: 0.2 });
        intervaloRadar = setInterval(() => {
            let rAtual = circuloRadarOrigem.getRadius();
            if (rAtual > 150000) direcao = -1;
            if (rAtual < 40000) direcao = 1;
            circuloRadarOrigem.setRadius(rAtual + (3000 * direcao));
        }, 50);
    } else {
        circuloRadarOrigem.setStyle({ opacity: 0, fillOpacity: 0 });
    }
}

function destacarRotaDashboard(idEdital, botaoCard) {
    const objRota = window.rotasDashboard[idEdital];
    if (!objRota) return;

    if (rotaEmDestaqueAtual === idEdital) {
        Object.values(window.rotasDashboard).forEach(r => {
            if (r.camada) r.camada.getLayers().forEach(l => l.getElement()?.classList.remove('rota-fundo'));
        });
        botaoCard.classList.remove("ativo");
        botaoCard.innerHTML = "🎯 Destacar Rota";
        rotaEmDestaqueAtual = null;
        mapaLeaflet.fitBounds(limitesGeraisMapa, { padding: [40, 40], maxZoom: 8 });
        return;
    }

    document.querySelectorAll(".btn-destaque").forEach(b => { b.classList.remove("ativo"); b.innerHTML = "🎯 Destacar Rota"; });

    Object.values(window.rotasDashboard).forEach(r => {
        if (r.camada) {
            r.camada.getLayers().forEach(l => {
                if (l.getElement()) l.getElement().classList.add('rota-fundo');
            });
        }
    });

    objRota.camada.getLayers().forEach(l => {
        if (l.getElement()) {
            l.getElement().classList.remove('rota-fundo');
            if (l.bringToFront) l.bringToFront();
        }
    });

    botaoCard.classList.add("ativo");
    botaoCard.innerHTML = "👁️‍🗨️ Em Destaque";
    rotaEmDestaqueAtual = idEdital;

    const limitesDestaque = L.latLngBounds([objRota.latOrigem, objRota.lngOrigem], [objRota.latDestino, objRota.lngDestino]);
    mapaLeaflet.fitBounds(limitesDestaque, { padding: [60, 60], maxZoom: 7 });
    document.getElementById("aba-dashboard").scrollIntoView({ behavior: "smooth", block: "start" });
}

// ==============================================================================
// MAPA SECUNDÁRIO (HISTÓRICO)
// ==============================================================================
function inicializarMapaHistorico() {
    mapaHistorico = L.map("mapa-brasil-historico").setView([-15.79, -47.88], 4);
    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 18 }).addTo(mapaHistorico);
    camadaHistorico = L.layerGroup().addTo(mapaHistorico);
}

function focarRotaHistorico(idEdital) {
    const vaga = todasOportunidades.find(o => o.id == idEdital || o.id_edital == idEdital);
    if (!vaga) return;

    if (!camadaHistorico) inicializarMapaHistorico();
    camadaHistorico.clearLayers();

    const latO = parseFloat(dadosPerfil.lat_origem);
    const lngO = parseFloat(dadosPerfil.lng_origem);
    const latV = parseFloat(vaga.latitude);
    const lngV = parseFloat(vaga.longitude);

    L.circle([latO, lngO], { color: "#004488", fillColor: "#004488", fillOpacity: 0.5, radius: 2000 }).addTo(camadaHistorico);
    const cor = { ALTA: "#28a745", MEDIA: "#ffc107", BAIXA: "#dc3545", EXPIRADO: "#6c757d" }[vaga.probabilidade] || "#004488";
    L.circle([latV, lngV], { color: cor, fillColor: cor, fillOpacity: 0.5, radius: 2000 }).addTo(camadaHistorico);
    
    L.polyline(gerarPontosArco([latO, lngO], [latV, lngV]), { color: cor, weight: 3, className: "arco-voo" }).addTo(camadaHistorico);

    mapaHistorico.fitBounds(L.latLngBounds([latO, lngO], [latV, lngV]), { padding: [50, 50], maxZoom: 10 });
    document.getElementById("mapa-container-historico").scrollIntoView({ behavior: "smooth", block: "center" });
}

function gerarPontosArco(origem, destino) {
    const lat1 = origem[0], lon1 = origem[1], lat2 = destino[0], lon2 = destino[1];
    const mx = (lat1 + lat2) / 2, my = (lon1 + lon2) / 2;
    const off = 0.25 * Math.abs(lon2 - lon1) + 0.6;
    const pts = [];
    for (let i = 0; i <= 35; i++) {
        const t = i / 35;
        const x = (1 - t) * (1 - t) * lat1 + 2 * (1 - t) * t * (mx + off) + t * t * lat2;
        const y = (1 - t) * (1 - t) * lon1 + 2 * (1 - t) * t * my + t * t * lon2;
        pts.push([x, y]);
    }
    return pts;
}

// ==============================================================================
// CALENDÁRIO FLATPICKR
// ==============================================================================
function inicializarSuperCalendario() {
    const inputData = document.getElementById("filtro-prazo");
    const btnLimpar = document.getElementById("btn-limpar-data");
    const divLegenda = document.getElementById("legenda-datas");
    if (!inputData) return;

    const datasComEditais = todasOportunidades.map(v => v.prazo_limite || v.prazo_fim).filter(d => d);

    superCalendario = flatpickr(inputData, {
        locale: "pt",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        placeholder: "Ver calendário...",
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            const dataAtual = dayElem.dateObj;
            const dataFormatada = dataAtual.getFullYear() + "-" + String(dataAtual.getMonth() + 1).padStart(2, '0') + "-" + String(dataAtual.getDate()).padStart(2, '0');
            if (datasComEditais.includes(dataFormatada)) {
                dayElem.innerHTML += "<span class='marcador-edital' style='position:absolute; bottom:2px; left:50%; transform:translateX(-50%); width:6px; height:6px; background-color:#004488; border-radius:50%;'></span>";
                dayElem.title = "Existem editais encerrando nesta data!";
            }
        },
        onChange: function (selectedDates, dateStr) {
            if (dateStr) {
                btnLimpar.style.display = "block";
                const editaisNoDia = todasOportunidades.filter(v => (v.prazo_limite || v.prazo_fim) === dateStr);
                if (editaisNoDia.length > 0) {
                    const nomes = editaisNoDia.map(e => e.instituicao.split(' ')[0]).join(', ');
                    divLegenda.innerHTML = `⚠️ Encerram neste dia: <b>${nomes}</b>`;
                    divLegenda.style.color = "#004488";
                } else {
                    divLegenda.innerHTML = `Mostrando editais até o dia selecionado.`;
                    divLegenda.style.color = "#64748b";
                }
                divLegenda.style.display = "block";
            } else {
                btnLimpar.style.display = "none";
                divLegenda.style.display = "none";
            }
            aplicarFiltrosHistorico();
        }
    });

    btnLimpar.addEventListener("click", () => {
        superCalendario.clear();
        btnLimpar.style.display = "none";
        divLegenda.style.display = "none";
        aplicarFiltrosHistorico();
    });
}

// ==============================================================================
// GRÁFICOS, RENDERIZAÇÃO DE CARDS E FILTROS HISTÓRICOS (COM GRUPOS)
// ==============================================================================
function renderizarGraficos(kpis, oportunidades) {
    const ctxPizza = document.getElementById("graficoPizza").getContext("2d");
    const ctxBarras = document.getElementById("graficoBarras").getContext("2d");
    if (chartPizza) chartPizza.destroy();
    if (chartBarras) chartBarras.destroy();

    chartPizza = new Chart(ctxPizza, {
        type: "doughnut",
        data: {
            labels: ["Alta Chance", "Atenção", "Improvável"],
            datasets: [{
                data: [kpis.verdes, kpis.amarelas, kpis.vermelhas],
                backgroundColor: ["#28a745", "#ffc107", "#dc3545"]
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } } }
    });

    const contagemTipos = {};
    oportunidades.forEach(vaga => {
        if (vaga.probabilidade !== 'EXPIRADO') {
            const tipo = formatarBeneficio(vaga.tipo_beneficio || vaga.tipo_vaga);
            contagemTipos[tipo] = (contagemTipos[tipo] || 0) + 1;
        }
    });

    chartBarras = new Chart(ctxBarras, {
        type: "bar",
        data: {
            labels: Object.keys(contagemTipos),
            datasets: [{ label: "Qtd. Editais", data: Object.values(contagemTipos), backgroundColor: "#004488", borderRadius: 6 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } }
        }
    });
}

function renderizarTop8Cards(lista) {
    document.getElementById("grid-top-8").innerHTML = lista.map(vaga => montarCardHTML(vaga, "dashboard")).join("");
}

function aplicarFiltrosHistorico() {
    const pChance = document.getElementById("filtro-chance").value;
    const pEscopo = document.getElementById("filtro-escopo").value;
    const pOrcamento = parseFloat(document.getElementById("filtro-orcamento").value);

    const inputOcultoData = document.querySelector("#filtro-prazo")._flatpickr;
    const pPrazo = inputOcultoData ? inputOcultoData.selectedDates[0] : null;

    const ufAluno = document.getElementById("estado_origem").value || dadosPerfil.estado_origem;
    const cidadeAluno = document.getElementById("cidade_origem").value || dadosPerfil.cidade_origem;

    const filtrados = todasOportunidades.filter(vaga => {
        const condChance = pChance === "TODAS" || vaga.probabilidade === pChance;

        let condEscopo = true;
        if (pEscopo === "LOCAL") condEscopo = (vaga.cidade === cidadeAluno) && (vaga.estado === ufAluno);
        else if (pEscopo === "ESTADUAL") condEscopo = (vaga.estado === ufAluno);
        else if (pEscopo === "INTERESTADUAL") condEscopo = (vaga.estado !== ufAluno);

        let condPrazo = true;
        if (pPrazo) {
            const dataEdital = new Date(vaga.prazo_limite || vaga.prazo_fim);
            dataEdital.setHours(0, 0, 0, 0);
            const dataFiltro = new Date(pPrazo);
            dataFiltro.setHours(0, 0, 0, 0);
            condPrazo = dataEdital <= dataFiltro;
        }

        const custoVaga = vaga.custo_medio_republica || vaga.custo_moradia_estimado || 0;
        const condOrcamento = isNaN(pOrcamento) || custoVaga <= pOrcamento;

        return condChance && condEscopo && condPrazo && condOrcamento;
    });

    const grid = document.getElementById("grid-historico-completo");
    if (filtrados.length === 0) {
        grid.innerHTML = '<div class="estado-vazio"><p>Nenhum edital encontrado para esta combinação de filtros.</p></div>';
    } else {
        // AGRUPAMENTO E ORDENAÇÃO DO HISTÓRICO
        const grupos = { ALTA: [], MEDIA: [], BAIXA: [], EXPIRADO: [] };
        filtrados.forEach(vaga => grupos[vaga.probabilidade].push(vaga));
        
        // Ordena cada grupo por data crescente (mais urgente primeiro)
        Object.keys(grupos).forEach(chave => {
            grupos[chave].sort((a, b) => new Date(a.prazo_limite || a.prazo_fim) - new Date(b.prazo_limite || b.prazo_fim));
        });

        let htmlGrid = "";
        
        if (grupos.ALTA.length > 0) {
            htmlGrid += `<div style="width: 100%; margin-top: 20px; grid-column: 1 / -1;"><h3 style="color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 5px;">Alta Compatibilidade</h3></div>`;
            htmlGrid += grupos.ALTA.map(vaga => montarCardHTML(vaga, "historico")).join("");
        }
        if (grupos.MEDIA.length > 0) {
            htmlGrid += `<div style="width: 100%; margin-top: 20px; grid-column: 1 / -1;"><h3 style="color: #ffc107; border-bottom: 2px solid #ffc107; padding-bottom: 5px;">Atenção Aos Critérios</h3></div>`;
            htmlGrid += grupos.MEDIA.map(vaga => montarCardHTML(vaga, "historico")).join("");
        }
        if (grupos.BAIXA.length > 0) {
            htmlGrid += `<div style="width: 100%; margin-top: 20px; grid-column: 1 / -1;"><h3 style="color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 5px;">Improváveis</h3></div>`;
            htmlGrid += grupos.BAIXA.map(vaga => montarCardHTML(vaga, "historico")).join("");
        }
        if (grupos.EXPIRADO.length > 0) {
            htmlGrid += `<div style="width: 100%; margin-top: 20px; grid-column: 1 / -1;"><h3 style="color: #6c757d; border-bottom: 2px solid #6c757d; padding-bottom: 5px;">Já Realizados / Expirados</h3></div>`;
            htmlGrid += grupos.EXPIRADO.map(vaga => montarCardHTML(vaga, "historico")).join("");
        }
        
        grid.innerHTML = htmlGrid;
    }
}

function montarCardHTML(vaga, contexto) {
    let cssProb, textoProb;
    if (vaga.probabilidade === "ALTA") { cssProb = "tag-alta"; textoProb = "Alta Compatibilidade"; }
    else if (vaga.probabilidade === "MEDIA") { cssProb = "tag-media"; textoProb = "Atenção aos Critérios"; }
    else if (vaga.probabilidade === "BAIXA") { cssProb = "tag-baixa"; textoProb = "Baixa Compatibilidade"; }
    else { cssProb = "tag-expirado"; textoProb = "Já Realizado / Expirado"; }

    const id = vaga.id || vaga.id_edital;
    const titulo = vaga.nome_processo || vaga.titulo;
    const tipo = vaga.tipo_beneficio || vaga.tipo_vaga;
    const prazo = vaga.prazo_formatado || vaga.prazo_limite;
    const custo = parseFloat(vaga.custo_medio_republica || vaga.custo_moradia_estimado || 0).toFixed(2);

    const botaoMapa = contexto === "dashboard"
        ? `<button onclick="destacarRotaDashboard(${id}, this)" class="btn-destaque">🎯 Destacar Rota</button>`
        : `<button onclick="focarRotaHistorico(${id})" class="btn-foco-rota">🗺️ Ver Rota no Mapa</button>`;

    return `
        <div class="card-oportunidade-rico borda-${vaga.probabilidade}">
            <div>
                <div class="card-topo">
                    <span class="instituicao-badge">${vaga.instituicao} • ${vaga.cidade}/${vaga.estado}</span>
                    <span class="tag-probabilidade ${cssProb}">${textoProb}</span>
                </div>
                <h3>${titulo}</h3>
                <p class="meta-info">🎯 <strong>Modalidade:</strong> ${formatarBeneficio(tipo)}</p>
                <p class="meta-info">📅 <strong>Prazo Final:</strong> ${prazo}</p>
                <p class="meta-info">🏠 <strong>República Média:</strong> R$ ${custo} ${vaga.tem_moradia ? "(Possui Moradia Gratuita)" : ""}</p>
                <p class="meta-info">✈️ <strong>Distância de Casa:</strong> ${vaga.distancia_km} km</p>
                <div class="diagnostico-box">
                    <strong>Análise do Motor:</strong> ${vaga.diagnostico || "Classificação automática pendente."}
                </div>
            </div>
            <div class="card-rodape">
                ${botaoMapa}
                <a href="${vaga.link_documento || "#"}" class="link-edital">Resumo e Edital →</a>
            </div>
        </div>
    `;
}

function formatarBeneficio(codigo) {
    return {
        ISENCAO_TAXA: "Isenção de Taxa",
        COTA_RENDA: "Cota Renda",
        COTA_ESCOLA_PUBLICA: "Cota Escola Pública",
        COTA_RACIAL: "Cota Étnico-Racial (PPI)",
        AMPLA_CONCORRENCIA: "Ampla Concorrência"
    }[codigo] || codigo;
}