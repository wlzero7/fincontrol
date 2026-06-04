<?php
require_once "../includes/session.php";
require_once "../config/database.php";

$usuario_id = $_SESSION['usuario_id'];

function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}

$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) AS receitas,
        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS despesas
    FROM movimentacoes
    WHERE usuario_id = ?
");
$stmt->execute([$usuario_id]);
$resumo = $stmt->fetch(PDO::FETCH_ASSOC);

$saldoAtual = ($resumo['receitas'] ?? 0) - ($resumo['despesas'] ?? 0);

$alerta = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST["titulo"]);
    $valor_meta = $_POST["valor_meta"];

    if (!empty($titulo) && $valor_meta > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO metas (usuario_id, titulo, valor_meta, valor_atual)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $usuario_id,
            $titulo,
            $valor_meta,
            $saldoAtual
        ]);

        header("Location: metas.php?sucesso=1");
        exit;
    }
}

if (isset($_GET["excluir"])) {
    $id = $_GET["excluir"];

    $stmt = $pdo->prepare("
        DELETE FROM metas
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$id, $usuario_id]);

    header("Location: metas.php?excluido=1");
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM metas
    WHERE usuario_id = ?
    ORDER BY id DESC
");
$stmt->execute([$usuario_id]);

$metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET["sucesso"])) {
    $alerta = "sucesso";
}

if (isset($_GET["excluido"])) {
    $alerta = "excluido";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">

    <title>Metas | FinControl</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .metas-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .metas-resumo {
            background: #1e293b;
            padding: 18px 22px;
            border-radius: 18px;
            color: #cbd5e1;
        }

        .metas-resumo strong {
            display: block;
            color: #93c5fd;
            margin-bottom: 6px;
        }

        .meta-form-card {
            background: #1e293b;
            padding: 28px;
            border-radius: 18px;
            max-width: 520px;
            margin-bottom: 35px;
        }

        .meta-form-card input {
            max-width: 100% !important;
        }

        .metas-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
        }

        .meta-card {
            background: #1e293b;
            padding: 26px;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.12);
        }

        .meta-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            height: 5px;
            width: 100%;
            background: linear-gradient(135deg, #2563eb, #38bdf8);
        }

        .meta-topo {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .meta-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .meta-title h2 {
            margin: 0;
        }

        .meta-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .meta-percentual {
            font-size: 34px;
            font-weight: bold;
            color: #ffffff;
        }

        .meta-info {
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .meta-faltante {
            color: #93c5fd;
            font-weight: bold;
            margin-top: 8px;
        }

        .progress-container {
            width: 100%;
            height: 18px;
            background: #0f172a;
            border-radius: 30px;
            overflow: hidden;
            margin: 18px 0;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #22c55e, #38bdf8);
            border-radius: 30px;
            transition: width 0.5s;
        }

        .progress-bar.concluida {
            background: linear-gradient(135deg, #22c55e, #84cc16);
        }

        .meta-actions {
            display: flex;
            gap: 12px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .delete-link {
            background: linear-gradient(135deg, #ef4444, #f97316) !important;
            color: #ffffff !important;
            padding: 11px 16px !important;
            border-radius: 12px !important;
            text-decoration: none !important;
            font-weight: bold !important;
            display: inline-block;
        }

        .empty-meta {
            background: #1e293b;
            padding: 28px;
            border-radius: 18px;
            color: #cbd5e1;
        }

        @media (max-width: 900px) {
            .metas-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="container">

    <div class="metas-header">
        <div>
            <h1>Metas Financeiras</h1>
            <p style="color:#cbd5e1;">
                Acompanhe seus objetivos com base no seu saldo atual.
            </p>
        </div>

        <div class="metas-resumo">
            <strong>Saldo atual disponível</strong>
            <?= formatarMoeda($saldoAtual) ?>
        </div>
    </div>

    <div class="meta-form-card">

        <h2>Nova Meta</h2>

        <form method="POST">
            <input
                type="text"
                name="titulo"
                placeholder="Ex: Comprar Notebook"
                required
            >

            <input
                type="number"
                step="0.01"
                name="valor_meta"
                placeholder="Valor da meta"
                required
            >

            <button class="btn">
                Salvar Meta
            </button>
        </form>

    </div>

    <h2>Minhas Metas</h2>

    <br>

    <?php if (count($metas) === 0): ?>
        <div class="empty-meta">
            Nenhuma meta cadastrada ainda.
        </div>
    <?php endif; ?>

    <div class="metas-grid">

        <?php foreach ($metas as $meta): ?>

            <?php
                $percentual = 0;

                if ($meta["valor_meta"] > 0) {
                    $percentual = ($saldoAtual / $meta["valor_meta"]) * 100;
                }

                if ($percentual > 100) {
                    $percentual = 100;
                }

                $percentualArredondado = round($percentual);
                $valorFaltante = $meta["valor_meta"] - $saldoAtual;

                if ($valorFaltante < 0) {
                    $valorFaltante = 0;
                }
            ?>

            <div class="meta-card">

                <div class="meta-topo">

                    <div class="meta-title">
                        <div class="meta-icon">🎯</div>

                        <div>
                            <h2><?= htmlspecialchars($meta["titulo"]) ?></h2>

                            <p class="meta-info">
                                <?= formatarMoeda($saldoAtual) ?> /
                                <?= formatarMoeda($meta["valor_meta"]) ?>
                            </p>
                        </div>
                    </div>

                    <div class="meta-percentual">
                        <?= $percentualArredondado ?>%
                    </div>

                </div>

                <div class="progress-container">
                    <div
                        class="progress-bar <?= $percentualArredondado >= 100 ? 'concluida' : '' ?>"
                        style="width: <?= $percentualArredondado ?>%;"
                    ></div>
                </div>

                <?php if ($percentualArredondado >= 100): ?>
                    <p class="meta-faltante">
                        🎉 Meta concluída!
                    </p>
                <?php else: ?>
                    <p class="meta-faltante">
                        Faltam <?= formatarMoeda($valorFaltante) ?> para concluir.
                    </p>
                <?php endif; ?>

                <div class="meta-actions">
                    <a
                        href="metas.php?excluir=<?= $meta["id"] ?>"
                        onclick="return confirmarExclusao(event, this.href)"
                        class="delete-link"
                    >
                        Excluir meta
                    </a>
                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<script>
const alertaSistema = "<?= $alerta ?>";

function confirmarExclusao(event, url) {
    event.preventDefault();

    Swal.fire({
        title: "Excluir meta?",
        text: "Esta ação não poderá ser desfeita.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#2563eb",
        confirmButtonText: "Sim, excluir",
        cancelButtonText: "Cancelar",
        background: "#1e293b",
        color: "#ffffff"
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });

    return false;
}

if (alertaSistema === "sucesso") {
    Swal.fire({
        icon: "success",
        title: "Meta criada!",
        text: "Sua meta financeira foi cadastrada com sucesso.",
        background: "#1e293b",
        color: "#ffffff",
        confirmButtonColor: "#2563eb"
    });
}

if (alertaSistema === "excluido") {
    Swal.fire({
        icon: "success",
        title: "Meta excluída!",
        text: "A meta foi removida com sucesso.",
        background: "#1e293b",
        color: "#ffffff",
        confirmButtonColor: "#2563eb"
    });
}
</script>
<?php include "../includes/footer.php"; ?>
</body>
</html>