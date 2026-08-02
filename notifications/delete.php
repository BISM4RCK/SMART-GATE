<?php
/* BISM4RCK/KUN3H0 2026 */
require_once __DIR__ . '/../app/Core.php';
require_once __DIR__ . '/../app/Models.php';
/* BISM4RCK/KUN3H0 2026 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid notification ID');
}

$csrf = $_POST['csrf_token'] ?? '';
if (function_exists('verify_csrf')) {
    if (!verify_csrf($csrf)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

$deleted = false;
if (class_exists('NotificationModel') && method_exists('NotificationModel', 'delete')) {
    $deleted = NotificationModel::delete($id, (int)$user['id']);
} else {
    $pdo = db();
    $stmt = $pdo->prepare(
        "DELETE FROM notifications WHERE id = :id AND user_id = :user_id"
    );
    $stmt->execute([
        ':id' => $id,
        ':user_id' => (int)$user['id'],
    ]);
    $deleted = $stmt->rowCount() > 0;
}

$back = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header('Location: ' . $back);
exit;

/* BISM4RCK/KUN3H0 2026 */
