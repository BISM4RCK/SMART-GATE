<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$fullName = trim($_POST['full_name'] ?? '');
$contactNumber = trim($_POST['contact_number'] ?? '');
$vehicleType = trim($_POST['vehicle_type'] ?? 'other');
$plateNumber = strtoupper(trim($_POST['plate_number'] ?? ''));
$purpose = trim($_POST['purpose_of_visit'] ?? '');
$residentId = (int)($_POST['resident_id'] ?? 0);
$requestDate = $_POST['requested_visit_date'] ?? null;
$requestTime = $_POST['requested_arrival_time'] ?? null;
$idType = trim($_POST['government_id_type'] ?? '');
$idNumber = trim($_POST['government_id_number'] ?? '');

if ($fullName === '' || $plateNumber === '' || $purpose === '' || $residentId <= 0) {
    flash('error', 'Please complete all required fields.');
    redirect('visitor/register.php');
}

$allowedVehicleTypes = ['car', 'motorcycle', 'truck', 'other'];
if (!in_array($vehicleType, $allowedVehicleTypes, true)) {
    $vehicleType = 'other';
}

try {
    db()->beginTransaction();

    $stmt = db()->prepare("
        INSERT INTO visitor_requests
            (resident_id, visitor_name, contact_number, plate_number, vehicle_type, purpose_of_visit,
             government_id_type, government_id_number, status, requested_visit_date, requested_arrival_time)
        VALUES
            (:resident_id, :visitor_name, :contact_number, :plate_number, :vehicle_type, :purpose_of_visit,
             :government_id_type, :government_id_number, 'pending', :requested_visit_date, :requested_arrival_time)
    ");
    $stmt->execute([
        ':resident_id' => $residentId,
        ':visitor_name' => $fullName,
        ':contact_number' => $contactNumber ?: null,
        ':plate_number' => $plateNumber,
        ':vehicle_type' => $vehicleType,
        ':purpose_of_visit' => $purpose,
        ':government_id_type' => $idType ?: null,
        ':government_id_number' => $idNumber ?: null,
        ':requested_visit_date' => $requestDate ?: null,
        ':requested_arrival_time' => $requestTime ?: null,
    ]);

    $requestId = (int)db()->lastInsertId();

    $uploadPath = null;
    if (!empty($_FILES['government_id_image']['tmp_name'])) {
        $file = $_FILES['government_id_image'];
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = 'gov_id_' . $requestId . '_' . time() . ($extension ? '.' . $extension : '');
        $destination = UPLOAD_DIR . '/' . $safeName;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $uploadPath = 'uploads/' . $safeName;
            $stmt = db()->prepare("
                INSERT INTO visitor_attachments
                    (visitor_request_id, file_type, file_path, original_filename, mime_type, file_size)
                VALUES
                    (:visitor_request_id, 'government_id', :file_path, :original_filename, :mime_type, :file_size)
            ");
            $stmt->execute([
                ':visitor_request_id' => $requestId,
                ':file_path' => $uploadPath,
                ':original_filename' => $file['name'],
                ':mime_type' => $file['type'] ?? null,
                ':file_size' => $file['size'] ?? null,
            ]);
        }
    }

    // Simple booking reference starter (QR text can be replaced later with actual QR generation)
    $qrReference = 'VR-' . date('Y') . '-' . str_pad((string)$requestId, 5, '0', STR_PAD_LEFT);
    db()->prepare("UPDATE visitor_requests SET qr_reference = :qr_reference WHERE id = :id")
        ->execute([':qr_reference' => $qrReference, ':id' => $requestId]);

    db()->commit();

    flash('success', 'Visitor request submitted successfully. Await resident approval.');
    redirect('visitor/status.php');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash('error', 'Unable to submit request. Please check the database connection and try again.');
    redirect('visitor/register.php');
}
