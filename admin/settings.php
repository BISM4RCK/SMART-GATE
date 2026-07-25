<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_role(['admin']);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="content">
    <h1>Settings</h1>
    <div class="card">
        <p class="small">Starter admin page. Connect your database queries and CRUD forms here.</p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
