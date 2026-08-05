<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Admin Dashboard</h2>
        <div class="d-flex gap-2">
            <a class="btn gh-primary rounded-pill" href="<?= e(url('admin/tickets.php')) ?>">Tickets</a>
            <a class="btn gh-gold rounded-pill" href="<?= e(url('admin/users.php')) ?>">Users</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Residents</div><div class="value"><?= (int)$stats['residents'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Requests</div><div class="value"><?= (int)$stats['requests'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Tickets</div><div class="value"><?= (int)$stats['tickets'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Logs</div><div class="value"><?= (int)$stats['logs'] ?></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="gh-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Open tickets</h5>
                    <a class="small" href="<?= e(url('admin/tickets.php')) ?>">Open all</a>
                </div>
                <div class="d-grid gap-3">
                    <?php foreach ($tickets as $t): ?>
                        <div class="gh-card-soft p-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                <div class="fw-semibold"><?= e($t['subject']) ?></div>
                                <span class="badge rounded-pill <?= e(gate_badge($t['status'])) ?>"><?= e(ucfirst($t['status'])) ?></span>
                            </div>
                            <div class="small text-muted mt-1"><?= e($t['sender_name']) ?> · House <?= e($t['house_number']) ?></div>
                            <div class="mt-2"><?= e($t['message']) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($tickets)): ?><div class="text-muted">No tickets yet.</div><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="gh-card p-4 h-100">
                <h5 class="mb-3">Shortcuts</h5>
                <div class="d-grid gap-2">
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('admin/tickets.php')) ?>"><i class="bi bi-chat-left-text me-2"></i>Tickets</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('admin/users.php')) ?>"><i class="bi bi-people me-2"></i>Users</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('admin/logs.php')) ?>"><i class="bi bi-journal-text me-2"></i>Logs</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('notifications.php')) ?>"><i class="bi bi-bell me-2"></i>Notifications</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
