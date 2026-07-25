<?php
$pageTitle = 'Landing Page';
require_once __DIR__ . '/includes/header.php';
?>
<div class="content">
    <section class="hero center">
        <h1>Smart Visitor and Resident Management System</h1>
        <p>
            Choose your access path below. Residents log in to manage bookings and vehicles.
            Visitors submit a visit request and wait for resident approval.
        </p>
        <div class="actions" style="justify-content:center">
            <a class="btn" href="<?= e(url('resident/login.php')) ?>">Resident Login</a>
            <a class="btn secondary" href="<?= e(url('visitor/register.php')) ?>">Visitor Registration</a>
        </div>
    </section>

    <div class="grid cards">
        <div class="card">
            <div class="label">Resident Portal</div>
            <div class="value">Login</div>
            <p class="small">Approve visitors, register vehicles, and review access history.</p>
        </div>
        <div class="card">
            <div class="label">Visitor Portal</div>
            <div class="value">Request</div>
            <p class="small">Submit a visit request, upload ID, and wait for approval.</p>
        </div>
        <div class="card">
            <div class="label">Local Hosting</div>
            <div class="value">LAN</div>
            <p class="small">Runs on a local server and can be opened from phones on the same Wi-Fi.</p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
