<?php
/* BISM4RCK/KUN3H0 2026 */
require_once __DIR__ . '/../app/bootstrap.php';
/* BISM4RCK/KUN3H0 2026 */

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

csrf_validate();

$user = current_user();
$notificationId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$notificationId) {
    flash_set('danger', 'Invalid notification.');
    redirect('notifications.php');
}

$deleted = NotificationModel::delete((int)$notificationId, (int)$user['id']);

if ($deleted) {
    flash_set('success', 'Notification deleted.');
} else {
    flash_set('warning', 'Notification could not be deleted.');
}

redirect('notifications.php');

/* BISM4RCK/KUN3H0 2026 */
