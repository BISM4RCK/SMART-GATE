<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Visitor Request';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $house = trim($_POST['house_number'] ?? '');
    $name = trim($_POST['visitor_name'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $plate = trim($_POST['plate_number'] ?? '');
    $type = trim($_POST['vehicle_type'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $idPath = upload_file($_FILES['government_id'] ?? []);

    $resident = get_resident_by_house($house);

    if (!$resident) {
        flash_set('danger', 'No resident was found for that house number.');
    } elseif ($house === '' || $name === '' || $plate === '' || $type === '' || $purpose === '') {
        flash_set('danger', 'Please complete the required fields.');
    } else {
        $stmt = db()->prepare("INSERT INTO visitor_requests (resident_id, house_number, visitor_name, contact_number, plate_number, vehicle_type, purpose_of_visit, status, qr_reference) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $qr = 'GH-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt->execute([
            $resident['id'],
            $house,
            $name,
            $contact ?: null,
            $plate,
            $type,
            $purpose,
            $qr,
        ]);

        $requestId = (int)db()->lastInsertId();

        if ($idPath !== '') {
            $stmt = db()->prepare("INSERT INTO visitor_attachments (visitor_request_id, file_type, file_path, original_filename, mime_type, file_size) VALUES (?, 'government_id', ?, ?, ?, ?)");
            $stmt->execute([
                $requestId,
                $idPath,
                $_FILES['government_id']['name'] ?? '',
                $_FILES['government_id']['type'] ?? '',
                $_FILES['government_id']['size'] ?? null,
            ]);
        }

        add_log('Visitor Request', "House {$house} · {$plate}");
        add_notification((int)$resident['user_id'], 'New visitor request', "A visitor request was submitted for House {$house}.");
        flash_set('success', 'Your visitor request was submitted and is now pending approval.');
        redirect('visitor/register.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="gh-card p-4 p-lg-5">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                <div>
                    <span class="gh-badge mb-2"><i class="bi bi-person-vcard"></i> Visitor request</span>
                    <h2 class="gh-section-title mb-1">Enter the house number, not the resident name</h2>
                    <div class="gh-muted">This keeps the form faster and avoids exposing resident names in a public dropdown.</div>
                </div>
                <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('index.php')) ?>">Back to home</a>
            </div>

            <form method="post" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="house_number">Resident House Number</label>
                    <input class="form-control" id="house_number" name="house_number" placeholder="12-A" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="visitor_name">Visitor Name</label>
                    <input class="form-control" id="visitor_name" name="visitor_name" placeholder="Juan Dela Cruz" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="contact_number">Contact Number</label>
                    <input class="form-control" id="contact_number" name="contact_number" placeholder="09xx xxx xxxx">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="plate_number">Plate Number</label>
                    <input class="form-control" id="plate_number" name="plate_number" placeholder="ABC 1234" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="vehicle_type">Vehicle Type</label>
                    <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                        <option value="">Choose...</option>
                        <option>Car</option>
                        <option>Motorcycle</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="purpose">Purpose of Visit</label>
                    <input class="form-control" id="purpose" name="purpose" placeholder="Family visit" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="government_id">Upload ID</label>
                    <div class="gh-file">
                        <input class="form-control" id="government_id" name="government_id" type="file" accept="image/*,.pdf" data-preview-file>
                        <div class="small gh-muted mt-2">Selected file: <span data-file-name>No file selected</span></div>
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                    <button class="btn gh-gold gh-action-btn" type="submit"><i class="bi bi-send me-1"></i>Submit Request</button>
                    <a class="btn btn-outline-secondary gh-action-btn" href="<?= e(url('login.php')) ?>">Resident Login</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
