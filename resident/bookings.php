<?php
$pageTitle = 'Booking History';
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_role(['resident']);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="content">
    <h1>Booking History</h1>
    <div class="notice" data-auto-hide>
        Starter page. This file is ready for database queries and form handling.
    </div>
    <div class="card">
        <p class="small">Add your PHP logic here. This scaffold already includes the shared layout, auth check, and local-hosted navigation.</p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
