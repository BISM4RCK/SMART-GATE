<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
require_once __DIR__ . '/_bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['ok' => false, 'message' => 'Method not allowed'], 405); }
$user = current_user();
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
if ($subject === '' || $message === '') { json_response(['ok' => false, 'message' => 'Subject and message are required'], 422); }
$residentId = 0;
$house = '';
if (($user['role'] ?? '') === 'resident') {
    $resident = ResidentModel::findByUserId((int)$user['id']);
    $residentId = (int)($resident['id'] ?? 0);
    $house = $resident['house_number'] ?? ($user['house'] ?? '');
}
TicketModel::create($residentId, $user['name'], $user['role'], $house, $subject, $message);
$admin = UserModel::byRole('admin')[0] ?? null;
if ($admin) NotificationModel::create((int)$admin['id'], 'New trouble ticket', $subject);
json_response(['ok' => true, 'message' => 'Ticket created']);
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
