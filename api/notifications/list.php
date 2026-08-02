<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
require_once __DIR__ . '/_bootstrap.php';
require_login();
$user = current_user();
json_response(['ok' => true, 'unread' => unread_notifications_count((int)$user['id']), 'notifications' => latest_notifications((int)$user['id'], 20)]);
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026


<?php
/* BISM4RCK/KUN3H0 2026 */
if (!function_exists('notification_delete_button')) {
    function notification_delete_button($notificationId): string
    {
        $csrf = function_exists('csrf_token') ? csrf_token() : '';
        $id = (int)$notificationId;
        return '<form method="post" action="' . e(url('notifications/delete.php')) . '" class="d-inline ms-2" onsubmit="return confirm(\'Delete this notification?\');">'
             . '<input type="hidden" name="id" value="' . $id . '">'
             . '<input type="hidden" name="csrf_token" value="' . e($csrf) . '">'
             . '<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>'
             . '</form>';
    }
}
/* BISM4RCK/KUN3H0 2026 */
?>
