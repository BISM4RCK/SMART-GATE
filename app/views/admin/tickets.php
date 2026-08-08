<?php
/* BISM4RCK-KUN3H0 2026 */
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Tickets</h2>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('admin/dashboard.php')) ?>">Back</a>
    </div>

    <div class="d-grid gap-3">
        <?php foreach ($tickets as $t): ?>
            <div class="gh-card p-4">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div>
                        <div class="fw-semibold"><?= e($t['subject']) ?></div>
                        <div class="small text-muted"><?= e($t['sender_name']) ?> · House <?= e($t['house_number']) ?> · <?= e($t['created_at']) ?></div>
                    </div>
                    <span class="badge rounded-pill <?= e(gate_badge($t['status'])) ?>"><?= e(ucfirst($t['status'])) ?></span>
                </div>
                <div class="mt-3"><?= e($t['message']) ?></div>

                <?php if (!empty($t['reply'])): ?>
                    <div class="mt-3 p-3 rounded-4 bg-light border">
                        <div class="small text-uppercase text-muted">Reply</div>
                        <div><?= e($t['reply']) ?></div>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= e(url('admin/tickets.php')) ?>" class="mt-3"><?= csrf_field() ?>
                        <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
                        <label class="form-label">Reply</label>
                        <textarea class="form-control" name="reply" rows="3" placeholder="Write a reply..."></textarea>
                        <button class="btn gh-primary rounded-pill mt-3" type="submit">Send reply</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="<?= e(url('admin/tickets.php')) ?>" class="mt-2" onsubmit="return confirm('Delete this ticket?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete ticket</button></form>
            </div>
        <?php endforeach; ?>
        <?php if (empty($tickets)): ?><div class="text-muted">No tickets yet.</div><?php endif; ?>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
