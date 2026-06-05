<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Responde preflight do navegador
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db_config.php';

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Método inválido'
    ]);

    exit;
}

// Recebe JSON do Flutter
$dados = json_decode(file_get_contents("php://input"), true);

$email = trim($dados['email'] ?? '');
$senha = $dados['senha'] ?? '';

if (empty($email) || empty($senha)) {

    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'E-mail e senha obrigatórios'
    ]);

    exit;
}

try {

    $sql = "SELECT 
            u.*,

            t.descsit,

            e.descemp,
            f.descfil

        FROM tb_web_usuarios u

        INNER JOIN tb_web_usuarios_tip t
            ON u.idtip = t.idtip

        INNER JOIN tb_web_empresas e
            ON u.codemp = e.codemp

        INNER JOIN tb_web_filiais f
            ON u.codfil = f.codfil
            AND u.codemp = f.codemp

        WHERE u.email = :email";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'email' => $email
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {

        echo json_encode([

            'status' => 'sucesso',

            'usuario' => [

                'id' => $usuario['idusu'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],

                'cargo' => $usuario['descsit'],

                'empresa' => $usuario['descemp'],
                'filial' => $usuario['descfil'],

                'codemp' => $usuario['codemp'],
                'codfil' => $usuario['codfil']
            ]

        ]);

    } else {

        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'E-mail ou senha inválidos'
        ]);

    }

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ]);

}