<?php
require_once __DIR__ . '/_bootstrap.php';
require_login();
$user = current_user();
json_response(['ok' => true, 'unread' => unread_notifications_count((int)$user['id']), 'notifications' => latest_notifications((int)$user['id'], 20)]);
