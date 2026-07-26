<?php
require_once __DIR__ . '/_bootstrap.php';
require_login();

$user = current_user();
if (($user['role'] ?? '') === 'resident') {
    $resident = resident_record((int)$user['id']);
    $residentId = $resident['id'] ?? 0;
    $stmt = db()->prepare("SELECT * FROM visitor_requests WHERE resident_id = ? ORDER BY created_at DESC");
    $stmt->execute([$residentId]);
} else {
    $stmt = db()->query("SELECT * FROM visitor_requests ORDER BY created_at DESC");
}

json_response(['ok' => true, 'requests' => $stmt->fetchAll()]);
