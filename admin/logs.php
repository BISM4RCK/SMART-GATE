<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'admin') redirect('../dashboard.php');
$pageTitle = 'Admin Logs';
require_once __DIR__ . '/../includes/header.php';
$stmt = db()->query("SELECT * FROM gate_logs ORDER BY created_at DESC");
$logs = $stmt->fetchAll();
?>
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <span class="gh-badge mb-2"><i class="bi bi-journal-text"></i> Logs</span>
        <h2 class="gh-section-title mb-1">System history</h2>
        <div class="gh-muted">Quick log view for admins.</div>
    </div>
    <a class="btn gh-gold rounded-pill" href="<?= e(url('admin/dashboard.php')) ?>">Back to dashboard</a>
</div>

<div class="gh-card p-4">
    <div class="table-responsive">
        <table class="table gh-table align-middle">
            <thead><tr><th>Time</th><th>Event</th><th>Detail</th></tr></thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['created_at']) ?></td>
                    <td><?= e($log['event_type']) ?></td>
                    <td><?= e($log['log_notes'] ?: ($log['person_name'] ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$logs): ?><tr><td colspan="3" class="text-center text-muted py-4">No logs yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
