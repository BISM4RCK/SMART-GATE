<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
/* BISM4RCK/KUN3H0 2026 */
$user = current_user();
$role = $user['role'] ?? '';
$links = match ($role) {
    'resident' => [
        ['Dashboard', 'dashboard.php', 'bi-speedometer2'],
        ['Requests', 'requests.php', 'bi-person-lines-fill'],
        ['Vehicles', 'vehicles.php', 'bi-car-front'],
        ['Tickets', 'tickets.php', 'bi-chat-left-text'],
        ['Notifications', 'notifications.php', 'bi-bell'],
    ],
    'guard' => [
        ['Dashboard', 'dashboard.php', 'bi-speedometer2'],
        ['Quick Scan', 'scan.php', 'bi-qr-code-scan'],
        ['Logs', 'logs.php', 'bi-journal-text'],
        ['Notifications', 'notifications.php', 'bi-bell'],
    ],
    'admin' => [
        ['Dashboard', 'dashboard.php', 'bi-speedometer2'],
        ['Tickets', 'tickets.php', 'bi-chat-left-text'],
        ['Users', 'users.php', 'bi-people'],
        ['Logs', 'logs.php', 'bi-journal-text'],
        ['Notifications', 'notifications.php', 'bi-bell'],
    ],
    default => []
};
$current = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
?>
<aside class="gh-sidebar d-none d-lg-block">
    <div class="p-3">
        <div class="gh-sidebar-card">
            <div class="small text-uppercase text-muted mb-1">Signed in as</div>
            <div class="fw-semibold"><?= e($user['name'] ?? 'User') ?></div>
            <div class="small text-muted text-capitalize"><?= e($role) ?></div>
        </div>
        <div class="mt-3 d-grid gap-2">
            <?php foreach ($links as [$label, $href, $icon]): ?>
                <a class="btn <?= str_contains($current, $href) ? 'btn-ghost-active' : 'btn-ghost' ?>" href="<?= e(url($href)) ?>">
                    <i class="bi <?= e($icon) ?> me-2"></i><?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 d-grid gap-2">
            <a class="btn btn-sm gh-btn-soft" href="<?= e(dashboard_url($user)) ?>"><i class="bi bi-arrow-return-left me-2"></i>Back to Dashboard</a>
            <a class="btn btn-sm gh-btn-outline" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
        </div>
    </div>
</aside>

