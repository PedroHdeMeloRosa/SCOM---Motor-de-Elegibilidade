<?php
// Configurações do Banco de Dados Local (XAMPP)
$host = '127.0.0.1';
$db   = 'motor_elegibilidade';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // A senha que será usada para todos os testes
    $senha_plana = 'scom2026';
    
    // O PHP cria o Hash garantindo a codificação perfeita do seu servidor
    $senha_hash = password_hash($senha_plana, PASSWORD_BCRYPT);

    // Array com as 3 contas e seus respectivos perfis RBAC
    $contas = [
        ['Diretoria SCOM', 'diretor@unilegi.com', 'DIRETOR'],
        ['Coordenação Acadêmica', 'coordenador@unilegi.com', 'COORDENADOR'],
        ['Pedro (Aluno Teste)', 'estudante@unilegi.com', 'ESTUDANTE']
    ];

    $stmt = $pdo->prepare("INSERT INTO cadastros (nome, email, senha_hash, tipo_perfil, status_conta) VALUES (?, ?, ?, ?, 'ATIVA')");

    echo "<h3>Iniciando criação de contas...</h3>";

    foreach ($contas as $conta) {
        // Verifica se a conta já existe para não duplicar
        $check = $pdo->prepare("SELECT id FROM cadastros WHERE email = ?");
        $check->execute([$conta[1]]);
        
        if ($check->rowCount() == 0) {
            $stmt->execute([$conta[0], $conta[1], $senha_hash, $conta[2]]);
            echo "<p style='color: green;'>✔ Conta criada: <strong>{$conta[1]}</strong> ({$conta[2]})</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Conta já existia: <strong>{$conta[1]}</strong></p>";
        }
    }
    
    echo "<br><p style='color: red;'><strong>IMPORTANTE:</strong> Por questões de segurança, apague este arquivo (gerar_contas.php) após o uso!</p>";
    echo "<a href='login.html'>Ir para a tela de Login</a>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro no banco de dados: " . $e->getMessage() . "</p>";
}
?>