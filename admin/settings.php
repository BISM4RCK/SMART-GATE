<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'admin') redirect('../dashboard.php');
$pageTitle = 'System Settings';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="gh-card p-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <span class="gh-badge mb-2"><i class="bi bi-gear"></i> Settings</span>
            <h2 class="gh-section-title mb-1">System settings placeholder</h2>
            <div class="gh-muted">Reserved for future configuration panels.</div>
        </div>
        <a class="btn gh-gold rounded-pill" href="<?= e(url('admin/dashboard.php')) ?>">Back to dashboard</a>
    </div>
    <hr>
    <p class="mb-0">This page is intentionally simple for now. It can later hold gate devices, notifications, and approval rules.</p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
