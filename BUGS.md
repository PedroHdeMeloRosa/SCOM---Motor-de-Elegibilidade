# Bugs e dívidas técnicas — revisão de entrega

## Resolvidos em 2026-11-20

- [x] `bootstrap/cache` era junction quebrada com ReadOnly
- [x] `APP_KEY` ausente — causava 500
- [x] Primary key duplicada em migrations (SQLite)
- [x] Índice duplicado em colunas FK (SQLite)
- [x] `node_modules` corrompido
- [x] CI do GitHub Actions: PHP 8.3 → 8.5, cria `.env`, gera `APP_KEY`, roda `npm run build` antes dos testes

## Resolvidos em 2026-11-21

- [x] Vazamento de variáveis do `.env` para o PHPUnit (`phpunit.xml` agora força `<server>` e `<env>` para SQLite em memória)
- [x] Aviso de resolução de asset no `vite build` em `resources/css/app.css`
- [x] **Validação completa em MariaDB (`scom_test`)**: corrigido tipo de FK legada (`INT signed`) em `LegacySchemaMigrationSafetyTest`, criado perfil isolado `phpunit.mariadb.xml` (eliminando `errno: 150`, erro `419 CSRF` e acionamento indevido do `GuardsDemoSeeders`)
- [x] **CRUD de editais do coordenador + testes**: edição (`PATCH /coordenacao/editais/{edital}`), publicação, arquivamento e retificação versionada já implementados e cobertos por testes automatizados
- [x] **Testes ampliados de governança, RBAC, segurança e seeders**: `GovernancaESegurancaTest` cobrindo isolamento entre perfis, proteção entre múltiplos diretores, bloqueio de escalação/IDOR/XSS, rate limit e idempotência de seeders
- [x] Alinhamento de `minlength="12"` na view `estudante/cadastro.blade.php` com o `AuthController`
- [x] Filtro de mobilidade no Dashboard e destaque visual de restrição logística em `public/assets/js/painel_estudante.js`
- [x] Documentação de escopo, limitações e comando de validação em MariaDB adicionados ao `README.md`
- [x] Remoção do legado PHP puro e HTML estático (`api/`, `editor/`, `estudante/`, `index.html`, `gerar_contas.php`, `public/assets/js/engine.js`) — branch `fix/estabilidade-site` concluída e integrada
- [x] **Painel da Diretoria e Auditoria (`feat/interface-diretoria`)**:
  - Criação da tabela aditiva `logs_auditoria` (`LogAuditoria`) e registro automático das ações de Diretoria e Coordenação
  - Redesign do painel em 4 abas (*Visão Geral*, *Estudantes*, *Equipe & Pontuação* e *Auditoria*) com cards flutuantes, ficha socioeconômica expansível, badges interativas com modal de confirmação e persistência de aba/scroll
  - Motor de elegíveis prováveis, funil de engajamento, gráfico combinado (alunos vs. editais em 14 dias), Top 5 coordenadores mais ativos e gráfico de pizza alterável por ação
  - Correção de tipagem (`toArray()`) na chamada de `AvaliadorElegibilidade::avaliar()` e adequação do `DiretoriaDemoSeeder` aos valores válidos do `ENUM` `nivel_mobilidade`
  - Suíte ampliada para **71 testes / 258 asserções** passando em SQLite e MariaDB

- [x] **IA para leitura assistida de editais (`feat/ai-edital-import`)**:
  - Integração com `smalot/pdfparser` e Ollama local (`qwen2.5:3b` via `/api/chat` com `format: json`)
  - Serviços `ExtratorTextoEdital` e `AnalisadorEditalIA` com sanitização estrita de JSON, bloqueio de links `javascript:` e validação de enums/datas
  - Endpoint `POST /coordenacao/editais/escanear-ia` restrito a `COORDENADOR` com fallback gracioso (`503`) quando o Ollama está offline
  - Botão recolhível *"Escanear Edital com IA"* no painel da Coordenação mantendo revisão humana obrigatória e fluxo manual intacto
  - Guia de instalação do Ollama no Windows adicionado em `docs/AMBIENTE_DESENVOLVIMENTO.md` e suíte ampliada para **77 testes / 272 asserções**

## Nota de ambiente (não bloqueia merge)

- [x] `public/hot` pode falhar ao ser gravado pelo Vite quando o repositório é clonado dentro do OneDrive (bloqueio de sincronização do Windows). Contornado mantendo o projeto fora do OneDrive (ex.: `C:\xampp\htdocs\...`) e mantendo `/public/hot` no `.gitignore`.

## Próximas evoluções planejadas (a partir da `main`)

- [ ] **Inteligência de editais e auditoria avançada (`feat/diretoria-ranking-auditoria`)** — Ranking de Editais na Visão Geral (elegíveis prováveis, comentários e alertas de prazo), filtros por ação/responsável na aba Auditoria e exportação em CSV
- [ ] **Novas funcionalidades da área do estudante** — melhorias de experiência e recursos adicionais (ex.: favoritos) em branch dedicada


## Backlog futuro (fora do escopo atual)
- [ ] **Governança colegiada da Diretoria (`50% + 1`)** — removido do escopo atual; mantido apenas como planejamento futuro caso haja necessidade de aprovação por maioria para alterar/remover contas `DIRETOR`
