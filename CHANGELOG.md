# CHANGELOG - SCOM Motor de Elegibilidade

Este documento regista as modificações estruturais, implementações e decisões arquiteturais do projeto.

---

## [v0.1.0] - 13/11/2026
**Branch:** `chore/laravel-bootstrap`
**Responsável:** Pedro Henrique

### 1. Atualização e Validação do Ambiente (Strict Compliance)
- **PHP / XAMPP:** Atualizado rigorosamente para a versão **8.5.12** para garantir suporte total ao Laravel 13 sem conflitos de dependências.
- **Node.js & NPM:** Validadas as versões locais para **24.21.0** e **11.19.0**, respetivamente, para gestão do motor Vite.

### 2. Instalação e Estruturação do Framework (Laravel 13)
- Gerado o projeto Laravel 13 nativo (`13.*`) diretamente na raiz do repositório `SCOM---Motor-de-Elegibilidade`.
- Devido à presença de ficheiros legados (`index.html`, etc.), a instalação foi roteada temporariamente e os diretórios core (`app/`, `config/`, `public/`, `resources/`, `routes/`, etc.) foram unificados na raiz de forma limpa.
- **Banco de Dados:** Criada a base de dados `scom` (MariaDB 10.11.16). Ficheiro `.env` configurado e as migrations padrão de sessão/utilizador foram aplicadas com sucesso via `php artisan migrate`.

### 3. Integração de Frontend (Vite, Bootstrap 5 e Alpine.js)
- Dependências de UI instaladas via NPM (`bootstrap`, `@popperjs/core`, `alpinejs`). A pasta `node_modules` foi gerada e está no `.gitignore`.
- **Configuração do Compilador (Vite):**
  - **CSS:** Injeção do Bootstrap configurada no pipeline oficial do Laravel em `resources/css/app.css` (`@import 'bootstrap/dist/css/bootstrap.min.css';`).
  - **JavaScript:** Otimização do `resources/js/app.js` para importar os módulos do Bootstrap e inicializar o estado global do Alpine.js (`window.Alpine = Alpine; Alpine.start();`).

### 4. Migração da Interface Vanilla para Blade
- O antigo código estático de `index.html` foi migrado para o motor de templates oficial do Laravel em `resources/views/welcome.blade.php`.
- Inserida a diretiva de compilação `@vite(['resources/css/app.css', 'resources/js/app.js'])` na tag `<head>` para garantir o *Hot Module Replacement* (HMR).
- **Validação:** Servidor local (`php artisan serve`) e compilador frontend (`npm run dev`) testados em paralelo. Renderização do Bootstrap e Alpine validada com sucesso no navegador (Status 200).

---

### 🚀 Instruções de Sincronização para a Equipa
Ao fazer o `git pull` desta branch para a tua máquina local, executa os seguintes comandos para espelhar este ambiente:

1. `composer install` *(para gerar a tua pasta vendor)*
2. `npm ci` *(para gerar a tua pasta node_modules com base no package-lock)*
3. `cp .env.example .env` *(depois, ajusta as credenciais DB_DATABASE=scom)*
4. `php artisan key:generate`
5. `php artisan migrate`