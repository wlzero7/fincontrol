<?php

require_once "../includes/session.php";
require_once "../config/database.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;

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

$receitas = 0;
$despesas = 0;

foreach ($movimentacoes as $mov) {
    if ($mov['tipo'] === 'receita') {
        $receitas += $mov['valor'];
    }

    if ($mov['tipo'] === 'despesa') {
        $despesas += $mov['valor'];
    }
}

$saldo = $receitas - $despesas;

$periodo = "Todos os registros";

if (!empty($dataInicio) || !empty($dataFim)) {
    $periodo = "";

    $periodo .= !empty($dataInicio)
        ? date('d/m/Y', strtotime($dataInicio))
        : "Início";

    $periodo .= " até ";

    $periodo .= !empty($dataFim)
        ? date('d/m/Y', strtotime($dataFim))
        : "Hoje";
}

$html = "
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>

    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
        }

        h1 {
            color: #1e3a8a;
            margin-bottom: 5px;
        }

        h3 {
            color: #334155;
            margin-top: 0;
        }

        .resumo {
            background: #f1f5f9;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .resumo p {
            margin: 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th {
            background: #1e3a8a;
            color: white;
            padding: 8px;
            text-align: left;
        }

        td {
            padding: 7px;
            border: 1px solid #cbd5e1;
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        .rodape {
            margin-top: 25px;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>
<body>

<h1>FinControl</h1>
<h3>Relatório Financeiro</h3>

<div class='resumo'>
    <p><strong>Período:</strong> {$periodo}</p>
    <p><strong>Receitas:</strong> R$ " . number_format($receitas, 2, ',', '.') . "</p>
    <p><strong>Despesas:</strong> R$ " . number_format($despesas, 2, ',', '.') . "</p>
    <p><strong>Saldo:</strong> R$ " . number_format($saldo, 2, ',', '.') . "</p>
    <p><strong>Total de movimentações:</strong> " . count($movimentacoes) . "</p>
</div>

<table>
    <tr>
        <th>Descrição</th>
        <th>Tipo</th>
        <th>Valor</th>
        <th>Data</th>
    </tr>
";

if (count($movimentacoes) === 0) {
    $html .= "
        <tr>
            <td colspan='4'>Nenhuma movimentação encontrada.</td>
        </tr>
    ";
}

foreach ($movimentacoes as $mov) {
    $descricao = htmlspecialchars($mov['descricao']);
    $tipo = ucfirst($mov['tipo']);
    $valor = "R$ " . number_format($mov['valor'], 2, ',', '.');
    $data = date('d/m/Y', strtotime($mov['data_movimentacao']));

    $html .= "
        <tr>
            <td>{$descricao}</td>
            <td>{$tipo}</td>
            <td>{$valor}</td>
            <td>{$data}</td>
        </tr>
    ";
}

$html .= "
</table>

<p class='rodape'>Relatório gerado em " . date('d/m/Y H:i') . "</p>

</body>
</html>
";

$dompdf = new Dompdf();

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$dompdf->stream(
    "relatorio-fincontrol.pdf",
    ["Attachment" => true]
);