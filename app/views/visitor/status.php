<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
?>
<div class="container py-4">
    <div class="gh-card p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h2 class="mb-1">Visitor Status</h2>
                <div class="text-muted">Reference: <?= e($ref) ?></div>
            </div>
            <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('index.php')) ?>">Home</a>
        </div>

        <?php if (empty($request)): ?>
            <div class="alert alert-warning mb-0">No request found for that reference.</div>
        <?php else: ?>
            <div class="row g-3">
                <div class="col-md-4"><div class="gh-stat"><div class="label">House</div><div class="value fs-4"><?= e($request['house_number']) ?></div></div></div>
                <div class="col-md-4"><div class="gh-stat"><div class="label">Visitor</div><div class="value fs-4"><?= e($request['visitor_name']) ?></div></div></div>
                <div class="col-md-4"><div class="gh-stat"><div class="label">Status</div><div class="value fs-4"><?= e($request['status']) ?></div></div></div>
            </div>
            <div class="mt-4">
                <div class="fw-semibold mb-2">Purpose</div>
                <div><?= e($request['purpose_of_visit']) ?></div>
            </div>
            <div class="mt-4 p-3 rounded-4 border bg-light">
                <div class="fw-semibold mb-1">QR Reference</div>
                <div><?= e($request['qr_reference']) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
