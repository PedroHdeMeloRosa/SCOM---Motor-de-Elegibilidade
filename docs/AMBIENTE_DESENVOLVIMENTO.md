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
php artisan key:generate
```

`composer install` e `npm ci` instalam as versões registradas nos arquivos de dependências do projeto.

> **Atenção:** esses comandos pressupõem que a aplicação Laravel já esteja integrada ao repositório. O Laravel Installer global, sozinho, não cria a aplicação dentro do projeto.

## 5. Banco de dados

1. Inicie o serviço MySQL/MariaDB pelo XAMPP.
2. Crie um banco chamado `scom`.
3. Configure o `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scom
DB_USERNAME=root
DB_PASSWORD=
```

Ajuste usuário e senha conforme a instalação local. Depois execute:

```powershell
php artisan migrate
```

As migrations são a fonte oficial da estrutura do banco. Não é necessário criar as tabelas manualmente no phpMyAdmin.

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

## Referências

- [Laravel 13](https://laravel.com/docs/13.x)
- [Composer](https://getcomposer.org/doc/)
- [Node.js](https://nodejs.org/en/download)
- [XAMPP](https://www.apachefriends.org/)
