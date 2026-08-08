<?php
/* BISM4RCK-KUN3H0 2026 */
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1">Account Management</h2>
            <div class="text-muted">Manage resident and staff accounts separately.</div>
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
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage account
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><h6 class="dropdown-header"><?= e($r['full_name']) ?></h6></li>
                                            <li><button class="dropdown-item" type="button" data-account-action="password" data-user-id="<?= e($r['user_id']) ?>" data-user-name="<?= e($r['full_name']) ?>">Change password</button></li>
                                            <li><button class="dropdown-item" type="button" data-account-action="username" data-user-id="<?= e($r['user_id']) ?>" data-user-name="<?= e($r['full_name']) ?>" data-current-username="<?= e($r['email']) ?>">Change username</button></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="post" onsubmit="return confirm('Remove this resident and their vehicles?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= e($r['user_id']) ?>">
                                                    <button class="dropdown-item text-danger" type="submit">Delete account</button>
                                                </form>
                                            </li>
                                        </ul>
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
                    <div><h5 class="mb-1">Staff Accounts</h5><div class="small text-muted">Guards and administrators</div></div>
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
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Manage account
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><h6 class="dropdown-header"><?= e($u['full_name']) ?></h6></li>
                                            <li><button class="dropdown-item" type="button" data-account-action="password" data-user-id="<?= e($u['id']) ?>" data-user-name="<?= e($u['full_name']) ?>">Change password</button></li>
                                            <li><button class="dropdown-item" type="button" data-account-action="username" data-user-id="<?= e($u['id']) ?>" data-user-name="<?= e($u['full_name']) ?>" data-current-username="<?= e($u['email']) ?>">Change username</button></li>
                                            <?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="post" onsubmit="return confirm('Remove this account?')">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                                        <button class="dropdown-item text-danger" type="submit">Delete account</button>
                                                    </form>
                                                </li>
                                            <?php else: ?>
                                                <li><span class="dropdown-item-text small text-muted">Current account cannot be deleted</span></li>
                                            <?php endif; ?>
                                        </ul>
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

<div class="modal fade" id="accountPasswordModal" tabindex="-1" aria-labelledby="accountPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="user_id" id="passwordUserId">
                <div class="modal-header"><h5 class="modal-title" id="accountPasswordModalLabel">Change password</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p class="small text-muted">Set a new password for <strong id="passwordUserName"></strong>.</p>
                    <label class="form-label" for="newPassword">New password</label>
                    <input class="form-control" id="newPassword" type="password" name="new_password" minlength="6" autocomplete="new-password" required>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn gh-primary" type="submit">Save password</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="accountUsernameModal" tabindex="-1" aria-labelledby="accountUsernameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_username">
                <input type="hidden" name="user_id" id="usernameUserId">
                <div class="modal-header"><h5 class="modal-title" id="accountUsernameModalLabel">Change username</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p class="small text-muted">The username is the account's login email address.</p>
                    <label class="form-label" for="newUsername">New username / email</label>
                    <input class="form-control" id="newUsername" type="email" name="new_username" autocomplete="username" required>
                    <div class="form-text">This must be unique across all accounts.</div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn gh-primary" type="submit">Save username</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const passwordModalElement = document.getElementById('accountPasswordModal');
    const usernameModalElement = document.getElementById('accountUsernameModal');
    const passwordModal = passwordModalElement ? new bootstrap.Modal(passwordModalElement) : null;
    const usernameModal = usernameModalElement ? new bootstrap.Modal(usernameModalElement) : null;

    document.querySelectorAll('[data-account-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.dataset.accountAction;
            const userId = button.dataset.userId || '';
            const userName = button.dataset.userName || 'this account';
            if (action === 'password' && passwordModal) {
                document.getElementById('passwordUserId').value = userId;
                document.getElementById('passwordUserName').textContent = userName;
                document.getElementById('newPassword').value = '';
                passwordModal.show();
            }
            if (action === 'username' && usernameModal) {
                document.getElementById('usernameUserId').value = userId;
                document.getElementById('newUsername').value = button.dataset.currentUsername || '';
                usernameModal.show();
            }
        });
    });

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
