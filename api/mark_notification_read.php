<?php
require_once __DIR__ . '/_bootstrap.php';
require_login();
$user = current_user();
$id = (int)($_GET['id'] ?? 0);
if ($id) mark_notification_read($id, (int)$user['id']);
json_response(['ok' => true, 'message' => 'Marked as read']);
