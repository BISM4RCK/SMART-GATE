<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Tickets</h2>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('resident/dashboard.php')) ?>">Back</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="gh-card p-4">
                <h5 class="mb-3">Create ticket</h5>
                <form method="post" action="<?= e(url('resident/tickets.php')) ?>" class="d-grid gap-3"><?= csrf_field() ?>
                    <div><label>Subject</label><input class="form-control" name="subject" required></div>
                    <div><label>Message</label><textarea class="form-control" name="message" rows="7" required></textarea></div>
                    <button class="btn gh-primary rounded-pill" type="submit">Send</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="gh-card p-4">
                <h5 class="mb-3">My tickets</h5>
                <div class="d-grid gap-3">
                    <?php foreach ($tickets as $t): ?>
                        <div class="gh-card-soft p-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                <div class="fw-semibold"><?= e($t['subject']) ?></div>
                                <span class="badge rounded-pill <?= e(gate_badge($t['status'])) ?>"><?= e(ucfirst($t['status'])) ?></span>
                            </div>
                            <div class="small text-muted mt-1"><?= e($t['created_at']) ?></div>
                            <div class="mt-2"><?= e($t['message']) ?></div>
                            <?php if (!empty($t['reply'])): ?>
                                <div class="mt-3 p-3 rounded-4 bg-light border">
                                    <div class="small text-uppercase text-muted">Admin reply</div>
                                    <div><?= e($t['reply']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($tickets)): ?><div class="text-muted">No tickets yet.</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
