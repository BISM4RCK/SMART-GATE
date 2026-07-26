<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'admin') redirect('../dashboard.php');
$pageTitle = 'Admin Concerns';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');
    if ($id && $reply !== '') {
        $stmt = db()->prepare("UPDATE concerns SET reply = ?, status = 'closed', replied_by = ?, replied_at = NOW() WHERE id = ?");
        $stmt->execute([$reply, $user['id'], $id]);
        add_log('Concern replied', "Concern #{$id}");
        flash_set('success', 'Reply saved.');
        redirect('concerns.php');
    }
    flash_set('danger', 'Please enter a reply.');
}

require_once __DIR__ . '/../includes/header.php';
$concerns = concerns_for_role($user);
?>
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <span class="gh-badge mb-2"><i class="bi bi-chat-left-text"></i> Concerns inbox</span>
        <h2 class="gh-section-title mb-1">Resident concerns</h2>
        <div class="gh-muted">Reply to issues directly on the website.</div>
    </div>
    <a class="btn gh-gold rounded-pill" href="<?= e(url('admin/dashboard.php')) ?>">Back to dashboard</a>
</div>

<div class="d-grid gap-3">
    <?php foreach ($concerns as $c): ?>
    <div class="gh-card p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="fw-semibold"><?= e($c['subject']) ?></div>
                <div class="small gh-muted">From <?= e($c['sender_name']) ?> · House <?= e($c['house_number']) ?> · <?= e($c['created_at']) ?></div>
            </div>
            <span class="badge rounded-pill <?= e(badge_class($c['status'])) ?>"><?= e(ucfirst($c['status'])) ?></span>
        </div>
        <div class="mt-3"><?= e($c['message']) ?></div>

        <?php if (!empty($c['reply'])): ?>
            <div class="mt-3 p-3 rounded-4 bg-light border">
                <div class="small text-uppercase gh-muted">Reply</div>
                <div><?= e($c['reply']) ?></div>
            </div>
        <?php else: ?>
            <form method="post" class="mt-3">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <label class="form-label">Reply</label>
                <textarea class="form-control" name="reply" rows="3" placeholder="Type your response..."></textarea>
                <button class="btn gh-primary rounded-pill mt-3" type="submit">Send reply</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
