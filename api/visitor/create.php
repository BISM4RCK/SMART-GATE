<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['ok' => false, 'message' => 'Method not allowed'], 405); }
$house = trim($_POST['house_number'] ?? '');
$name = trim($_POST['visitor_name'] ?? '');
$contact = trim($_POST['contact_number'] ?? '');
$plates = $_POST['plate_number'] ?? [];
$types = $_POST['vehicle_type'] ?? [];
if (!is_array($plates)) $plates = [$plates];
if (!is_array($types)) $types = [$types];
$vehicles = [];
foreach ($plates as $i => $vehiclePlate) {
    $vehiclePlate = trim((string)$vehiclePlate);
    $vehicleType = trim((string)($types[$i] ?? ''));
    if ($vehiclePlate !== '' || $vehicleType !== '') {
        $vehicles[] = ['plate_number' => $vehiclePlate, 'vehicle_type' => $vehicleType];
    }
}
$plate = $vehicles[0]['plate_number'] ?? '';
$type = $vehicles[0]['vehicle_type'] ?? '';
$purpose = trim($_POST['purpose'] ?? '');
$resident = ResidentModel::findByHouse($house);
if (!$resident) { json_response(['ok' => false, 'message' => 'No resident found for that house number'], 404); }
if ($house === '' || $name === '' || $purpose === '' || count($vehicles) < 1 || $plate === '' || $type === '') { json_response(['ok' => false, 'message' => 'Missing required fields'], 422); }
$qr = 'GH-' . strtoupper(bin2hex(random_bytes(4)));
$requestId = VisitorRequestModel::create([
    'resident_id' => (int)$resident['id'],
    'house_number' => $house,
    'visitor_name' => $name,
    'contact_number' => $contact,
    'plate_number' => $plate,
    'vehicle_type' => $type,
    'vehicles' => $vehicles,
    'purpose_of_visit' => $purpose,
    'qr_reference' => $qr,
    'requested_visit_date' => date('Y-m-d'),
    'requested_arrival_time' => date('H:i:s'),
]);
$idFile = store_upload($_FILES['government_id'] ?? [], 'ids');
if ($idFile) {
    $stmt = Database::pdo()->prepare("INSERT INTO visitor_attachments (visitor_request_id, file_type, file_path, original_filename, mime_type, file_size) VALUES (?, 'government_id', ?, ?, ?, ?)");
    $stmt->execute([$requestId, $idFile, $_FILES['government_id']['name'] ?? '', $_FILES['government_id']['type'] ?? '', $_FILES['government_id']['size'] ?? null]);
}
NotificationModel::create((int)$resident['user_id'], 'New visitor request', 'A visitor request was submitted for House ' . $house . '.');
json_response(['ok' => true, 'qr_reference' => $qr, 'request_id' => $requestId]);
/* BISM4RCK/KUN3H0 2026 */
