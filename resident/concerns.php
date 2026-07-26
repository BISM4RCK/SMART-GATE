<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'resident') redirect('../dashboard.php');
$pageTitle = 'Resident Concerns';

$resident = resident_record((int)$user['id']);
$residentId = $resident['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($subject !== '' && $message !== '') {
        $stmt = db()->prepare("INSERT INTO concerns (resident_id, sender_name, sender_role, house_number, subject, message, status) VALUES (?, ?, 'resident', ?, ?, ?, 'open')");
        $stmt->execute([$residentId, $user['name'], $user['house'] ?? '', $subject, $message]);
        add_log('Concern submitted', $subject);
        add_notification((int)$user['id'], 'Concern submitted', 'Your concern has been sent.');
        flash_set('success', 'Your concern has been submitted.');
        redirect('concerns.php');
    }
    flash_set('danger', 'Please fill in the concern subject and message.');
}

require_once __DIR__ . '/../includes/header.php';
$stmt = db()->prepare("SELECT * FROM concerns WHERE resident_id = ? OR sender_role = 'resident' ORDER BY created_at DESC");
$stmt->execute([$residentId]);
$concerns = $stmt->fetchAll();
?>
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <span class="gh-badge mb-2"><i class="bi bi-chat-left-dots"></i> Concerns</span>
        <h2 class="gh-section-title mb-1">Send a concern to admin</h2>
        <div class="gh-muted">A simple message board instead of phone calls.</div>
    </div>
    <a class="btn gh-gold rounded-pill" href="<?= e(url('resident/dashboard.php')) ?>">Back to dashboard</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="gh-card p-4">
            <h5 class="mb-3">New concern</h5>
            <form method="post" class="d-grid gap-3">
                <div><label class="form-label">Subject</label><input class="form-control" name="subject" placeholder="Streetlight issue" required></div>
                <div><label class="form-label">Message</label><textarea class="form-control" name="message" rows="6" placeholder="Write your concern here..." required></textarea></div>
                <button class="btn gh-primary rounded-pill" type="submit">Submit concern</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="gh-card p-4">
            <h5 class="mb-3">My concerns</h5>
            <div class="d-grid gap-3">
                <?php foreach ($concerns as $c): ?>
                    <div class="gh-card-soft p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="fw-semibold"><?= e($c['subject']) ?></div>
                            <span class="badge rounded-pill <?= e(badge_class($c['status'])) ?>"><?= e(ucfirst($c['status'])) ?></span>
                        </div>
                        <div class="small gh-muted mt-1">House <?= e($c['house_number']) ?> · <?= e($c['created_at']) ?></div>
                        <div class="mt-2"><?= e($c['message']) ?></div>
                        <?php if (!empty($c['reply'])): ?>
                            <div class="mt-3 p-3 rounded-4 bg-white border">
                                <div class="small text-uppercase gh-muted">Admin reply</div>
                                <div><?= e($c['reply']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
