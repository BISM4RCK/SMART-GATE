<?php
/* BISM4RCK-KUN3H0 2026 */
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1">Account Management</h2>
            <div class="text-muted">Manage resident and staff accounts separately, including administrator-controlled password resets.</div>
        </div>
    </div>

    <div class="gh-card p-3 mb-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Account Type</label>
                <select class="form-select" name="account_type">
                    <option value="all" <?= ($accountType ?? 'all') === 'all' ? 'selected' : '' ?>>All Accounts</option>
                    <option value="resident" <?= ($accountType ?? '') === 'resident' ? 'selected' : '' ?>>Residents</option>
                    <option value="staff" <?= ($accountType ?? '') === 'staff' ? 'selected' : '' ?>>Staff (Guards &amp; Admins)</option>
                </select>
            </div>
            <div class="col-md-3"><button class="btn gh-primary rounded-pill w-100">Filter Accounts</button></div>
            <div class="col-md-3"><a class="btn btn-outline-secondary rounded-pill w-100" href="<?= e(url('admin/users.php')) ?>">Clear Filter</a></div>
        </form>
    </div>

    <div class="row g-3">
        <div class="col-xl-5">
            <div class="gh-card p-4">
                <h5>Add Account</h5>
                <form method="post" class="d-grid gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create_user">
                    <input class="form-control" name="full_name" placeholder="Full name" required>
                    <input class="form-control" type="email" name="email" placeholder="Email" required>
                    <input class="form-control" type="password" name="password" placeholder="Password (6+ chars)" minlength="6" required>
                    <select class="form-select" id="accountRole" name="role" required>
                        <option value="">Account type...</option>
                        <option value="resident">Resident</option>
                        <option value="guard">Guard</option>
                        <option value="admin">Admin</option>
                    </select>
                    <div id="residentFields" class="d-none border rounded-3 p-3 bg-light">
                        <strong>Resident Address</strong>
                        <div class="row g-2 mt-1">
                            <div class="col"><label>Block</label><input class="form-control" id="resident_block" name="resident_block" inputmode="numeric"></div>
                            <div class="col"><label>Lot</label><input class="form-control" id="resident_lot" name="resident_lot" inputmode="numeric"></div>
                            <div class="col"><label>Letter</label><input class="form-control text-uppercase" id="resident_letter" name="resident_letter" maxlength="1"></div>
                        </div>
                        <div class="small text-muted mt-2">Stored house number: <strong id="resident-house-preview">Block-Lot-Letter</strong></div>
                        <input class="form-control mt-2" name="contact_number" placeholder="Contact number">
                    </div>
                    <div id="guardFields" class="d-none border rounded-3 p-3 bg-light">
                        <strong>Guard Details</strong>
                        <input class="form-control mt-2" name="guard_code" placeholder="Guard ID">
                        <input class="form-control mt-2" name="shift_name" placeholder="Shift">
                        <input class="form-control mt-2" name="contact_number_guard" placeholder="Contact number">
                    </div>
                    <div id="adminFields" class="d-none border rounded-3 p-3 bg-light">
                        <strong>Admin Details</strong>
                        <input class="form-control mt-2" name="admin_code" placeholder="Admin ID">
                    </div>
                    <button class="btn gh-primary rounded-pill">Create Account</button>
                </form>
            </div>
        </div>

        <div class="col-xl-7">
            <?php if (($accountType ?? 'all') !== 'staff'): ?>
            <div class="gh-card p-4 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div><h5 class="mb-1">Resident Accounts</h5><div class="small text-muted">Resident profiles and house numbers.</div></div>
                    <span class="badge text-bg-primary"><?= count($residents) ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table gh-table align-middle">
                        <thead><tr><th>Name</th><th>Email</th><th>House</th><th>Manage</th></tr></thead>
                        <tbody>
                        <?php foreach ($residents as $r): ?>
                            <tr>
                                <td><?= e($r['full_name']) ?></td>
                                <td><?= e($r['email']) ?></td>
                                <td><?= e($r['house_number']) ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <form method="post" class="d-flex gap-1" onsubmit="return confirm('Change this account password?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="change_password">
                                            <input type="hidden" name="user_id" value="<?= e($r['user_id']) ?>">
                                            <input class="form-control form-control-sm" type="password" name="new_password" minlength="6" placeholder="New password" required>
                                            <button class="btn btn-sm btn-outline-primary">Change</button>
                                        </form>
                                        <form method="post" onsubmit="return confirm('Remove this resident and their vehicles?')">
                                            <?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= e($r['user_id']) ?>">
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($residents)): ?><tr><td colspan="4" class="text-center text-muted py-4">No resident accounts match this filter.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (($accountType ?? 'all') !== 'resident'): ?>
            <div class="gh-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div><h5 class="mb-1">Staff Accounts</h5><div class="small text-muted">Guards and administrators are grouped together as staff.</div></div>
                    <span class="badge text-bg-dark"><?= count($guards) + count($admins) ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table gh-table align-middle">
                        <thead><tr><th>Name</th><th>Type</th><th>Email</th><th>Manage</th></tr></thead>
                        <tbody>
                        <?php foreach (array_merge($guards, $admins) as $u): ?>
                            <tr>
                                <td><?= e($u['full_name']) ?></td>
                                <td><span class="badge <?= $u['role'] === 'admin' ? 'text-bg-danger' : 'text-bg-warning' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                                <td><?= e($u['email']) ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <form method="post" class="d-flex gap-1" onsubmit="return confirm('Change this account password?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="change_password">
                                            <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                            <input class="form-control form-control-sm" type="password" name="new_password" minlength="6" placeholder="New password" required>
                                            <button class="btn btn-sm btn-outline-primary">Change</button>
                                        </form>
                                        <?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
                                            <form method="post" onsubmit="return confirm('Remove this account?')">
                                                <?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                                <button class="btn btn-sm btn-outline-danger">Remove</button>
                                            </form>
                                        <?php else: ?><span class="small text-muted align-self-center">Current account</span><?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($guards) && empty($admins)): ?><tr><td colspan="4" class="text-center text-muted py-4">No staff accounts match this filter.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const role = document.getElementById('accountRole');
    const boxes = {
        resident: document.getElementById('residentFields'),
        guard: document.getElementById('guardFields'),
        admin: document.getElementById('adminFields')
    };
    const block = document.getElementById('resident_block');
    const lot = document.getElementById('resident_lot');
    const letter = document.getElementById('resident_letter');
    const preview = document.getElementById('resident-house-preview');
    const update = () => {
        Object.entries(boxes).forEach(([key, box]) => box.classList.toggle('d-none', role.value !== key));
        const b = block.value.trim();
        const l = lot.value.trim();
        const x = letter.value.trim().toUpperCase();
        preview.textContent = b && l ? `${b}-${l}${x ? `-${x}` : ''}` : 'Block-Lot-Letter';
    };
    role.addEventListener('change', update);
    [block, lot, letter].forEach((field) => field.addEventListener('input', update));
    update();
});
</script>
<?php include app_path('views/layouts/footer.php'); ?>
