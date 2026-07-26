<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'resident') redirect('../dashboard.php');
$pageTitle = 'Resident Dashboard';
$stats = role_page_counts($user);
$resident = resident_record((int)$user['id']);
$rid = $resident['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_submit'])) {
    $subject = trim($_POST['ticket_subject'] ?? '');
    $message = trim($_POST['ticket_message'] ?? '');
    if ($subject !== '' && $message !== '') {
        $stmt = db()->prepare("INSERT INTO concerns (resident_id, sender_name, sender_role, house_number, subject, message, status) VALUES (?, ?, 'resident', ?, ?, ?, 'open')");
        $stmt->execute([$rid, $user['name'], $user['house'] ?? '', $subject, $message]);
        add_notification(admin_user_id(), 'New trouble ticket', $subject);
        add_notification((int)$user['id'], 'Ticket created', 'Your trouble ticket has been sent.');
        add_log('Ticket created', $subject);
        flash_set('success', 'Trouble ticket created.');
        redirect('dashboard.php');
    }
    flash_set('danger', 'Please fill in the ticket subject and message.');
}

$stmt = db()->prepare("SELECT * FROM visitor_requests WHERE resident_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$rid]);
$requests = $stmt->fetchAll();

$stmt = db()->prepare("SELECT * FROM vehicles WHERE resident_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$rid]);
$vehicles = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-0">Resident Dashboard</h2>
            <div class="text-muted">House <?= e($user['house'] ?? '') ?></div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn gh-primary rounded-pill" href="<?= e(url('notifications.php')) ?>">Notifications</a>
            <a class="btn gh-gold rounded-pill" href="<?= e(url('resident/concerns.php')) ?>">Tickets</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Pending</div><div class="value"><?= (int)$stats['pending'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Vehicles</div><div class="value"><?= (int)$stats['vehicles'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Tickets</div><div class="value"><?= (int)$stats['concerns'] ?></div></div></div>
        <div class="col-md-3 col-6"><div class="gh-stat"><div class="label">Logs</div><div class="value"><?= (int)$stats['logs'] ?></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="gh-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Recent visitor requests</h5>
                    <a class="small" href="<?= e(url('visitor/register.php')) ?>">New request</a>
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
                                <td><span class="badge rounded-pill <?= e(badge_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$requests): ?><tr><td colspan="4" class="text-center text-muted py-4">No visitor requests yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="gh-card p-4 h-100">
                <h5 class="mb-3">Quick links</h5>
                <div class="d-grid gap-2">
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('resident/vehicles.php')) ?>"><i class="bi bi-car-front me-2"></i>Vehicles</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('resident/concerns.php')) ?>"><i class="bi bi-chat-left-dots me-2"></i>Tickets</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('resident/history.php')) ?>"><i class="bi bi-clock-history me-2"></i>History</a>
                    <a class="btn gh-btn-soft text-start" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="gh-card p-4 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Trouble ticket</h5>
            <span class="small text-muted">Sends directly to admin</span>
        </div>
        <form method="post" class="row g-3">
            <input type="hidden" name="ticket_submit" value="1">
            <div class="col-md-4">
                <label>Subject</label>
                <input class="form-control" name="ticket_subject" placeholder="Broken light, noise, etc." required>
            </div>
            <div class="col-md-8">
                <label>Message</label>
                <input class="form-control" name="ticket_message" placeholder="Write the trouble here..." required>
            </div>
            <div class="col-12">
                <button class="btn gh-primary rounded-pill" type="submit">Create ticket</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
