<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Welcome';
require_once __DIR__ . '/includes/header.php';
?>
<section class="gh-hero p-4 p-lg-5">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="gh-badge mb-3"><i class="bi bi-shield-check"></i> Minimalist local-hosted community access</span>
            <h1 class="mb-3">GOLDEN HOMES Subdivision</h1>
            <p class="lead gh-muted mb-4">
                A simple visitor and resident management system built for gated communities.
                Residents approve requests, visitors submit by house number, and guards view quick logs on one clean dashboard.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn gh-primary gh-action-btn" href="<?= e(url('visitor/register.php')) ?>"><i class="bi bi-person-plus me-1"></i> Visitor Request</a>
                <a class="btn gh-gold gh-action-btn" href="<?= e(url('login.php')) ?>"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="gh-card p-4">
                <h5 class="fw-bold mb-3">Designed to stay simple</h5>
                <div class="d-grid gap-3">
                    <div class="gh-card-soft p-3">
                        <div class="fw-semibold"><i class="bi bi-house-door me-2"></i>House number matching</div>
                        <div class="small gh-muted">Visitors enter the house number directly for faster matching and less name exposure.</div>
                    </div>
                    <div class="gh-card-soft p-3">
                        <div class="fw-semibold"><i class="bi bi-chat-dots me-2"></i>Resident-admin concerns</div>
                        <div class="small gh-muted">Raise concerns on the website without needing to call anyone.</div>
                    </div>
                    <div class="gh-card-soft p-3">
                        <div class="fw-semibold"><i class="bi bi-phone me-2"></i>Phone-friendly</div>
                        <div class="small gh-muted">Minimal layout that works cleanly on phones, tablets, and desktop browsers.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
