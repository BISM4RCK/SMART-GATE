<?php
/* BISM4RCK/KUN3H0 2026 */
include app_path('views/layouts/header.php');
?>
<div class="container-fluid admin-dashboard">
  <div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="display-6 fw-semibold mb-0">Admin Control</h1><div class="text-muted">Subdivision management</div></div><span class="badge text-bg-light border px-3 py-2">Administrator</span></div>
  <div class="row g-3 admin-action-grid">
    <div class="col-md-4"><a class="admin-action admin-action-primary" href="<?=e(url('admin/vehicles.php'))?>"><i class="bi bi-car-front"></i><span>VEHICLES</span><small>Residents · guards · admins</small></a></div>
    <div class="col-md-4"><a class="admin-action" href="<?=e(url('admin/users.php'))?>"><i class="bi bi-people"></i><span>ACCOUNTS</span><small>Residents · guards · admins</small></a></div>
    <div class="col-md-4"><a class="admin-action" href="<?=e(url('admin/logs.php'))?>"><i class="bi bi-journal-text"></i><span>GATE LOGS</span><small>Entries and exits</small></a></div>
    <div class="col-md-4"><a class="admin-action" href="<?=e(url('admin/account_logs.php'))?>"><i class="bi bi-clock-history"></i><span>ACCOUNT LOGS</span><small>Login, logout and actions</small></a></div>
    <div class="col-md-4"><a class="admin-action" href="<?=e(url('admin/tickets.php'))?>"><i class="bi bi-chat-left-text"></i><span>TICKETS</span><small>Resident concerns</small></a></div>
    <div class="col-md-4"><button class="admin-action" data-bs-toggle="modal" data-bs-target="#overrideModal"><i class="bi bi-unlock"></i><span>MANUAL OVERRIDE</span><small>Open gate</small></button></div>
  </div>
  <div class="row g-2 compact-stats mt-2 mb-4">
    <div class="col-6 col-lg-3"><div class="gh-stat compact"><span>Residents</span><strong><?= (int)$stats['residents']?></strong></div></div>
    <div class="col-6 col-lg-3"><div class="gh-stat compact"><span>Requests</span><strong><?= (int)$stats['requests']?></strong></div></div>
    <div class="col-6 col-lg-3"><div class="gh-stat compact"><span>Tickets</span><strong><?= (int)$stats['tickets']?></strong></div></div>
    <div class="col-6 col-lg-3"><div class="gh-stat compact"><span>Logs</span><strong><?= (int)$stats['logs']?></strong></div></div>
  </div>
  <div class="row g-3"><div class="col-lg-7"><div class="gh-card p-4"><h3 class="h5 mb-3">Open tickets</h3><?php foreach($tickets as $t): ?><div class="border-bottom py-3"><div class="fw-semibold"><?=e($t['subject'])?></div><small><?=e($t['sender_name'])?> · House <?=e($t['house_number'])?></small><p class="mb-0 mt-1"><?=e($t['message'])?></p></div><?php endforeach; ?><?php if(!$tickets): ?><div class="text-muted">No open tickets.</div><?php endif; ?></div></div>
  <div class="col-lg-5"><div class="gh-card p-4"><h3 class="h5 mb-3">Shortcuts</h3><div class="d-grid gap-2"><a class="btn gh-btn-soft text-start" href="<?=e(url('notifications.php'))?>">Notifications</a><a class="btn gh-btn-soft text-start" href="<?=e(url('admin/tickets.php'))?>">Tickets</a><a class="btn gh-btn-soft text-start" href="<?=e(url('admin/settings.php'))?>">Settings</a></div></div></div></div>
  <div class="mt-4 d-flex justify-content-end"><a class="btn btn-danger btn-sm" href="<?=e(url('logout.php'))?>"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></div>
</div>
<div class="modal fade" id="overrideModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="post" action="<?=e(url('admin/manual_override.php'))?>"><?=csrf_field()?><div class="modal-header"><h5 class="modal-title">Manual Gate Override</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label>Plate Number *</label><input class="form-control form-control-lg" name="plate_number" required><div class="form-text">This action is logged under your admin account.</div></div><div class="modal-footer"><button class="btn btn-danger">OPEN GATE</button></div></form></div></div></div>
<?php include app_path('views/layouts/footer.php'); ?>
