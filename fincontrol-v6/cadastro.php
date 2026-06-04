<?php
session_start();

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/auth/mailer.php";

$erro = "";
$sucesso = "";

function gerarCodigo($tamanho = 6) {
    $caracteres = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $codigo = "";

    for ($i = 0; $i < $tamanho; $i++) {
        $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }

    return $codigo;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);
    $senha = $_POST["senha"];

    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $erro = "Este e-mail já está cadastrado.";
        } else {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nome, email, senha, verificado)
                VALUES (?, ?, ?, 0)
            ");

            $stmt->execute([$nome, $email, $senhaHash]);

            $usuarioId = $pdo->lastInsertId();
            $codigo = gerarCodigo();
            $expiracao = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            $stmt = $pdo->prepare("
                INSERT INTO codigos_verificacao (usuario_id, codigo, expiracao)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([$usuarioId, $codigo, $expiracao]);

            $_SESSION["email_verificacao"] = $email;
            $_SESSION["codigo_demo"] = $codigo;

            enviarCodigo($email, $codigo);

            header("Location: verificar.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro | FinControl</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <h1>💰 FinControl</h1>
        <h2>Criar conta</h2>
        <p>Cadastre-se para começar a controlar suas finanças.</p>

        <?php if ($erro): ?>
            <div class="alert error"><?= $erro ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="nome" placeholder="Nome completo" required>
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="senha" placeholder="Senha" required>

            <button type="submit">Criar conta</button>
        </form>

        <a href="login.php">Já tenho uma conta</a>
    </div>
</div>

</body>
</html>