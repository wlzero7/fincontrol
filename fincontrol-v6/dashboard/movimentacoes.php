<?php
require_once "../includes/session.php";
require_once "../config/database.php";

$usuario_id = $_SESSION['usuario_id'];

if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];

    $stmt = $pdo->prepare("
        DELETE FROM movimentacoes
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$id, $usuario_id]);

    header("Location: movimentacoes.php?excluido=1");
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM categorias
    WHERE usuario_id = ?
    ORDER BY nome ASC
");
$stmt->execute([$usuario_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$movEditar = null;

if (isset($_GET['editar'])) {
    $id = $_GET['editar'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM movimentacoes
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$id, $usuario_id]);

    $movEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = trim($_POST['descricao']);
    $valor = $_POST['valor'];
    $tipo = $_POST['tipo'];
    $categoria_id = !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
    $data = $_POST['data_movimentacao'];
    $id = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("
            UPDATE movimentacoes
            SET descricao = ?, valor = ?, tipo = ?, categoria_id = ?, data_movimentacao = ?
            WHERE id = ? AND usuario_id = ?
        ");

        $stmt->execute([
            $descricao,
            $valor,
            $tipo,
            $categoria_id,
            $data,
            $id,
            $usuario_id
        ]);

        header("Location: movimentacoes.php?atualizado=1");
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO movimentacoes
        (usuario_id, categoria_id, descricao, valor, tipo, data_movimentacao)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $usuario_id,
        $categoria_id,
        $descricao,
        $valor,
        $tipo,
        $data
    ]);

    header("Location: movimentacoes.php?sucesso=1");
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        movimentacoes.*,
        categorias.nome AS categoria_nome
    FROM movimentacoes
    LEFT JOIN categorias 
        ON movimentacoes.categoria_id = categorias.id
    WHERE movimentacoes.usuario_id = ?
    ORDER BY movimentacoes.data_movimentacao DESC, movimentacoes.id DESC
");
$stmt->execute([$usuario_id]);
$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}

$alerta = "";

if (isset($_GET['sucesso'])) {
    $alerta = "sucesso";
}

if (isset($_GET['atualizado'])) {
    $alerta = "atualizado";
}

if (isset($_GET['excluido'])) {
    $alerta = "excluido";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">

    <title>Movimentações | FinControl</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .mov-form-card {
            background: #1e293b;
            padding: 28px;
            border-radius: 18px;
            max-width: 520px;
            margin-bottom: 35px;
        }

        .mov-form-card input,
        .mov-form-card select {
            max-width: 100% !important;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filtros-mov {
            background: #1e293b;
            padding: 22px;
            border-radius: 18px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
            align-items: center;
        }

        .filtros-mov input,
        .filtros-mov select {
            max-width: 260px !important;
            margin-bottom: 0 !important;
        }

        .filtro-btn {
            background: linear-gradient(135deg, #475569, #64748b) !important;
        }

        .badge-receita {
            background: rgba(34, 197, 94, 0.18);
            color: #22c55e;
            padding: 7px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }

        .badge-despesa {
            background: rgba(239, 68, 68, 0.18);
            color: #ef4444;
            padding: 7px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }

        .acao-editar {
            background: linear-gradient(135deg, #06b6d4, #2563eb);
            color: #fff !important;
            padding: 9px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            margin-right: 8px;
            display: inline-block;
        }

        .acao-excluir {
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: #fff !important;
            padding: 9px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
        }

        .acao-editar:hover,
        .acao-excluir:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .sem-resultados {
            display: none;
            background: #1e293b;
            padding: 18px;
            border-radius: 14px;
            color: #cbd5e1;
            margin-top: 15px;
        }

        @media (max-width: 700px) {
            .filtros-mov input,
            .filtros-mov select {
                max-width: 100% !important;
                width: 100% !important;
            }

            .mov-form-card {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="container">

    <h1>
        <?= $movEditar ? "Editar Movimentação" : "Nova Movimentação" ?>
    </h1>

    <div class="mov-form-card">

        <form method="POST">

            <?php if ($movEditar): ?>
                <input type="hidden" name="id" value="<?= $movEditar['id'] ?>">
            <?php endif; ?>

            <input
                type="text"
                name="descricao"
                placeholder="Descrição"
                value="<?= $movEditar ? htmlspecialchars($movEditar['descricao']) : '' ?>"
                required
            >

            <input
                type="number"
                step="0.01"
                name="valor"
                placeholder="Valor"
                value="<?= $movEditar ? $movEditar['valor'] : '' ?>"
                required
            >

            <select name="tipo" required>
                <option value="receita" <?= $movEditar && $movEditar['tipo'] === 'receita' ? 'selected' : '' ?>>
                    Receita
                </option>

                <option value="despesa" <?= $movEditar && $movEditar['tipo'] === 'despesa' ? 'selected' : '' ?>>
                    Despesa
                </option>
            </select>

            <select name="categoria_id">
                <option value="">Sem categoria</option>

                <?php foreach ($categorias as $cat): ?>
                    <option 
                        value="<?= $cat['id'] ?>"
                        <?= $movEditar && $movEditar['categoria_id'] == $cat['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($cat['nome']) ?> - <?= ucfirst($cat['tipo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input
                type="date"
                name="data_movimentacao"
                value="<?= $movEditar ? $movEditar['data_movimentacao'] : date('Y-m-d') ?>"
                required
            >

            <div class="form-actions">
                <button class="btn">
                    <?= $movEditar ? "Atualizar" : "Salvar" ?>
                </button>

                <?php if ($movEditar): ?>
                    <a href="movimentacoes.php" class="btn btn-secondary">
                        Cancelar
                    </a>
                <?php endif; ?>
            </div>

        </form>

    </div>

    <h2>Movimentações</h2>

    <div class="filtros-mov">
        <select id="filtroTipo">
            <option value="">Todos os tipos</option>
            <option value="receita">Receitas</option>
            <option value="despesa">Despesas</option>
        </select>

        <select id="filtroCategoria">
            <option value="">Todas as categorias</option>

            <?php foreach ($categorias as $cat): ?>
                <option value="<?= htmlspecialchars(strtolower($cat['nome'])) ?>">
                    <?= htmlspecialchars($cat['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input
            type="text"
            id="filtroBusca"
            placeholder="Pesquisar descrição..."
        >

        <button type="button" class="btn filtro-btn" id="limparFiltros">
            Limpar filtros
        </button>
    </div>

    <table class="table" id="tabelaMovimentacoes">
        <thead>
            <tr>
                <th>ID</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>
            <?php if (count($movimentacoes) === 0): ?>
                <tr>
                    <td colspan="7">Nenhuma movimentação cadastrada.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($movimentacoes as $mov): ?>
                <?php
                    $categoriaNome = $mov['categoria_nome'] ? $mov['categoria_nome'] : 'Sem categoria';
                    $tipoLabel = ucfirst($mov['tipo']);
                ?>

                <tr
                    class="linha-mov"
                    data-tipo="<?= htmlspecialchars($mov['tipo']) ?>"
                    data-categoria="<?= htmlspecialchars(strtolower($categoriaNome)) ?>"
                    data-descricao="<?= htmlspecialchars(strtolower($mov['descricao'])) ?>"
                >
                    <td><?= $mov['id'] ?></td>

                    <td><?= htmlspecialchars($mov['descricao']) ?></td>

                    <td><?= htmlspecialchars($categoriaNome) ?></td>

                    <td>
                        <span class="<?= $mov['tipo'] === 'receita' ? 'badge-receita' : 'badge-despesa' ?>">
                            <?= $tipoLabel ?>
                        </span>
                    </td>

                    <td><?= formatarMoeda($mov['valor']) ?></td>

                    <td><?= date('d/m/Y', strtotime($mov['data_movimentacao'])) ?></td>

                    <td>
                        <a
                            href="movimentacoes.php?editar=<?= $mov['id'] ?>"
                            class="acao-editar"
                        >
                            Editar
                        </a>

                        <a
                            href="movimentacoes.php?excluir=<?= $mov['id'] ?>"
                            class="acao-excluir"
                            onclick="return confirmarExclusao(event, this.href)"
                        >
                            Excluir
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div id="semResultados" class="sem-resultados">
        Nenhuma movimentação encontrada com os filtros selecionados.
    </div>

</div>

<script>
const filtroTipo = document.getElementById("filtroTipo");
const filtroCategoria = document.getElementById("filtroCategoria");
const filtroBusca = document.getElementById("filtroBusca");
const limparFiltros = document.getElementById("limparFiltros");
const semResultados = document.getElementById("semResultados");
const alertaSistema = "<?= $alerta ?>";

function aplicarFiltros() {
    const tipo = filtroTipo.value.toLowerCase();
    const categoria = filtroCategoria.value.toLowerCase();
    const busca = filtroBusca.value.toLowerCase();

    const linhas = document.querySelectorAll(".linha-mov");
    let totalVisiveis = 0;

    linhas.forEach(function(linha) {
        const linhaTipo = linha.dataset.tipo.toLowerCase();
        const linhaCategoria = linha.dataset.categoria.toLowerCase();
        const linhaDescricao = linha.dataset.descricao.toLowerCase();

        let mostrar = true;

        if (tipo && linhaTipo !== tipo) {
            mostrar = false;
        }

        if (categoria && linhaCategoria !== categoria) {
            mostrar = false;
        }

        if (busca && !linhaDescricao.includes(busca)) {
            mostrar = false;
        }

        linha.style.display = mostrar ? "" : "none";

        if (mostrar) {
            totalVisiveis++;
        }
    });

    semResultados.style.display = totalVisiveis === 0 && linhas.length > 0 ? "block" : "none";
}

function confirmarExclusao(event, url) {
    event.preventDefault();

    Swal.fire({
        title: "Excluir movimentação?",
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

function mostrarAlertaSistema() {
    if (alertaSistema === "sucesso") {
        Swal.fire({
            icon: "success",
            title: "Movimentação salva!",
            text: "A movimentação foi cadastrada com sucesso.",
            background: "#1e293b",
            color: "#ffffff",
            confirmButtonColor: "#2563eb"
        });
    }

    if (alertaSistema === "atualizado") {
        Swal.fire({
            icon: "success",
            title: "Movimentação atualizada!",
            text: "As alterações foram salvas com sucesso.",
            background: "#1e293b",
            color: "#ffffff",
            confirmButtonColor: "#2563eb"
        });
    }

    if (alertaSistema === "excluido") {
        Swal.fire({
            icon: "success",
            title: "Movimentação excluída!",
            text: "O registro foi removido com sucesso.",
            background: "#1e293b",
            color: "#ffffff",
            confirmButtonColor: "#2563eb"
        });
    }
}

filtroTipo.addEventListener("change", aplicarFiltros);
filtroCategoria.addEventListener("change", aplicarFiltros);
filtroBusca.addEventListener("keyup", aplicarFiltros);

limparFiltros.addEventListener("click", function() {
    filtroTipo.value = "";
    filtroCategoria.value = "";
    filtroBusca.value = "";
    aplicarFiltros();
});

mostrarAlertaSistema();
</script>
<?php include "../includes/footer.php"; ?>
</body>
</html>