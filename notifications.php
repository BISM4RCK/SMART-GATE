<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();
$pageTitle = 'Notifications';

if (isset($_GET['read'])) {
    mark_notification_read((int)$_GET['read'], (int)$user['id']);
    flash_set('success', 'Notification marked as read.');
    if (!empty($_GET['back'])) redirect($_GET['back']);
    redirect('notifications.php');
}

require_once __DIR__ . '/includes/header.php';
$notes = notifications_for_user((int)$user['id']);
?>
<div class="container py-4">
    <div class="gh-card p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="mb-0">Notifications</h2>
            <a class="btn btn-outline-secondary rounded-pill" href="<?= e(dashboard_url()) ?>">Back</a>
        </div>
        <div class="d-grid gap-3">
            <?php foreach ($notes as $n): ?>
                <div class="gh-card-soft p-3" id="n-<?= (int)$n['id'] ?>">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <div class="fw-semibold"><?= e($n['title']) ?></div>
                        <span class="badge rounded-pill <?= e($n['is_read'] ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis') ?>"><?= $n['is_read'] ? 'Read' : 'Unread' ?></span>
                    </div>
                    <div class="small text-muted mt-1"><?= e($n['created_at']) ?></div>
                    <div class="mt-2"><?= e($n['message']) ?></div>
                    <?php if (!$n['is_read']): ?>
                        <a class="btn btn-sm gh-primary rounded-pill mt-3" href="<?= e(url('notifications.php?read='.(int)$n['id'])) ?>">Mark as read</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$notes): ?><div class="text-muted">No notifications yet.</div><?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
