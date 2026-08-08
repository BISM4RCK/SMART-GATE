<?php
/* BISM4RCK-KUN3H0 2026 */
require_once __DIR__ . '/_bootstrap.php';
require_login();
$user = current_user();
$id = (int)($_GET['id'] ?? 0);
if ($id) NotificationModel::markRead($id, (int)$user['id']);
json_response(['ok' => true]);
