<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Logs</h2>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('admin/dashboard.php')) ?>">Back</a>
    </div>

    <div class="gh-card p-4">
        <div class="table-responsive">
            <table class="table gh-table align-middle">
                <thead>
                    <tr><th>Time</th><th>Event</th><th>Plate / Visitor</th><th>House</th><th>Actor</th><th>Status</th><th>Photos</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= e($log['created_at']) ?></td>
                            <td><?= e(ucwords(str_replace('_',' ', $log['event_type']))) ?></td>
                            <td><?= e($log['plate_number']) ?><br><small><?= e($log['visitor_name'] ?? '') ?></small></td>
                            <td><?= e($log['visit_house_number'] ?? '') ?></td>
                            <td><?= e($log['actor_name'] ?? 'ESP32') ?></td>
                            <td><span class="badge rounded-pill <?= e(gate_badge($log['gate_status'])) ?>"><?= e($log['gate_status']) ?></span></td>
                            <td>
                                <?php if (!empty($log['plate_photo_path'])): ?><a href="<?= e(url($log['plate_photo_path'])) ?>" target="_blank">Plate</a><?php endif; ?>
                                <?php if (!empty($log['vehicle_photo_path'])): ?><?php if (!empty($log['plate_photo_path'])): ?> · <?php endif; ?><a href="<?= e(url($log['vehicle_photo_path'])) ?>" target="_blank">Vehicle</a><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?><tr><td colspan="7" class="text-center text-muted py-4">No logs yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
