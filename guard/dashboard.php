<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'guard') redirect('../dashboard.php');
$pageTitle = 'Guard Dashboard';
$stats = user_stats();
$requests = [];
$vehicles = [];
$concerns = [];
$logs = [];
if ($user['role'] === 'resident') {
    $resident = resident_record((int)$user['id']);
    $residentId = $resident['id'] ?? 0;
    $stmt = db()->prepare("SELECT * FROM visitor_requests WHERE resident_id = ? ORDER BY created_at DESC");
    $stmt->execute([$residentId]);
    $requests = $stmt->fetchAll();
    $stmt = db()->prepare("SELECT * FROM vehicles WHERE resident_id = ? ORDER BY created_at DESC");
    $stmt->execute([$residentId]);
    $vehicles = $stmt->fetchAll();
    $stmt = db()->prepare("SELECT * FROM gate_logs WHERE resident_id = ? ORDER BY created_at DESC");
    $stmt->execute([$residentId]);
    $logs = $stmt->fetchAll();
} else {
    $requests = db()->query("SELECT * FROM visitor_requests ORDER BY created_at DESC")->fetchAll();
    $vehicles = db()->query("SELECT * FROM vehicles ORDER BY created_at DESC")->fetchAll();
    $logs = db()->query("SELECT * FROM gate_logs ORDER BY created_at DESC")->fetchAll();
}
$concerns = concerns_for_role($user);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <span class="gh-badge mb-2"><i class="bi bi-shield-lock"></i> Guard Dashboard</span>
        <h2 class="gh-section-title mb-1">Guard Dashboard</h2>
        <div class="gh-muted">Simple guard dashboard with quick buttons and logs only.</div>
    </div>
    <div class="gh-toolbar">

        <a class="btn gh-primary rounded-pill" href="<?= e(url('guard/logs.php')) ?>"><i class="bi bi-journal-text me-1"></i>Logs</a>
        <a class="btn gh-gold rounded-pill" href="<?= e(url('guard/dashboard.php#scan')) ?>"><i class="bi bi-qr-code-scan me-1"></i>Scan</a>

    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Pending</div><div class="value"><?= (int)$stats['pending'] ?></div></div></div>
    <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Approved</div><div class="value"><?= count(array_filter($requests, fn($r) => strtolower($r['status']) === 'approved')) ?></div></div></div>
    <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Logs</div><div class="value"><?= (int)$stats['logs'] ?></div></div></div>
    <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Shortcuts</div><div class="value">4</div></div></div>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="gh-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3" id="scan">
                <h5 class="mb-0">Visitor queue</h5>
                <span class="small gh-muted">House number matching shown here</span>
            </div>
            <div class="table-responsive">
                <table class="table gh-table align-middle">
                    <thead><tr><th>House</th><th>Visitor</th><th>Plate</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($requests, 0, 6) as $row): ?>
                        <tr>
                            <td><?= e($row['house_number']) ?></td>
                            <td><?= e($row['visitor_name']) ?></td>
                            <td><?= e($row['plate_number']) ?></td>
                            <td><span class="badge rounded-pill <?= e(badge_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="gh-card p-4 h-100" id="gate">
            <h5 class="mb-3">Helpful shortcuts</h5>
            <div class="d-grid gap-2">
                <a class="btn gh-btn-soft text-start" href="<?= e(url('guard/logs.php')) ?>"><i class="bi bi-journal-text me-2"></i>View logs</a>
                <button class="btn gh-btn-soft text-start" type="button"><i class="bi bi-qr-code-scan me-2"></i>Scan QR (later)</button>
                <button class="btn gh-btn-soft text-start" type="button"><i class="bi bi-door-open me-2"></i>Open gate (later)</button>
                <a class="btn gh-btn-soft text-start" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
