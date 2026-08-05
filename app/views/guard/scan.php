<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Quick Scan</h2>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('guard/dashboard.php')) ?>">Back</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="gh-card p-4">
                <h5 class="mb-3">Manual gate log</h5>
                <form method="post" action="<?= e(url('guard/scan.php')) ?>" class="d-grid gap-3">
                    <div><label>Plate Number</label><input class="form-control" name="plate_number" placeholder="ABC 1234"></div>
                    <div><label>RFID UID</label><input class="form-control" name="rfid_uid" placeholder="RFID-123"></div>
                    <div>
                        <label>Event Type</label>
                        <select class="form-select" name="event_type">
                            <option value="manual_open">manual_open</option>
                            <option value="rfid_scan">rfid_scan</option>
                            <option value="plate_scan">plate_scan</option>
                            <option value="combined_scan">combined_scan</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="manual_override" id="manual_override" value="1">
                        <label class="form-check-label" for="manual_override">Manual override</label>
                    </div>
                    <button class="btn gh-primary rounded-pill" type="submit">Log access</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="gh-card p-4">
                <h5 class="mb-3">Recent logs</h5>
                <div class="table-responsive">
                    <table class="table gh-table align-middle">
                        <thead><tr><th>Time</th><th>Event</th><th>Plate</th><th>RFID</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= e($log['created_at']) ?></td>
                                    <td><?= e($log['event_type']) ?></td>
                                    <td><?= e($log['plate_number']) ?></td>
                                    <td><?= e($log['rfid_uid']) ?></td>
                                    <td><span class="badge rounded-pill <?= e(gate_badge($log['gate_status'])) ?>"><?= e($log['gate_status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($logs)): ?><tr><td colspan="5" class="text-center text-muted py-4">No logs yet.</td></tr><?php endif; ?>
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
