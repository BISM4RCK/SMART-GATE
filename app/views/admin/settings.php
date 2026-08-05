<?php
/* BISM4RCK/KUN3H0 2026 */
include app_path('views/layouts/header.php');
$buttonTargets = [
 ['title'=>'Landing · Login','prefix'=>'landing_login'],
 ['title'=>'Landing · Visitor','prefix'=>'landing_visitor'],
 ['title'=>'Guard · Walk In Visitor','prefix'=>'guard_walkin'],
 ['title'=>'Guard · Logs','prefix'=>'guard_logs'],
 ['title'=>'Guard · Blacklist','prefix'=>'guard_blacklist'],
 ['title'=>'Admin · Manual Override','prefix'=>'admin_override'],
];
?>
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div><h1 class="display-6 fw-semibold mb-0">Appearance</h1><div class="text-muted">Change individual button colors and sizes without editing CSS.</div></div>
    <a class="btn btn-outline-secondary rounded-pill" href="<?=e(url('admin/dashboard.php'))?>">Dashboard</a>
  </div>
  <form method="post">
    <?=csrf_field()?>
    <div class="gh-card p-4 mb-3">
      <h2 class="h5 mb-3">Dashboard background</h2>
      <div class="row g-3 align-items-end"><div class="col-md-4"><label>Background color</label><input class="form-control form-control-lg" type="color" name="dashboard_bg" value="<?=e($settings['dashboard_bg'])?>"></div></div>
    </div>
    <div class="row g-3">
    <?php foreach($buttonTargets as $t): $p=$t['prefix']; ?>
      <div class="col-lg-6"><div class="gh-card p-4 h-100">
        <h2 class="h5 mb-3"><?=e($t['title'])?></h2>
        <div class="row g-3">
          <div class="col-sm-6"><label>Button color</label><input class="form-control form-control-lg" type="color" name="<?=$p?>_bg" value="<?=e($settings[$p.'_bg'])?>"></div>
          <div class="col-sm-6"><label>Text color</label><input class="form-control form-control-lg" type="color" name="<?=$p?>_text" value="<?=e($settings[$p.'_text'])?>"></div>
          <div class="col-sm-6"><label>Width (px)</label><input class="form-control" type="number" min="80" max="600" name="<?=$p?>_width" value="<?=e($settings[$p.'_width'])?>"></div>
          <div class="col-sm-6"><label>Height (px)</label><input class="form-control" type="number" min="80" max="600" name="<?=$p?>_height" value="<?=e($settings[$p.'_height'])?>"></div>
        </div>
      </div></div>
    <?php endforeach; ?>
    </div>
    <div class="d-flex justify-content-end mt-4"><button class="btn gh-primary btn-lg px-4">Save appearance</button></div>
  </form>
</div>
<?php include app_path('views/layouts/footer.php'); ?>
