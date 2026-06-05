<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// PREFLIGHT
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db_config.php';

// SÓ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Método inválido'
    ]);

    exit;
}

// RECEBE JSON
$dados = json_decode(file_get_contents("php://input"), true);

$idusu = $dados['idusu'] ?? null;

$senhaAtual = $dados['senha_atual'] ?? '';
$novaSenha = $dados['nova_senha'] ?? '';
$confirmarSenha = $dados['confirmar_senha'] ?? '';

if (
    empty($idusu) ||
    empty($senhaAtual) ||
    empty($novaSenha) ||
    empty($confirmarSenha)
) {

    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Preencha todos os campos'
    ]);

    exit;
}

// SENHAS DIFERENTES
if ($novaSenha !== $confirmarSenha) {

    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'As novas senhas não coincidem'
    ]);

    exit;
}

// SENHA PEQUENA
if (strlen($novaSenha) < 6) {

    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Nova senha muito curta'
    ]);

    exit;
}

try {

    // BUSCA USUÁRIO
    $sql = "SELECT * FROM tb_web_usuarios WHERE idusu = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$idusu]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {

        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Usuário não encontrado'
        ]);

        exit;
    }

    // VERIFICA SENHA ATUAL
    if (!password_verify($senhaAtual, $usuario['senha'])) {

        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Senha atual incorreta'
        ]);

        exit;
    }

    // NOVA SENHA
    $novoHash = password_hash(
        $novaSenha,
        PASSWORD_DEFAULT
    );

    $sqlUpdate = "
        UPDATE tb_web_usuarios
        SET senha = ?
        WHERE idusu = ?
    ";

    $stmtUpdate = $pdo->prepare($sqlUpdate);

    $stmtUpdate->execute([
        $novoHash,
        $idusu
    ]);

    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'Senha alterada com sucesso'
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ]);

}