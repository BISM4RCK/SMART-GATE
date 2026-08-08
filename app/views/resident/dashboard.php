<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-0">Resident Dashboard</h2>
            <div class="text-muted">House <?= e($resident['house_number'] ?? '') ?></div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn gh-primary rounded-pill" href="<?= e(url('resident/requests.php')) ?>">Requests</a>
            <a class="btn gh-gold rounded-pill" href="<?= e(url('resident/tickets.php')) ?>">Tickets</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Pending</div><div class="value"><?= (int)$stats['pending'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Vehicles</div><div class="value"><?= (int)$stats['vehicles'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Tickets</div><div class="value"><?= (int)$stats['tickets'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Logs</div><div class="value"><?= (int)$stats['logs'] ?></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="gh-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Visitor requests</h5>
                    <a class="small" href="<?= e(url('resident/visitor.php')) ?>">Pre-register visitor</a>
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
                <h5 class="mb-3">Quick links</h5>
                <div class="d-grid gap-2">
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('resident/visitor.php')) ?>"><i class="bi bi-person-plus me-2"></i>Pre-register visitor</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('resident/vehicles.php')) ?>"><i class="bi bi-car-front me-2"></i>Vehicles</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('resident/requests.php')) ?>"><i class="bi bi-person-lines-fill me-2"></i>Requests</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('resident/tickets.php')) ?>"><i class="bi bi-chat-left-text me-2"></i>Tickets</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('notifications.php')) ?>"><i class="bi bi-bell me-2"></i>Notifications</a>
                </div>
            </div>
        </div>
    </div>

    <div class="gh-card p-4 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Trouble ticket</h5>
            <span class="small text-muted">Sends to admin</span>
        </div>
        <form method="post" action="<?= e(url('resident/tickets.php')) ?>" class="row g-3">
            <div class="col-md-4">
                <label>Subject</label>
                <input class="form-control" name="subject" placeholder="Broken light, noise, etc." required>
            </div>
            <div class="col-md-8">
                <label>Message</label>
                <input class="form-control" name="message" placeholder="Write the trouble here..." required>
            </div>
            <div class="col-12">
                <button class="btn gh-primary rounded-pill" type="submit">Create ticket</button>
            </div>
        </form>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
/* BISM4RCK-KUN3H0 2026 */
