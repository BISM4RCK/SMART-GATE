<?php
/* BISM4RCK-KUN3H0 2026 */
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['ok' => false, 'message' => 'Method not allowed'], 405); }
$house = trim($_POST['house_number'] ?? '');
$name = trim($_POST['visitor_name'] ?? '');
$contact = trim($_POST['contact_number'] ?? '');
$plate = trim($_POST['plate_number'] ?? '');
$type = trim($_POST['vehicle_type'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');
$people = max(1,(int)($_POST['people_count'] ?? 1));
$idNotAvailable = !empty($_POST['id_not_available']);
$plates=$_POST['vehicle_plate']??[$plate]; $types=$_POST['vehicle_type']??[$type]; $vpeople=$_POST['vehicle_people']??[$people];
if(!is_array($plates))$plates=[$plates]; if(!is_array($types))$types=[$types]; if(!is_array($vpeople))$vpeople=[$vpeople];
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
    'people_count' => $people,
    'id_not_available' => $idNotAvailable,
    'qr_reference' => $qr,
    'requested_visit_date' => date('Y-m-d'),
    'requested_arrival_time' => date('H:i:s'),
]);
$firstPlate=strtoupper(trim($plates[0]??$plate)); $firstType=strtolower(trim($types[0]??$type));
VisitorRequestModel::addVehicle($requestId,$firstPlate,$firstType,max(1,(int)($vpeople[0]??$people)));
for($i=1,$n=count($plates);$i<$n;$i++){ $vp=strtoupper(trim($plates[$i]??''));$vt=strtolower(trim($types[$i]??''));$pc=max(1,(int)($vpeople[$i]??1)); if($vp!==''&&in_array($vt,['car','motorcycle','truck','other'],true)) VisitorRequestModel::addVehicle($requestId,$vp,$vt,$pc); }
$credential=VisitorCredentialModel::create($requestId);
$idFile = store_upload($_FILES['government_id'] ?? [], 'ids');
if ($idFile) {
    $stmt = Database::pdo()->prepare("INSERT INTO visitor_attachments (visitor_request_id, file_type, file_path, original_filename, mime_type, file_size) VALUES (?, 'government_id', ?, ?, ?, ?)");
    $stmt->execute([$requestId, $idFile, $_FILES['government_id']['name'] ?? '', $_FILES['government_id']['type'] ?? '', $_FILES['government_id']['size'] ?? null]);
}
NotificationModel::create((int)$resident['user_id'], 'New visitor request', 'A visitor request was submitted for House ' . $house . '.');
json_response(['ok'=>true,'qr_reference'=>$qr,'request_id'=>$requestId,'visitor_id'=>$credential['visitor_id'],'qr_token'=>$credential['qr_token'],'barcode_token'=>$credential['barcode_token']]);
