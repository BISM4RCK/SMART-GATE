<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Users</h2>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('admin/dashboard.php')) ?>">Back</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="gh-card p-4">
                <h5 class="mb-3">Residents</h5>
                <div class="table-responsive">
                    <table class="table gh-table align-middle">
                        <thead><tr><th>Name</th><th>House</th><th>Email</th></tr></thead>
                        <tbody>
                            <?php foreach ($residents as $r): ?>
                                <tr>
                                    <td><?= e($r['full_name']) ?></td>
                                    <td><?= e($r['house_number']) ?></td>
                                    <td><?= e($r['email']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($residents)): ?><tr><td colspan="3" class="text-center text-muted py-4">None</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="gh-card p-4">
                <h5 class="mb-3">Guards / Admins</h5>
                <div class="table-responsive mb-4">
                    <table class="table gh-table align-middle">
                        <thead><tr><th>Name</th><th>Role</th><th>Email</th></tr></thead>
                        <tbody>
                            <?php foreach (array_merge($guards, $admins) as $u): ?>
                                <tr>
                                    <td><?= e($u['full_name']) ?></td>
                                    <td><?= e($u['role']) ?></td>
                                    <td><?= e($u['email']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h5 class="mb-3">Vehicles</h5>
                <div class="table-responsive">
                    <table class="table gh-table align-middle">
                        <thead><tr><th>Plate</th><th>Type</th><th>Color</th><th>House</th></tr></thead>
                        <tbody>
                            <?php foreach ($vehicles as $v): ?>
                                <tr>
                                    <td><?= e($v['plate_number']) ?></td>
                                    <td><?= e($v['vehicle_type']) ?></td>
                                    <td><?= e($v['color']) ?></td>
                                    <td><?= e($v['house_number']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($vehicles)): ?><tr><td colspan="4" class="text-center text-muted py-4">None</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h5 class="mb-3 mt-4">Blacklist</h5>
                <div class="table-responsive">
                    <table class="table gh-table align-middle">
                        <thead><tr><th>Plate</th><th>Visitor</th><th>Reason</th></tr></thead>
                        <tbody>
                            <?php foreach ($blacklist as $b): ?>
                                <tr>
                                    <td><?= e($b['plate_number']) ?></td>
                                    <td><?= e($b['visitor_name']) ?></td>
                                    <td><?= e($b['reason']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($blacklist)): ?><tr><td colspan="3" class="text-center text-muted py-4">None</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
