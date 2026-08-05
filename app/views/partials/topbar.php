<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
/* BISM4RCK/KUN3H0 2026 */
$user = current_user();
?>

<nav class="navbar navbar-expand-lg navbar-dark gh-navbar border-bottom border-white border-opacity-10">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="<?= e($user ? dashboard_url($user) : url('index.php')) ?>">
            <span class="gh-brand-mark">GH</span>
            <span><?= e(APP_SHORT) ?></span>
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
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item dropdown">
                        <a class="nav-link gh-header-pill gh-header-notify dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown">Notifications
                            <?php if ($unreadCount > 0): ?><span class="badge bg-danger rounded-pill ms-1"><?= (int)$unreadCount ?></span><?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <?php foreach (latest_notifications((int)$user['id'], 5) as $n): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= e(url('notifications.php?read=' . (int)$n['id'] . '&back=' . urlencode($_SERVER['REQUEST_URI'] ?? 'notifications.php'))) ?>">
                                        <div class="fw-semibold"><?= e($n['title']) ?></div>
                                        <div class="small text-muted text-truncate" style="max-width: 260px;"><?= e($n['message']) ?></div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <?php if (!$unreadCount): ?><li><span class="dropdown-item-text small text-muted">No unread notifications</span></li><?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= e(url('notifications.php')) ?>">View all notifications</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link gh-header-pill gh-header-dashboard" href="<?= e(dashboard_url($user)) ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link gh-header-logout" href="<?= e(url('logout.php')) ?>">Logout</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>



<?php
/* BISM4RCK/KUN3H0 2026 */
?>
