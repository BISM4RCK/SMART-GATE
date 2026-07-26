<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'admin') redirect('../dashboard.php');
$pageTitle = 'Admin Dashboard';
$stats = role_page_counts($user);
$concerns = concerns_for_user($user);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Admin Dashboard</h2>
        <div class="d-flex gap-2">
            <a class="btn gh-primary rounded-pill" href="<?= e(url('admin/concerns.php')) ?>">Tickets</a>
            <a class="btn gh-gold rounded-pill" href="<?= e(url('admin/logs.php')) ?>">Logs</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Residents</div><div class="value"><?= count_rows("SELECT COUNT(*) c FROM residents") ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Requests</div><div class="value"><?= (int)$stats['pending'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Tickets</div><div class="value"><?= (int)$stats['concerns'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Logs</div><div class="value"><?= (int)$stats['logs'] ?></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="gh-card p-4">
                <h5 class="mb-3">Open tickets</h5>
                <div class="d-grid gap-3">
                    <?php foreach (array_filter($concerns, fn($c) => strtolower($c['status']) === 'open') as $c): ?>
                        <div class="gh-card-soft p-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                <div class="fw-semibold"><?= e($c['subject']) ?></div>
                                <span class="badge rounded-pill <?= e(badge_class($c['status'])) ?>"><?= e(ucfirst($c['status'])) ?></span>
                            </div>
                            <div class="small text-muted mt-1"><?= e($c['sender_name']) ?> · House <?= e($c['house_number']) ?></div>
                            <div class="mt-2"><?= e($c['message']) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!array_filter($concerns, fn($c) => strtolower($c['status']) === 'open')): ?><div class="text-muted">No open tickets.</div><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="gh-card p-4">
                <h5 class="mb-3">Shortcuts</h5>
                <div class="d-grid gap-2">
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('admin/concerns.php')) ?>">Reply to tickets</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('notifications.php')) ?>">Notifications</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('admin/logs.php')) ?>">View logs</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('admin/settings.php')) ?>">Settings</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('logout.php')) ?>">Logout</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
