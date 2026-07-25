<?php
$pageTitle = 'Visitor Registration';
require_once __DIR__ . '/../includes/header.php';

try {
    $residents = db()->query("SELECT r.id, u.full_name, r.house_number
                             FROM residents r
                             JOIN users u ON u.id = r.user_id
                             WHERE r.status = 'active'
                             ORDER BY u.full_name ASC")->fetchAll();
} catch (Throwable $e) {
    $residents = [];
}
?>
<div class="content">
    <h1>Visitor Registration</h1>
    <p class="small">Fill out the form below. Your request will stay pending until the resident approves it.</p>

    <form method="post" action="<?= e(url('api/book_visitor.php')) ?>" enctype="multipart/form-data" class="grid" style="margin-top:18px">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-grid">
            <div class="field">
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" required>
            </div>

            <div class="field">
                <label for="contact_number">Contact Number</label>
                <input id="contact_number" name="contact_number">
            </div>

            <div class="field">
                <label for="vehicle_type">Vehicle Type</label>
                <select id="vehicle_type" name="vehicle_type" required>
                    <option value="">Select type</option>
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
                <label for="resident_id">Resident to Visit</label>
                <select id="resident_id" name="resident_id" required>
                    <option value="">Select resident</option>
                    <?php foreach ($residents as $resident): ?>
                        <option value="<?= (int)$resident['id'] ?>">
                            <?= e($resident['full_name']) ?> — <?= e($resident['house_number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="purpose_of_visit">Purpose of Visit</label>
                <input id="purpose_of_visit" name="purpose_of_visit" required>
            </div>

            <div class="field">
                <label for="requested_visit_date">Expected Visit Date</label>
                <input id="requested_visit_date" name="requested_visit_date" type="date" required>
            </div>

            <div class="field">
                <label for="requested_arrival_time">Expected Arrival Time</label>
                <input id="requested_arrival_time" name="requested_arrival_time" type="time" required>
            </div>

            <div class="field">
                <label for="government_id_type">Government ID Type</label>
                <input id="government_id_type" name="government_id_type" placeholder="e.g., Driver's License">
            </div>

            <div class="field">
                <label for="government_id_number">Government ID Number</label>
                <input id="government_id_number" name="government_id_number">
            </div>
        </div>

        <div class="field">
            <label for="government_id_image">Upload ID Image</label>
            <input id="government_id_image" name="government_id_image" type="file" accept="image/*" required>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">Submit Request</button>
            <a class="btn secondary" href="<?= e(url('index.php')) ?>">Back</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
