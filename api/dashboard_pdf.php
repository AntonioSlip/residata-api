<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");

ob_start();

require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$codemp = $_GET['codemp'] ?? null;
$codfil = $_GET['codfil'] ?? null;

$emissor = "Aplicativo Mobile";

if (!$codemp || !$codfil) {

    die("Parâmetros inválidos");
}

// 1. FILTROS
$mes_selecionado = $_GET['mes'] ?? date('Y-m');
$id_res_filtro = $_GET['idresidenc'] ?? 'todas';
$perref = $mes_selecionado . "-01";

// 2. BUSCA DADOS (Mesma lógica do dashboardSQL)
$params = [$codemp, $codfil, $perref];
$where = "";
if ($id_res_filtro !== 'todas') {
    $where = " AND idresidenc = ? ";
    $params[] = (int) $id_res_filtro;
}

$sql = "SELECT 
    SUM(vagas_ofertadas) as total_vagas, SUM(matriculados) as total_matr,
    SUM(desistencias) as total_desist, SUM(concluintes) as total_concl,
    SUM(modulos_previstos) as mod_p, SUM(modulos_realizados) as mod_r,
    SUM(rodizios_previstos) as rod_p, SUM(rodizios_realizados) as rod_r,
    AVG(freq_media_campos) as avg_freq, AVG(part_teorica) as avg_teorica,
    SUM(avaliacoes_previstas) as aval_p, SUM(avaliacoes_realizadas) as aval_r,
    AVG(campos_pactuados) as avg_pact, AVG(part_preceptores) as avg_prec_part,
    AVG(satisfacao_residentes) as sat_res, AVG(satisfacao_preceptores) as sat_prec,
    AVG(adequacao_cenarios) as adeq_cen
    FROM tb_web_acompanhamento_mensal 
    WHERE codemp = ? AND codfil = ? AND mes_ref = ?" . $where;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

$nome_residencia = "Todas as Residências";
if ($id_res_filtro !== 'todas') {
    $st = $pdo->prepare("SELECT descprgm FROM tb_web_residencias WHERE idresidenc = ?");
    $st->execute([$id_res_filtro]);
    $nome_residencia = $st->fetchColumn();
}

// Lógica de Cores para o PDF
function getStatusColor($result, $meta, $isEvasao = false)
{
    if ($result === null)
        return '#ddd';
    if ($isEvasao) {
        if ($result <= $meta)
            return '#22c55e'; // Verde
        if ($result <= $meta * 1.5)
            return '#eab308'; // Amarelo
        return '#ef4444'; // Vermelho
    }
    if ($result >= $meta)
        return '#22c55e';
    if ($result >= $meta * 0.9)
        return '#eab308';
    return '#ef4444';
}

$html = "
<html>
<head>
    <style>
        body { font-family: Helvetica, sans-serif; font-size: 12px; color: #333; }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 20px; }
        .title { color: #1e3a8a; font-size: 18px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f0f4ff; color: #1e3a8a; }
        .text-start { text-align: left; padding-left: 15px; }
        .circle { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-top: 2px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 10px; color: #777; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>
    <div class='header'>
        <table style='border:none; width:100%'>
            <tr style='border:none'>
                <td style='border:none; text-align:left'>
                    <div class='title'>Residata</div>
                    <div style='font-size:14px'>Dashboard de Monitoramento</div>
                </td>
                <td style='border:none; text-align:right'>
                    <b>Filtro:</b> $nome_residencia<br>
                    <b>Referência:</b> " . date('m/Y', strtotime($perref)) . "
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th class='text-start'>Indicador</th>
                <th>Resultado</th>
                <th>Meta</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr><td class='text-start'>Total de Residentes Matriculados</td><td>" . (int) $d['total_matr'] . "</td><td>-</td><td>-</td></tr>";

// Array de indicadores conforme View
$indicadores = [
    ['Taxa de ocupação de vagas', ($d['total_vagas'] > 0 ? ($d['total_matr'] / $d['total_vagas']) * 100 : 0), 100, '%', false],
    ['Taxa de evasão', ($d['total_matr'] > 0 ? ($d['total_desist'] / $d['total_matr']) * 100 : 0), 10, '%', true],
    ['Taxa de conclusão', ($d['total_matr'] > 0 ? ($d['total_concl'] / $d['total_matr']) * 100 : 0), 90, '%', false],
    ['Módulos teóricos realizados', ($d['mod_p'] > 0 ? ($d['mod_r'] / $d['mod_p']) * 100 : 0), 90, '%', false],
    ['Rodízios realizados', ($d['rod_p'] > 0 ? ($d['rod_r'] / $d['rod_p']) * 100 : 0), 90, '%', false],
    ['Frequência média residentes', $d['avg_freq'], 90, '%', false],
    ['Satisfação Residentes (1-5)', $d['sat_res'], 4, '', false],
    ['Satisfação Preceptores (1-5)', $d['sat_prec'], 4, '', false],
    ['Adequação Cenários', $d['adeq_cen'], 90, '%', false],
];

foreach ($indicadores as $ind) {
    $color = getStatusColor($ind[1], $ind[2], $ind[4]);
    $res_fmt = number_format($ind[1], 1, ',', '.') . $ind[3];
    $meta_fmt = ($ind[4] ? '< ' : '') . number_format($ind[2], 1, ',', '.') . $ind[3];

    $html .= "<tr>
                    <td class='text-start'>{$ind[0]}</td>
                    <td><b>$res_fmt</b></td>
                    <td style='color:#666'>$meta_fmt</td>
                    <td><div class='circle' style='background-color:$color'></div></td>
                </tr>";
}

$html .= "
        </tbody>
    </table>

    <div style='background:#f9f9f9; padding:10px; border:1px solid #ddd'>
        <b>Legenda:</b> 
        <span class='circle' style='background:#22c55e'></span> Adequado | 
        <span class='circle' style='background:#eab308'></span> Atenção | 
        <span class='circle' style='background:#ef4444'></span> Crítico
    </div>

    <div class='footer'>
        Gerado por: <b>$emissor</b> em " . date('d/m/Y H:i') . " | Residata
    </div>
</body>
</html>";

ob_end_clean();
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Dashboard_" . str_replace('-', '', $mes_selecionado) . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);