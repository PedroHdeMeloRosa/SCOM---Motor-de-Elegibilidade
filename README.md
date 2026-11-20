# SCOM — Motor de Elegibilidade

Aplicação web para cadastrar estudantes, manter uma ficha socioeconômica e comparar as informações declaradas com critérios de elegibilidade associados a editais e regras cadastradas.

O resultado do motor é uma **triagem preliminar explicável**. Não representa garantia de aprovação nem probabilidade estatística.

## Módulos

- **Estudante:** cadastro, autenticação, ficha e consulta de oportunidades publicadas.
- **Coordenação:** cadastro de editais como rascunho, publicação/arquivamento e retificação versionada de regras.
- **Diretoria:** administração de perfis de estudante/coordenador e situação das contas.
- **Motor:** avaliação dos critérios cadastrados, vigência e pendências, com alertas logísticos separados.

## Tecnologias

- Laravel 13 / PHP 8.3+
- MariaDB / MySQL
- Blade, JavaScript e Vite
- PHPUnit

## Desenvolvimento local (Windows/XAMPP)

Consulte [`docs/AMBIENTE_DESENVOLVIMENTO.md`](docs/AMBIENTE_DESENVOLVIMENTO.md) e o [guia de inicialização]("==COMO INICIAR O SERVIDOR==.md"). Em resumo:

```powershell
composer install
npm ci
Copy-Item .env.example .env
# Gere uma chave somente para uma instalação nova e isolada, se APP_KEY estiver vazia.
php artisan key:generate
npm run build
php artisan test
php artisan serve
```

Configure o `.env` local para o banco `motor_elegibilidade` existente. Cada desenvolvedor mantém seu próprio `.env`; nunca versione esse arquivo.

## Banco de dados e preservação

O esquema oficial existente utiliza `cadastros`, `perfis_estudantes`, `editais`, `regras_elegibilidade`, `comentarios` e `historico_retificacoes`. Faça backup antes de qualquer alteração estrutural. Revise `php artisan migrate:status` e as migrations antes de executar `php artisan migrate`.

**Nunca execute `migrate:fresh`, `db:wipe`, `TRUNCATE` ou importe o dump SQL sobre a base populada `motor_elegibilidade`.** Os testes usam SQLite em memória e não devem conectar ao banco de desenvolvimento.

A chave `APP_KEY` precisa ser preservada e guardada com segurança: `perfis_estudantes.raca_cor` é armazenado com o cast criptográfico do Laravel. Perder ou trocar a chave sem um plano de recriptografia torna os valores existentes ilegíveis. Nunca versione `.env` ou `APP_KEY`. Se duas instalações acessarem a mesma base com valores `raca_cor` já criptografados, elas precisam receber a mesma chave por um canal seguro de gestão de segredos; chaves diferentes não conseguem descriptografar os mesmos registros. Em bancos locais independentes, cada ambiente pode ter sua própria chave.

## Testes e build

```powershell
php artisan test
npm run build
```

O PHP CLI deve ter `pdo_sqlite` habilitado para a suíte. Testes não devem usar `motor_elegibilidade`. O workflow `.github/workflows/ci.yml` repete validação do Composer, sintaxe PHP, PHPUnit e build do Vite em pushes para `main`/`fix/estabilidade-site` e em Pull Requests para `main`.

## Seeders de demonstração

O `DatabaseSeeder` não cria contas automaticamente. Os seeders específicos de demonstração são bloqueados por padrão e somente podem ser usados em um banco isolado, com `SCOM_ALLOW_DEMO_SEEDERS=true` e `SCOM_DEMO_PASSWORD` configurada. Eles nunca podem ser executados em `motor_elegibilidade`.

### Contas de Demonstração

Configure `SCOM_DEMO_PASSWORD` no `.env` (mínimo 12 caracteres) antes de rodar
`php artisan db:seed --class=UsuariosSeeder`.

| Perfil | Email | Senha |
|---|---|---|
| Estudante | `estudante@example.test` | (o valor de `SCOM_DEMO_PASSWORD`) |
| Coordenador | `coordenador@example.test` | (o valor de `SCOM_DEMO_PASSWORD`) |
| Diretor | `diretor@example.test` | (o valor de `SCOM_DEMO_PASSWORD`) |

## Git

Desenvolva em branches de funcionalidade/correção. Antes do Pull Request, rode testes, build e `git diff --check`, revise as alterações e documente resultados. Não faça merge com testes pendentes ou falhando.
