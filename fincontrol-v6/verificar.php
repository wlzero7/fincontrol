<?php
session_start();

require_once __DIR__ . "/config/database.php";

$erro = "";
$sucesso = "";

$email = $_SESSION["email_verificacao"] ?? null;
$codigoDemo = $_SESSION["codigo_demo"] ?? null;

if (!$email) {
    header("Location: cadastro.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo = strtoupper(trim($_POST["codigo"]));

    $stmt = $pdo->prepare("
        SELECT usuarios.id, codigos_verificacao.codigo, codigos_verificacao.expiracao
        FROM usuarios
        INNER JOIN codigos_verificacao ON usuarios.id = codigos_verificacao.usuario_id
        WHERE usuarios.email = ?
        ORDER BY codigos_verificacao.id DESC
        LIMIT 1
    ");

    $stmt->execute([$email]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        $erro = "Código não encontrado.";
    } elseif ($registro["codigo"] !== $codigo) {
        $erro = "Código incorreto.";
    } elseif (strtotime($registro["expiracao"]) < time()) {
        $erro = "Código expirado.";
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET verificado = 1 WHERE id = ?");
        $stmt->execute([$registro["id"]]);

        $stmt = $pdo->prepare("DELETE FROM codigos_verificacao WHERE usuario_id = ?");
        $stmt->execute([$registro["id"]]);

        unset($_SESSION["email_verificacao"]);
        unset($_SESSION["codigo_demo"]);

        $sucesso = "Conta verificada com sucesso! Você já pode fazer login.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verificação | FinControl</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <h1>💰 FinControl</h1>
        <h2>Verificar e-mail</h2>
        <p>Digite o código de 6 caracteres enviado para seu e-mail.</p>

        <?php if ($codigoDemo): ?>
            <div class="alert info">
                Código de demonstração: <strong><?= $codigoDemo ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert error"><?= $erro ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert success"><?= $sucesso ?></div>
            <a href="login.php">Ir para login</a>
        <?php else: ?>
            <form method="POST">
                <input type="text" name="codigo" maxlength="6" placeholder="Código de verificação" required>
                <button type="submit">Confirmar</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>