<?php
$user = current_user();
$role = $user['role'] ?? '';
?>
<aside class="sidebar">
    <div class="sidebar-group">
        <a href="<?= e(url('index.php')) ?>">Landing Page</a>
        <?php if ($role === 'resident'): ?>
            <a href="<?= e(url('resident/dashboard.php')) ?>">Dashboard</a>
            <a href="<?= e(url('resident/vehicles.php')) ?>">Vehicles</a>
            <a href="<?= e(url('resident/book_visitor.php')) ?>">Book Visitor</a>
            <a href="<?= e(url('resident/bookings.php')) ?>">Bookings</a>
            <a href="<?= e(url('resident/notifications.php')) ?>">Notifications</a>
            <a href="<?= e(url('resident/profile.php')) ?>">Profile</a>
        <?php elseif ($role === 'guard'): ?>
            <a href="<?= e(url('guard/dashboard.php')) ?>">Dashboard</a>
            <a href="<?= e(url('guard/scan_qr.php')) ?>">Scan QR</a>
            <a href="<?= e(url('guard/walkin.php')) ?>">Walk-in</a>
            <a href="<?= e(url('guard/manual_gate.php')) ?>">Manual Gate</a>
            <a href="<?= e(url('guard/logs.php')) ?>">Logs</a>
        <?php elseif ($role === 'admin'): ?>
            <a href="<?= e(url('admin/dashboard.php')) ?>">Dashboard</a>
            <a href="<?= e(url('admin/users.php')) ?>">Users</a>
            <a href="<?= e(url('admin/residents.php')) ?>">Residents</a>
            <a href="<?= e(url('admin/vehicles.php')) ?>">Vehicles</a>
            <a href="<?= e(url('admin/rfid.php')) ?>">RFID</a>
            <a href="<?= e(url('admin/reports.php')) ?>">Reports</a>
            <a href="<?= e(url('admin/blacklist.php')) ?>">Blacklist</a>
            <a href="<?= e(url('admin/settings.php')) ?>">Settings</a>
        <?php endif; ?>
    </div>
</aside>
