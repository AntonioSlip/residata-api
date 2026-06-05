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

    p.idpreceptor,
    p.nome,
    p.email,
    p.conselho,
    p.titulacao,
    p.status_bolsa,

    r.descprgm,

    e.descemp,
    f.descfil

FROM tb_web_preceptores p

INNER JOIN tb_web_residencias r
ON p.idresidenc = r.idresidenc

LEFT JOIN tb_web_empresas e
ON p.codemp = e.codemp

LEFT JOIN tb_web_filiais f
ON p.codfil = f.codfil

WHERE
    p.codemp = ?
    AND p.codfil = ?

";

$params = [$codemp, $codfil];

if (!empty($busca)) {

    $sql .= " AND p.nome LIKE ? ";

    $params[] = "%$busca%";
}

$sql .= " ORDER BY p.nome ASC ";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$preceptores = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $preceptores[] = [

        "id" => $row['idpreceptor'],
        "nome" => $row['nome'],
        "email" => $row['email'],
        "conselho" => $row['conselho'],
        "titulacao" => $row['titulacao'],
        "programa" => $row['descprgm'],
        "empresa" => $row['descemp'],
        "filial" => $row['descfil'],
        "status" => $row['status_bolsa']
    ];
}

echo json_encode([
    "status" => "sucesso",
    "preceptores" => $preceptores
]);