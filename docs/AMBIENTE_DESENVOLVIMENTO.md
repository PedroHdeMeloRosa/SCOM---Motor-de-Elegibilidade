# Ambiente de desenvolvimento

Guia rápido para manter os computadores do projeto SCOM com as mesmas ferramentas.

## 1. Ferramentas utilizadas

| Ferramenta | Versão de referência |
|---|---:|
| Windows + XAMPP | XAMPP 8.5.12 |
| PHP | 8.5.12 |
| MariaDB | 10.11.16 |
| Apache | 2.4.65 |
| phpMyAdmin | 5.2.3 |
| Composer | 2.11.2 |
| Node.js | 24.21.0 |
| NPM | 11.19.0 |
| Laravel Installer | 5.33.0 |
| Git | 2.55.0 |

## 2. Tecnologias escolhidas

- **Backend:** Laravel 13 e PHP.
- **Interface:** Blade, HTML, CSS e JavaScript.
- **Frontend:** Vite e NPM.
- **Banco:** MariaDB, acessado pelo Eloquent.
- **Banco versionado:** migrations do Laravel.
- **Testes:** PHPUnit.
- **Execução local:** `php artisan serve`.

O Apache pode ser usado para acessar o phpMyAdmin, mas não é necessário para iniciar o Laravel. O MariaDB precisa estar ligado no XAMPP.

## 3. Configuração do PHP

O PHP do terminal deve ser o do XAMPP:

```text
C:\xampp\php\php.exe
```

Adicionar `C:\xampp\php` ao `PATH` do Windows. No `php.ini`, conferir se estas extensões estão habilitadas:

```ini
extension=curl
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=pdo_sqlite
extension=fileinfo
extension=zip
```

Também devem estar disponíveis: `ctype`, `dom`, `filter`, `hash`, `pcre`, `pdo`, `session` e `tokenizer`.

Conferir no PowerShell:

```powershell
where.exe php
php -v
php -m
composer --version
node -v
npm -v
laravel --version
git --version
```

## 4. Preparar o projeto

Depois de clonar o repositório:

```powershell
git clone URL_DO_REPOSITORIO
cd SCOM---Motor-de-Elegibilidade

composer install
npm ci
Copy-Item .env.example .env
# Somente em instalação nova e isolada, com APP_KEY vazia:
php artisan key:generate
```

`composer install` e `npm ci` instalam as versões registradas nos arquivos de dependências do projeto.

> **Atenção:** esses comandos pressupõem que a aplicação Laravel já esteja integrada ao repositório. O Laravel Installer global, sozinho, não cria a aplicação dentro do projeto.

## 5. Banco de dados

O banco legado oficial utilizado no desenvolvimento é `motor_elegibilidade`. Ele já contém as tabelas `cadastros`, `perfis_estudantes`, `editais`, `regras_elegibilidade`, `comentarios` e `historico_retificacoes`. Não crie um banco `scom` vazio para substituir essa base e não importe novamente o dump SQL sobre dados existentes.

Antes de qualquer alteração estrutural:

1. Exporte o banco `motor_elegibilidade` pelo phpMyAdmin.
2. Confira que o backup foi baixado e pode ser aberto.
3. Confira `DB_DATABASE` no `.env` sem compartilhar o conteúdo do arquivo.
4. Revise as migrations e o estado com `php artisan migrate:status`.
5. Execute migrations somente após revisão e backup. Nunca utilize `migrate:fresh`, `db:wipe` ou `migrate:rollback` na base legada.

Em uma instalação nova, depois de revisar backup/conexão e executar as migrations, crie a primeira conta administrativa pelo comando interativo `php artisan scom:criar-diretor`. O comando recusa criar outra conta se já existir um diretor.

Para testes automatizados, `phpunit.xml` configura SQLite em memória (`:memory:`). Os testes devem criar seu próprio esquema de teste; nunca devem se conectar a `motor_elegibilidade`. É necessário que a extensão `pdo_sqlite` esteja habilitada no PHP CLI.

A coluna `perfis_estudantes.raca_cor` usa o cast `encrypted` do Laravel. Preserve a `APP_KEY` em backup protegido e nunca a versione. Se duas instalações acessarem a mesma base com dados já cifrados, ambas precisam receber a mesma chave por um canal seguro de gestão de segredos; não é seguro enviar a chave pelo Git, e chaves diferentes não conseguem ler os mesmos valores. Não execute `key:generate` sobre uma instalação que já tenha valores criptografados sem um plano de recriptografia.

## 6. Iniciar e testar

Use dois terminais na pasta do projeto.

**Terminal 1:**

```powershell
php artisan serve
```

**Terminal 2:**

```powershell
npm run dev
```

Para executar os testes:

```powershell
php artisan test
```

Os testes que usam banco devem apontar para uma base exclusiva, como `scom_test`, nunca para dados que precisam ser preservados.

## 7. O que vai para o Git

**Versionar:** `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `.env.example`, migrations, código e documentação.

**Não versionar:** `.env`, `vendor/`, `node_modules/`, logs, senhas ou tokens.

Cada pessoa mantém seu próprio `.env`. Nunca enviar credenciais pelo Git.

## 8. IA Local (Ollama) — Leitura Assistida de Editais

A leitura de editais por IA é **opcional** (o cadastro manual continua funcionando normalmente se o Ollama estiver desligado). Para ativar o botão de escaneamento local na Coordenação:

> **Importante (Windows):** Execute a instalação pelo **PowerShell do Windows (aberto direto pelo Menu Iniciar)**, e não pelo terminal integrado do VS Code, para evitar bloqueio de permissão (`exit code 5`) no `winget` e garantir que o comando `ollama` entre no `PATH`.

1. Abra o **PowerShell** do Windows (fora do VS Code) e instale o Ollama:

```powershell
winget install Ollama.Ollama --interactive

## Referências

- [Laravel 13](https://laravel.com/docs/13.x)
- [Composer](https://getcomposer.org/doc/)
- [Node.js](https://nodejs.org/en/download)
- [XAMPP](https://www.apachefriends.org/)
