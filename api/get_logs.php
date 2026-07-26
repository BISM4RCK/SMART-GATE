<?php
require_once __DIR__ . '/_bootstrap.php';
require_login();

$user = current_user();
if (!in_array(($user['role'] ?? ''), ['resident', 'guard', 'admin'], true)) {
    json_response(['ok' => false, 'message' => 'Forbidden'], 403);
}

if ($user['role'] === 'resident') {
    $resident = resident_record((int)$user['id']);
    $residentId = $resident['id'] ?? 0;
    $stmt = db()->prepare("SELECT * FROM gate_logs WHERE resident_id = ? ORDER BY created_at DESC");
    $stmt->execute([$residentId]);
} else {
    $stmt = db()->query("SELECT * FROM gate_logs ORDER BY created_at DESC");
}

json_response(['ok' => true, 'logs' => $stmt->fetchAll()]);
