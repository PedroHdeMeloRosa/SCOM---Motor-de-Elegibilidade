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