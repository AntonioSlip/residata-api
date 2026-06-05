<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

// ==========================================
// PREFLIGHT
// ==========================================

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db_config.php';

// ==========================================
// APENAS GET
// ==========================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Método inválido'
    ]);

    exit;
}

// ==========================================
// RECEBE PARÂMETROS
// ==========================================

$codemp = $_GET['codemp'] ?? null;
$codfil = $_GET['codfil'] ?? null;
$mes = $_GET['mes'] ?? date('Y-m');

$idresidenc = $_GET['idresidenc'] ?? 'todas';

if (!$codemp || !$codfil) {

    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'codemp e codfil obrigatórios'
    ]);

    exit;
}

$perref = $mes . "-01";

// ==========================================
// QUERY PRINCIPAL
// ==========================================

$sql = "SELECT 

    SUM(vagas_ofertadas) as total_vagas,
    SUM(matriculados) as total_matr,
    SUM(desistencias) as total_desist,
    SUM(concluintes) as total_concl,

    SUM(modulos_previstos) as mod_p,
    SUM(modulos_realizados) as mod_r,

    SUM(rodizios_previstos) as rod_p,
    SUM(rodizios_realizados) as rod_r,

    AVG(freq_media_campos) as avg_freq,
    AVG(part_teorica) as avg_teorica,

    SUM(avaliacoes_previstas) as aval_p,
    SUM(avaliacoes_realizadas) as aval_r,

    AVG(campos_pactuados) as avg_pact,
    AVG(part_preceptores) as avg_prec_part,
    AVG(satisfacao_residentes) as sat_res,
    AVG(satisfacao_preceptores) as sat_prec,
    AVG(adequacao_cenarios) as adeq_cen

FROM tb_web_acompanhamento_mensal

WHERE codemp = ?
AND codfil = ?
AND mes_ref = ?";

// ==========================================
// FILTRO RESIDÊNCIA
// ==========================================

$params = [
    $codemp,
    $codfil,
    $perref
];

if ($idresidenc !== 'todas') {

    $sql .= " AND idresidenc = ?";

    $params[] = $idresidenc;
}

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$d = $stmt->fetch(PDO::FETCH_ASSOC);

// ==========================================
// FUNÇÃO STATUS
// ==========================================

function getStatus($resultado, $meta, $isEvasao = false)
{
    if ($resultado === null) {
        return "pendente";
    }

    if ($isEvasao) {

        if ($resultado <= $meta) {
            return "adequado";
        }

        if ($resultado <= $meta * 1.5) {
            return "atencao";
        }

        return "critico";
    }

    if ($resultado >= $meta) {
        return "adequado";
    }

    if ($resultado >= $meta * 0.9) {
        return "atencao";
    }

    return "critico";
}

// ==========================================
// INDICADORES
// ==========================================

$indicadores = [

    [
        "nome" => "Taxa de ocupação",
        "resultado" => (
            $d['total_vagas'] > 0
            ? ($d['total_matr'] / $d['total_vagas']) * 100
            : 0
        ),
        "meta" => 100,
        "status" => getStatus(
            (
                $d['total_vagas'] > 0
                ? ($d['total_matr'] / $d['total_vagas']) * 100
                : 0
            ),
            100
        )
    ],

    [
        "nome" => "Taxa de evasão",
        "resultado" => (
            $d['total_matr'] > 0
            ? ($d['total_desist'] / $d['total_matr']) * 100
            : 0
        ),
        "meta" => 10,
        "status" => getStatus(
            (
                $d['total_matr'] > 0
                ? ($d['total_desist'] / $d['total_matr']) * 100
                : 0
            ),
            10,
            true
        )
    ],

    [
        "nome" => "Taxa de conclusão",
        "resultado" => (
            $d['total_matr'] > 0
            ? ($d['total_concl'] / $d['total_matr']) * 100
            : 0
        ),
        "meta" => 90,
        "status" => getStatus(
            (
                $d['total_matr'] > 0
                ? ($d['total_concl'] / $d['total_matr']) * 100
                : 0
            ),
            90
        )
    ],

    [
        "nome" => "Módulos realizados",
        "resultado" => (
            $d['mod_p'] > 0
            ? ($d['mod_r'] / $d['mod_p']) * 100
            : 0
        ),
        "meta" => 90,
        "status" => getStatus(
            (
                $d['mod_p'] > 0
                ? ($d['mod_r'] / $d['mod_p']) * 100
                : 0
            ),
            90
        )
    ],

    [
        "nome" => "Rodízios realizados",
        "resultado" => (
            $d['rod_p'] > 0
            ? ($d['rod_r'] / $d['rod_p']) * 100
            : 0
        ),
        "meta" => 90,
        "status" => getStatus(
            (
                $d['rod_p'] > 0
                ? ($d['rod_r'] / $d['rod_p']) * 100
                : 0
            ),
            90
        )
    ],

    [
        "nome" => "Frequência média",
        "resultado" => (float) $d['avg_freq'],
        "meta" => 90,
        "status" => getStatus($d['avg_freq'], 90)
    ],

    [
        "nome" => "Participação teórica",
        "resultado" => (float) $d['avg_teorica'],
        "meta" => 90,
        "status" => getStatus($d['avg_teorica'], 90)
    ],

    [
        "nome" => "Avaliações realizadas",
        "resultado" => (
            $d['aval_p'] > 0
            ? ($d['aval_r'] / $d['aval_p']) * 100
            : 0
        ),
        "meta" => 100,
        "status" => getStatus(
            (
                $d['aval_p'] > 0
                ? ($d['aval_r'] / $d['aval_p']) * 100
                : 0
            ),
            100
        )
    ],

    [
        "nome" => "Campos pactuados",
        "resultado" => (float) $d['avg_pact'],
        "meta" => 100,
        "status" => getStatus($d['avg_pact'], 100)
    ],

    [
        "nome" => "Participação preceptores",
        "resultado" => (float) $d['avg_prec_part'],
        "meta" => 90,
        "status" => getStatus($d['avg_prec_part'], 90)
    ],

    [
        "nome" => "Satisfação residentes",
        "resultado" => (float) $d['sat_res'],
        "meta" => 4,
        "status" => getStatus($d['sat_res'], 4)
    ],

    [
        "nome" => "Satisfação preceptores",
        "resultado" => (float) $d['sat_prec'],
        "meta" => 4,
        "status" => getStatus($d['sat_prec'], 4)
    ],

    [
        "nome" => "Adequação cenários",
        "resultado" => (float) $d['adeq_cen'],
        "meta" => 90,
        "status" => getStatus($d['adeq_cen'], 90)
    ]

];

// ==========================================
// RESUMO
// ==========================================

$adequados = count(array_filter(
    $indicadores,
    fn($i) => $i['status'] == 'adequado'
));

$atencao = count(array_filter(
    $indicadores,
    fn($i) => $i['status'] == 'atencao'
));

$criticos = count(array_filter(
    $indicadores,
    fn($i) => $i['status'] == 'critico'
));

// ==========================================
// LISTA DE RESIDÊNCIAS
// ==========================================

$stmt_res = $pdo->prepare("
    SELECT
        idresidenc,
        descprgm
    FROM tb_web_residencias
    WHERE codemp = ?
    AND codfil = ?
    ORDER BY descprgm
");

$stmt_res->execute([
    $codemp,
    $codfil
]);

$residencias = $stmt_res->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// PDF
// ==========================================

$pdf_url = "http://localhost/residata/api/dashboard_pdf.php?codemp={$codemp}&codfil={$codfil}&mes={$mes}&idresidenc={$idresidenc}";

// ==========================================
// RESPOSTA FINAL
// ==========================================

echo json_encode([

    'status' => 'sucesso',

    'dashboard' => [

        'mes' => $mes,

        'idresidenc' => $idresidenc,

        'pdf_url' => $pdf_url,

        'residencias' => $residencias,

        'total_vagas' => (int) ($d['total_vagas'] ?? 0),

        'residentes' => (int) ($d['total_matr'] ?? 0),

        'adequados' => $adequados,

        'atencao' => $atencao,

        'criticos' => $criticos,

        'indicadores' => $indicadores

    ]

]);