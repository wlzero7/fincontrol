<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
   <div class="sidebar-logo">
    <img src="../assets/img/logo-fincontrol.png" alt="FinControl">
</div>
    <nav class="sidebar-menu">
        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
            🏠 <span>Dashboard</span>
        </a>

        <a href="movimentacoes.php" class="<?= $currentPage === 'movimentacoes.php' ? 'active' : '' ?>">
            💸 <span>Movimentações</span>
        </a>

        <a href="categorias.php" class="<?= $currentPage === 'categorias.php' ? 'active' : '' ?>">
            📂 <span>Categorias</span>
        </a>

        <a href="metas.php" class="<?= $currentPage === 'metas.php' ? 'active' : '' ?>">
            🎯 <span>Metas</span>
        </a>

        <a href="relatorios.php" class="<?= $currentPage === 'relatorios.php' ? 'active' : '' ?>">
            📊 <span>Relatórios</span>
        </a>

        <a href="perfil.php" class="<?= $currentPage === 'perfil.php' ? 'active' : '' ?>">
            👤 <span>Perfil</span>
        </a>
    </nav>

    <a href="../logout.php" class="sidebar-logout">
        🚪 <span>Sair</span>
    </a>
</aside>