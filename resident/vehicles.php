<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'resident') redirect('../dashboard.php');
$pageTitle = 'Resident Vehicles';

$resident = resident_record((int)$user['id']);
$residentId = $resident['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plate = trim($_POST['plate'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $color = trim($_POST['color'] ?? '');
    if ($plate !== '' && $type !== '') {
        $stmt = db()->prepare("INSERT INTO vehicles (resident_id, plate_number, vehicle_type, color, status) VALUES (?, ?, ?, ?, 'active')");
        try {
            $stmt->execute([$residentId, $plate, strtolower($type), $color ?: 'N/A']);
            add_log('Vehicle added', "{$plate} added to resident account.");
            flash_set('success', 'Vehicle added successfully.');
            redirect('vehicles.php');
        } catch (Throwable $e) {
            flash_set('danger', 'Vehicle could not be saved. Plate may already exist.');
        }
    } else {
        flash_set('danger', 'Please fill in the required fields.');
    }
}

require_once __DIR__ . '/../includes/header.php';
$stmt = db()->prepare("SELECT * FROM vehicles WHERE resident_id = ? ORDER BY created_at DESC");
$stmt->execute([$residentId]);
$vehicles = $stmt->fetchAll();
?>
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <span class="gh-badge mb-2"><i class="bi bi-car-front"></i> Vehicles</span>
        <h2 class="gh-section-title mb-1">Simple vehicle list</h2>
        <div class="gh-muted">Add a vehicle and keep the dashboard clean.</div>
    </div>
    <a class="btn gh-gold rounded-pill" href="<?= e(url('resident/dashboard.php')) ?>">Back to dashboard</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="gh-card p-4">
            <h5 class="mb-3">Add vehicle</h5>
            <form method="post" class="d-grid gap-3">
                <div><label class="form-label">Plate Number</label><input class="form-control" name="plate" placeholder="ABC 1234" required></div>
                <div>
                    <label class="form-label">Type</label>
                    <select class="form-select" name="type" required>
                        <option value="">Choose...</option>
                        <option>Car</option>
                        <option>Motorcycle</option>
                        <option>Other</option>
                    </select>
                </div>
                <div><label class="form-label">Color</label><input class="form-control" name="color" placeholder="White"></div>
                <button class="btn gh-primary rounded-pill" type="submit">Save vehicle</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="gh-card p-4">
            <h5 class="mb-3">Registered vehicles</h5>
            <div class="table-responsive">
                <table class="table gh-table align-middle">
                    <thead><tr><th>Plate</th><th>Type</th><th>Color</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($vehicles as $v): ?>
                        <tr>
                            <td><?= e($v['plate_number']) ?></td>
                            <td><?= e($v['vehicle_type']) ?></td>
                            <td><?= e($v['color']) ?></td>
                            <td><span class="badge rounded-pill <?= e(badge_class($v['status'])) ?>"><?= e($v['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
