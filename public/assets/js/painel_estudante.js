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

// Valores vindos do banco/API nunca devem ser interpolados em HTML sem escape.
function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, char => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    })[char]);
}

function safeDocumentUrl(value) {
    const raw = String(value ?? "").trim();
    if (!/^https?:\/\//i.test(raw)) return "";
    try {
        const url = new URL(raw);
        return ["http:", "https:"].includes(url.protocol) ? url.href : "";
    } catch {
        return "";
    }
}

function rotuloElegibilidade(status) {
    return ({
        COMPATIVEL: "Compatível com os critérios cadastrados",
        INCOMPATIVEL: "Incompatível com um ou mais critérios",
        PENDENTE: "Dados insuficientes para concluir",
        AGUARDANDO_ABERTURA: "Inscrições ainda não iniciadas",
        ENCERRADO: "Prazo encerrado",
        REGRA_INVALIDA: "Regra precisa de revisão"
    })[status] || "Classificação não disponível";
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
        const resposta = await fetch("/api/estudante/painel", {
            headers: { "Accept": "application/json" }
        });
        if (resposta.status === 401) {
            window.location.href = "/login";
            return;
        }
        const json = await resposta.json();

        if (!json.sucesso) throw new Error(json.mensagem);

        todasOportunidades = json.oportunidades || [];
        dadosPerfil = json.perfil || {};

        document.getElementById("nome-estudante").textContent = `Olá, ${json.usuario}!`;

        if (json.ficha_completa && dadosPerfil.cidade_origem && dadosPerfil.estado_origem) {
            document.getElementById("badge-origem").textContent = `📍 Origem: ${dadosPerfil.cidade_origem} - ${dadosPerfil.estado_origem}`;
        } else {
            document.getElementById("badge-origem").textContent = `📍 Origem: Preencha sua Ficha Socioeconômica`;
        }

        document.getElementById("kpi-universidades").textContent = json.kpis.total_universidades;
        document.getElementById("kpi-verdes").textContent = json.kpis.verdes;
        document.getElementById("kpi-amarelas").textContent = json.kpis.amarelas;
        document.getElementById("kpi-vermelhas").textContent = json.kpis.vermelhas;

        await preencherFormularioPerfil(dadosPerfil);
        renderizarGraficos(json.kpis, todasOportunidades);

        // TOP 8 DO DASHBOARD: Filtra expirados e vagas além da mobilidade declarada na ficha
        const pesosStatus = { COMPATIVEL: 1, PENDENTE: 2, AGUARDANDO_ABERTURA: 3, INCOMPATIVEL: 4, ENCERRADO: 5 };
        const ordemMobilidade = { LOCAL: 1, ESTADUAL: 2, INTERESTADUAL: 3, INTERNACIONAL: 4 };
        const limiteMobilidade = ordemMobilidade[dadosPerfil.nivel_mobilidade] ?? 4;

        const vagasAtivas = todasOportunidades.filter(v => {
            if (v.status_elegibilidade === 'ENCERRADO') return false;
            const escopoVaga = v.escopo_geografico || v.escopo_mobilidade;
            const nivelVaga = ordemMobilidade[escopoVaga] ?? 1;
            return nivelVaga <= limiteMobilidade;
        });

        const top8 = [...vagasAtivas].sort((a, b) => {
            const pesoA = pesosStatus[a.status_elegibilidade] ?? 99;
            const pesoB = pesosStatus[b.status_elegibilidade] ?? 99;
            if (pesoA !== pesoB) return pesoA - pesoB;

            const alertaA = Array.isArray(a.alertas_logisticos) && a.alertas_logisticos.length > 0 ? 1 : 0;
            const alertaB = Array.isArray(b.alertas_logisticos) && b.alertas_logisticos.length > 0 ? 1 : 0;
            if (alertaA !== alertaB) return alertaA - alertaB;

            return new Date(a.prazo_limite || a.prazo_fim) - new Date(b.prazo_limite || b.prazo_fim);
        }).slice(0, 8);

        renderizarMapaBrasil(dadosPerfil, top8);
        renderizarTop8Cards(top8, json.ficha_completa);
        aplicarFiltrosHistorico(json.ficha_completa);
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
        raca_cor: document.getElementById("raca_cor").value,
        nota_media_enem: document.getElementById("nota_media_enem").value,
        cep: document.getElementById("cep").value.trim(), // Opcional
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
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": getCsrfToken()
            },
            body: JSON.stringify(dadosFicha)
        });

        const json = await resposta.json();

        if (resposta.ok && json.sucesso) {
            fichaModificada = false;
            alerta.style.display = "block";
            alerta.style.backgroundColor = "#dcfce7";
            alerta.style.color = "#166534";
            alerta.textContent = "✅ " + json.mensagem + " Clique em 'Voltar ao Mapa' para ver os resultados.";
            await carregarDadosPainel();
        } else {
            throw new Error(json.message || json.mensagem || "Falha na validação dos dados.");
        }
    } catch (erro) {
        alerta.style.display = "block";
        alerta.style.backgroundColor = "#fee2e2";
        alerta.style.color = "#991b1b";
        alerta.textContent = "❌ " + (erro.message || "Ocorreu um erro ao salvar a ficha. Verifique os campos obrigatórios.");
    } finally {
        btnSalvar.textContent = "💾 Salvar Dados e Rever Oportunidades";
        btnSalvar.disabled = false;
    }
}

async function fazerLogout() {
    try {
        await fetch("/logout", {
            method: "POST",
            headers: { "Accept": "application/json", "X-CSRF-TOKEN": getCsrfToken() }
        });
    }
    finally { window.location.href = "/login"; }
}

// ==============================================================================
// BUSCA DE ENDEREÇO: CEP OPCIONAL -> ESTADO/CIDADE (SEM BLOQUEIO)
// ==============================================================================
async function preencherFormularioPerfil(perfil) {
    if (!perfil || Object.keys(perfil).length === 0) {
        fichaModificada = false;
        return;
    }

    document.getElementById("renda_per_capita").value = perfil.renda_per_capita ?? "";
    document.getElementById("tipo_escola").value = perfil.tipo_escola || "";
    document.getElementById("raca_cor").value = perfil.raca_cor || "";
    document.getElementById("nota_media_enem").value = perfil.nota_media_enem ?? "";
    document.getElementById("cep").value = perfil.cep || "";

    if (perfil.estado_origem) {
        document.getElementById("estado_origem").value = perfil.estado_origem;
        await carregarCidadesPorUF(perfil.estado_origem, perfil.cidade_origem);
    }

    document.getElementById("lat_origem").value = perfil.lat_origem ?? "";
    document.getElementById("lng_origem").value = perfil.lng_origem ?? "";
    document.getElementById("nivel_mobilidade").value = perfil.nivel_mobilidade || "";
    document.getElementById("orcamento_moradia").value = perfil.orcamento_moradia ?? "";
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
            if (cidadePreSelecionada && m.nome.toLowerCase() === cidadePreSelecionada.toLowerCase()) {
                option.selected = true;
            }
            selectCidade.appendChild(option);
        });
    } catch (e) {
        console.error("Erro IBGE:", e);
    }
}

async function aoMudarEstado(uf) {
    await carregarCidadesPorUF(uf);
    // Limpa o CEP antigo pois o usuário decidiu trocar o estado manualmente
    document.getElementById("cep").value = "";
    fichaModificada = true;
}

/**
 * Quando o usuário seleciona Estado + Cidade, NÃO exigimos nem alteramos o CEP.
 * Apenas buscamos a Latitude e Longitude da cidade escolhida para posicionar o pino no mapa.
 */
async function aoSelecionarCidade() {
    const uf = document.getElementById("estado_origem").value;
    const cidade = document.getElementById("cidade_origem").value;
    const btnSalvar = document.getElementById("btn-salvar-ficha");

    if (!uf || !cidade) return;

    buscandoLocalizacao = true;
    btnSalvar.disabled = true;
    btnSalvar.textContent = "⏳ Obtendo coordenadas da cidade...";

    try {
        const query = `${cidade}, ${uf}, Brazil`;
        const resGeo = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`);
        const dadosGeo = await resGeo.json();

        if (Array.isArray(dadosGeo) && dadosGeo.length > 0) {
            document.getElementById("lat_origem").value = dadosGeo[0].lat;
            document.getElementById("lng_origem").value = dadosGeo[0].lon;
        }
        fichaModificada = true;
    } catch (erro) {
        console.warn("Não foi possível obter coordenadas exatas da cidade:", erro);
    } finally {
        buscandoLocalizacao = false;
        btnSalvar.disabled = false;
        btnSalvar.textContent = "💾 Salvar Dados e Rever Oportunidades";
    }
}

/**
 * Quando o usuário digita um CEP de 8 dígitos, preenchemos Estado, Cidade e Coordenadas automaticamente.
 * Se o CEP estiver em branco ou não for encontrado, não trava o formulário.
 */
async function buscarCep() {
    const cepInput = document.getElementById("cep");
    const cepLimpo = cepInput.value.replace(/\D/g, "");

    // Se estiver vazio, não faz nada (pois o CEP é opcional)
    if (cepLimpo.length === 0) return;

    if (cepLimpo.length !== 8 || cepLimpo === "00000000") {
        cepInput.value = "";
        cepInput.placeholder = "CEP inválido (use Estado/Cidade)";
        return;
    }

    const btnSalvar = document.getElementById("btn-salvar-ficha");
    buscandoLocalizacao = true;
    btnSalvar.disabled = true;
    btnSalvar.textContent = "⏳ Buscando endereço pelo CEP...";

    try {
        const resViaCep = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
        const dadosCep = await resViaCep.json();
        if (dadosCep.erro) throw new Error("CEP não encontrado");

        // Preenche Estado e Cidade automaticamente a partir do CEP
        document.getElementById("estado_origem").value = dadosCep.uf;
        await carregarCidadesPorUF(dadosCep.uf, dadosCep.localidade);
        cepInput.value = dadosCep.cep || cepLimpo;

        // Busca coordenadas geográficas (Lat/Lng) para o mapa
        const query = `${dadosCep.logradouro ? dadosCep.logradouro + ', ' : ''}${dadosCep.localidade}, ${dadosCep.uf}, Brazil`;
        const n = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`);
        const r = await n.json();

        if (Array.isArray(r) && r.length > 0) {
            document.getElementById("lat_origem").value = r[0].lat;
            document.getElementById("lng_origem").value = r[0].lon;
        } else {
            const e = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(dadosCep.localidade + ", " + dadosCep.uf + ", Brazil")}`);
            const a = await e.json();
            if (Array.isArray(a) && a.length > 0) {
                document.getElementById("lat_origem").value = a[0].lat;
                document.getElementById("lng_origem").value = a[0].lon;
            }
        }
        fichaModificada = true;
    } catch (erro) {
        cepInput.value = "";
        cepInput.placeholder = "Não achado. Selecione UF e Cidade!";
    } finally {
        buscandoLocalizacao = false;
        btnSalvar.disabled = false;
        btnSalvar.textContent = "💾 Salvar Dados e Rever Oportunidades";
    }
}

// ==============================================================================
// MAPA PRINCIPAL (DASHBOARD)
// ==============================================================================
function renderizarMapaBrasil(perfil, oportunidades) {
    const latInformada = perfil?.lat_origem;
    const lngInformada = perfil?.lng_origem;
    const latNumero = Number(latInformada);
    const lngNumero = Number(lngInformada);
    const temOrigemReal = Boolean(
        perfil?.cidade_origem && latInformada !== null && latInformada !== undefined && latInformada !== "" &&
        lngInformada !== null && lngInformada !== undefined && lngInformada !== "" &&
        Number.isFinite(latNumero) && Number.isFinite(lngNumero) &&
        latNumero >= -90 && latNumero <= 90 && lngNumero >= -180 && lngNumero <= 180
    );
    const lat = temOrigemReal ? latNumero : -15.793889;
    const lng = temOrigemReal ? lngNumero : -47.882778;

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
                };
                return container;
            }
        });
        mapaLeaflet.addControl(new ExpandirControl());
        mapaLeaflet.on('zoomend', atualizarRadarOrigem);
    }

    clearInterval(intervaloRadar);
    circuloRadarOrigem = null;
    limitesGeraisMapa = temOrigemReal ? L.latLngBounds([[lat, lng]]) : null;

    if (temOrigemReal) {
        const iconePulso = L.divIcon({ className: 'icone-origem-pulso', iconSize: [16, 16] });
        L.marker([lat, lng], { icon: iconePulso, zIndexOffset: 1000 })
            .bindPopup(`<b>🏠 Ponto de Partida: ${escapeHtml(perfil.cidade_origem)}/${escapeHtml(perfil.estado_origem)}</b>`)
            .addTo(camadaElementosMapa);

        circuloRadarOrigem = L.circle([lat, lng], {
            color: "#004488", fillColor: "#004488", fillOpacity: 0.2, radius: 25000, weight: 1, interactive: false
        }).addTo(camadaElementosMapa);
    }

    const cores = { ALTA: "#28a745", MEDIA: "#ffc107", BAIXA: "#dc3545", EXPIRADO: "#6c757d" };
    window.rotasDashboard = {};

    oportunidades.forEach(vaga => {
        const cor = cores[vaga.classe_visual] || "#004488";
        const latVaga = Number(vaga.latitude);
        const lngVaga = Number(vaga.longitude);
        if (!Number.isFinite(latVaga) || !Number.isFinite(lngVaga)
            || latVaga < -90 || latVaga > 90 || lngVaga < -180 || lngVaga > 180) {
            return;
        }

        const pino = L.circleMarker([latVaga, lngVaga], { color: cor, fillColor: cor, fillOpacity: 0.8, radius: 6, weight: 2 })
            .bindPopup(`<b>🎓 ${escapeHtml(vaga.instituicao)} (${escapeHtml(vaga.cidade)})</b><br>${escapeHtml(formatarBeneficio(vaga.tipo_beneficio))}<br>${escapeHtml(rotuloElegibilidade(vaga.status_elegibilidade))}`);
        const camadas = [pino];

        // Sem coordenadas de origem, mostra o destino, mas não inventa uma linha de deslocamento.
        if (temOrigemReal) {
            camadas.push(L.polyline(gerarPontosArco([lat, lng], [latVaga, lngVaga]), {
                color: cor, weight: 2.5, opacity: 0.8, className: "arco-voo"
            }));
        }

        const grupo = L.layerGroup(camadas).addTo(camadaElementosMapa);
        if (limitesGeraisMapa) {
            limitesGeraisMapa.extend([latVaga, lngVaga]);
        } else {
            limitesGeraisMapa = L.latLngBounds([[latVaga, lngVaga]]);
        }

        const idUnico = vaga.id || vaga.id_regra || vaga.id_edital;
        window.rotasDashboard[idUnico] = {
            camada: grupo,
            latDestino: latVaga,
            lngDestino: lngVaga,
            latOrigem: temOrigemReal ? lat : null,
            lngOrigem: temOrigemReal ? lng : null
        };
    });

    if (limitesGeraisMapa && limitesGeraisMapa.isValid()) {
        mapaLeaflet.fitBounds(limitesGeraisMapa, { padding: [40, 40], maxZoom: 8 });
    } else {
        mapaLeaflet.setView([lat, lng], 4);
    }
    atualizarRadarOrigem();
}

function atualizarRadarOrigem() {
    if (!circuloRadarOrigem || !mapaLeaflet) return;
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

function destacarRotaDashboard(idRegra, botaoCard) {
    const objRota = window.rotasDashboard[idRegra];
    if (!objRota) return;

    if (rotaEmDestaqueAtual === idRegra) {
        Object.values(window.rotasDashboard).forEach(r => {
            if (r.camada) r.camada.getLayers().forEach(l => l.getElement()?.classList.remove('rota-fundo'));
        });
        botaoCard.classList.remove("ativo");
        botaoCard.innerHTML = "🎯 Destacar no Mapa";
        rotaEmDestaqueAtual = null;
        mapaLeaflet.fitBounds(limitesGeraisMapa, { padding: [40, 40], maxZoom: 8 });
        return;
    }

    document.querySelectorAll(".btn-destaque").forEach(b => { b.classList.remove("ativo"); b.innerHTML = "🎯 Destacar no Mapa"; });

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
    rotaEmDestaqueAtual = idRegra;

    if (objRota.latOrigem === null || objRota.lngOrigem === null) {
        mapaLeaflet.setView([objRota.latDestino, objRota.lngDestino], 8);
    } else {
        const limitesDestaque = L.latLngBounds(
            [objRota.latOrigem, objRota.lngOrigem],
            [objRota.latDestino, objRota.lngDestino]
        );
        mapaLeaflet.fitBounds(limitesDestaque, { padding: [60, 60], maxZoom: 7 });
    }
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

function focarRotaHistorico(idRegra) {
    const vaga = todasOportunidades.find(o => (o.id || o.id_regra || o.id_edital) == idRegra);
    if (!vaga) return;

    if (!camadaHistorico) inicializarMapaHistorico();
    camadaHistorico.clearLayers();

    const latV = Number(vaga.latitude);
    const lngV = Number(vaga.longitude);
    if (!Number.isFinite(latV) || !Number.isFinite(lngV)
        || latV < -90 || latV > 90 || lngV < -180 || lngV > 180) {
        return;
    }

    const cor = { ALTA: "#28a745", MEDIA: "#ffc107", BAIXA: "#dc3545", EXPIRADO: "#6c757d" }[vaga.classe_visual] || "#004488";
    L.circle([latV, lngV], { color: cor, fillColor: cor, fillOpacity: 0.5, radius: 2000 })
        .bindPopup(`<b>🎓 ${escapeHtml(vaga.instituicao)} (${escapeHtml(vaga.cidade)})</b>`)
        .addTo(camadaHistorico);

    const latOrigemInformada = dadosPerfil.lat_origem;
    const lngOrigemInformada = dadosPerfil.lng_origem;
    const origemInformada = latOrigemInformada !== null && latOrigemInformada !== undefined && latOrigemInformada !== ''
        && lngOrigemInformada !== null && lngOrigemInformada !== undefined && lngOrigemInformada !== '';

    if (origemInformada) {
        const latO = Number(latOrigemInformada);
        const lngO = Number(lngOrigemInformada);
        const origemValida = Number.isFinite(latO) && Number.isFinite(lngO)
            && latO >= -90 && latO <= 90 && lngO >= -180 && lngO <= 180;

        if (origemValida) {
            L.circle([latO, lngO], { color: "#004488", fillColor: "#004488", fillOpacity: 0.5, radius: 2000 })
                .addTo(camadaHistorico);
            L.polyline(gerarPontosArco([latO, lngO], [latV, lngV]), {
                color: cor, weight: 3, className: "arco-voo"
            }).addTo(camadaHistorico);
            mapaHistorico.fitBounds(L.latLngBounds([latO, lngO], [latV, lngV]), { padding: [50, 50], maxZoom: 10 });
        } else {
            mapaHistorico.setView([latV, lngV], 8);
        }
    } else {
        mapaHistorico.setView([latV, lngV], 8);
    }

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
    if (!inputData || typeof flatpickr === "undefined") return;

    const datasComEditais = todasOportunidades.map(v => v.prazo_limite || v.prazo_fim).filter(d => d);

    if (superCalendario) superCalendario.destroy();

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
                    const nomes = editaisNoDia.map(e => String(e.instituicao || "Instituição").split(' ')[0]).join(', ');
                    divLegenda.innerHTML = `⚠️ Encerram neste dia: <b>${escapeHtml(nomes)}</b>`;
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
            aplicarFiltrosHistorico(true);
        }
    });

    btnLimpar.onclick = () => {
        superCalendario.clear();
        btnLimpar.style.display = "none";
        divLegenda.style.display = "none";
        aplicarFiltrosHistorico(true);
    };
}

// ==============================================================================
// GRÁFICOS, RENDERIZAÇÃO DE CARDS E FILTROS HISTÓRICOS
// ==============================================================================
function renderizarGraficos(kpis, oportunidades) {
    const ctxPizza = document.getElementById("graficoPizza").getContext("2d");
    const ctxBarras = document.getElementById("graficoBarras").getContext("2d");
    if (chartPizza) chartPizza.destroy();
    if (chartBarras) chartBarras.destroy();

    chartPizza = new Chart(ctxPizza, {
        type: "doughnut",
        data: {
            labels: ["Compatível", "Dados pendentes", "Incompatível"],
            datasets: [{
                data: [kpis.verdes, kpis.amarelas, kpis.vermelhas],
                backgroundColor: ["#28a745", "#ffc107", "#dc3545"]
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } } }
    });

    const contagemTipos = {};
    oportunidades.forEach(vaga => {
        if (vaga.status_elegibilidade !== 'ENCERRADO') {
            const tipo = formatarBeneficio(vaga.tipo_beneficio || vaga.tipo_vaga);
            contagemTipos[tipo] = (contagemTipos[tipo] || 0) + 1;
        }
    });

    chartBarras = new Chart(ctxBarras, {
        type: "bar",
        data: {
            labels: Object.keys(contagemTipos),
            datasets: [{ label: "Qtd. Modalidades", data: Object.values(contagemTipos), backgroundColor: "#004488", borderRadius: 6 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } }
        }
    });
}

function renderizarTop8Cards(lista, fichaCompleta = true) {
    const grid = document.getElementById("grid-top-8");
    if (!fichaCompleta) {
        grid.innerHTML = `
            <div class="estado-vazio">
                <p>Sua ficha socioeconômica ainda está vazia. Preencha seus dados para destravar o cálculo de elegibilidade e o radar geográfico!</p>
                <br>
                <button class="btn-primario" style="background: var(--azul-primario); color: white;" onclick="trocarAba('aba-ficha')">📝 Preencher Minha Ficha Agora</button>
            </div>
        `;
        return;
    }

    if (lista.length === 0) {
        grid.innerHTML = '<div class="estado-vazio"><p>Nenhuma modalidade ativa encontrada no momento.</p></div>';
        return;
    }

    grid.innerHTML = lista.map(vaga => montarCardHTML(vaga, "dashboard")).join("");
}

function aplicarFiltrosHistorico(fichaCompleta = true) {
    const grid = document.getElementById("grid-historico-completo");
    if (!fichaCompleta && todasOportunidades.length === 0) {
        grid.innerHTML = '<div class="estado-vazio"><p>Preencha sua Ficha Socioeconômica na terceira aba para consultar o histórico analisado.</p></div>';
        return;
    }

    const pChance = document.getElementById("filtro-chance").value;
    const pEscopo = document.getElementById("filtro-escopo").value;
    const pOrcamento = parseFloat(document.getElementById("filtro-orcamento").value);

    const inputOcultoData = document.querySelector("#filtro-prazo")._flatpickr;
    const pPrazo = inputOcultoData ? inputOcultoData.selectedDates[0] : null;

    const filtrados = todasOportunidades.filter(vaga => {
        const condChance = pChance === "TODAS" || vaga.status_elegibilidade === pChance;
        const condEscopo = pEscopo === "TODOS" || (vaga.escopo_geografico || vaga.escopo_mobilidade) === pEscopo;

        let condPrazo = true;
        if (pPrazo) {
            const dataEdital = new Date(vaga.prazo_limite || vaga.prazo_fim);
            dataEdital.setHours(0, 0, 0, 0);
            const dataFiltro = new Date(pPrazo);
            dataFiltro.setHours(0, 0, 0, 0);
            condPrazo = dataEdital <= dataFiltro;
        }

        const custoVaga = vaga.custo_medio_republica ?? vaga.custo_moradia_estimado;
        const condOrcamento = isNaN(pOrcamento) || custoVaga === null || custoVaga === undefined || Number(custoVaga) <= pOrcamento;

        return condChance && condEscopo && condPrazo && condOrcamento;
    });

    if (filtrados.length === 0) {
        grid.innerHTML = '<div class="estado-vazio"><p>Nenhum edital encontrado para esta combinação de filtros.</p></div>';
    } else {
        const grupos = { COMPATIVEL: [], PENDENTE: [], AGUARDANDO_ABERTURA: [], INCOMPATIVEL: [], ENCERRADO: [], REGRA_INVALIDA: [] };
        filtrados.forEach(vaga => {
            if (grupos[vaga.status_elegibilidade]) grupos[vaga.status_elegibilidade].push(vaga);
        });

        Object.keys(grupos).forEach(chave => {
            grupos[chave].sort((a, b) => new Date(a.prazo_limite || a.prazo_fim) - new Date(b.prazo_limite || b.prazo_fim));
        });

        let htmlGrid = "";

        const secoes = [
            ["COMPATIVEL", "Compatíveis com os critérios", "#28a745"],
            ["PENDENTE", "Dados pendentes", "#ffc107"],
            ["AGUARDANDO_ABERTURA", "Inscrições ainda não iniciadas", "#0d6efd"],
            ["INCOMPATIVEL", "Incompatíveis com os critérios", "#dc3545"],
            ["ENCERRADO", "Prazos encerrados", "#6c757d"],
            ["REGRA_INVALIDA", "Regras que precisam de revisão administrativa", "#7c3aed"]
        ];
        secoes.forEach(([chave, titulo, cor]) => {
            if (grupos[chave].length > 0) {
                htmlGrid += `<div style="width: 100%; margin-top: 20px; grid-column: 1 / -1;"><h3 style="color: ${cor}; border-bottom: 2px solid ${cor}; padding-bottom: 5px;">${titulo}</h3></div>`;
                htmlGrid += grupos[chave].map(vaga => montarCardHTML(vaga, "historico")).join("");
            }
        });

        grid.innerHTML = htmlGrid;
    }
}

function montarCardHTML(vaga, contexto) {
    const status = vaga.status_elegibilidade || "PENDENTE";
    const temAlertaLogistico = Array.isArray(vaga.alertas_logisticos) && vaga.alertas_logisticos.length > 0;
    const classeVisual = (status === "COMPATIVEL" && temAlertaLogistico)
        ? "MEDIA"
        : (vaga.classe_visual || "MEDIA");
    const classes = { ALTA: "tag-alta", MEDIA: "tag-media", BAIXA: "tag-baixa", EXPIRADO: "tag-expirado" };
    const classeTag = classes[classeVisual] || "tag-media";
    const textoStatus = (status === "COMPATIVEL" && temAlertaLogistico)
        ? "Compatível (restrição logística)"
        : rotuloElegibilidade(status);

    const id = Number.parseInt(vaga.id || vaga.id_regra || vaga.id_edital, 10);
    const titulo = escapeHtml(vaga.nome_processo || vaga.titulo || "Edital sem título");
    const instituicao = escapeHtml(vaga.instituicao || "Instituição não informada");
    const cidade = escapeHtml(vaga.cidade || "Cidade não informada");
    const estado = escapeHtml(vaga.estado || "--");
    const tipo = escapeHtml(formatarBeneficio(vaga.tipo_beneficio || vaga.tipo_vaga));
    const prazo = escapeHtml(vaga.prazo_formatado || vaga.prazo_limite || "Não informado");
    const custoBruto = vaga.custo_medio_republica ?? vaga.custo_moradia_estimado;
    const custo = custoBruto === null || custoBruto === undefined ? "Não informado" : `R$ ${Number(custoBruto).toFixed(2)}`;
    const notaCorte = vaga.nota_corte_estimada !== null && vaga.nota_corte_estimada !== undefined
        ? `${Number(vaga.nota_corte_estimada)} pts (estimada)`
        : "Não informada";
    const moradia = vaga.tem_moradia === null || vaga.tem_moradia === undefined
        ? "Disponibilidade não informada"
        : (vaga.tem_moradia ? "Possui moradia estudantil" : "Não possui moradia estudantil");
    const distancia = vaga.distancia_km === null || vaga.distancia_km === undefined
        ? "Não informada"
        : `${Number(vaga.distancia_km).toFixed(1)} km`;
    const diagnostico = escapeHtml(vaga.diagnostico || "Classificação pendente.");
    const documentos = vaga.documentos_exigidos
        ? `<p class="meta-info" style="font-size: 0.8rem; margin-top: 6px;">📄 <strong>Documentos:</strong> ${escapeHtml(vaga.documentos_exigidos)}</p>`
        : "";
    const alertasLogisticos = Array.isArray(vaga.alertas_logisticos) && vaga.alertas_logisticos.length
        ? `<p class="meta-info"><strong>Logística:</strong> ${escapeHtml(vaga.alertas_logisticos.join(" "))}</p>`
        : "";
    const botaoMapa = Number.isInteger(id) && id > 0
        ? (contexto === "dashboard"
            ? `<button onclick="destacarRotaDashboard(${id}, this)" class="btn-destaque">🎯 Destacar no Mapa</button>`
            : `<button onclick="focarRotaHistorico(${id})" class="btn-foco-rota">🗺️ Ver no Mapa</button>`)
        : "";
    const linkDocumento = safeDocumentUrl(vaga.link_documento);
    const urlDetalhe = escapeHtml(vaga.url_detalhe || '#');
    const linkDocumentoHTML = linkDocumento
        ? `<a href="${escapeHtml(linkDocumento)}" target="_blank" rel="noopener noreferrer" class="link-edital">Documento oficial →</a>`
        : '<span class="meta-info">Documento externo não informado</span>';

    return `
        <div class="card-oportunidade-rico borda-${classeVisual}">
            <div>
                <div class="card-topo">
                    <span class="instituicao-badge">${instituicao} • ${cidade}/${estado}</span>
                    <span class="tag-probabilidade ${classeTag}">${escapeHtml(textoStatus)}</span>
                </div>
                <h3>${titulo}</h3>
                <p class="meta-info">🎯 <strong>Modalidade:</strong> ${tipo}</p>
                <p class="meta-info">📊 <strong>Nota de Corte de Referência:</strong> ${escapeHtml(notaCorte)}</p>
                <p class="meta-info">📅 <strong>Prazo Final:</strong> ${prazo}</p>
                <p class="meta-info">🏠 <strong>República Média:</strong> ${escapeHtml(custo)} · ${escapeHtml(moradia)}</p>
                <p class="meta-info">📏 <strong>Distância em Linha Reta:</strong> ${escapeHtml(distancia)}</p>
                <div class="diagnostico-box">
                    <strong>Triagem baseada nas regras cadastradas:</strong> ${diagnostico}
                </div>
                ${alertasLogisticos}
                ${documentos}
            </div>
            <div class="card-rodape">
                ${botaoMapa}
                <a href="${urlDetalhe}" class="link-edital">Ver detalhes →</a>
                ${linkDocumentoHTML}
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