<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
include app_path('views/layouts/header.php');
?>
<div class="container-fluid">
    <div class="gh-card p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h2 class="mb-1">Visitor Request</h2>
                <div class="text-muted">House number only.</div>
            </div>
            <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('index.php')) ?>">Home</a>
        </div>
        <form method="post" action="<?= e(url('visitor/register.php')) ?>" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-4"><label>House Number</label><input class="form-control" name="house_number" placeholder="12-A" required></div>
            <div class="col-md-4"><label>Visitor Name</label><input class="form-control" name="visitor_name" required></div>
            <div class="col-md-4"><label>Contact Number</label><input class="form-control" name="contact_number"></div>
            <div class="col-md-4"><label>Plate Number</label><input class="form-control" name="plate_number" required></div>
            <div class="col-md-4">
                <label>Vehicle Type</label>
                <select class="form-select" name="vehicle_type" required>
                    <option value="">Choose...</option>
                    <option>Car</option><option>Motorcycle</option><option>Other</option>
                </select>
            </div>
            <div class="col-md-4"><label>Purpose</label><input class="form-control" name="purpose" required></div>
            <div class="col-12">
                <label>Government ID</label>
                <div class="gh-file">
                    <input class="form-control" type="file" name="government_id" accept="image/*,.pdf" data-preview-file>
                    <div class="small mt-2">Selected file: <span data-file-name>No file selected</span></div>
                </div>
            </div>
            <div class="col-12"><button class="btn gh-gold rounded-pill py-3" type="submit">Submit Request</button></div>
        </form>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
