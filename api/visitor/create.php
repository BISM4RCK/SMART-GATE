<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['ok' => false, 'message' => 'Method not allowed'], 405); }
$house = trim($_POST['house_number'] ?? '');
$name = trim($_POST['visitor_name'] ?? '');
$contact = trim($_POST['contact_number'] ?? '');
$plate = trim($_POST['plate_number'] ?? '');
$type = trim($_POST['vehicle_type'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');
$resident = ResidentModel::findByHouse($house);
if (!$resident) { json_response(['ok' => false, 'message' => 'No resident found for that house number'], 404); }
if ($house === '' || $name === '' || $plate === '' || $type === '' || $purpose === '') { json_response(['ok' => false, 'message' => 'Missing required fields'], 422); }
$qr = 'GH-' . strtoupper(bin2hex(random_bytes(4)));
$requestId = VisitorRequestModel::create([
    'resident_id' => (int)$resident['id'],
    'house_number' => $house,
    'visitor_name' => $name,
    'contact_number' => $contact,
    'plate_number' => $plate,
    'vehicle_type' => $type,
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
