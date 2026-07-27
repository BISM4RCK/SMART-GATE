<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
/* BISM4RCK/KUN3H0 2026 */
$user = current_user();
$role = $user['role'] ?? '';
$links = match ($role) {
    'resident' => [
        ['Dashboard', 'resident/dashboard.php', 'bi-speedometer2'],
        ['Requests', 'resident/requests.php', 'bi-person-lines-fill'],
        ['Vehicles', 'resident/vehicles.php', 'bi-car-front'],
        ['Tickets', 'resident/tickets.php', 'bi-chat-left-text'],
        ['Notifications', 'notifications.php', 'bi-bell'],
    ],
    'guard' => [
        ['Dashboard', 'guard/dashboard.php', 'bi-speedometer2'],
        ['Quick Scan', 'guard/scan.php', 'bi-qr-code-scan'],
        ['Logs', 'guard/logs.php', 'bi-journal-text'],
        ['Notifications', 'notifications.php', 'bi-bell'],
    ],
    'admin' => [
        ['Dashboard', 'admin/dashboard.php', 'bi-speedometer2'],
        ['Tickets', 'admin/tickets.php', 'bi-chat-left-text'],
        ['Users', 'admin/users.php', 'bi-people'],
        ['Logs', 'admin/logs.php', 'bi-journal-text'],
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
            <a class="btn btn-sm gh-btn-outline" href="<?= e(url('logout.php')) ?>
"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
        </div>
    </div>
</aside>
<?php /* BISM4RCK/KUN3H0 2026 */ ?>
