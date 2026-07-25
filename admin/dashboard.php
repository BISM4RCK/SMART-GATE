<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_role(['admin']);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="content">
    <?php if ($msg = flash('success')): ?>
        <div class="notice success" data-auto-hide><?= e($msg) ?></div>
    <?php endif; ?>
    <h1>Admin Dashboard</h1>
    <p class="small">Starter dashboard for local-hosted development.</p>

    <div class="grid cards">
        <div class="card"><div class="label">Residents</div><div class="value">0</div></div>
        <div class="card"><div class="label">Users</div><div class="value">0</div></div>
        <div class="card"><div class="label">Bookings</div><div class="value">0</div></div>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0">Next Step</h2>
        <p class="small">Connect user management, resident management, RFID management, reports, and blacklist tools.</p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
