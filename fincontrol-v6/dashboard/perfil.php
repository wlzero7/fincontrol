<?php
require_once "../includes/session.php";
require_once "../config/database.php";

$usuario_id = $_SESSION["usuario_id"];
$erro = "";
$alerta = "";

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($_GET["remover_avatar"])) {
    if (!empty($usuario["avatar"])) {
        $arquivo = __DIR__ . "/../assets/img/avatars/" . $usuario["avatar"];

        if (file_exists($arquivo)) {
            unlink($arquivo);
        }
    }

    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET avatar = NULL, avatar_pos_x = 50, avatar_pos_y = 50, avatar_zoom = 100
        WHERE id = ?
    ");
    $stmt->execute([$usuario_id]);

    header("Location: perfil.php?avatar_removido=1");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);
    $nova_senha = trim($_POST["nova_senha"]);

    $avatar_pos_x = $_POST["avatar_pos_x"] ?? 50;
    $avatar_pos_y = $_POST["avatar_pos_y"] ?? 50;
    $avatar_zoom = $_POST["avatar_zoom"] ?? 100;

    $avatar = $usuario["avatar"];
    $fotoAlterada = false;

    if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] === UPLOAD_ERR_OK) {
        $pasta = __DIR__ . "/../assets/img/avatars/";

        if (!is_dir($pasta)) {
            mkdir($pasta, 0777, true);
        }

        $extensao = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        $permitidas = ["jpg", "jpeg", "png", "webp"];

        if (in_array($extensao, $permitidas)) {
            if (!empty($usuario["avatar"])) {
                $antigo = $pasta . $usuario["avatar"];

                if (file_exists($antigo)) {
                    unlink($antigo);
                }
            }

            $nomeArquivo = "user_" . $usuario_id . "_" . time() . "." . $extensao;
            $caminhoFinal = $pasta . $nomeArquivo;

            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $caminhoFinal)) {
                $avatar = $nomeArquivo;
                $fotoAlterada = true;
            } else {
                $erro = "Erro ao salvar a imagem.";
            }
        } else {
            $erro = "Formato inválido. Use JPG, PNG ou WEBP.";
        }
    }

    if (empty($erro)) {
        if (!empty($nova_senha)) {
            $senhaHash = password_hash($nova_senha, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nome = ?, email = ?, senha = ?, avatar = ?, avatar_pos_x = ?, avatar_pos_y = ?, avatar_zoom = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $nome,
                $email,
                $senhaHash,
                $avatar,
                $avatar_pos_x,
                $avatar_pos_y,
                $avatar_zoom,
                $usuario_id
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nome = ?, email = ?, avatar = ?, avatar_pos_x = ?, avatar_pos_y = ?, avatar_zoom = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $nome,
                $email,
                $avatar,
                $avatar_pos_x,
                $avatar_pos_y,
                $avatar_zoom,
                $usuario_id
            ]);
        }

        $_SESSION["usuario_nome"] = $nome;

        if ($fotoAlterada) {
            header("Location: perfil.php?foto=1");
            exit;
        }

        header("Location: perfil.php?sucesso=1");
        exit;
    }
}

if (isset($_GET["sucesso"])) {
    $alerta = "sucesso";
}

if (isset($_GET["foto"])) {
    $alerta = "foto";
}

if (isset($_GET["avatar_removido"])) {
    $alerta = "avatar_removido";
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM movimentacoes WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$totalMovimentacoes = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$totalCategorias = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM metas WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$totalMetas = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) AS receitas,
        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS despesas
    FROM movimentacoes
    WHERE usuario_id = ?
");
$stmt->execute([$usuario_id]);
$resumo = $stmt->fetch(PDO::FETCH_ASSOC);

$saldo = ($resumo["receitas"] ?? 0) - ($resumo["despesas"] ?? 0);

$posX = $usuario["avatar_pos_x"] ?? 50;
$posY = $usuario["avatar_pos_y"] ?? 50;
$zoom = $usuario["avatar_zoom"] ?? 100;
$scale = $zoom / 100;

function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">

    <title>Perfil | FinControl</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .perfil-layout {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 25px;
            align-items: start;
        }

        .perfil-user-card {
            background: #1e293b;
            padding: 30px;
            border-radius: 22px;
            text-align: center;
            border: 1px solid rgba(148, 163, 184, 0.12);
            position: sticky;
            top: 30px;
        }

        .avatar-box {
            position: relative;
            width: 165px;
            height: 165px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid #38bdf8;
            background: #0f172a;
            cursor: pointer;
            margin: 0 auto 20px;
            box-shadow: 0 0 30px rgba(56, 189, 248, 0.25);
        }

        .avatar-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            transform-origin: center;
        }

        .avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 55px;
        }

        .avatar-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .68);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            font-weight: bold;
            opacity: 0;
            transition: .3s;
            font-size: 15px;
        }

        .avatar-box:hover .avatar-overlay {
            opacity: 1;
        }

        .perfil-user-card h2 {
            margin-bottom: 6px;
            font-size: 28px;
        }

        .perfil-user-card p {
            color: #cbd5e1;
            margin-bottom: 6px;
            word-break: break-word;
        }

        .status-conta {
            display: inline-block;
            margin-top: 14px;
            padding: 9px 15px;
            border-radius: 999px;
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
            font-weight: bold;
        }

        .perfil-main {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .stat-card {
            background: #1e293b;
            padding: 22px;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.12);
        }

        .stat-card h3 {
            color: #93c5fd;
            font-size: 15px;
            margin-bottom: 12px;
        }

        .stat-card p {
            font-size: 28px;
            font-weight: bold;
        }

        .perfil-form-card {
            background: #1e293b;
            padding: 30px;
            border-radius: 22px;
            border: 1px solid rgba(148, 163, 184, 0.12);
        }

        .perfil-form-card h2 {
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group input {
            max-width: 100% !important;
        }

        .range-section {
            background: #111827;
            padding: 22px;
            border-radius: 18px;
            margin: 20px 0;
        }

        .range-section h3 {
            color: #93c5fd;
            margin-bottom: 16px;
        }

        .range-group {
            margin-bottom: 16px;
        }

        .range-group input {
            max-width: 100% !important;
            width: 100% !important;
        }

        .range-value {
            color: #cbd5e1;
            font-size: 13px;
            margin-top: -6px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #93c5fd;
        }

        #avatarInput {
            display: none;
        }

        .avatar-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #f97316) !important;
        }

        .erro-box {
            background: #dc2626;
            padding: 14px 18px;
            border-radius: 14px;
            color: white;
            margin-bottom: 20px;
            display: inline-block;
        }

        .info-box {
            background: #111827;
            padding: 18px;
            border-radius: 16px;
            color: #cbd5e1;
            margin-top: 20px;
            line-height: 1.6;
        }

        .info-box strong {
            color: #93c5fd;
        }

        @media (max-width: 1050px) {
            .perfil-layout {
                grid-template-columns: 1fr;
            }

            .perfil-user-card {
                position: static;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 650px) {
            .stats-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="container">

    <h1>Meu Perfil</h1>

    <?php if ($erro): ?>
        <div class="erro-box"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <div class="perfil-layout">

        <div class="perfil-user-card">

            <label for="avatarInput" class="avatar-box">

                <?php if (!empty($usuario["avatar"])): ?>

                    <img
                        id="avatarPreview"
                        src="../assets/img/avatars/<?= htmlspecialchars($usuario["avatar"]) ?>?v=<?= time() ?>"
                        class="avatar-preview"
                        alt="Avatar do usuário"
                        style="object-position: <?= $posX ?>% <?= $posY ?>%; transform: scale(<?= $scale ?>);"
                    >

                <?php else: ?>

                    <div id="avatarPreview" class="avatar-placeholder">
                        👤
                    </div>

                <?php endif; ?>

                <div class="avatar-overlay">
                    Trocar<br>foto
                </div>

            </label>

            <h2><?= htmlspecialchars($usuario["nome"]) ?></h2>

            <p><?= htmlspecialchars($usuario["email"]) ?></p>

            <p>ID da Conta: #<?= $usuario["id"] ?></p>

            <span class="status-conta">Conta ativa</span>

            <div class="info-box">
                <strong>Resumo da conta</strong><br>
                Saldo atual: <?= formatarMoeda($saldo) ?><br>
                Movimentações: <?= $totalMovimentacoes ?><br>
                Categorias: <?= $totalCategorias ?><br>
                Metas: <?= $totalMetas ?>
            </div>

        </div>

        <div class="perfil-main">

            <div class="stats-grid">

                <div class="stat-card">
                    <h3>Movimentações</h3>
                    <p><?= $totalMovimentacoes ?></p>
                </div>

                <div class="stat-card">
                    <h3>Categorias</h3>
                    <p><?= $totalCategorias ?></p>
                </div>

                <div class="stat-card">
                    <h3>Metas</h3>
                    <p><?= $totalMetas ?></p>
                </div>

                <div class="stat-card">
                    <h3>Saldo Atual</h3>
                    <p><?= formatarMoeda($saldo) ?></p>
                </div>

            </div>

            <div class="perfil-form-card">

                <h2>Editar informações</h2>

                <form method="POST" enctype="multipart/form-data" class="form-perfil">

                    <input 
                        type="file" 
                        name="avatar" 
                        id="avatarInput"
                        accept="image/*"
                    >

                    <div class="avatar-actions">
                        <label for="avatarInput" class="btn">
                            Trocar foto
                        </label>

                        <?php if (!empty($usuario["avatar"])): ?>
                            <a 
                                href="perfil.php?remover_avatar=1"
                                onclick="return confirmarRemoverFoto(event, this.href)"
                                class="btn btn-danger"
                            >
                                Remover foto
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="range-section">

                        <h3>Ajustar foto de perfil</h3>

                        <div class="range-group">
                            <label>Zoom da foto</label>
                            <input 
                                type="range" 
                                name="avatar_zoom" 
                                id="zoom"
                                min="100" 
                                max="250" 
                                value="<?= $zoom ?>"
                            >
                            <div class="range-value">
                                Zoom: <span id="zoomValue"><?= $zoom ?></span>%
                            </div>
                        </div>

                        <div class="range-group">
                            <label>Posição horizontal da foto</label>
                            <input 
                                type="range" 
                                name="avatar_pos_x" 
                                id="posX"
                                min="0" 
                                max="100" 
                                value="<?= $posX ?>"
                            >
                            <div class="range-value">
                                Horizontal: <span id="posXValue"><?= $posX ?></span>%
                            </div>
                        </div>

                        <div class="range-group">
                            <label>Posição vertical da foto</label>
                            <input 
                                type="range" 
                                name="avatar_pos_y" 
                                id="posY"
                                min="0" 
                                max="100" 
                                value="<?= $posY ?>"
                            >
                            <div class="range-value">
                                Vertical: <span id="posYValue"><?= $posY ?></span>%
                            </div>
                        </div>

                    </div>

                    <div class="form-grid">

                        <div class="form-group">
                            <label>Nome</label>
                            <input 
                                type="text" 
                                name="nome" 
                                value="<?= htmlspecialchars($usuario["nome"]) ?>" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>E-mail</label>
                            <input 
                                type="email" 
                                name="email" 
                                value="<?= htmlspecialchars($usuario["email"]) ?>" 
                                required
                            >
                        </div>

                        <div class="form-group full">
                            <label>Nova senha</label>
                            <input 
                                type="password" 
                                name="nova_senha" 
                                placeholder="Deixe vazio para manter a senha atual"
                            >
                        </div>

                    </div>

                    <br>

                    <button class="btn">
                        Salvar alterações
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<script>
const avatarInput = document.getElementById("avatarInput");
let avatarPreview = document.getElementById("avatarPreview");

const posX = document.getElementById("posX");
const posY = document.getElementById("posY");
const zoom = document.getElementById("zoom");

const posXValue = document.getElementById("posXValue");
const posYValue = document.getElementById("posYValue");
const zoomValue = document.getElementById("zoomValue");

const alertaSistema = "<?= $alerta ?>";

avatarInput.addEventListener("change", function () {
    const file = this.files[0];

    if (file) {
        const reader = new FileReader();

        reader.onload = function (e) {
            if (avatarPreview.tagName !== "IMG") {
                const img = document.createElement("img");
                img.id = "avatarPreview";
                img.className = "avatar-preview";
                img.alt = "Avatar do usuário";

                avatarPreview.replaceWith(img);
                avatarPreview = img;
            }

            avatarPreview.src = e.target.result;
            atualizarAvatar();
        };

        reader.readAsDataURL(file);
    }
});

function atualizarAvatar() {
    if (avatarPreview.tagName === "IMG") {
        avatarPreview.style.objectPosition = posX.value + "% " + posY.value + "%";
        avatarPreview.style.transform = "scale(" + (zoom.value / 100) + ")";
    }

    posXValue.textContent = posX.value;
    posYValue.textContent = posY.value;
    zoomValue.textContent = zoom.value;
}

function confirmarRemoverFoto(event, url) {
    event.preventDefault();

    Swal.fire({
        title: "Remover foto?",
        text: "Sua foto de perfil será removida.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#2563eb",
        confirmButtonText: "Sim, remover",
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

posX.addEventListener("input", atualizarAvatar);
posY.addEventListener("input", atualizarAvatar);
zoom.addEventListener("input", atualizarAvatar);

if (alertaSistema === "sucesso") {
    Swal.fire({
        icon: "success",
        title: "Perfil atualizado!",
        text: "Suas informações foram salvas com sucesso.",
        background: "#1e293b",
        color: "#ffffff",
        confirmButtonColor: "#2563eb"
    });
}

if (alertaSistema === "foto") {
    Swal.fire({
        icon: "success",
        title: "Foto atualizada!",
        text: "Sua nova foto de perfil foi salva com sucesso.",
        background: "#1e293b",
        color: "#ffffff",
        confirmButtonColor: "#2563eb"
    });
}

if (alertaSistema === "avatar_removido") {
    Swal.fire({
        icon: "success",
        title: "Foto removida!",
        text: "Sua foto de perfil foi removida com sucesso.",
        background: "#1e293b",
        color: "#ffffff",
        confirmButtonColor: "#2563eb"
    });
}
</script>
<?php include "../includes/footer.php"; ?>
</body>
</html>