# Bugs e dívidas técnicas — revisão de entrega

## Resolvidos em 2026-11-20

- [x] `bootstrap/cache` era junction quebrada com ReadOnly
- [x] `APP_KEY` ausente — causava 500
- [x] Primary key duplicada em migrations (SQLite)
- [x] Índice duplicado em colunas FK (SQLite)
- [x] `node_modules` corrompido
- [x] CI do GitHub Actions: PHP 8.3 → 8.5, cria `.env`, gera `APP_KEY`, roda `npm run build` antes dos testes (era: 5 fails + 33 warnings → 52 passed)

## Pendências (não bloqueiam demo)

- [ ] Documentação de escopo no README
- [ ] Remover legado PHP puro (`api/controllers/`, `index.html`)
- [ ] `public/hot` não é criado pelo Vite no OneDrive — avaliar mover para `C:\dev\SCOM`
## Pendências para branches futuras (não bloqueiam a `fix`)

- [ ] **CRUD de editais do coordenador** — rotas `/editais` (POST), `/editais/{id}` (PUT), `/editais/{id}/arquivar` (PATCH) não existem. Coordenador cria/publica (via `AuthAndStudentFlowTest`), mas **não edita nem arquiva**. Branch planejada: `feat/coordenador-crud-editais` (sairá da `main` após merge da `fix`).
- [ ] **Testes de edição/arquivamento** movidos para essa branch junto com a feature.
- [ ] **IA para leitura de editais** — decisão do grupo (obrigatório ou opcional?).