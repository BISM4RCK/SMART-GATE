<?php
/* BISM4RCK-KUN3H0 2026 */
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Vehicles</h2>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('resident/dashboard.php')) ?>">Back</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="gh-card p-4">
                <h5 class="mb-3">Add vehicle</h5>
                <form method="post" action="<?= e(url('resident/vehicles.php')) ?>" class="d-grid gap-3"><?= csrf_field() ?>
                    <div><label>Plate</label><input class="form-control" name="plate_number" required></div>
                    <div>
                        <label>Type</label>
                        <select class="form-select" name="vehicle_type" required>
                            <option value="">Choose...</option>
                            <option>Car</option><option>Motorcycle</option><option>Truck</option><option>Other</option>
                        </select>
                    </div>
                    <div><label>Color</label><input class="form-control" name="color"></div>
                    <button class="btn gh-primary rounded-pill" type="submit">Save</button>
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
                                    <td><span class="badge rounded-pill <?= e(gate_badge($v['status'])) ?>"><?= e($v['status']) ?></span> <form method="post" action="<?= e(url('resident/vehicles.php')) ?>" class="d-inline" onsubmit="return confirm('Remove this vehicle?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="vehicle_id" value="<?= (int)$v['id'] ?>"><button class="btn btn-sm btn-outline-danger ms-2" type="submit">Remove</button></form></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($vehicles)): ?><tr><td colspan="4" class="text-center text-muted py-4">No vehicles yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
