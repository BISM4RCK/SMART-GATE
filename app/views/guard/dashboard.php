<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0">Guard Dashboard</h2>
        <div class="d-flex gap-2">
            <a class="btn gh-primary rounded-pill" href="<?= e(url('guard/scan.php')) ?>">Quick Scan</a>
            <a class="btn gh-gold rounded-pill" href="<?= e(url('guard/logs.php')) ?>">Logs</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Pending</div><div class="value"><?= (int)$stats['pending'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Logs</div><div class="value"><?= (int)$stats['logs'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Tickets</div><div class="value"><?= (int)$stats['tickets'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Vehicles</div><div class="value"><?= (int)$stats['vehicles'] ?></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="gh-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Recent requests</h5>
                    <span class="small text-muted">House number matching</span>
                </div>
                <div class="table-responsive">
                    <table class="table gh-table align-middle">
                        <thead><tr><th>House</th><th>Visitor</th><th>Plate</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($requests as $row): ?>
                                <tr>
                                    <td><?= e($row['house_number']) ?></td>
                                    <td><?= e($row['visitor_name']) ?></td>
                                    <td><?= e($row['plate_number']) ?></td>
                                    <td><span class="badge rounded-pill <?= e(gate_badge($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($requests)): ?><tr><td colspan="4" class="text-center text-muted py-4">No requests yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="gh-card p-4 h-100">
                <h5 class="mb-3">Shortcuts</h5>
                <div class="d-grid gap-2">
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('guard/scan.php')) ?>"><i class="bi bi-qr-code-scan me-2"></i>Quick Scan</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('guard/logs.php')) ?>"><i class="bi bi-journal-text me-2"></i>Logs</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('guard/blacklist.php')) ?>"><i class="bi bi-slash-circle me-2"></i>Vehicle Blacklist</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('notifications.php')) ?>"><i class="bi bi-bell me-2"></i>Notifications</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
