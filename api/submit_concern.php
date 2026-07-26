<?php
require_once __DIR__ . '/_bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$user = current_user();
if (!in_array(($user['role'] ?? ''), ['resident', 'admin'], true)) {
    json_response(['ok' => false, 'message' => 'Forbidden'], 403);
}

$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($subject === '' || $message === '') {
    json_response(['ok' => false, 'message' => 'Subject and message are required'], 422);
}

$residentId = null;
$houseNumber = null;

if ($user['role'] === 'resident') {
    $resident = resident_record((int)$user['id']);
    $residentId = $resident['id'] ?? null;
    $houseNumber = $resident['house_number'] ?? ($user['house'] ?? null);
}

$stmt = db()->prepare("INSERT INTO concerns (resident_id, sender_name, sender_role, house_number, subject, message, status) VALUES (?, ?, ?, ?, ?, ?, 'open')");
$stmt->execute([
    $residentId,
    $user['name'],
    $user['role'],
    $houseNumber,
    $subject,
    $message,
]);

add_log('Concern submitted API', $subject);
json_response(['ok' => true, 'message' => 'Concern submitted']);
