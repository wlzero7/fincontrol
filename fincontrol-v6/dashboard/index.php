<?php
require_once "../includes/session.php";
require_once "../config/database.php";

$usuario_id = $_SESSION['usuario_id'];

function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$posX = $usuario["avatar_pos_x"] ?? 50;
$posY = $usuario["avatar_pos_y"] ?? 50;
$zoom = $usuario["avatar_zoom"] ?? 100;
$scale = $zoom / 100;

$avatarSrc = "";

if (!empty($usuario["avatar"])) {
    $avatarSrc = "../assets/img/avatars/" . htmlspecialchars($usuario["avatar"]);
}

$avatarStyle = "object-position: {$posX}% {$posY}%; transform: scale({$scale});";

/* Totais gerais */
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) AS receitas,
        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS despesas,
        COUNT(*) AS total_movimentacoes
    FROM movimentacoes
    WHERE usuario_id = ?
");
$stmt->execute([$usuario_id]);
$resumo = $stmt->fetch(PDO::FETCH_ASSOC);

$receitas = $resumo['receitas'] ?? 0;
$despesas = $resumo['despesas'] ?? 0;
$saldo = $receitas - $despesas;
$totalMovimentacoes = $resumo['total_movimentacoes'] ?? 0;

/* Totais do mês */
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) AS receitas_mes,
        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS despesas_mes
    FROM movimentacoes
    WHERE usuario_id = ?
    AND MONTH(data_movimentacao) = MONTH(CURDATE())
    AND YEAR(data_movimentacao) = YEAR(CURDATE())
");
$stmt->execute([$usuario_id]);
$mes = $stmt->fetch(PDO::FETCH_ASSOC);

$receitasMes = $mes['receitas_mes'] ?? 0;
$despesasMes = $mes['despesas_mes'] ?? 0;
$saldoMes = $receitasMes - $despesasMes;

/* Últimas movimentações */
$stmt = $pdo->prepare("
    SELECT 
        movimentacoes.*,
        categorias.nome AS categoria_nome
    FROM movimentacoes
    LEFT JOIN categorias
        ON movimentacoes.categoria_id = categorias.id
    WHERE movimentacoes.usuario_id = ?
    ORDER BY movimentacoes.data_movimentacao DESC, movimentacoes.id DESC
    LIMIT 5
");
$stmt->execute([$usuario_id]);
$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Meta mais recente */
$stmt = $pdo->prepare("
    SELECT *
    FROM metas
    WHERE usuario_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$usuario_id]);
$meta = $stmt->fetch(PDO::FETCH_ASSOC);

$percentualMeta = 0;

if ($meta && $meta['valor_meta'] > 0) {
    $percentualMeta = ($saldo / $meta['valor_meta']) * 100;

    if ($percentualMeta > 100) {
        $percentualMeta = 100;
    }
}

/* Despesas por categoria */
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(categorias.nome, 'Sem categoria') AS categoria,
        SUM(movimentacoes.valor) AS total
    FROM movimentacoes
    LEFT JOIN categorias
        ON movimentacoes.categoria_id = categorias.id
    WHERE movimentacoes.usuario_id = ?
    AND movimentacoes.tipo = 'despesa'
    GROUP BY categorias.nome
");
$stmt->execute([$usuario_id]);
$despesasCategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoriasLabels = [];
$categoriasValores = [];

foreach ($despesasCategorias as $item) {
    $categoriasLabels[] = $item['categoria'];
    $categoriasValores[] = $item['total'];
}

/* Dados mensais */
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(data_movimentacao, '%m/%Y') AS mes,
        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) AS receitas,
        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS despesas
    FROM movimentacoes
    WHERE usuario_id = ?
    AND data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(data_movimentacao), MONTH(data_movimentacao)
    ORDER BY YEAR(data_movimentacao), MONTH(data_movimentacao)
");
$stmt->execute([$usuario_id]);
$dadosMensais = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labelsMeses = [];
$receitasMensais = [];
$despesasMensais = [];
$saldosMensais = [];

foreach ($dadosMensais as $linha) {
    $labelsMeses[] = $linha["mes"];
    $receitasMensais[] = $linha["receitas"];
    $despesasMensais[] = $linha["despesas"];
    $saldosMensais[] = $linha["receitas"] - $linha["despesas"];
}

/* Maior despesa */
$stmt = $pdo->prepare("
    SELECT descricao, valor
    FROM movimentacoes
    WHERE usuario_id = ?
    AND tipo = 'despesa'
    ORDER BY valor DESC
    LIMIT 1
");
$stmt->execute([$usuario_id]);
$maiorDespesa = $stmt->fetch(PDO::FETCH_ASSOC);

/* Maior categoria */
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(categorias.nome, 'Sem categoria') AS categoria,
        SUM(movimentacoes.valor) AS total
    FROM movimentacoes
    LEFT JOIN categorias
        ON movimentacoes.categoria_id = categorias.id
    WHERE movimentacoes.usuario_id = ?
    AND movimentacoes.tipo = 'despesa'
    GROUP BY categorias.nome
    ORDER BY total DESC
    LIMIT 1
");
$stmt->execute([$usuario_id]);
$maiorCategoria = $stmt->fetch(PDO::FETCH_ASSOC);

$hora = date("H");

if ($hora >= 5 && $hora < 12) {
    $saudacao = "Bom dia";
} elseif ($hora >= 12 && $hora < 18) {
    $saudacao = "Boa tarde";
} else {
    $saudacao = "Boa noite";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Dashboard | FinControl</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            margin-left: 20px;
        }

        .user-menu img,
        .user-avatar-placeholder {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid #38bdf8;
            object-fit: cover;
            background: #0f172a;
        }

        .user-avatar-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .dashboard-welcome {
            background: #1e293b;
            padding: 25px;
            border-radius: 18px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 22px;
            flex-wrap: wrap;
        }

        .dashboard-avatar {
            width: 95px;
            height: 95px;
            border-radius: 50%;
            border: 4px solid #38bdf8;
            object-fit: cover;
            background: #0f172a;
            transform-origin: center;
        }

        .dashboard-avatar-placeholder {
            width: 95px;
            height: 95px;
            border-radius: 50%;
            border: 4px solid #38bdf8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            background: #0f172a;
        }

        .welcome-text h1 {
            margin-bottom: 6px;
        }

        .welcome-text p {
            color: #cbd5e1;
            font-size: 16px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .chart-card {
            background: #1e293b;
            padding: 25px;
            border-radius: 18px;
        }

        .chart-box {
            height: 280px;
            margin-top: 20px;
        }

        .insight-card {
            background: #1e293b;
            padding: 25px;
            border-radius: 18px;
            margin-bottom: 20px;
        }

        .insight-card h3 {
            color: #93c5fd;
            margin-bottom: 10px;
        }

        .insight-card p {
            font-size: 28px;
            font-weight: bold;
        }

        .insight-card small {
            color: #cbd5e1;
        }

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .user-menu span {
                display: none;
            }
        }
    </style>
</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="container">

    <div class="dashboard-welcome">

        <?php if (!empty($usuario["avatar"])): ?>
            <img 
                src="<?= $avatarSrc ?>?v=<?= time() ?>" 
                class="dashboard-avatar"
                alt="Avatar do usuário"
                style="<?= $avatarStyle ?>"
            >
        <?php else: ?>
            <div class="dashboard-avatar-placeholder">👤</div>
        <?php endif; ?>

        <div class="welcome-text">
            <h1><?= $saudacao ?>, <?= htmlspecialchars($usuario["nome"]) ?></h1>
            <p>Seu resumo financeiro está atualizado.</p>
        </div>

    </div>

    <div class="cards">

        <div class="card">
            <h3>Saldo Total</h3>
            <p><?= formatarMoeda($saldo) ?></p>
        </div>

        <div class="card">
            <h3>Receita do Mês</h3>
            <p><?= formatarMoeda($receitasMes) ?></p>
        </div>

        <div class="card">
            <h3>Despesa do Mês</h3>
            <p><?= formatarMoeda($despesasMes) ?></p>
        </div>

        <div class="card">
            <h3>Saldo do Mês</h3>
            <p><?= formatarMoeda($saldoMes) ?></p>
        </div>

    </div>

    <br>

    <div class="cards">

        <div class="card">
            <h3>Receitas Totais</h3>
            <p><?= formatarMoeda($receitas) ?></p>
        </div>

        <div class="card">
            <h3>Despesas Totais</h3>
            <p><?= formatarMoeda($despesas) ?></p>
        </div>

        <div class="card">
            <h3>Movimentações</h3>
            <p><?= $totalMovimentacoes ?></p>
        </div>

        <div class="card">
            <h3>Meta Financeira</h3>

            <?php if ($meta): ?>
                <p><?= round($percentualMeta) ?>%</p>
                <small><?= htmlspecialchars($meta['titulo']) ?></small>
            <?php else: ?>
                <p>0%</p>
                <small>Nenhuma meta cadastrada</small>
            <?php endif; ?>
        </div>

    </div>

    <br><br>

    <div class="dashboard-grid">

        <div class="chart-card">
            <h3>Evolução dos últimos 6 meses</h3>

            <?php if (count($dadosMensais) > 0): ?>
                <div class="chart-box">
                    <canvas id="graficoMensal"></canvas>
                </div>
            <?php else: ?>
                <p>Nenhum dado mensal disponível.</p>
            <?php endif; ?>
        </div>

        <div>

            <div class="insight-card">
                <h3>Maior despesa</h3>

                <?php if ($maiorDespesa): ?>
                    <p><?= formatarMoeda($maiorDespesa["valor"]) ?></p>
                    <small><?= htmlspecialchars($maiorDespesa["descricao"]) ?></small>
                <?php else: ?>
                    <p>R$ 0,00</p>
                    <small>Nenhuma despesa cadastrada</small>
                <?php endif; ?>
            </div>

            <div class="insight-card">
                <h3>Categoria com maior gasto</h3>

                <?php if ($maiorCategoria): ?>
                    <p><?= formatarMoeda($maiorCategoria["total"]) ?></p>
                    <small><?= htmlspecialchars($maiorCategoria["categoria"]) ?></small>
                <?php else: ?>
                    <p>R$ 0,00</p>
                    <small>Nenhuma categoria encontrada</small>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <div class="dashboard-grid">

        <div class="chart-card">
            <h3>Despesas por Categoria</h3>

            <?php if (count($despesasCategorias) > 0): ?>
                <div style="width:260px;height:260px;margin:20px auto 0;">
                    <canvas id="graficoCategorias"></canvas>
                </div>
            <?php else: ?>
                <p>Nenhuma despesa cadastrada.</p>
            <?php endif; ?>
        </div>

        <div class="chart-card">
            <h3>Saldo mensal</h3>

            <?php if (count($dadosMensais) > 0): ?>
                <div class="chart-box">
                    <canvas id="graficoSaldo"></canvas>
                </div>
            <?php else: ?>
                <p>Nenhum dado disponível.</p>
            <?php endif; ?>
        </div>

    </div>

    <div class="content">

        <h2>Últimas movimentações</h2>

        <br>

        <table class="table">
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th>Valor</th>
            </tr>

            <?php if (count($movimentacoes) === 0): ?>
                <tr>
                    <td colspan="5">Nenhuma movimentação cadastrada.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($movimentacoes as $mov): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($mov['data_movimentacao'])) ?></td>
                    <td><?= htmlspecialchars($mov['descricao']) ?></td>
                    <td><?= $mov['categoria_nome'] ? htmlspecialchars($mov['categoria_nome']) : 'Sem categoria' ?></td>
                    <td><?= ucfirst($mov['tipo']) ?></td>
                    <td><?= formatarMoeda($mov['valor']) ?></td>
                </tr>
            <?php endforeach; ?>

        </table>

    </div>

</div>

<?php if (count($dadosMensais) > 0): ?>
<script>
new Chart(document.getElementById('graficoMensal'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsMeses) ?>,
        datasets: [
            {
                label: 'Receitas',
                data: <?= json_encode($receitasMensais) ?>,
                backgroundColor: '#22c55e'
            },
            {
                label: 'Despesas',
                data: <?= json_encode($despesasMensais) ?>,
                backgroundColor: '#ef4444'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#ffffff'
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#cbd5e1'
                },
                grid: {
                    color: '#334155'
                }
            },
            y: {
                ticks: {
                    color: '#cbd5e1'
                },
                grid: {
                    color: '#334155'
                }
            }
        }
    }
});

new Chart(document.getElementById('graficoSaldo'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labelsMeses) ?>,
        datasets: [{
            label: 'Saldo',
            data: <?= json_encode($saldosMensais) ?>,
            borderColor: '#38bdf8',
            backgroundColor: 'rgba(56,189,248,.2)',
            tension: .4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#ffffff'
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#cbd5e1'
                },
                grid: {
                    color: '#334155'
                }
            },
            y: {
                ticks: {
                    color: '#cbd5e1'
                },
                grid: {
                    color: '#334155'
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php if (count($despesasCategorias) > 0): ?>
<script>
new Chart(document.getElementById('graficoCategorias'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($categoriasLabels) ?>,
        datasets: [{
            data: <?= json_encode($categoriasValores) ?>,
            backgroundColor: [
                '#ef4444',
                '#f97316',
                '#eab308',
                '#22c55e',
                '#06b6d4',
                '#3b82f6',
                '#8b5cf6',
                '#ec4899'
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
                    padding: 18,
                    font: {
                        size: 13
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<script src="../assets/js/dashboard.js"></script>
<?php include "../includes/footer.php"; ?>
</body>
</html>