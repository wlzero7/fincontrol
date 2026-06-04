<?php

require_once "../includes/session.php";
require_once "../config/database.php";

$usuario_id = $_SESSION['usuario_id'];

$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

$sql = "
    SELECT *
    FROM movimentacoes
    WHERE usuario_id = ?
";

$params = [$usuario_id];

if (!empty($dataInicio)) {
    $sql .= " AND data_movimentacao >= ?";
    $params[] = $dataInicio;
}

if (!empty($dataFim)) {
    $sql .= " AND data_movimentacao <= ?";
    $params[] = $dataFim;
}

$sql .= " ORDER BY data_movimentacao DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nomeArquivo = "relatorio-fincontrol.csv";

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename={$nomeArquivo}");

$output = fopen("php://output", "w");

fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, [
    "Descrição",
    "Tipo",
    "Valor",
    "Data"
], ";");

foreach ($movimentacoes as $mov) {
    fputcsv($output, [
        $mov['descricao'],
        ucfirst($mov['tipo']),
        number_format($mov['valor'], 2, ',', '.'),
        date('d/m/Y', strtotime($mov['data_movimentacao']))
    ], ";");
}

fclose($output);
exit;