<?php

require_once __DIR__ . '/../includes/db_config.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");

$codemp = $_GET['codemp'] ?? null;
$codfil = $_GET['codfil'] ?? null;
$busca = $_GET['busca'] ?? '';

if (!$codemp || !$codfil) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "codemp e codfil obrigatórios"
    ]);
    exit;
}

$sql = "
SELECT
    r.idresident,
    r.nome,
    r.cpf,
    r.email,
    r.curso,
    r.sttsacad,
    res.descprgm
FROM tb_web_residentes r
INNER JOIN tb_web_residencias res
    ON r.idresidenc = res.idresidenc
WHERE r.codemp = ?
AND r.codfil = ?
";

$params = [$codemp, $codfil];

if (!empty($busca)) {
    $sql .= " AND r.nome LIKE ?";
    $params[] = "%$busca%";
}

$sql .= " ORDER BY r.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$residentes = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $residentes[] = [
        "id" => $row['idresident'],
        "nome" => $row['nome'],
        "cpf" => $row['cpf'],
        "email" => $row['email'],
        "programa" => $row['descprgm'],
        "curso" => $row['curso'],
        "status" => $row['sttsacad']
    ];
}

echo json_encode([
    "status" => "sucesso",
    "residentes" => $residentes
]);