<?php
/* BISM4RCK-KUN3H0 2026 */
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1">Admin Dashboard</h2>
            <div class="text-muted">Administrative controls first. Guard-compatible tools remain available where appropriate; Gate Scan is guard-only.</div>
        </div>
    </div>

    <section class="gh-card p-4 mb-4 admin-override-card" aria-labelledby="gate-control-heading">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h4 id="gate-control-heading" class="mb-1"><i class="bi bi-unlock me-2"></i>Gate Control</h4>
                <div class="text-muted">Manual override for authorized gate opening. Enter a plate number or select Emergency.</div>
            </div>
            <span class="badge text-bg-danger rounded-pill px-3 py-2">ADMIN OVERRIDE</span>
        </div>
        <form method="post" class="row g-2 align-items-end mt-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="gate_override">
            <div class="col-md-4">
                <label class="form-label" for="adminPlate">Plate Number</label>
                <input id="adminPlate" class="form-control form-control-lg" name="plate_number" placeholder="ABC 1234">
            </div>
            <div class="col-md-2">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="emergency" id="adminEmergency" value="1">
                    <label class="form-check-label fw-semibold" for="adminEmergency">EMERGENCY</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="adminReason">Reason</label>
                <input id="adminReason" class="form-control form-control-lg" name="reason" placeholder="Reason / incident">
            </div>
            <div class="col-md-2">
                <button class="btn btn-danger btn-lg w-100" type="submit">Open Gate</button>
            </div>
        </form>
    </section>

    <section aria-label="Administrative shortcuts" class="mb-4">
        <div class="gh-action-grid">
            <a class="btn gh-primary gh-action-square ui-admin_vehicles" href="<?= e(url('admin/vehicles.php')) ?>">
                <i class="bi bi-car-front"></i>Vehicles
            </a>
            <a class="btn gh-gold gh-action-square ui-admin_users" href="<?= e(url('admin/users.php')) ?>">
                <i class="bi bi-people"></i>Users
            </a>
            <a class="btn btn-outline-danger gh-action-square" href="<?= e(url('admin/blacklist.php')) ?>">
                <i class="bi bi-slash-circle"></i>Blacklist
            </a>
            <a class="btn gh-btn-soft gh-action-square" href="<?= e(url('admin/logs.php')) ?>">
                <i class="bi bi-journal-text"></i>Gate Logs
            </a>
            <a class="btn gh-btn-soft gh-action-square" href="<?= e(url('admin/activity-logs.php')) ?>">
                <i class="bi bi-person-lines-fill"></i>Admin / Guard Logs
            </a>
            <a class="btn gh-btn-soft gh-action-square" href="<?= e(url('admin/settings.php')) ?>">
                <i class="bi bi-sliders"></i>Customize
            </a>
        </div>
    </section>

    <section class="row g-3 mb-4" aria-label="Admin operations">
        <div class="col-md-6 col-xl-3">
            <a class="gh-card p-4 h-100 d-block text-decoration-none" href="<?= e(url('admin/walkin.php')) ?>">
                <div class="fs-3 mb-2"><i class="bi bi-person-plus"></i></div>
                <h5 class="mb-1">Walk-in Visitor</h5>
                <div class="small text-muted">Register and check in walk-in visitors. No Gate Scan access is provided here.</div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a class="gh-card p-4 h-100 d-block text-decoration-none" href="<?= e(url('admin/tickets.php')) ?>">
                <div class="fs-3 mb-2"><i class="bi bi-chat-left-text"></i></div>
                <h5 class="mb-1">Tickets</h5>
                <div class="small text-muted">Review and respond to resident concerns and support tickets.</div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="gh-card p-4 h-100">
                <div class="fs-3 mb-2"><i class="bi bi-shield-check"></i></div>
                <h5 class="mb-1">Admin Access</h5>
                <div class="small text-muted">Account, vehicle, blacklist, audit-log, gate-control, walk-in, ticket, and customization controls are retained.</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="gh-card p-4 h-100">
                <div class="fs-3 mb-2"><i class="bi bi-qr-code-scan"></i></div>
                <h5 class="mb-1">Gate Scan</h5>
                <div class="small text-muted">Guard-only by design. Admin Gate Scan was intentionally removed per the latest requirement.</div>
            </div>
        </div>
    </section>

    <div class="row g-2 gh-small-stats mb-4">
        <div class="col-6 col-md-3"><div class="gh-stat"><div class="label">Residents</div><div class="value"><?= (int)$stats['residents'] ?></div></div></div>
        <div class="col-6 col-md-3"><div class="gh-stat"><div class="label">Requests</div><div class="value"><?= (int)$stats['requests'] ?></div></div></div>
        <div class="col-6 col-md-3"><div class="gh-stat"><div class="label">Tickets</div><div class="value"><?= (int)$stats['tickets'] ?></div></div></div>
        <div class="col-6 col-md-3"><div class="gh-stat"><div class="label">Gate Logs</div><div class="value"><?= (int)$stats['logs'] ?></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="gh-card p-4">
                <div class="d-flex justify-content-between">
                    <h5>Open Tickets</h5>
                    <a href="<?= e(url('admin/tickets.php')) ?>">Open all</a>
                </div>
                <div class="d-grid gap-3 mt-3">
                    <?php foreach ($tickets as $t): ?>
                        <div class="gh-card-soft p-3">
                            <strong><?= e($t['subject']) ?></strong>
                            <div class="small text-muted"><?= e($t['sender_name']) ?> · House <?= e($t['house_number']) ?></div>
                            <div class="mt-2"><?= e($t['message']) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($tickets)): ?><div class="text-muted">No tickets yet.</div><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="gh-card p-4">
                <h5>Recent Gate Activity</h5>
                <div class="d-grid gap-2 mt-3">
                    <?php foreach ($logs as $log): ?>
                        <div class="gh-card-soft p-3">
                            <strong><?= e($log['event_type'] ?? 'gate_event') ?></strong>
                            <div class="small text-muted"><?= e($log['gate_status'] ?? '') ?> · <?= e($log['created_at'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?><div class="text-muted">No gate activity yet.</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include app_path('views/layouts/footer.php'); ?>
