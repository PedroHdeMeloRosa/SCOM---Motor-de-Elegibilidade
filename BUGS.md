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
- [x] **Testes ampliados de governança, RBAC, segurança e seeders**: `GovernancaESegurancaTest` cobrindo isolamento entre perfis, proteção entre múltiplos diretores, bloqueio de escalação/IDOR/XSS, rate limit e idempotência de seeders (69 testes / 250 asserções passando em SQLite e MariaDB)
- [x] Alinhamento de `minlength="12"` na view `estudante/cadastro.blade.php` com o `AuthController`
- [x] Filtro de mobilidade no Dashboard e destaque visual de restrição logística em `public/assets/js/painel_estudante.js`
- [x] Documentação de escopo, limitações e comando de validação em MariaDB adicionados ao `README.md`
- [x] Remoção do legado PHP puro e HTML estático (`api/`, `editor/`, `estudante/`, `index.html`, `gerar_contas.php`, `public/assets/js/engine.js`)

## Nota de ambiente (não bloqueia merge)

- [ ] `public/hot` não é criado pelo Vite quando o repositório está dentro do OneDrive — recomendação: clonar o projeto fora do OneDrive (ex: `C:\xampp\htdocs\...` ou `C:\dev\SCOM`).

## Evoluções planejadas para branches futuras (a partir da `main` atualizada)

- [ ] **Governança colegiada da Diretoria (`feat/governanca-diretoria`)** — fluxo de aprovação por maioria (`50% + 1` dos diretores ativos) caso seja necessário alterar ou remover outra conta de nível `DIRETOR`
- [ ] **IA para leitura de editais (`feat/ai-edital-import`)** — decisão do grupo (obrigatório ou opcional?), sempre mantendo revisão humana obrigatória
- [ ] **Novas funcionalidades da área do estudante** — branch dedicada criada após merge da `fix/estabilidade-site`