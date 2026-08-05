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
            <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0">Vehicles</label>
        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="addVehicle">+ Add another vehicle</button>
    </div>
    <div id="vehicleList">
        <div class="vehicle-row row g-2 mb-2">
            <div class="col-md-4">
                <input class="form-control" name="plate_number[]" placeholder="Plate number" required>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="vehicle_type[]" required>
                    <option value="">Vehicle type</option>
                    <option value="car">Car</option>
                    <option value="motorcycle">Motorcycle</option>
                    <option value="truck">Truck</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="col-md-4">
                <input class="form-control" type="number" name="people_count[]" min="1" step="1"
                       placeholder="People in this vehicle" required>
            </div>
            <div class="col-md-1 d-flex align-items-center">
                <button type="button" class="btn btn-outline-danger w-100 removeVehicle" title="Remove vehicle" disabled>×</button>
            </div>
        </div>
    </div>
    <div class="form-text">Each vehicle must have its own passenger count. Use <strong>+ Add another vehicle</strong> for additional vehicles.</div>
</div>

            <div class="col-md-4"><label>Purpose</label><input class="form-control" name="purpose" required></label><input class="form-control" name="purpose" required></div>
            <div class="col-12">
                <label>Government ID</label>
                <div class="gh-file">
                    <input class="form-control" type="file" name="government_id" accept="image/*,.pdf" data-preview-file>
<div class="form-check mt-2">
  <input class="form-check-input" type="checkbox" id="no_id" name="no_id" value="1">
  <label class="form-check-label" for="no_id">
    I cannot provide a government ID
  </label>
</div>

                    <div class="small mt-2">Selected file: <span data-file-name>No file selected</span></div>
                </div>
            </div>
            <div class="col-12"><button class="btn gh-gold rounded-pill py-3" type="submit">Submit Request</button></div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('vehicleList');
    const add = document.getElementById('addVehicle');

    function refreshRemoveButtons() {
        const rows = list.querySelectorAll('.vehicle-row');
        rows.forEach(row => {
            row.querySelector('.removeVehicle').disabled = rows.length === 1;
        });
    }

    add.addEventListener('click', function () {
        const row = list.querySelector('.vehicle-row').cloneNode(true);
        row.querySelector('input').value = '';
        row.querySelector('select').selectedIndex = 0;
        row.querySelector('input[name="people_count[]"]').value = '';
        list.appendChild(row);
        refreshRemoveButtons();
    });

    list.addEventListener('click', function (event) {
        const button = event.target.closest('.removeVehicle');
        if (!button) return;
        const rows = list.querySelectorAll('.vehicle-row');
        if (rows.length > 1) {
            button.closest('.vehicle-row').remove();
            refreshRemoveButtons();
        }
    });

    refreshRemoveButtons();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('no_id');
    if (!checkbox) return;

    const selectors = [
        'input[name="government_id"]',
        'input[name="government_id_file"]',
        'input[name="id_file"]',
        'input[name="visitor_id"]',
        'input[name="id_number"]'
    ];

    function toggleNoId() {
        selectors.forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (input) {
                input.required = !checkbox.checked;
                input.disabled = checkbox.checked;
                if (checkbox.checked) input.value = '';
            });
        });
    }

    checkbox.addEventListener('change', toggleNoId);
    toggleNoId();
});
</script>

<?php
include app_path('views/layouts/footer.php');
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026


