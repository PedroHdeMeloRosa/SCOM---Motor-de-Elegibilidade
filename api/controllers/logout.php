<?php
// api/controllers/logout.php
session_start();

// Limpa todas as variáveis de sessão
$_SESSION = array();

// Se os cookies de sessão estiverem sendo usados, destrói o cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão no servidor
session_destroy();

// Retorna resposta de sucesso para o Front-end
header("Content-Type: application/json; charset=UTF-8");
http_response_code(200);
echo json_encode(["sucesso" => true, "mensagem" => "Sessão encerrada."]);
?>