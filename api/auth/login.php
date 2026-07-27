<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['ok' => false, 'message' => 'Method not allowed'], 405); }
$user = Auth::login(trim($_POST['email'] ?? ''), trim($_POST['password'] ?? ''));
if (!$user) { json_response(['ok' => false, 'message' => 'Invalid email or password'], 401); }
json_response(['ok' => true, 'user' => $user, 'redirect' => dashboard_url($user)]);
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
