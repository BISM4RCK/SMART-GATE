<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
$user = login_with_credentials(trim($_POST['email'] ?? ''), trim($_POST['password'] ?? ''));
if (!$user) json_response(['ok' => false, 'message' => 'Invalid email or password'], 401);
add_log('API Login', $user['role'] . ' signed in via API.');
json_response(['ok' => true, 'message' => 'Login successful', 'user' => $user, 'redirect' => dashboard_url($user)]);
