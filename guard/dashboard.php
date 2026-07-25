<?php
$pageTitle = 'Guard Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_role(['guard']);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="content">
    <?php if ($msg = flash('success')): ?>
        <div class="notice success" data-auto-hide><?= e($msg) ?></div>
    <?php endif; ?>
    <h1>Guard Dashboard</h1>
    <p class="small">Starter dashboard for local-hosted development.</p>

    <div class="grid cards">
        <div class="card"><div class="label">Pending Visitors</div><div class="value">0</div></div>
        <div class="card"><div class="label">Walk-ins Today</div><div class="value">0</div></div>
        <div class="card"><div class="label">Logs</div><div class="value">0</div></div>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0">Next Step</h2>
        <p class="small">Connect QR verification, walk-in registration, manual gate control, and log tables.</p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
