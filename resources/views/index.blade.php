<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNIELEGI - Motor de Elegibilidade</title>
    
    <!-- Favicon com o tipo corrigido para imagem jpeg e usando asset() -->
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/UNIELEGI logo.jpg') }}">
    
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <script src="{{ asset('assets/js/engine.js') }}" defer></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <!-- Barra de Navegação -->
    <header class="navbar">
        <div class="logo">
            <img src="{{ asset('assets/img/UNIELEGI LOGO 2026.svg') }}" alt="Logo UNIELEGI" class="logo-img">
            <span>UNILEGI</span>
        </div>
        <nav>
            <ul>
                <li><a href="#inicio">Início</a></li>
                <li><a href="#sobre">Sobre o Projeto</a></li>
                <li><a href="#oportunidades">Oportunidades</a></li>
                <!-- Conectado à rota do painel recém-criada -->
                <li><a href="{{ route('login') }}" class="btn-primario" style="padding: 8px 15px;">Entrar</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section (Boas-vindas) com Imagem Mesclada no Degradê -->
    <section id="inicio" class="hero" style="background: linear-gradient(rgba(0, 68, 136, 0.85), rgba(10, 25, 47, 0.95)), url({{ asset('assets/img/estudantes.webp') }}); background-size: cover; background-position: center; background-repeat: no-repeat;">
        
        <div class="hero-content">
            <h1>UNILEGI</h1>
            <p class="slogan">Mentoria e integração universitária de confiança há 0 anos.</p>
            <p class="sub-slogan">Descubra cotas, isenções e oportunidades de forma simples, direta e sem burocracia.</p>
            
            <a href="{{ route('login') }}" class="btn-primario">Descubra Suas Chances</a>
        </div>
    </section>

    <!-- Contexto do Projeto (Problema e Solução) -->
    <section id="sobre" class="contexto">
        <div class="bloco-texto">
            <h2>O sonho da universidade não pode morrer em burocracia.</h2>
            <p>A perda de oportunidades de acesso ao ensino superior raramente é por falta de vontade. Ela acontece pela dificuldade real de interpretar e aplicar as dezenas de regras confusas dos editais.</p>
            <p>Quantas mentes brilhantes ficam de fora por um erro de papelada? Milhares de alunos perdem prazos de isenção de taxas ou deixam de solicitar cotas simplesmente por não saberem como comprovar sua própria história e elegibilidade.</p>
        </div>
        <div class="bloco-texto">
            <h2>Nós traduzimos as regras para você.</h2>
            <p>A UNILEGI nasceu para ser a sua bússola. Diferente das soluções existentes, nós não somos apenas um calendário ou buscador de notícias. Construímos um sistema inteligente que cruza o perfil socioeconômico com os critérios de cada universidade.</p>
            <p>Em vez de entregar PDFs de 50 páginas, nós entregamos um plano de ação claro, dedicado <b>especialmente para você</b>.

            Clique em <b>"Descubra Suas Chances"</b> no topo da página e veja quais oportunidades você pode acessar hoje mesmo.
            </p>
        </div>
    </section>

    <!-- Vitrine de Oportunidades -->
    <section id="oportunidades" class="secao-resultados">
        <div class="container-resultados">
            <h2 class="titulo-resultados">Suas Oportunidades</h2>
            <div id="resultados" class="grid-cards">
                <!-- Estado Inicial (Vazio) -->
                <div class="estado-vazio">
                    <p>Faça login e preencha sua ficha para destravar as vagas compatíveis com o seu perfil.</p>
                    <br>
                    <a href="{{ route('login') }}" class="btn-primario">Acessar Sistema</a>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <p>UNILEGI © 2026. Desenvolvido para a disciplina de SCOM por Pedro H. Rosa e Roberta R. Pompeu.</p>
    </footer>

</body>
</html>