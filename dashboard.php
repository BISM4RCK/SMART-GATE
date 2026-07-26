<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
redirect(match ((current_user()['role'] ?? '')) {
    'resident' => 'resident/dashboard.php',
    'guard' => 'guard/dashboard.php',
    'admin' => 'admin/dashboard.php',
    default => 'index.php',
});
