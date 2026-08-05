<?php
/* BISM4RCK/KUN3H0 2026 */
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="display-6 fw-semibold mb-0">Gate Entry</h1><div class="text-muted">Guard walk-in protocol</div></div><a class="btn btn-outline-secondary rounded-pill" href="<?=e(url('guard/dashboard.php'))?>">Dashboard</a></div>
  <div class="gh-card p-4 p-lg-5">
    <div class="walkin-hero mb-4"><i class="bi bi-person-walking"></i><div><h2>WALK IN VISITOR</h2><p class="mb-0">Every field is required before the gate-open command can be queued.</p></div></div>
    <form method="post" class="row g-3">
      <?=csrf_field()?><input type="hidden" name="action" value="walk_in">
      <div class="col-md-6"><label>Plate Number *</label><input class="form-control form-control-lg" name="plate_number" required></div>
      <div class="col-md-6"><label>House Number *</label><input class="form-control form-control-lg" name="visit_house_number" required></div>
      <div class="col-md-4"><label>People in Vehicle *</label><input class="form-control form-control-lg" type="number" min="1" name="visitor_count" required></div>
      <div class="col-md-4"><label>Driver Name *</label><input class="form-control form-control-lg" name="driver_name" required></div>
      <div class="col-md-4"><label>Visitor Name *</label><input class="form-control form-control-lg" name="visitor_name" required></div>
      <div class="col-12 d-flex gap-2 flex-wrap mt-3"><button class="btn btn-primary btn-lg rounded-4 px-5" type="submit"><i class="bi bi-unlock me-2"></i>OPEN GATE & RECORD</button><a class="btn btn-outline-danger btn-lg rounded-4" href="<?=e(url('guard/dashboard.php'))?>">Cancel</a></div>
    </form>
  </div>
  <div class="gh-card p-4 mt-3"><h3 class="h5">Manual override</h3><form method="post" class="row g-2 align-items-end"><?=csrf_field()?><input type="hidden" name="action" value="manual_override"><div class="col-md-6"><label>Plate Number *</label><input class="form-control" name="plate_number" required></div><div class="col-md-3"><button class="btn btn-warning w-100">OPEN GATE</button></div></form></div>
</div>
<?php include app_path('views/layouts/footer.php'); ?>
