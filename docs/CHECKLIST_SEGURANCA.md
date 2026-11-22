# Checklist de Inspeção Manual de Segurança — SCOM

Guia de verificação manual (QA de segurança) para validar controles de acesso, proteção contra força bruta, integridade de consultas, sanitização de dados e resiliência do módulo de IA local antes de entregas ou apresentações.

> **Escopo:** Executar exclusivamente em ambiente local de desenvolvimento/homologação (`127.0.0.1:8000`), com backup prévio do banco `motor_elegibilidade`.

---

## 1. Controle de Acesso e Isolamento de Perfis (RBAC e IDOR)

Objetivo: confirmar que nenhum usuário consegue acessar telas, ações ou registros de outro perfil ou de outro estudante.

**Acesso no navegador**: localhost:8000/endereços teste.

| # | Verificação Manual | Comportamento Esperado | Status |
|---|---|---|---|
| 1.1 | **Visitante em rotas protegidas:** Abrir `/diretoria/dashboard`, `/coordenacao/dashboard` e a área do estudante em aba anônima. | Redirecionamento para `/login` (ou `401` em rotas de API JSON). | [x] |
| 1.2 | **Estudante tentando acessar Diretoria/Coordenação:** Logado como `ESTUDANTE`, digitar manualmente `/diretoria/dashboard` e `/coordenacao/dashboard` na barra de endereço. | Bloqueio pelo middleware `EnsureUserHasRole` (`403 Forbidden` ou redirecionamento seguro). | [x] |
| 1.3 | **Coordenador tentando acessar Diretoria:** Logado como `COORDENADOR`, tentar abrir `/diretoria/dashboard` ou disparar alteração de conta de usuário. | Bloqueio imediato (`403 Forbidden`). | [x] |
| 1.4 | **Isolamento entre estudantes (IDOR):** Logado como Estudante A, tentar consultar ou alterar dados da ficha socioeconômica informando o ID do Estudante B na URL ou no corpo da requisição. | O backend deve ignorar IDs externos e usar apenas `auth()->id()`, impedindo leitura ou sobrescrita de terceiros. | [x] |
| 1.5 | **Proteção de contas da Diretoria:** Logado como `DIRETOR`, verificar se a própria conta e outras contas `DIRETOR` aparecem bloqueadas para edição/banimento na interface e rejeitam `PATCH` direto. | Erro de validação/regra de negócio impedindo autoalteração ou alteração de outro diretor. | [x] |
| 1.6 | **Revogação imediata de sessão:** Com um usuário logado em outro navegador, alterar seu status para `BANIDA` ou rebaixar um `COORDENADOR` para `ESTUDANTE` pelo painel da Diretoria e recarregar a página no outro navegador. | A conta banida é deslogada na próxima requisição; o coordenador rebaixado perde acesso imediato ao painel da Coordenação. | [x] |

---

## 2. Autenticação, Sessão e Força Bruta (Rate Limiting)

Objetivo: validar que o login e o cadastro resistem a tentativas repetidas e não vazam informações sobre contas existentes.

| # | Verificação Manual | Comportamento Esperado | Status |
|---|---|---|---|
| 2.1 | **Bloqueio de força bruta no Login:** Inserir um e-mail qualquer e errar a senha consecutivamente (acima do limite configurado no rate limiter). | O sistema passa a bloquear novas tentativas temporariamente (HTTP `429 Too Many Requests` ou mensagem de limite excedido). | [x] |
| 2.2 | **Bloqueio de força bruta no Cadastro:** Submeter o formulário de registro repetidamente em curto intervalo. | Após 5 tentativas, o rate limit bloqueia novas submissões. | [x] |
| 2.3 | **Prevenção de enumeração de contas:** Testar login com um e-mail inexistente e depois com um e-mail válido + senha errada. | A mensagem de erro exibida ao usuário deve ser **idêntica** nos dois casos, sem revelar se o e-mail existe na base. | [x] |
| 2.4 | **Política de senha e limite do Bcrypt:** Tentar cadastrar senhas muito curtas ou excessivamente longas (incluindo caracteres multibyte/emojis acima de 72 bytes). | Validação rejeita com erro claro (`422`), sem truncamento silencioso. | [x] |
| 2.5 | **Logout limpo:** Clicar em *Sair* e usar o botão *Voltar* do navegador tentando reenviar um formulário. | Sessão invalidada e token CSRF renovado; ações protegidas não são executadas. | [x] |

---

## 3. Integridade de Consultas (Prevenção a SQL Injection)

Objetivo: garantir que todos os filtros, buscas e ordenações utilizem *prepared statements* (Eloquent/Query Builder) e listas fechadas (*allowlists*).

| # | Verificação Manual / Auditoria de Código | Comportamento Esperado | Status |
|---|---|---|---|
| 3.1 | **Busca e filtros da Diretoria:** Digitar caracteres especiais de pontuação (aspas simples, aspas duplas, `%`, `_`, `;`, `--`) nos campos `busca`, `prefixo_estudante` e `prefixo_equipe`. | A página responde `200 OK` normalmente, tratando o texto como busca literal, sem erros SQL ou alteração do conjunto de resultados fora do filtro. | [x] |
| 3.2 | **Parâmetros de ordenação manipulados na URL:** Alterar manualmente na barra de endereço os parâmetros `ordenar_estudantes`, `direcao_estudantes`, `metrica_equipe`, `ordem_pontos` e `data_auditoria` para valores inexistentes. | O `GestaoUsuarioController::extrairFiltros()` descarta valores fora da *allowlist* e aplica o padrão seguro sem erro `500`. | [x] |
| 3.3 | **Revisão estática de código:** Procurar por `DB::raw`, `whereRaw`, `orderByRaw` e `selectRaw` em `app/`. | Garantir que nenhuma variável vinda de `\$request` é concatenada diretamente em expressões SQL brutas. | [x] |

---

## 4. Validação de Entrada, XSS e CSRF

Objetivo: verificar que entradas malformadas ou marcações HTML/JS não são executadas no navegador e que formulários exigem token CSRF.

| # | Verificação Manual | Comportamento Esperado | Status |
|---|---|---|---|
| 4.1 | **Escape de saída (XSS armazenado):** Como `COORDENADOR`, cadastrar um edital de teste com caracteres HTML (`<b>`, `<script>`) no título ou descrição e visualizá-lo no painel do Estudante e na Diretoria. | O Blade (`{{ }}`) exibe o texto literalmente escapado, sem renderizar HTML nem executar scripts. | [ ] |
| 4.2 | **Bloqueio de URLs perigosas:** Tentar salvar um edital com link oficial iniciando por `javascript:` ou esquema inválido (tanto manualmente quanto via retorno da IA). | Validação rejeita o link no formulário manual e o sanitizador da IA descarta o campo automaticamente. | [ ] |
| 4.3 | **Proteção CSRF:** Abrir o DevTools (`F12`), remover ou corromper o campo oculto `_token` de um formulário `POST`/`PATCH` e enviar. | O Laravel rejeita a requisição com erro `419 Page Expired`. | [ ] |
| 4.4 | **Limites numéricos na Ficha do Estudante:** Enviar valores negativos ou absurdos para renda, orçamento ou nota do ENEM via formulário ou requisição direta. | API/Controller rejeita com `422 Unprocessable Entity`. | [ ] |

---

## 5. Segurança do Módulo de IA Local (PDF e Ollama)

Objetivo: garantir que o upload de arquivos e a comunicação com o Ollama não abram brechas nem quebrem o fluxo manual.

| # | Verificação Manual | Comportamento Esperado | Status |
|---|---|---|---|
| 5.1 | **Restrição de perfil no endpoint de IA:** Logado como `ESTUDANTE` ou `DIRETOR`, tentar enviar um `POST` para `/coordenacao/editais/escanear-ia`. | Bloqueio `403 Forbidden`. | [ ] |
| 5.2 | **Validação de arquivo (MIME e tamanho):** Tentar enviar um arquivo `.txt`, `.php` ou `.jpg` renomeado para `.pdf`, ou um PDF acima de 10 MB. | Rejeição com `422` antes de acionar o parser ou o Ollama. | [ ] |
| 5.3 | **Fallback offline (Resiliência):** Encerrar o serviço do Ollama localmente e clicar em *Escanear e Preencher Formulário*. | Retorno `503` com alerta amigável na interface; o cadastro manual continua 100% funcional. | [ ] |
| 5.4 | **Revisão humana obrigatória:** Escanear um texto/PDF e verificar o banco de dados antes de clicar em *Salvar edital como rascunho*. | A IA apenas preenche os campos no front-end; nenhum registro é gravado no banco automaticamente. | [ ] |

---

## 6. Dados Sensíveis e Configuração de Ambiente

| # | Verificação Manual | Comportamento Esperado | Status |
|---|---|---|---|
| 6.1 | **Criptografia de dado sensível (`raca_cor`):** Consultar a tabela `perfis_estudantes` diretamente no phpMyAdmin após salvar uma ficha. | A coluna `raca_cor` deve estar armazenada cifrada (string base64/JSON do Laravel), nunca em texto puro. | [ ] |
| 6.2 | **Exposição de arquivos sensíveis:** Tentar acessar `/.env` ou `/storage/logs/laravel.log` diretamente pelo navegador. | Acesso negado (`403` ou `404`). | [ ] |
| 6.3 | **Trava dos seeders demonstrativos:** Confirmar que `SCOM_ALLOW_DEMO_SEEDERS=false` no `.env` bloqueia execução acidental de seeders de demonstração. | Comando aborta com aviso de segurança. | [ ] |
| 6.4 | **Suíte automatizada de regressão:** Rodar `php artisan test` e `.\vendor\bin\phpunit -c phpunit.mariadb.xml`. | **77 passed / 272 assertions** em ambos os bancos. | [ ] |

---

## 7. Registro da Rodada de Inspeção

- **Data:** `____/____/2026`
- **Responsável:** `____________________`
- **Branch / Commit:** `____________________`
- **Observações / Achados:**
  - *(Registrar aqui qualquer anomalia encontrada para abertura de item em `BUGS.md`.)*