<?php
$user = current_user();
?>
<header class="topbar">
    <div class="brand">
        <div class="brand-mark">SG</div>
        <div>
            <div class="brand-title"><?= e(APP_NAME) ?></div>
            <div class="brand-subtitle">Local-hosted gated community system</div>
        </div>
    </div>

    <nav class="topnav">
        <a href="<?= e(url('index.php')) ?>">Home</a>
        <?php if ($user): ?>
            <span class="user-pill"><?= e($user['full_name']) ?> (<?= e($user['role']) ?>)</span>
            <a href="<?= e(url('api/logout.php')) ?>">Logout</a>
        <?php else: ?>
            <a href="<?= e(url('resident/login.php')) ?>">Resident</a>
            <a href="<?= e(url('guard/login.php')) ?>">Guard</a>
            <a href="<?= e(url('admin/login.php')) ?>">Admin</a>
        <?php endif; ?>
    </nav>
</header>
