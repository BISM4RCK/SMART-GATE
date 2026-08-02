<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
require_once __DIR__ . '/_bootstrap.php';
require_login();
$user = current_user();
json_response(['ok' => true, 'unread' => unread_notifications_count((int)$user['id']), 'notifications' => latest_notifications((int)$user['id'], 20)]);
/* BISM4RCK/KUN3H0 2026 */
