<?php $user = current_user(); ?>
<nav class="navbar navbar-expand-lg navbar-dark gh-navbar border-bottom border-white border-opacity-10">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="<?= e($user ? dashboard_url($user) : url('index.php')) ?>">
            <span class="gh-logo-mark"><i class="bi bi-house-door-fill"></i></span>
            <span>
                <?= e(APP_SHORT) ?>
                <small class="d-block gh-brand-subtitle"><?= e(APP_NAME) ?></small>
            </span>
        </a>

        <?php if (!$user): ?>
            <div class="ms-auto">
                <a class="btn btn-sm gh-btn-outline" href="<?= e(url('login.php')) ?>">Login</a>
            </div>
        <?php else: ?>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#ghTopNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="ghTopNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item"><a class="nav-link" href="<?= e(dashboard_url($user)) ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('dashboard.php')) ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url($user['role'] . '/concerns.php')) ?>">Concerns</a></li>
                    <li class="nav-item"><a class="btn btn-sm gh-btn-outline ms-lg-2" href="<?= e(url('logout.php')) ?>">Logout</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>
