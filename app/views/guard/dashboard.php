<?php
/* BISM4RCK/KUN3H0 2026 */
include app_path('views/layouts/header.php');
?>
<div class="container-fluid guard-dashboard">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div><h1 class="display-6 fw-semibold mb-0">Guard Control</h1><div class="text-muted">Gate operations</div></div>
    <span class="badge text-bg-light border px-3 py-2">Gate Guard</span>
  </div>

  <div class="row g-3 guard-action-grid">
    <div class="col-md-4"><a class="guard-action guard-action-primary guard-action-walkin" href="<?=e(url('guard/scan.php'))?>"><i class="bi bi-person-walking"></i><span>WALK IN VISITOR</span><small>Required visitor details</small></a></div>
    <div class="col-md-4"><a class="guard-action guard-action-logs" href="<?=e(url('guard/logs.php'))?>"><i class="bi bi-journal-text"></i><span>LOGS</span><small>Gate activity</small></a></div>
    <div class="col-md-4"><a class="guard-action guard-action-blacklist" href="<?=e(url('guard/blacklist.php'))?>"><i class="bi bi-slash-circle"></i><span>BLACKLIST</span><small>Block / remove access</small></a></div>
  </div>

  <div class="row g-2 compact-stats mb-4">
    <div class="col-6 col-lg-3"><div class="gh-stat compact"><span>Pending</span><strong><?= (int)$stats['pending']?></strong></div></div>
    <div class="col-6 col-lg-3"><div class="gh-stat compact"><span>Logs</span><strong><?= (int)$stats['logs']?></strong></div></div>
    <div class="col-6 col-lg-3"><div class="gh-stat compact"><span>Tickets</span><strong><?= (int)$stats['tickets']?></strong></div></div>
    <div class="col-6 col-lg-3"><div class="gh-stat compact"><span>Vehicles</span><strong><?= (int)$stats['vehicles']?></strong></div></div>
  </div>

  <div class="row g-3">
    <div class="col-xl-8"><div class="gh-card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3"><h3 class="h5 mb-0">Recent visitor requests</h3><a href="<?=e(url('guard/logs.php'))?>">View logs</a></div>
      <div class="table-responsive"><table class="table gh-table align-middle mb-0"><thead><tr><th>House</th><th>Visitor</th><th>Plate</th><th>Status</th></tr></thead><tbody>
      <?php foreach($requests as $r): ?><tr><td><?=e($r['house_number'])?></td><td><?=e(mb_convert_case($r['visitor_name'],MB_CASE_TITLE,'UTF-8'))?></td><td><?=e($r['plate_number'])?></td><td><span class="badge rounded-pill <?=e(gate_badge($r['status']))?>"><?=e(ucfirst($r['status']))?></span></td></tr><?php endforeach; ?>
      <?php if(!$requests): ?><tr><td colspan="4" class="text-muted text-center py-4">No requests.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>
    <div class="col-xl-4"><div class="gh-card p-4 h-100"><h3 class="h5 mb-3">Recent gate activity</h3>
      <?php foreach($logs as $l): ?><div class="border-bottom py-2"><div class="fw-semibold"><?=e($l['plate_number'] ?: 'No plate')?> <span class="badge <?=e(gate_badge($l['gate_status']))?>"><?=e($l['gate_status'])?></span></div><small><?=e($l['created_at'])?> · <?=e($l['actor_name'] ?? 'ESP32')?></small></div><?php endforeach; ?>
    </div></div>
  </div>
  <div class="mt-4 d-flex justify-content-end"><a class="btn btn-danger btn-sm" href="<?=e(url('logout.php'))?>"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></div>
</div>
<?php include app_path('views/layouts/footer.php'); ?>
