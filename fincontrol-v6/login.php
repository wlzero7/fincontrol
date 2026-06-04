<?php
session_start();

require_once __DIR__ . "/config/database.php";

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $senha = $_POST["senha"];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $erro = "E-mail não encontrado.";
    } elseif (!password_verify($senha, $usuario["senha"])) {
        $erro = "Senha incorreta.";
    } elseif (!$usuario["verificado"]) {
        $erro = "Confirme seu e-mail antes de acessar.";
    } else {
        $_SESSION["usuario_id"] = $usuario["id"];
        $_SESSION["usuario_nome"] = $usuario["nome"];

        header("Location: dashboard/index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login | FinControl</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <h1>💰 FinControl</h1>
        <h2>Entrar</h2>
        <p>Acesse seu painel financeiro.</p>

        <?php if ($erro): ?>
            <div class="alert error"><?= $erro ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="senha" placeholder="Senha" required>

            <button type="submit">Entrar</button>
        </form>

        <a href="cadastro.php">Criar uma conta</a>
    </div>
</div>

</body>
</html>