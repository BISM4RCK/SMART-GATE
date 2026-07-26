<?php
require_once __DIR__ . '/_bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
$user = current_user();
if (($user['role'] ?? '') !== 'resident') json_response(['ok' => false, 'message' => 'Forbidden'], 403);
$resident = resident_record((int)$user['id']);
$rid = $resident['id'] ?? 0;
$plate = trim($_POST['plate_number'] ?? '');
$type = trim($_POST['vehicle_type'] ?? '');
$color = trim($_POST['color'] ?? '');
if ($plate === '' || $type === '') json_response(['ok' => false, 'message' => 'Plate and type are required'], 422);
try {
    $stmt = db()->prepare("INSERT INTO vehicles (resident_id, plate_number, vehicle_type, color, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->execute([$rid, $plate, $type, $color ?: 'N/A']);
    add_log('Vehicle added', "{$plate} added via API.");
    json_response(['ok' => true, 'message' => 'Vehicle saved']);
} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => 'Vehicle could not be saved'], 400);
}
