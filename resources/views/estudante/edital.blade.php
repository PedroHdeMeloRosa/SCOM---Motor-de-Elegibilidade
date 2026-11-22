<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="scom-user-id" content="{{ auth()->id() ?? '' }}">
    <title>{{ $edital->instituicao }} - {{ $edital->nome_processo }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/favicon-uniele.jpg') }}?v=2">
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: #f5f7fa; color: #172033; }
        main { max-width: 900px; margin: 35px auto; padding: 0 18px; }
        .cartao { background: #fff; border: 1px solid #dce2ea; border-radius: 10px; padding: 22px; margin-bottom: 18px; }
        .voltar { display: inline-block; margin: 20px 4%; }
        dt { font-weight: 700; margin-top: 12px; } dd { margin-left: 0; }
        .btn-pdf {
            display: inline-block;
            background: #004488;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-right: 12px;
            margin-top: 4px;
        }
        .btn-pdf:hover { background: #003366; color: #fff; }
    </style>
</head>
<body>
<a class="voltar" href="{{ route('estudante.painel') }}">← Voltar ao painel</a>
<main>
    <section class="cartao">
        <h1>{{ $edital->instituicao }}</h1>
        <h2>{{ $edital->nome_processo }}</h2>
        <dl>
            <dt>Instituição</dt><dd>{{ $edital->instituicao }}</dd>
            <dt>Local</dt><dd>{{ $edital->cidade }}/{{ $edital->estado }} · Região {{ $edital->regiao }}</dd>
            <dt>Ano de referência</dt><dd>{{ $edital->ano_referencia }}</dd>
            <dt>Moradia estudantil</dt><dd>{{ $edital->tem_moradia_estudantil === null ? 'Não informado' : ($edital->tem_moradia_estudantil ? 'Disponível' : 'Não disponível') }}</dd>
            <dt>Custo médio de república</dt><dd>{{ $edital->custo_medio_republica === null ? 'Não informado' : 'R$ '.number_format((float) $edital->custo_medio_republica, 2, ',', '.') }}</dd>
            <dt>Documento de referência</dt>
            <dd>
                @if ($edital->arquivo_pdf)
                    <a href="{{ route('estudante.editais.pdf', $edital) }}" target="_blank" rel="noopener noreferrer" class="btn-pdf">
                        📄 Abrir / Baixar Cópia do Edital (PDF)
                    </a>
                @endif
                @if ($edital->link_documento && filter_var($edital->link_documento, FILTER_VALIDATE_URL) && in_array(parse_url($edital->link_documento, PHP_URL_SCHEME), ['http', 'https'], true))
                    <a href="{{ $edital->link_documento }}" target="_blank" rel="noopener noreferrer">Consultar link oficial externo →</a>
                @elseif (!$edital->arquivo_pdf)
                    Não há um documento ou link externo válido cadastrado. Consulte a página oficial da instituição.
                @endif
            </dd>
        </dl>
    </section>

    <section class="cartao">
        <h2>Regras e modalidades ativas</h2>
        @forelse ($regras as $regra)
            <article style="border-top:1px solid #e2e8f0;padding-top:16px;margin-top:16px;">
                <h3>{{ $regra->tipo_beneficio }} · versão {{ $regra->versao }}</h3>
                <p><strong>Vigência:</strong> {{ \Illuminate\Support\Carbon::parse($regra->prazo_inicio)->format('d/m/Y') }} até {{ \Illuminate\Support\Carbon::parse($regra->prazo_fim)->format('d/m/Y') }}</p>
                @if ($regra->renda_maxima_per_capita !== null)<p><strong>Renda máxima per capita:</strong> R$ {{ number_format((float) $regra->renda_maxima_per_capita, 2, ',', '.') }}</p>@endif
                @if ($regra->exige_escola_publica)<p>Exige ensino médio em escola pública.</p>@endif
                @if ($regra->exige_etnia)<p>Exige critério étnico-racial conforme a regra cadastrada. A classificação PPI considera a autodeclaração PRETA, PARDA ou INDÍGENA.</p>@endif
                @if ($regra->exige_pcd)<p>Exige a condição de pessoa com deficiência (PcD).</p>@endif
                @if ($regra->nota_corte_estimada !== null)<p><strong>Nota de corte estimada:</strong> {{ $regra->nota_corte_estimada }} (referência informativa, não critério automático nesta triagem).</p>@endif
                @if ($regra->documentos_exigidos)<p><strong>Documentos exigidos:</strong> {{ $regra->documentos_exigidos }}</p>@endif
            </article>
        @empty
            <p>Não há regras ativas cadastradas para este edital no momento.</p>
        @endforelse
    </section>
    <p><small>As informações e a triagem são orientativas. Confirme sempre os requisitos no edital oficial publicado pela instituição.</small></p>
</main>
</body>
</html>