# 🚀 Guia Rápido de Inicialização (UNILEGI - Motor de Elegibilidade)

Sempre que fechar e abrir o VS Code, ou reiniciar o computador, siga estes passos para religar o ambiente de desenvolvimento corretamente.

## 1. Preparar o Banco de Dados
Antes de qualquer comando no VS Code, certifique-se de que o **XAMPP** está aberto e com os seguintes módulos com o status verde (Start):
- **Apache** (Servidor Web)
- **MySQL** (Banco de Dados)

## 2. Religar os Motores (Terminais)
No VS Code, você precisará abrir **dois terminais divididos** para rodar o Backend (Laravel) e o Frontend (Vite) em simultâneo.

**No Terminal 1 (Backend - Laravel):**
Inicia o servidor PHP e disponibiliza o site na porta 8000.
```powershell
php artisan serve
```

**No Terminal 2 (Frontend - Vite):**
Inicia o motor de compilação de CSS/JS (Bootstrap e Alpine). Este termina'l deve ficar sempre aberto a rodar para que a tela atualize automaticamente (HMR).
```powershell
npm run dev
```

O site estará agora disponível no seu navegador em: `http://127.0.0.1:8000`

---

## 🛠️ Comandos Úteis e de Emergência

### Limpar Memória e Cache (Se o Laravel ignorar atualizações ou o `.env`)
Se fizer alterações no `.env` (como mudar o banco de dados) ou se o sistema parecer "preso" numa versão antiga, pare o `php artisan serve` (Ctrl + C) e rode:
```powershell
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Resetar Banco de Dados (Modo Desenvolvimento)
Se precisar de limpar todas as tabelas e reinjetar os dados de teste (contas de login e editais geolocalizados):
```powershell
# 1. Apaga e recria todas as tabelas vazias
php artisan migrate:fresh

# 2. Popula as tabelas com os dados falsos para teste
php artisan db:seed --class=UsuariosSeeder
php artisan db:seed --class=EditaisSeeder
```

### Contas de Acesso Padrão (RBAC)
- **Estudante:** `estudante@unilegi.com` | Senha: `scom2026`
- **Coordenador:** `coordenador@unilegi.com` | Senha: `scom2026`
- **Diretor:** `diretor@unilegi.com` | Senha: `scom2026`

## 🧪 Como Injetar Carga de Testes (30+ Editais)
Para testar a performance do mapa, gráficos e vagas expiradas (cinzas), rode o semeador massivo:
`php artisan db:seed --class=EditaisMassivosSeeder`

## 🧹 Como Limpar os Dados "Falsos" e Restaurar
Se os testes acabaram e você quiser apagar a bagunça para voltar apenas com os dados oficiais (4 editais reais e utilizadores):
1. `php artisan migrate:fresh` (Destrói e recria tabelas vazias)
2. `php artisan db:seed --class=UsuariosSeeder` (Restaura o seu Estudante Teste)
3. `php artisan db:seed --class=EditaisSeeder` (Restaura os 4 editais oficiais)