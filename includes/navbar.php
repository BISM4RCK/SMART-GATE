<?php $user = current_user(); $unreadCount = $user ? unread_notifications_count((int)$user['id']) : 0; $recentNotifications = $user ? latest_notifications((int)$user['id'], 5) : []; ?>
<nav class="navbar navbar-expand-lg navbar-dark gh-navbar border-bottom border-white border-opacity-10">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="<?= e($user ? dashboard_url($user) : url('index.php')) ?>">
            <span class="gh-logo-mark"><i class="bi bi-house-door-fill"></i></span>
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
                        <a class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            <?php if ($unreadCount > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= (int)$unreadCount ?></span><?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 320px;">
                            <li class="px-3 py-2 fw-semibold">Notifications</li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach ($recentNotifications as $n): ?>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-start gap-2" href="<?= e(url('notifications.php?read=' . (int)$n['id'])) ?>">
                                        <span>
                                            <span class="d-block fw-semibold"><?= e($n['title']) ?></span>
                                            <span class="small text-muted"><?= e($n['message']) ?></span>
                                        </span>
                                        <span class="badge rounded-pill <?= e($n['is_read'] ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis') ?>"><?= $n['is_read'] ? 'Read' : 'New' ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <?php if (!$recentNotifications): ?><li class="px-3 py-2 text-muted small">No notifications.</li><?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="<?= e(url('notifications.php')) ?>">View all</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(dashboard_url($user)) ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('dashboard.php')) ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('logout.php')) ?>">Logout</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>
