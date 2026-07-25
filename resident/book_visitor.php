<?php
$pageTitle = 'Book Visitor';
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_role(['resident']);

$user = current_user();
$residentId = null;

try {
    $stmt = db()->prepare("SELECT r.id FROM residents r JOIN users u ON u.id = r.user_id WHERE u.id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $user['id']]);
    $residentId = $stmt->fetchColumn() ?: null;
} catch (Throwable $e) {}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="content">
    <h1>Book Visitor</h1>
    <form method="post" action="<?= e(url('api/book_visitor.php')) ?>" enctype="multipart/form-data" class="grid" style="margin-top:18px">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="resident_id" value="<?= e((string)$residentId) ?>">

        <div class="form-grid">
            <div class="field">
                <label for="full_name">Visitor Full Name</label>
                <input id="full_name" name="full_name" required>
            </div>
            <div class="field">
                <label for="contact_number">Contact Number</label>
                <input id="contact_number" name="contact_number">
            </div>
            <div class="field">
                <label for="vehicle_type">Vehicle Type</label>
                <select id="vehicle_type" name="vehicle_type" required>
                    <option value="">Select</option>
                    <option value="car">Car</option>
                    <option value="motorcycle">Motorcycle</option>
                    <option value="truck">Truck</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="field">
                <label for="plate_number">Plate Number</label>
                <input id="plate_number" name="plate_number" required>
            </div>
            <div class="field">
                <label for="purpose_of_visit">Purpose of Visit</label>
                <input id="purpose_of_visit" name="purpose_of_visit" required>
            </div>
            <div class="field">
                <label for="requested_visit_date">Visit Date</label>
                <input id="requested_visit_date" name="requested_visit_date" type="date" required>
            </div>
            <div class="field">
                <label for="requested_arrival_time">Arrival Time</label>
                <input id="requested_arrival_time" name="requested_arrival_time" type="time" required>
            </div>
            <div class="field">
                <label for="government_id_type">ID Type</label>
                <input id="government_id_type" name="government_id_type" placeholder="Driver's License">
            </div>
            <div class="field">
                <label for="government_id_number">ID Number</label>
                <input id="government_id_number" name="government_id_number">
            </div>
        </div>

        <div class="field">
            <label for="government_id_image">Government ID Image</label>
            <input id="government_id_image" name="government_id_image" type="file" accept="image/*" required>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">Submit Booking Request</button>
            <a class="btn secondary" href="<?= e(url('resident/dashboard.php')) ?>">Back</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
