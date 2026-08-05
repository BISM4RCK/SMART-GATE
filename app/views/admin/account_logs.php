<?php
/* BISM4RCK/KUN3H0 2026 */
include app_path('views/layouts/header.php');
?>
<div class="container-fluid"><div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="display-6 fw-semibold mb-0">Account & Action Logs</h1><div class="text-muted">Only administrators can view these records.</div></div><a class="btn btn-outline-secondary rounded-pill" href="<?=e(url('admin/dashboard.php'))?>">Dashboard</a></div>
<div class="gh-card p-4"><div class="table-responsive"><table class="table gh-table align-middle"><thead><tr><th>Time</th><th>Account</th><th>Role</th><th>Action</th><th>Details</th><th>IP</th></tr></thead><tbody><?php foreach($logs as $l): ?><tr><td><?=e($l['created_at'])?></td><td><?=e($l['full_name'] ?? 'Deleted account')?></td><td class="text-capitalize"><?=e($l['role'] ?? '—')?></td><td><span class="badge <?=e(gate_badge($l['action']))?>"><?=e($l['action']==='login'?'TIME IN':($l['action']==='logout'?'TIME OUT':str_replace('_',' ',ucwords($l['action'],'_'))))?></span></td><td><?=e($l['details'])?></td><td><?=e($l['ip_address'])?></td></tr><?php endforeach;?><?php if(!$logs): ?><tr><td colspan="6" class="text-center text-muted py-4">No account activity yet.</td></tr><?php endif;?></tbody></table></div></div></div>
<?php include app_path('views/layouts/footer.php'); ?>
