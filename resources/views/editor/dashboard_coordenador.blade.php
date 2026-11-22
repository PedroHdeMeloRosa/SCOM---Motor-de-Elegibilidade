<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="scom-user-id" content="{{ auth()->id() ?? '' }}">
    <title>SCOM - Gestão de Editais</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: #f5f7fa; color: #172033; }
        .painel { max-width: 1200px; margin: 30px auto; padding: 0 18px; }
        .cartao { background: #fff; border: 1px solid #dce2ea; border-radius: 10px; padding: 20px; margin-bottom: 18px; }
        .grade { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 12px; }
        input[type=checkbox] { width: auto; }
        button { padding: 9px 14px; border: 0; border-radius: 6px; cursor: pointer; background: #174a80; color: #fff; }
        .secundario { background: #475569; } .perigo { background: #a83232; }
        .mensagem { padding: 12px; border-radius: 6px; margin-bottom: 15px; background: #e5f7eb; }
        .erro { color: #a11; margin: 8px 0; }
        .regra { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-top: 12px; }
        .cabecalho { background: #174a80; color: #fff; padding: 16px 4%; display: flex; justify-content: space-between; align-items: center; gap: 15px; }
        .cabecalho form { margin: 0; } .cabecalho button { background: transparent; border: 1px solid #fff; }
        small { color: #475569; }
    </style>
</head>
<body>
<header class="cabecalho">
    <div><strong>SCOM · Coordenação</strong><br><small>Gestão de editais e regras</small></div>
    <div>{{ auth()->user()->nome }}
        <form method="POST" action="{{ route('logout') }}" style="display:inline">@csrf<button type="submit">Sair</button></form>
    </div>
</header>
<main class="painel">
    @if (session('status')) <div class="mensagem" role="status">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="cartao erro" role="alert"><strong>Não foi possível concluir:</strong><ul>@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div> @endif

    <section class="cartao">
        <h1>Novo edital</h1>
        <p>O cadastro começa como rascunho. Depois de revisar as informações e a regra, publique o edital.</p>

        <details id="box-ia-edital" style="background:#f0f6ff; border:1px solid #bfdbfe; border-radius:8px; padding:14px; margin-bottom:16px;">
            <summary style="cursor:pointer; font-weight:700; color:#174a80;">
                🤖 Escanear Edital com IA (PDF ou Texto) — preenchimento assistido
            </summary>
            <div style="margin-top:12px;">
                <p style="margin:0 0 10px; font-size:0.9rem; color:#334155;">
                    Envie o PDF do edital <strong>ou</strong> cole o trecho principal abaixo. A IA local apenas preenche os campos do formulário para você revisar antes de salvar.
                </p>
                <div class="grade">
                    <div>
                        <label for="ia_arquivo_pdf">Arquivo PDF (opcional, até 10 MB)</label>
                        <input id="ia_arquivo_pdf" type="file" accept="application/pdf">
                    </div>
                </div>
                <label for="ia_texto_edital">Ou cole o texto do edital</label>
                <textarea id="ia_texto_edital" rows="4" placeholder="Cole aqui o texto do edital (instituição, prazos, renda máxima, cotas, documentos)..."></textarea>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <button type="button" id="btn-escanear-ia" onclick="escanearEditalComIA()">
                        🤖 Escanear e Preencher Formulário
                    </button>
                    <small id="ia-status-texto"></small>
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
                <div id="ia-feedback" style="display:none; margin-top:10px; padding:10px 12px; border-radius:6px; font-size:0.9rem;"></div>
            </div>
        </details>

        <form method="POST" action="{{ route('coordenacao.editais.store') }}">
            @csrf
            <div class="grade">
                <div><label for="instituicao">Instituição</label><input id="instituicao" name="instituicao" maxlength="100" required value="{{ old('instituicao') }}"></div>
                <div><label for="nome_processo">Nome do processo</label><input id="nome_processo" name="nome_processo" maxlength="150" required value="{{ old('nome_processo') }}"></div>
                <div><label for="cidade">Cidade do campus</label><input id="cidade" name="cidade" maxlength="100" required value="{{ old('cidade') }}"></div>
                <div><label for="estado">Estado (UF)</label><input id="estado" name="estado" maxlength="2" minlength="2" required value="{{ old('estado') }}"></div>
                <div><label for="regiao">Região</label><select id="regiao" name="regiao" required>@foreach (['SUDESTE','SUL','CENTRO_OESTE','NORDESTE','NORTE','INTERNACIONAL'] as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach</select></div>
                <div><label for="latitude">Latitude</label><input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" required value="{{ old('latitude') }}"></div>
                <div><label for="longitude">Longitude</label><input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" required value="{{ old('longitude') }}"></div>
                <div><label for="ano_referencia">Ano de referência</label><input id="ano_referencia" name="ano_referencia" type="number" min="2000" max="2155" required value="{{ old('ano_referencia', date('Y')) }}"></div>
                <div><label for="custo_medio_republica">Custo médio de república (R$)</label><input id="custo_medio_republica" name="custo_medio_republica" type="number" min="0" step="0.01" value="{{ old('custo_medio_republica') }}"></div>
                <div><label for="tem_moradia_estudantil">Moradia estudantil</label><select id="tem_moradia_estudantil" name="tem_moradia_estudantil"><option value="0">Não</option><option value="1">Sim</option></select></div>
                <div><label for="link_documento">Link/documento oficial</label><input id="link_documento" name="link_documento" maxlength="255" value="{{ old('link_documento') }}"></div>
            </div>
            <h3>Primeira regra de elegibilidade</h3>
            <div class="grade">
                <div><label for="tipo_beneficio">Tipo de benefício</label><select id="tipo_beneficio" name="regra[tipo_beneficio]" required>@foreach (['ISENCAO_TAXA','COTA_RENDA','COTA_ESCOLA_PUBLICA','COTA_RACIAL','AMPLA_CONCORRENCIA'] as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach</select></div>
                <div><label for="renda_maxima_per_capita">Renda máxima per capita (R$)</label><input id="renda_maxima_per_capita" name="regra[renda_maxima_per_capita]" type="number" min="0" step="0.01"></div>
                <div><label for="nota_corte_estimada">Nota de corte estimada (referência)</label><input id="nota_corte_estimada" name="regra[nota_corte_estimada]" type="number" min="0" max="1000"></div>
                <div><label for="prazo_inicio">Início das inscrições</label><input id="prazo_inicio" name="regra[prazo_inicio]" type="date" required></div>
                <div><label for="prazo_fim">Fim das inscrições</label><input id="prazo_fim" name="regra[prazo_fim]" type="date" required></div>
            </div>
            <div class="grade">
                <label><input type="hidden" name="regra[exige_escola_publica]" value="0"><input type="checkbox" name="regra[exige_escola_publica]" value="1"> Exige escola pública</label>
                <label><input type="hidden" name="regra[exige_etnia]" value="0"><input type="checkbox" name="regra[exige_etnia]" value="1"> Exige critério étnico-racial PPI</label>
                <label><input type="hidden" name="regra[exige_pcd]" value="0"><input type="checkbox" name="regra[exige_pcd]" value="1"> Exige condição PcD</label>
            </div>
            <label for="documentos_exigidos">Documentos exigidos</label><textarea id="documentos_exigidos" name="regra[documentos_exigidos]" rows="3"></textarea>
            <button type="submit">Salvar edital como rascunho</button>
        </form>
    </section>

    <section class="cartao">
        <h2>Editais cadastrados</h2>
        @forelse ($editais as $edital)
            <article class="regra">
                <h3>{{ $edital->instituicao }} · {{ $edital->nome_processo }}</h3>
                <p>{{ $edital->cidade }}/{{ $edital->estado }} · {{ $edital->ano_referencia }} · <strong>{{ $edital->status_publicacao }}</strong></p>
                <form method="POST" action="{{ route('coordenacao.editais.publicar', $edital) }}" style="display:inline">@csrf<button type="submit">Publicar</button></form>
                <form method="POST" action="{{ route('coordenacao.editais.arquivar', $edital) }}" style="display:inline">@csrf<button class="secundario" type="submit">Arquivar</button></form>
                <details style="margin-top:12px;">
                    <summary>Editar dados do edital</summary>
                    <form method="POST" action="{{ route('coordenacao.editais.atualizar', $edital) }}">
                        @csrf @method('PATCH')
                        <div class="grade">
                            <label>Instituição<input name="instituicao" maxlength="100" required value="{{ $edital->instituicao }}"></label>
                            <label>Nome do processo<input name="nome_processo" maxlength="150" required value="{{ $edital->nome_processo }}"></label>
                            <label>Cidade<input name="cidade" maxlength="100" required value="{{ $edital->cidade }}"></label>
                            <label>Estado<input name="estado" minlength="2" maxlength="2" required value="{{ $edital->estado }}"></label>
                            <label>Região<select name="regiao" required>@foreach (['SUDESTE','SUL','CENTRO_OESTE','NORDESTE','NORTE','INTERNACIONAL'] as $item)<option value="{{ $item }}" @selected($edital->regiao === $item)>{{ $item }}</option>@endforeach</select></label>
                            <label>Latitude<input name="latitude" type="number" step="any" min="-90" max="90" required value="{{ $edital->latitude }}"></label>
                            <label>Longitude<input name="longitude" type="number" step="any" min="-180" max="180" required value="{{ $edital->longitude }}"></label>
                            <label>Ano<input name="ano_referencia" type="number" min="2000" max="2155" required value="{{ $edital->ano_referencia }}"></label>
                            <label>Custo de república<input name="custo_medio_republica" type="number" min="0" step="0.01" value="{{ $edital->custo_medio_republica }}"></label>
                            <label>Moradia estudantil<select name="tem_moradia_estudantil"><option value="0" @selected(!$edital->tem_moradia_estudantil)>Não</option><option value="1" @selected($edital->tem_moradia_estudantil)>Sim</option></select></label>
                            <label>Documento/link<input name="link_documento" maxlength="255" value="{{ $edital->link_documento }}"></label>
                        </div>
                        <button type="submit">Salvar dados do edital</button>
                    </form>
                </details>
                @foreach ($edital->regras as $regra)
                    <div class="regra">
                        <strong>Regra #{{ $regra->id }} · {{ $regra->tipo_beneficio }} · versão {{ $regra->versao }} · {{ $regra->status_regra }}</strong>
                        <p>Vigência: {{ $regra->prazo_inicio }} até {{ $regra->prazo_fim }}</p>
                        @foreach ($regra->retificacoesOrigem as $historico)
                            <small>Retificada em {{ optional($historico->data_retificacao)->format('d/m/Y H:i') }}: versão nova #{{ $historico->id_regra_nova }}. Motivo: {{ $historico->motivo_retificacao }}</small><br>
                        @endforeach
                        <details>
                            <summary>Retificar regra (cria nova versão e preserva a anterior)</summary>
                            <form method="POST" action="{{ route('coordenacao.regras.retificar', $regra) }}">
                                @csrf
                                <label>Motivo da retificação<input name="motivo_retificacao" minlength="10" maxlength="255" required></label>
                                <label>Tipo de benefício<select name="tipo_beneficio" required>@foreach (['ISENCAO_TAXA','COTA_RENDA','COTA_ESCOLA_PUBLICA','COTA_RACIAL','AMPLA_CONCORRENCIA'] as $item)<option value="{{ $item }}" @selected($regra->tipo_beneficio === $item)>{{ $item }}</option>@endforeach</select></label>
                                <div class="grade">
                                    <label>Renda máxima<input name="renda_maxima_per_capita" type="number" min="0" step="0.01" value="{{ $regra->renda_maxima_per_capita }}"></label>
                                    <label>Nota estimada<input name="nota_corte_estimada" type="number" min="0" max="1000" value="{{ $regra->nota_corte_estimada }}"></label>
                                    <label>Início<input name="prazo_inicio" type="date" required value="{{ $regra->prazo_inicio }}"></label>
                                    <label>Fim<input name="prazo_fim" type="date" required value="{{ $regra->prazo_fim }}"></label>
                                </div>
                                <label><input type="hidden" name="exige_escola_publica" value="0"><input type="checkbox" name="exige_escola_publica" value="1" @checked($regra->exige_escola_publica)> Exige escola pública</label>
                                <label><input type="hidden" name="exige_etnia" value="0"><input type="checkbox" name="exige_etnia" value="1" @checked($regra->exige_etnia)> Exige critério étnico-racial PPI</label>
                                <label><input type="hidden" name="exige_pcd" value="0"><input type="checkbox" name="exige_pcd" value="1" @checked($regra->exige_pcd)> Exige condição PcD</label>
                                <label>Documentos exigidos<textarea name="documentos_exigidos" rows="2">{{ $regra->documentos_exigidos }}</textarea></label>
                                <button type="submit">Criar nova versão</button>
                            </form>
                        </details>
                    </div>
                @endforeach
            </article>
        @empty
            <p>Nenhum edital cadastrado.</p>
        @endforelse
        {{ $editais->links() }}
    </section>
</main>

<script>
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

    // Inicia barra de progresso e cronômetro
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

        // Avança suavemente até 92% (desacelerando perto do fim)
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
}
</script>
</body>
</html>
