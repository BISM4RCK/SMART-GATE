<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Notifications</h2>
        <div class="d-flex gap-2"><form method="post" action="<?= e(url('notifications_read.php')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="all"><input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? url('notifications.php')) ?>"><button class="btn btn-outline-primary rounded-pill">Mark all as read</button></form><a class="btn btn-outline-secondary rounded-pill" href="<?= e(dashboard_url()) ?>">Back</a></div>
    </div>

    <div class="d-grid gap-3">
        <?php foreach ($notifications as $n): ?>
            <div class="gh-card p-4" id="n-<?= (int)$n['id'] ?>">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div class="fw-semibold"><?= e($n['title']) ?></div>
                    <span class="badge rounded-pill <?= e($n['is_read'] ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis') ?>"><?= $n['is_read'] ? 'Read' : 'Unread' ?></span>
                </div>
                <div class="small text-muted mt-1"><?= e($n['created_at']) ?></div>
                <div class="mt-2"><?= e($n['message']) ?></div>
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <?php if (!$n['is_read']): ?>
                        <a class="btn btn-sm gh-primary rounded-pill" href="<?= e(url('notifications_read.php?id=' . (int)$n['id'] . '&back=' . urlencode($_SERVER['REQUEST_URI'] ?? 'notifications.php'))) ?>">Mark as read</a>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('notifications/delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this notification?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($notifications)): ?><div class="text-muted">No notifications yet.</div><?php endif; ?>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026


/* BISM4RCK/KUN3H0 2026 */
?>
/* BISM4RCK-KUN3H0 2026 */
