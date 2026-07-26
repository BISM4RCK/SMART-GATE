<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'admin') redirect('../dashboard.php');
$pageTitle = 'Admin Dashboard';
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
        <span class="gh-badge mb-2"><i class="bi bi-gear-fill"></i> Admin Dashboard</span>
        <h2 class="gh-section-title mb-1">Admin Dashboard</h2>
        <div class="gh-muted">Admin tools stay simple: dashboard, logs, concerns, and quick shortcuts.</div>
    </div>
    <div class="gh-toolbar">

        <a class="btn gh-primary rounded-pill" href="<?= e(url('admin/concerns.php')) ?>"><i class="bi bi-chat-left-text me-1"></i>Concerns</a>
        <a class="btn gh-gold rounded-pill" href="<?= e(url('admin/logs.php')) ?>"><i class="bi bi-journal-text me-1"></i>Logs</a>

    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Residents</div><div class="value"><?= count_rows("SELECT COUNT(*) c FROM residents") ?></div></div></div>
    <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Visitor requests</div><div class="value"><?= count_rows("SELECT COUNT(*) c FROM visitor_requests") ?></div></div></div>
    <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Concerns</div><div class="value"><?= count_rows("SELECT COUNT(*) c FROM concerns") ?></div></div></div>
    <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Logs</div><div class="value"><?= count_rows("SELECT COUNT(*) c FROM gate_logs") ?></div></div></div>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="gh-card p-4 h-100">
            <h5 class="mb-3">Recent concerns</h5>
            <div class="d-grid gap-3">
                <?php foreach (array_slice($concerns, 0, 3) as $c): ?>
                <div class="gh-card-soft p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="fw-semibold"><?= e($c['subject']) ?></div>
                        <span class="badge rounded-pill <?= e(badge_class($c['status'])) ?>"><?= e(ucfirst($c['status'])) ?></span>
                    </div>
                    <div class="small gh-muted mt-1">From <?= e($c['sender_name']) ?> · House <?= e($c['house_number']) ?></div>
                    <div class="mt-2"><?= e($c['message']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="gh-card p-4 h-100">
            <h5 class="mb-3">Shortcuts</h5>
            <div class="d-grid gap-2">
                <a class="btn gh-btn-soft text-start" href="<?= e(url('admin/concerns.php')) ?>"><i class="bi bi-chat-left-text me-2"></i>Open concerns inbox</a>
                <a class="btn gh-btn-soft text-start" href="<?= e(url('admin/logs.php')) ?>"><i class="bi bi-journal-text me-2"></i>View logs</a>
                <button class="btn gh-btn-soft text-start" type="button"><i class="bi bi-people me-2"></i>Residents (later)</button>
                <button class="btn gh-btn-soft text-start" type="button"><i class="bi bi-gear me-2"></i>System settings (later)</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
