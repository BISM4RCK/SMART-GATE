<?php
/* BISM4RCK-KUN3H0 2026 */
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Visitor Requests</h2>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('resident/dashboard.php')) ?>">Back</a>
    </div>

    <div class="gh-card p-4">
        <div class="table-responsive">
            <table class="table gh-table align-middle">
                <thead><tr><th>Visitor</th><th>House</th><th>Plate</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($requests as $row): ?>
                        <tr>
                            <td><?= e($row['visitor_name']) ?></td>
                            <td><?= e($row['house_number']) ?></td>
                            <td><?= e($row['plate_number']) ?></td>
                            <td><span class="badge rounded-pill <?= e(gate_badge($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                            <td class="d-flex gap-2">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form method="post" action="<?= e(url('resident/requests.php')) ?>" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>">
                                        <input type="hidden" name="action" value="approved">
                                        <button class="btn btn-sm gh-primary rounded-pill" type="submit">Approve</button>
                                    </form>
                                    <form method="post" action="<?= e(url('resident/requests.php')) ?>" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>">
                                        <input type="hidden" name="action" value="rejected">
                                        <input type="hidden" name="reason" value="Rejected by resident">
                                        <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small text-muted">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requests)): ?><tr><td colspan="5" class="text-center text-muted py-4">No requests yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
