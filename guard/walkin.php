<?php
$pageTitle = 'Walk-in Registration';
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_role(['guard']);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="content">
    <h1>Walk-in Registration</h1>
    <div class="card">
        <p class="small">Starter page for guard workflow and future QR/RFID integration.</p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
