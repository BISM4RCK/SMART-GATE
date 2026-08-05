<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="mb-0">Users & Vehicles</h2><a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('admin/dashboard.php')) ?>">Back</a></div>
  <div class="row g-3">
    <div class="col-xl-5">
      <div class="gh-card p-4 mb-3">
        <h5>Add account</h5>
        <form method="post" class="d-grid gap-2" action="<?= e(url('admin/users.php')) ?>">
          <?= csrf_field() ?><input type="hidden" name="action" value="create_user">
          <input class="form-control" name="full_name" placeholder="Full name" required>
          <input class="form-control" type="email" name="email" placeholder="Email" required>
          <input class="form-control" type="password" name="password" placeholder="Password (6+ chars)" minlength="6" required>
          <select class="form-select" name="role" id="accountRole" required>
            <option value="">Select account type...</option>
            <option value="resident">Resident</option>
            <option value="guard">Guard</option>
            <option value="admin">Admin</option>
          </select>

          <input class="form-control" name="contact_number" placeholder="Contact number">
          <div id="residentFields" class="role-fields d-none border rounded-3 p-3 bg-light">
            <div class="fw-semibold mb-2">Resident details</div>
            <input class="form-control mb-2" name="house_number" placeholder="House number">
            <input class="form-control mb-2" name="block_number" placeholder="Block number">
            <input class="form-control" name="lot_letter" placeholder="Lot letter (A, B, C...) if needed">
            <input class="form-control mt-2" name="contact_number" placeholder="Contact number">
          </div>

          <div id="guardFields" class="role-fields d-none border rounded-3 p-3 bg-light">
            <div class="fw-semibold mb-2">Guard details</div>
            <input class="form-control mb-2" name="guard_code" placeholder="Guard ID" disabled>
            <input class="form-control mb-2" name="shift_name" placeholder="Shift name" disabled>
          </div>

          <div id="adminFields" class="role-fields d-none border rounded-3 p-3 bg-light">
            <div class="fw-semibold mb-2">Admin details</div>
            <input class="form-control mb-2" name="admin_code" placeholder="Admin ID" disabled>
          </div>
          <button class="btn gh-primary rounded-pill">Create account</button>
        </form>
        <div class="small text-muted mt-2">Only admins can create or remove accounts. Your own admin account cannot be removed.</div>
      </div>
      <div class="gh-card p-4">
        <h5>Blacklist</h5>
        <form method="post" class="d-grid gap-2 mb-3" action="<?= e(url('admin/users.php')) ?>">
          <?= csrf_field() ?><input type="hidden" name="action" value="blacklist_add">
          <input class="form-control" name="plate_number" placeholder="Plate number">
          <input class="form-control" name="visitor_name" placeholder="Visitor name (optional)">
          <input class="form-control" name="reason" placeholder="Reason" required>
          <div class="row g-2"><div class="col"><input class="form-control" type="date" name="start_date"></div><div class="col"><input class="form-control" type="date" name="end_date"></div></div>
          <button class="btn btn-outline-danger rounded-pill">Add to blacklist</button>
        </form>
        <?php foreach ($blacklist as $b): ?>
          <div class="border rounded-3 p-2 mb-2 d-flex justify-content-between gap-2"><div><strong><?= e($b['plate_number'] ?: $b['visitor_name']) ?></strong><div class="small text-muted"><?= e($b['reason']) ?></div></div><form method="post" onsubmit="return confirm('Remove blacklist entry?')"><?= csrf_field() ?><input type="hidden" name="action" value="blacklist_remove"><input type="hidden" name="blacklist_id" value="<?= (int)$b['id'] ?>"><button class="btn btn-sm btn-outline-success">Remove</button></form></div>
        <?php endforeach; ?>
        <?php if (!$blacklist): ?><div class="text-muted small">No blacklist entries.</div><?php endif; ?>
      </div>
    </div>
    <div class="col-xl-7">
      <div class="gh-card p-4 mb-3">
        <h5>Residents</h5>
        <div class="table-responsive"><table class="table gh-table align-middle"><thead><tr><th>Name</th><th>House</th><th>Email</th><th></th></tr></thead><tbody>
        <?php foreach ($residents as $r): ?><tr><td><?= e($r['full_name']) ?></td><td><?= e($r['house_number']) ?></td><td><?= e($r['email']) ?></td><td><form method="post" onsubmit="return confirm('Remove this resident and their vehicles?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>"><button class="btn btn-sm btn-outline-danger">Remove</button></form></td></tr><?php endforeach; ?>
        </tbody></table></div>
      </div>
      <div class="gh-card p-4 mb-3"><h5>Guards & Admins</h5><div class="table-responsive"><table class="table gh-table align-middle"><thead><tr><th>Name</th><th>Role</th><th>Email</th><th></th></tr></thead><tbody>
        <?php foreach (array_merge($guards,$admins) as $u): ?><tr><td><?= e($u['full_name']) ?></td><td><?= e($u['role']) ?></td><td><?= e($u['email']) ?></td><td><?php if ((int)$u['id'] !== (int)current_user()['id']): ?><form method="post" onsubmit="return confirm('Remove this account?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><button class="btn btn-sm btn-outline-danger">Remove</button></form><?php else: ?><span class="small text-muted">Current account</span><?php endif; ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
      <div class="gh-card p-4"><h5>Resident vehicles</h5>
        <form method="post" class="row g-2 mb-3" action="<?= e(url('admin/users.php')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="add_vehicle"><div class="col-md-4"><select class="form-select" name="resident_id" required><option value="">Resident...</option><?php foreach($residents as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['house_number'].' — '.$r['full_name']) ?></option><?php endforeach; ?></select></div><div class="col-md-3"><input class="form-control" name="plate_number" placeholder="Plate" required></div><div class="col-md-3"><select class="form-select" name="vehicle_type" required><option value="">Type...</option><option>Car</option><option>Motorcycle</option><option>Truck</option><option>Other</option></select></div><div class="col-md-2"><input class="form-control" name="color" placeholder="Color"></div><div class="col-12"><button class="btn gh-primary rounded-pill">Add vehicle</button></div></form>
        <div class="table-responsive"><table class="table gh-table align-middle"><thead><tr><th>Plate</th><th>Type</th><th>Resident</th><th>House</th><th></th></tr></thead><tbody><?php foreach($vehicles as $v): ?><tr><td><?= e($v['plate_number']) ?></td><td><?= e($v['vehicle_type']) ?></td><td><?= e($v['full_name']) ?></td><td><?= e($v['house_number']) ?></td><td><form method="post" onsubmit="return confirm('Remove this vehicle?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_vehicle"><input type="hidden" name="vehicle_id" value="<?= (int)$v['id'] ?>"><button class="btn btn-sm btn-outline-danger">Remove</button></form></td></tr><?php endforeach; ?></tbody></table></div>
      </div>
    </div>
  </div>
</div>

<script>
function syncAccountFields() {
    const role = document.getElementById('accountRole');
    if (!role) return;

    document.querySelectorAll('.role-fields').forEach(function (section) {
        section.classList.add('d-none');
        section.querySelectorAll('input, select, textarea').forEach(function (field) {
            field.disabled = true;
            field.required = false;
        });
    });

    const selected = role.value;
    const section = document.getElementById(selected + 'Fields');
    if (!section) return;

    section.classList.remove('d-none');
    section.querySelectorAll('input, select, textarea').forEach(function (field) {
        field.disabled = false;
    });

    if (selected === 'resident') {
        const house = section.querySelector('[name="house_number"]');
        const block = section.querySelector('[name="block_number"]');
        if (house) house.required = true;
        if (block) block.required = true;
    }
    if (selected === 'guard') {
        const guardId = section.querySelector('[name="guard_code"]');
        if (guardId) guardId.required = true;
    }
    if (selected === 'admin') {
        const adminId = section.querySelector('[name="admin_code"]');
        if (adminId) adminId.required = true;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('accountRole');
    if (role) {
        role.addEventListener('change', syncAccountFields);
        syncAccountFields();
    }
});
</script>

<?php include app_path('views/layouts/footer.php'); /* BISM4RCK/KUN3H0 2026 */ ?>
<!-- BISM4RCK/KUN3H0 2026 -->
