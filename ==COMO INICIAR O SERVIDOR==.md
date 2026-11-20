# Guia de inicialização do SCOM — Motor de Elegibilidade

Este guia considera que o banco legado `motor_elegibilidade` já existe e contém os registros que devem ser preservados.

## 1. Iniciar os serviços

No XAMPP, inicie **MySQL/MariaDB**. O Apache só é necessário para acessar o phpMyAdmin; o Laravel será servido pelo Artisan.

## 2. Iniciar a aplicação

Na raiz do repositório, abra dois terminais.

**Terminal 1 — Laravel:**

```powershell
php artisan serve
```

**Terminal 2 — Vite:**

```powershell
npm run dev
```

Acesse `http://127.0.0.1:8000`.

## 3. Limpar caches de configuração

Depois de alterar configurações ou rotas:

```powershell
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Não execute `php artisan cache:clear` contra o banco legado se o cache estiver configurado para usar o banco de dados.

## 4. Banco de dados: regras de segurança

- O banco de trabalho legado chama-se `motor_elegibilidade`.
- Faça uma exportação SQL atualizada antes de qualquer alteração estrutural.
- Não importe `sql/motor_elegibilidade.sql` sobre uma base populada: o arquivo contém `DROP TABLE IF EXISTS`.
- **Nunca execute `migrate:fresh`, `db:wipe`, `TRUNCATE`, `migrate:rollback` ou seeders de demonstração no banco `motor_elegibilidade`.**
- A migration baseline preserva tabelas de negócio existentes e seu `down()` é intencionalmente não destrutivo.
- Antes de executar `php artisan migrate` pela primeira vez em qualquer ambiente, confirme o banco selecionado e revise `php artisan migrate:status`. A execução deve ser feita somente depois de backup verificável e revisão do diff das migrations.

Para testes automatizados, utilize SQLite em memória conforme `phpunit.xml`. Se a extensão `pdo_sqlite` não estiver habilitada no PHP CLI, habilite-a no `php.ini` utilizado pelo terminal antes de executar a suíte. Os testes não devem usar `motor_elegibilidade`.

## 5. Testes e validações

```powershell
php artisan test
npm run build
```

Não use `--env=production` nem a conexão de produção para rodar testes. Antes do merge, confira também:

```powershell
git diff --check
git status -sb
```

## 6. Credenciais

Não existem credenciais padrão para novas instalações. O cadastro público cria exclusivamente estudantes. A primeira conta administrativa deve ser provisionada por procedimento controlado, fora do cadastro público. Nunca reutilize senhas de demonstração em um ambiente com dados reais.
