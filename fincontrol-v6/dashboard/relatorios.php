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
$totalMovimentacoes = count($movimentacoes);

$queryString = "";

if (!empty($dataInicio)) {
    $queryString .= "data_inicio=" . urlencode($dataInicio);
}

if (!empty($dataFim)) {
    $queryString .= !empty($queryString) ? "&" : "";
    $queryString .= "data_fim=" . urlencode($dataFim);
}

$linkPdf = "gerar_pdf.php";
$linkCsv = "exportar_csv.php";

if (!empty($queryString)) {
    $linkPdf .= "?" . $queryString;
    $linkCsv .= "?" . $queryString;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Relatórios | FinControl</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .relatorio-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .btn-csv {
            background: linear-gradient(135deg, #16a34a, #22c55e) !important;
        }

        .periodo-info {
            background: #1e293b;
            padding: 15px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            color: #cbd5e1;
            display: inline-block;
        }

        .grafico-card {
            background: #1e293b;
            padding: 25px;
            border-radius: 18px;
        }
    </style>
</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="container">

    <h1>Relatórios Financeiros</h1>

    <div class="relatorio-actions">
        <a href="<?= $linkPdf ?>" class="btn">
            Baixar PDF
        </a>

        <a href="<?= $linkCsv ?>" class="btn btn-csv">
            Baixar CSV
        </a>
    </div>

    <form method="GET" class="filtro-relatorio">

        <input
            type="date"
            name="data_inicio"
            value="<?= htmlspecialchars($dataInicio) ?>"
        >

        <input
            type="date"
            name="data_fim"
            value="<?= htmlspecialchars($dataFim) ?>"
        >

        <button type="submit" class="btn">
            Filtrar
        </button>

        <a href="relatorios.php" class="btn btn-secondary">
            Limpar
        </a>

    </form>

    <br>

    <?php if (!empty($dataInicio) || !empty($dataFim)): ?>
        <div class="periodo-info">
            <strong>Período filtrado:</strong>

            <?= !empty($dataInicio) ? date('d/m/Y', strtotime($dataInicio)) : 'Início' ?>

            até

            <?= !empty($dataFim) ? date('d/m/Y', strtotime($dataFim)) : 'Hoje' ?>
        </div>
    <?php endif; ?>

    <div class="cards">

        <div class="card">
            <h3>Receitas</h3>
            <p>R$ <?= number_format($receitas, 2, ',', '.') ?></p>
        </div>

        <div class="card">
            <h3>Despesas</h3>
            <p>R$ <?= number_format($despesas, 2, ',', '.') ?></p>
        </div>

        <div class="card">
            <h3>Saldo</h3>
            <p>R$ <?= number_format($saldo, 2, ',', '.') ?></p>
        </div>

        <div class="card">
            <h3>Movimentações</h3>
            <p><?= $totalMovimentacoes ?></p>
        </div>

    </div>

    <br><br>

    <div class="grafico-card">

        <h3>Receitas x Despesas</h3>

        <br>

        <?php if ($receitas > 0 || $despesas > 0): ?>

            <div style="width:250px;height:250px;margin:0 auto;">
                <canvas id="graficoFinanceiro"></canvas>
            </div>

        <?php else: ?>

            <p>Nenhum dado disponível para gerar o gráfico.</p>

        <?php endif; ?>

    </div>

    <br><br>

    <h2>Resumo Completo</h2>

    <br>

    <table class="table">

        <tr>
            <th>Descrição</th>
            <th>Tipo</th>
            <th>Valor</th>
            <th>Data</th>
        </tr>

        <?php if ($totalMovimentacoes == 0): ?>

            <tr>
                <td colspan="4">
                    Nenhuma movimentação encontrada.
                </td>
            </tr>

        <?php endif; ?>

        <?php foreach ($movimentacoes as $mov): ?>

            <tr>

                <td><?= htmlspecialchars($mov['descricao']) ?></td>

                <td><?= ucfirst($mov['tipo']) ?></td>

                <td>
                    R$ <?= number_format($mov['valor'], 2, ',', '.') ?>
                </td>

                <td>
                    <?= date('d/m/Y', strtotime($mov['data_movimentacao'])) ?>
                </td>

            </tr>

        <?php endforeach; ?>

    </table>

</div>

<?php if ($receitas > 0 || $despesas > 0): ?>

<script>

const ctx = document.getElementById('graficoFinanceiro');

new Chart(ctx, {

    type: 'doughnut',

    data: {

        labels: ['Receitas', 'Despesas'],

        datasets: [{

            data: [
                <?= $receitas ?>,
                <?= $despesas ?>
            ],

            backgroundColor: [
                '#22c55e',
                '#ef4444'
            ],

            borderColor: '#1e293b',
            borderWidth: 3

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        cutout: '60%',

        plugins: {

            legend: {

                position: 'bottom',

                labels: {
                    color: '#ffffff',
                    padding: 20,
                    font: {
                        size: 14
                    }
                }

            }

        }

    }

});

</script>

<?php endif; ?>

</body>
</html>