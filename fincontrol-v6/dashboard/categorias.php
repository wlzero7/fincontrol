<?php
require_once "../includes/session.php";
require_once "../config/database.php";

$usuario_id = $_SESSION['usuario_id'];
$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"]);
    $tipo = $_POST["tipo"];

    if (empty($nome)) {
        $erro = "Digite o nome da categoria.";
    } else {
        $sql = "INSERT INTO categorias (usuario_id, nome, tipo) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $nome, $tipo]);

        header("Location: categorias.php");
        exit;
    }
}

if (isset($_GET["excluir"])) {
    $id = $_GET["excluir"];

    $sql = "DELETE FROM categorias WHERE id = ? AND usuario_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $usuario_id]);

    header("Location: categorias.php");
    exit;
}

$sql = "SELECT * FROM categorias WHERE usuario_id = ? ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Categorias | FinControl</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="container">

    <h1>Categorias</h1>

    <br>

    <?php if ($erro): ?>
        <p style="color: #ff6b6b;"><?= $erro ?></p>
        <br>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="nome" placeholder="Nome da categoria" required>

        <br><br>

        <select name="tipo">
            <option value="receita">Receita</option>
            <option value="despesa">Despesa</option>
        </select>

        <br><br>

        <button class="btn">Adicionar categoria</button>
    </form>

    <br><br>

    <h2>Minhas categorias</h2>

    <br>

    <table class="table">
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Ação</th>
        </tr>

        <?php if (count($categorias) === 0): ?>
            <tr>
                <td colspan="4">Nenhuma categoria cadastrada.</td>
            </tr>
        <?php endif; ?>

        <?php foreach ($categorias as $cat): ?>
            <tr>
                <td><?= $cat["id"] ?></td>
                <td><?= htmlspecialchars($cat["nome"]) ?></td>
                <td><?= ucfirst($cat["tipo"]) ?></td>
                <td>
                    <a 
                        href="categorias.php?excluir=<?= $cat["id"] ?>" 
                        onclick="return confirm('Deseja excluir esta categoria?')"
                        style="color:#ff6b6b; font-weight:bold;"
                    >
                        Excluir
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

</div>
<?php include "../includes/footer.php"; ?>
</body>
</html>