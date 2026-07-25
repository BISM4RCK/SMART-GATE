<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$email = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$role = trim($_POST['role'] ?? '');

$user = get_user_by_email($email);

if (!$user || !password_verify($password, $user['password'])) {
    flash('error', 'Invalid credentials.');
    redirect(str_starts_with($_SERVER['HTTP_REFERER'] ?? '', url('guard/')) ? 'guard/login.php' : 'resident/login.php');
}

if ($role && $user['role'] !== $role) {
    flash('error', 'You are not authorized for that portal.');
    redirect(match ($role) {
        'resident' => 'resident/login.php',
        'guard' => 'guard/login.php',
        'admin' => 'admin/login.php',
        default => 'index.php',
    });
}

login_user($user);
audit_log((int)$user['id'], 'login', 'authentication', 'User logged in.');

redirect(match ($user['role']) {
    'resident' => 'resident/dashboard.php',
    'guard' => 'guard/dashboard.php',
    'admin' => 'admin/dashboard.php',
    default => 'index.php',
});
