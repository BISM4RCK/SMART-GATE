<?php
require_once __DIR__ . '/_bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
$user = current_user();
if (($user['role'] ?? '') !== 'resident') json_response(['ok' => false, 'message' => 'Forbidden'], 403);
$resident = resident_record((int)$user['id']);
$rid = $resident['id'] ?? 0;
$house = trim($_POST['house_number'] ?? '');
$name = trim($_POST['visitor_name'] ?? '');
$contact = trim($_POST['contact_number'] ?? '');
$plate = trim($_POST['plate_number'] ?? '');
$type = trim($_POST['vehicle_type'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');
if ($house === '' || $name === '' || $plate === '' || $type === '' || $purpose === '') json_response(['ok' => false, 'message' => 'Missing required fields'], 422);
$residentMatch = get_resident_by_house($house);
if (!$residentMatch) json_response(['ok' => false, 'message' => 'No resident found for that house number'], 404);
$qr = 'GH-' . strtoupper(bin2hex(random_bytes(4)));
$stmt = db()->prepare("INSERT INTO visitor_requests (resident_id, house_number, visitor_name, contact_number, plate_number, vehicle_type, purpose_of_visit, status, qr_reference) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
$stmt->execute([$rid, $house, $name, $contact ?: null, $plate, $type, $purpose, $qr]);
if (!empty($_FILES['government_id']['name'])) {
    $path = upload_file($_FILES['government_id']);
    if ($path !== '') {
        $stmt = db()->prepare("INSERT INTO visitor_attachments (visitor_request_id, file_type, file_path, original_filename, mime_type, file_size) VALUES (?, 'government_id', ?, ?, ?, ?)");
        $stmt->execute([db()->lastInsertId(), $path, $_FILES['government_id']['name'] ?? '', $_FILES['government_id']['type'] ?? '', $_FILES['government_id']['size'] ?? null]);
    }
}
add_notification((int)$residentMatch['user_id'], 'New visitor request', "A request was submitted for House {$house}.");
add_log('Visitor Request API', "House {$house} · {$plate}");
json_response(['ok' => true, 'message' => 'Visitor request submitted', 'qr_reference' => $qr]);
