<?php

header('Content-Type: application/json');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/../includes/db_config.php';

// ==========================================
// RECEBE PARÂMETROS
// ==========================================

$codemp = $_GET['codemp'] ?? null;
$codfil = $_GET['codfil'] ?? null;

$busca = $_GET['busca'] ?? '';

// ==========================================
// VALIDA
// ==========================================

if (!$codemp || !$codfil) {

    echo json_encode([
        "status" => "erro",
        "mensagem" => "codemp e codfil obrigatórios"
    ]);

    exit;
}

$sql = "SELECT 
            idresidenc,
            descprgm,
            descctgr,
            qtdvagas
        FROM tb_web_residencias
        WHERE codemp = ?
        AND codfil = ?
";

$params = [$codemp, $codfil];

if (!empty($busca)) {
    $sql .= " AND descprgm LIKE ?";
    $params[] = "%$busca%";
}

$sql .= " ORDER BY descprgm ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$residencias = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $residencias[] = [
        "id" => $row['idresidenc'],
        "programa" => $row['descprgm'],
        "categoria" => $row['descctgr'],
        "vagas" => (int) $row['qtdvagas'],
        "status" => "Regular"
    ];
}

echo json_encode([
    "status" => "sucesso",
    "residencias" => $residencias
]);