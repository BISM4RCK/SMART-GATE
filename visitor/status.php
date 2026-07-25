<?php
$pageTitle = 'Visitor Status';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="content">
    <h1>Visitor Status</h1>
    <p class="small">This page can later be used to check approval using a booking code or QR reference.</p>

    <div class="card">
        <form method="get" class="form-grid">
            <div class="field">
                <label for="booking_code">Booking Code</label>
                <input id="booking_code" name="booking_code" placeholder="BK-2026-00015">
            </div>
            <div class="field" style="align-self:end">
                <button class="btn" type="submit">Check Status</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
