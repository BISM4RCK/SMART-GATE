<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
$targets=[
'landing_login'=>'Landing Login','landing_visitor'=>'Landing Visitor','guard_walkin'=>'Guard Walk-In Visitor',
'guard_logs'=>'Guard Logs','guard_blacklist'=>'Guard Blacklist','admin_override'=>'Admin Gate Override',
'admin_users'=>'Admin Accounts','admin_vehicles'=>'Admin Vehicles','admin_logs'=>'Admin Logs','dashboard_background'=>'Dashboard Background'];
?>
<div class="container-fluid">
<div class="gh-card p-4">
<h2 class="mb-1">Interface Customization</h2><p class="text-muted">Choose one specific button or the dashboard background, then set its color and size.</p>
<form method="post" class="row g-3">
<?= csrf_field() ?>
<div class="col-md-4"><label>Target</label><select class="form-select" name="setting_key" required><?php foreach($targets as $k=>$label): ?><option value="<?=e($k)?>"><?=e($label)?></option><?php endforeach;?></select></div>
<div class="col-md-2"><label>Background</label><input class="form-control form-control-color w-100" type="color" name="bg_color" value="#10263f"></div>
<div class="col-md-2"><label>Text</label><input class="form-control form-control-color w-100" type="color" name="text_color" value="#ffffff"></div>
<div class="col-md-2"><label>Width (px)</label><input class="form-control" type="number" name="width" min="80" max="800" value="220"></div>
<div class="col-md-2"><label>Height (px)</label><input class="form-control" type="number" name="height" min="40" max="600" value="180"></div>
<div class="col-md-2"><label>Radius (px)</label><input class="form-control" type="number" name="radius" min="0" max="80" value="24"></div>
<div class="col-12"><button class="btn gh-primary rounded-pill">Save Customization</button><a class="btn btn-outline-secondary rounded-pill ms-2" href="<?=e(url('admin/dashboard.php'))?>">Back</a></div>
</form>
<hr>
<h5>Current customizations</h5>
<div class="table-responsive"><table class="table"><thead><tr><th>Target</th><th>Background</th><th>Text</th><th>Size</th><th>Radius</th></tr></thead><tbody>
<?php foreach($settings as $s): ?><tr><td><?=e($targets[$s['setting_key']]??$s['setting_key'])?></td><td><?=e($s['bg_color'])?></td><td><?=e($s['text_color'])?></td><td><?=e($s['width'].' × '.$s['height'])?></td><td><?=e($s['radius'])?></td></tr><?php endforeach;?>
</tbody></table></div>
</div></div>
<?php include app_path('views/layouts/footer.php'); ?>
<!-- BISM4RCK/KUN3H0 2026 -->
/* BISM4RCK-KUN3H0 2026 */
