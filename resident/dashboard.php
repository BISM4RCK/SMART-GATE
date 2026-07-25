<?php
$pageTitle = 'Resident Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_role(['resident']);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="content">
    <?php if ($msg = flash('success')): ?>
        <div class="notice success" data-auto-hide><?= e($msg) ?></div>
    <?php endif; ?>
    <h1>Resident Dashboard</h1>
    <p class="small">Starter dashboard for local-hosted development.</p>

    <div class="grid cards">
        <div class="card"><div class="label">Upcoming Visitors</div><div class="value">0</div></div>
        <div class="card"><div class="label">Registered Vehicles</div><div class="value">0</div></div>
        <div class="card"><div class="label">Notifications</div><div class="value">0</div></div>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0">Next Step</h2>
        <p class="small">Connect this page to booking, vehicle, notification, and history queries.</p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
