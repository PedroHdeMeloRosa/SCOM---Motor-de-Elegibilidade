# CHANGELOG - SCOM Motor de Elegibilidade

Este documento regista as modificações estruturais, implementações e decisões arquiteturais do projeto.
### [feat/ai-edital-import] - 2026-11-21

- **Motor de Leitura Assistida por IA Local:** Integração com `smalot/pdfparser` (`^2.13`) e Ollama (`0.40.16` / `qwen2.5:3b`) para extração estruturada de editais via PDF ou texto colado.
- **Resiliência e Otimização de PDF:** Normalização UTF-8, recorte seguro em `6.000` caracteres, ajuste de contexto (`num_ctx: 4096`) para evitar HTTP 500 em CPU e fallback amigável (`503`) quando a IA estiver offline.
- **UX na Coordenação (sem alterar o fluxo manual):** Bloco recolhível *"Escanear Edital com IA"* com barra de progresso, cronômetro de execução, destaque visual nos campos sugeridos e busca automática de latitude/longitude via OpenStreetMap Nominatim.
- **Testes e Documentação:** Suíte `ImportacaoEditalIATest` cobrindo RBAC, validação de MIME, sanitização e fallback (**77 testes / 272 asserções** em SQLite e MariaDB) + guia de versões homologadas em `docs/AMBIENTE_DESENVOLVIMENTO.md`.

### [feat/interface-diretoria] - 2026-11-21
- **Banco de Dados (Migration Aditiva):** Criação da tabela `logs_auditoria` (`ator_id`, `acao`, `entidade_tipo`, `entidade_id`, `detalhes`, `criado_em`) e model `LogAuditoria`.
- **Painel da Diretoria:** Redesign em 4 abas (*Visão Geral*, *Estudantes*, *Equipe & Pontuação* e *Auditoria*) com identidade visual institucional, cards flutuantes por nível de acesso, ficha socioeconômica expansível e badges interativas com modal de confirmação e persistência de aba/scroll.
- **Inteligência & Gráficos:** Motor de contagem de alunos elegíveis a vagas prováveis, funil de engajamento, gráfico combinado (novos alunos vs. editais nos últimos 14 dias), Top 5 coordenadores mais ativos e gráfico de pizza alterável por ação.
- **Auditoria & Pontuação:** Registro automático das ações de Diretoria e Coordenação (`EDITAL_CRIADO`, `EDITAL_EDITADO`, `EDITAL_PUBLICADO`, `REGRA_RETIFICADA`, `EDITAL_ARQUIVADO`, `CONTA_ATUALIZADA`).
- **Qualidade & Demonstração:** Inclusão do `DiretoriaDemoSeeder` (idempotente, bloqueado em produção) e ampliação da suíte para **71 testes / 258 asserções** passando em SQLite e MariaDB.

## \[v0.2.0\] - 16/11/2026

**Branch:** `fix/estabilidade_site`
**Responsáveis:** Pedro Henrique

### 🎯 Contexto da Atualização

Esta etapa foi dedicada à estabilização do núcleo da aplicação, resolvendo inconsistências críticas apontadas no Diagnóstico Técnico. O foco principal foi garantir a integridade do banco de dados legado, implementar um controle de acesso rigoroso (RBAC) de ponta a ponta e ativar o cérebro real do Motor de Elegibilidade.

### 🔐 Segurança e Controle de Acesso (RBAC)

* **Blindagem do Login:** Refatoração completa do `AuthController.php`. Substituição da autenticação estática por consulta direta à tabela `cadastros` do banco de dados usando Eloquent e validação de hash Bcrypt.

* **Proteção contra Força Bruta (Rate Limiting):** Implementação de bloqueio temporário (60 segundos) após 5 tentativas falhas de login por IP/E-mail, utilizando o RateLimiter nativo do Laravel.

* **Tratamento de Contas Banidas:** O sistema agora verifica ativamente a coluna `status_conta`. Contas marcadas como `BANIDA` são bloqueadas imediatamente, mesmo que a senha esteja correta.

* **Middleware `EnsureUserHasRole`:** Criação de um interceptador de rotas para garantir que Estudantes não acessem áreas administrativas e vice-versa. Retorna HTTP 403 (Forbidden) em caso de violação e protege as APIs contra acessos anônimos.

* **Isolamento de Painéis:** Criação de views provisórias (`dashboard_coordenador.blade.php` e `dashboard_diretor.blade.php`) e rotas blindadas para evitar o erro 404 após o login das entidades administrativas.

### ⚙️ Banco de Dados e Motor de Regras

* **Alinhamento do Banco de Dados Oficial:** A configuração de conexão (`.env`) foi ajustada para apontar definitivamente para o banco `motor_elegibilidade` (abandonando a base provisória `scom`). Isso garante que o Laravel acesse nativamente o mesmo esquema SQL legado já enraizado no projeto, unificando a fonte da verdade dos dados.

* **Migrations Não-Destrutivas:** Adição da trava arquitetural `if (!Schema::hasTable(...))` em todas as migrations. O Laravel agora reconhece as tabelas do banco legado (`cadastros`, `editais`) sem tentar sobrescrevê-las, prevenindo perda de dados ou erros fatais (`SQLSTATE 1050`).

* **Conexão Real dos Models:** Criação dos Models `RegraElegibilidade` e `PerfilEstudante`. Estabelecimento das relações (`hasOne`, `belongsTo`, `hasMany`) para mapear o banco de dados relacional perfeitamente à orientação a objetos do Laravel.

* **Ativação do Motor de Elegibilidade:** O `PainelEstudanteController` deixou de usar regras fixas ("chumbadas"). O algoritmo agora cruza os dados em tempo real:

  * Renda do Aluno *vs.* Teto específico da Regra (`renda_maxima_per_capita`).

  * Tipo de Escola *vs.* Exigência de Escola Pública.

  * Etnia Declarada *vs.* Cotas Exclusivas PPI (`exige_etnia`).

  * Nota Estimada (ENEM/Provão Paulista) *vs.* Nota de Corte da Vaga.

  * Orçamento e Preferência de Mudança *vs.* Custo de República e Escopo Geográfico (Local, Estadual, Interestadual).

* **Barreira de Ficha Incompleta:** O motor agora identifica contas recém-criadas. Se a relação `$user->perfil` for nula, o sistema devolve uma resposta controlada que bloqueia o mapa e pede o preenchimento dos dados, evitando o erro de "adivinhar" que o aluno é de Sorocaba.

### 🗺️ UX/UI e Fluxo de Endereço

* **Visualização de Senha Dinâmica:** Substituição do botão de emoji genérico por SVGs manipulados via JavaScript (`ver_senha.svg` e `ocultar_senha.svg`) nas telas de Login e Cadastro.

* **Lógica de CEP Opcional e Não-Bloqueante:** O campo de CEP deixou de ser obrigatório no formulário e no back-end.

  * **Fluxo 1 (Automático):** Ao digitar o CEP, o sistema consulta o *ViaCEP* e preenche Estado e Cidade automaticamente.

  * **Fluxo 2 (Manual):** O aluno pode selecionar Estado e Cidade manualmente. O sistema consulta a API do *OpenStreetMap (Nominatim)* silenciosamente em segundo plano para capturar as coordenadas (Lat/Lng) da cidade, permitindo o uso do radar geográfico sem exigir CEP.

* **Tratamento de Dados de Inserção (`updateOrCreate`):** O salvamento da ficha agora lida perfeitamente com atualizações ou criações inéditas no mesmo endpoint, utilizando casts seguros no backend para evitar quebras por envio de strings no lugar de inteiros.

## \[v0.1.0\] - 13/11/2026

**Branch:** `chore/laravel-bootstrap`
**Responsável:** Pedro Henrique

### 1. Atualização e Validação do Ambiente (Strict Compliance)

* **PHP / XAMPP:** Atualizado rigorosamente para a versão **8.5.12** para garantir suporte total ao Laravel 13 sem conflitos de dependências.

* **Node.js & NPM:** Validadas as versões locais para **24.21.0** e **11.19.0**, respetivamente, para gestão do motor Vite.

### 2. Instalação e Estruturação do Framework (Laravel 13)

* Gerado o projeto Laravel 13 nativo (`13.*`) diretamente na raiz do repositório `SCOM---Motor-de-Elegibilidade`.

* Devido à presença de ficheiros legados (`index.html`, etc.), a instalação foi roteada temporariamente e os diretórios core (`app/`, `config/`, `public/`, `resources/`, `routes/`, etc.) foram unificados na raiz de forma limpa.

* **Banco de Dados:** Criada a base de dados `scom` (MariaDB 10.11.16). Ficheiro `.env` configurado e as migrations padrão de sessão/utilizador foram aplicadas com sucesso via `php artisan migrate`.

### 3. Integração de Frontend (Vite, Bootstrap 5 e Alpine.js)

* Dependências de UI instaladas via NPM (`bootstrap`, `@popperjs/core`, `alpinejs`). A pasta `node_modules` foi gerada e está no `.gitignore`.

* **Configuração do Compilador (Vite):**

  * **CSS:** Injeção do Bootstrap configurada no pipeline oficial do Laravel em `resources/css/app.css` (`@import 'bootstrap/dist/css/bootstrap.min.css';`).

  * **JavaScript:** Otimização do `resources/js/app.js` para importar os módulos do Bootstrap e inicializar o estado global do Alpine.js (`window.Alpine = Alpine; Alpine.start();`).

### 4. Migração da Interface Vanilla para Blade

* O antigo código estático de `index.html` foi migrado para o motor de templates oficial do Laravel em `resources/views/welcome.blade.php`.

* Inserida a diretiva de compilação `@vite(['resources/css/app.css', 'resources/js/app.js'])` na tag `<head>` para garantir o *Hot Module Replacement* (HMR).

* **Validação:** Servidor local (`php artisan serve`) e compilador frontend (`npm run dev`) testados em paralelo. Renderização do Bootstrap e Alpine validada com sucesso no navegador (Status 200).