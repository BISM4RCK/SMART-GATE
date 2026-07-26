<?php
$user = current_user();
$role = $user['role'] ?? '';
$menu = match ($role) {
    'resident' => [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'href' => 'resident/dashboard.php'],
        ['icon' => 'bi-chat-left-dots', 'label' => 'Concerns', 'href' => 'resident/concerns.php'],
        ['icon' => 'bi-car-front', 'label' => 'Vehicles', 'href' => 'resident/vehicles.php'],
        ['icon' => 'bi-clock-history', 'label' => 'History', 'href' => 'resident/history.php'],
    ],
    'guard' => [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'href' => 'guard/dashboard.php'],
        ['icon' => 'bi-journal-text', 'label' => 'Logs', 'href' => 'guard/logs.php'],
        ['icon' => 'bi-qr-code-scan', 'label' => 'Quick Scan', 'href' => 'guard/dashboard.php#scan'],
        ['icon' => 'bi-door-open', 'label' => 'Gate', 'href' => 'guard/dashboard.php#gate'],
    ],
    'admin' => [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'href' => 'admin/dashboard.php'],
        ['icon' => 'bi-chat-left-text', 'label' => 'Concerns', 'href' => 'admin/concerns.php'],
        ['icon' => 'bi-journal-text', 'label' => 'Logs', 'href' => 'admin/logs.php'],
        ['icon' => 'bi-gear', 'label' => 'Settings', 'href' => 'admin/settings.php'],
    ],
    default => []
};
$current = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
?>
<aside class="gh-sidebar">
    <div class="p-3">
        <div class="gh-sidebar-card">
            <div class="small text-uppercase text-muted mb-1">Signed in as</div>
            <div class="fw-semibold"><?= e($user['name'] ?? 'User') ?></div>
            <div class="small text-muted text-capitalize"><?= e($role) ?></div>
        </div>
        <div class="mt-3 d-grid gap-2">
            <?php foreach ($menu as $item): ?>
                <a class="btn <?= str_contains($current, $item['href']) ? 'btn-ghost-active' : 'btn-ghost' ?>" href="<?= e(url($item['href'])) ?>">
                    <i class="bi <?= e($item['icon']) ?> me-2"></i><?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 d-grid gap-2">
            <a class="btn btn-sm gh-btn-soft" href="<?= e(url('dashboard.php')) ?>"><i class="bi bi-arrow-return-left me-2"></i>Back to Dashboard</a>
            <a class="btn btn-sm gh-btn-outline" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
        </div>
    </div>
</aside>
