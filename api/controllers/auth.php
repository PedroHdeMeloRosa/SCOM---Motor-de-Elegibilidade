<?php
// =========================================================================
// CAMADA 1: CONFIGURAÇÃO SEGURA DE SESSÃO
// Impede que o Cookie de sessão seja acessado via JavaScript (Mitiga XSS)
// =========================================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["sucesso" => false, "mensagem" => "Método não permitido."]);
    exit;
}

// =========================================================================
// CAMADA 2: CAPTURA E SANITIZAÇÃO DE ENTRADAS
// =========================================================================
$dados = json_decode(file_get_contents("php://input"), true);
$email_puro = trim($dados['email'] ?? '');
$senha_pura = $dados['senha'] ?? '';

if (empty($email_puro) || empty($senha_pura)) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "mensagem" => "Preencha todos os campos."]);
    exit;
}

// Sanitiza o formato do email
$email = filter_var($email_puro, FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "mensagem" => "Formato de e-mail inválido."]);
    exit;
}

try {
    // Conexão PDO com o MySQL local (ajuste usuário e senha do seu XAMPP)
    $host = '127.0.0.1';
    $db   = 'motor_elegibilidade';
    $user = 'root';
    $pass = ''; 
    
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $opcoes = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Impede o PHP de emular preparos, forçando o MySQL a fazer isso (Mais seguro)
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $opcoes);

    // =========================================================================
    // CAMADA 3: PREPARED STATEMENTS (PREVENÇÃO DE SQL INJECTION)
    // O PDO separa a query dos dados, tornando impossível injetar código SQL.
    // =========================================================================
   $stmt = $pdo->prepare("SELECT id, nome, senha_hash, tipo_perfil, status_conta FROM cadastros WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch();

    // Nova verificação de segurança:
    if ($usuario && password_verify($senha_pura, $usuario['senha_hash'])) {
    
    // Se o Diretor baniu a conta, o login é negado na hora
    if ($usuario['status_conta'] === 'BANIDA') {
        http_response_code(403); // Status HTTP 403: Proibido
        echo json_encode(["sucesso" => false, "mensagem" => "Sua conta foi suspensa pela administração."]);
        exit;
    }
    }

    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $usuario = $stmt->fetch();

    // =========================================================================
    // CAMADA 4: VERIFICAÇÃO DE HASH E PREVENÇÃO DE FIXAÇÃO DE SESSÃO
    // Nunca salvar senha em texto plano. Usa password_verify para bater o hash.
    // =========================================================================
    if ($usuario && password_verify($senha_pura, $usuario['senha_hash'])) {
        
        // Anti Session Hijacking: Gera um novo ID de sessão após o login bem sucedido
        session_regenerate_id(true);
        
        // Armazena variáveis de sessão
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); // Mitiga XSS
        $_SESSION['tipo_perfil'] = $usuario['tipo_perfil'];
        
        // Responde ao Front-end
        http_response_code(200);
        echo json_encode([
            "sucesso" => true,
            "mensagem" => "Login efetuado com sucesso.",
            "perfil" => $usuario['tipo_perfil']
        ]);
        
    } else {
        // Mensagem genérica para não revelar se o erro foi no e-mail ou na senha (Boa prática)
        http_response_code(401);
        echo json_encode(["sucesso" => false, "mensagem" => "Credenciais inválidas."]);
    }

} catch (PDOException $e) {
    // Log de erro interno (Não expor o erro real do banco de dados para o usuário)
    error_log("Erro de Banco de Dados [Login]: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(["sucesso" => false, "mensagem" => "Erro interno no servidor. Tente novamente mais tarde."]);
}
?>