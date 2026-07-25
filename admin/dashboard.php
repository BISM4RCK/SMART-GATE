<?php

require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user = current_user();

if($user['role'] != 'admin'){

    redirect('../login.php');

}

$pageTitle = "Administrator Dashboard";

require_once __DIR__ . '/../includes/header.php';

/*==========================
    DATABASE COUNTS
==========================*/

$residents = $pdo->query("
SELECT COUNT(*)
FROM users
WHERE role='resident'
")->fetchColumn();

$guards = $pdo->query("
SELECT COUNT(*)
FROM users
WHERE role='guard'
")->fetchColumn();

$vehicles = $pdo->query("
SELECT COUNT(*)
FROM vehicles
")->fetchColumn();

$visitors = $pdo->query("
SELECT COUNT(*)
FROM visitor_bookings
")->fetchColumn();

?>

<div class="topbar">

<div>

<h1>

Administrator Dashboard

</h1>

<p>

Welcome back,

<strong>

<?=e($user['fullname'])?>

</strong>

</p>

</div>

<div class="top-actions">

<div class="profile">

<img
src="https://ui-avatars.com/api/?name=<?=urlencode($user['fullname'])?>&background=2563eb&color=fff"
alt="profile">

<div>

<h4>

<?=e($user['fullname'])?>

</h4>

<p>

Administrator

</p>

</div>

</div>

</div>

</div>

<!-- ======================
    STATISTICS
======================= -->

<div class="dashboard-grid">

<div class="stat-card">

<div class="stat-title">

Residents

</div>

<div class="stat-number">

<?=$residents?>

</div>

<span class="badge badge-success">

Registered

</span>

</div>

<div class="stat-card">

<div class="stat-title">

Guards

</div>

<div class="stat-number">

<?=$guards?>

</div>

<span class="badge badge-primary">

Employees

</span>

</div>

<div class="stat-card">

<div class="stat-title">

Vehicles

</div>

<div class="stat-number">

<?=$vehicles?>

</div>

<span class="badge badge-success">

Registered

</span>

</div>

<div class="stat-card">

<div class="stat-title">

Visitor Bookings

</div>

<div class="stat-number">

<?=$visitors?>

</div>

<span class="badge badge-warning">

Pending

</span>

</div>

</div>

<!-- ======================
    QUICK ACTIONS
======================= -->

<div class="quick-actions">

<div class="action-card">

<i class="bi bi-person-fill-add"></i>

<h3>

Manage Residents

</h3>

<p>

Register, edit and disable resident accounts.

</p>

<a
href="#"
class="btn btn-primary mt-3">

Open

</a>

</div>

<div class="action-card">

<i class="bi bi-car-front-fill"></i>

<h3>

Vehicle Database

</h3>

<p>

View every registered vehicle inside the community.

</p>

<a
href="#"
class="btn btn-primary mt-3">

Open

</a>

</div>

<div class="action-card">

<i class="bi bi-credit-card-2-front-fill"></i>

<h3>

RFID Tags

</h3>

<p>

Assign RFID stickers and cards to residents.

</p>

<a
href="#"
class="btn btn-primary mt-3">

Open

</a>

</div>

<div class="action-card">

<i class="bi bi-camera-video-fill"></i>

<h3>

Live Cameras

</h3>

<p>

Monitor every entrance and exit gate.

</p>

<a
href="#"
class="btn btn-primary mt-3">

Open

</a>

</div>

</div>
<!-- ======================
    GATE STATUS
======================= -->

<div class="section mt-5">

<h2 class="mb-3">

Gate Monitoring

</h2>

<div class="gate-panel">

<div class="gate-card">

<h3>

North Entrance

</h3>

<div class="gate-status">

OPEN

</div>

<span class="badge badge-success">

Online

</span>

<p class="mt-3">

RFID Reader Connected

</p>

</div>

<div class="gate-card">

<h3>

South Exit

</h3>

<div class="gate-status">

OPEN

</div>

<span class="badge badge-success">

Online

</span>

<p class="mt-3">

AI Plate Recognition Ready

</p>

</div>

</div>

</div>


<!-- ======================
    LIVE CAMERA STATUS
======================= -->

<div class="camera-grid mt-5">

<div class="camera-card">

<div class="camera-feed">

<i class="bi bi-camera-video-fill"></i>

</div>

<div class="camera-info">

<h3>

Entrance Camera

</h3>

<div class="camera-status">

<span class="badge badge-success">

LIVE

</span>

<small>

192.168.1.20

</small>

</div>

</div>

</div>

<div class="camera-card">

<div class="camera-feed">

<i class="bi bi-camera-video-fill"></i>

</div>

<div class="camera-info">

<h3>

Exit Camera

</h3>

<div class="camera-status">

<span class="badge badge-success">

LIVE

</span>

<small>

192.168.1.21

</small>

</div>

</div>

</div>

</div>

<!-- ======================
    RECENT ACCESS LOGS
======================= -->

<?php

$logs = $pdo->query("

SELECT
access_logs.*,
vehicles.plate_number

FROM access_logs

LEFT JOIN vehicles
ON vehicles.id = access_logs.vehicle_id

ORDER BY access_time DESC

LIMIT 10

")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="table-card mt-5">

<div class="table-header">

<h3>

Recent Access Logs

</h3>

</div>

<div class="table-responsive">

<table>

<thead>

<tr>

<th>Plate</th>

<th>Gate</th>

<th>Direction</th>

<th>Verification</th>

<th>Date</th>

</tr>

</thead>

<tbody>

<?php if(count($logs)==0): ?>

<tr>

<td colspan="5">

No access logs found.

</td>

</tr>

<?php endif; ?>

<?php foreach($logs as $log): ?>

<tr>

<td>

<?=e($log['plate_number'] ?? '-')?>

</td>

<td>

<?=$log['gate_id']?>

</td>

<td>

<?=ucfirst($log['direction'])?>

</td>

<td>

<?=strtoupper($log['verification'])?>

</td>

<td>

<?=$log['access_time']?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<!-- ======================
    LATEST VEHICLES
======================= -->

<?php

$latestVehicles = $pdo->query("

SELECT

vehicles.*,

users.fullname

FROM vehicles

LEFT JOIN users

ON users.id=vehicles.resident_id

ORDER BY vehicles.id DESC

LIMIT 6

")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="section mt-5">

<h2 class="mb-4">

Latest Registered Vehicles

</h2>

<div class="vehicle-grid">

<?php foreach($latestVehicles as $vehicle): ?>

<div class="vehicle-card">

<div class="vehicle-plate">

<?=e($vehicle['plate_number'])?>

</div>

<div class="vehicle-owner">

<?=e($vehicle['fullname'])?>

</div>

<div class="vehicle-details">

<div>

Brand :

<strong>

<?=e($vehicle['brand'])?>

</strong>

</div>

<div>

Model :

<strong>

<?=e($vehicle['model'])?>

</strong>

</div>

<div>

Color :

<strong>

<?=e($vehicle['color'])?>

</strong>

</div>

<div>

Status :

<span class="badge badge-success">

<?=e($vehicle['status'])?>

</span>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

</div>
<!-- ======================
    LATEST RESIDENTS
======================= -->

<?php

$latestResidents = $pdo->query("

SELECT *

FROM users

WHERE role='resident'

ORDER BY id DESC

LIMIT 5

")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="section mt-5">

<h2>

Latest Residents

</h2>

<div class="table-card">

<table>

<thead>

<tr>

<th>Name</th>

<th>Username</th>

<th>Block</th>

<th>Lot</th>

<th>Status</th>

</tr>

</thead>

<tbody>

<?php foreach($latestResidents as $resident): ?>

<tr>

<td><?=e($resident['fullname'])?></td>

<td><?=e($resident['username'])?></td>

<td><?=e($resident['house_block'])?></td>

<td><?=e($resident['house_lot'])?></td>

<td>

<span class="badge badge-success">

<?=ucfirst($resident['status'])?>

</span>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<!-- ======================
    ANALYTICS
======================= -->

<div class="analytics-grid mt-5">

<div class="card">

<h3>

Today's Entries

</h3>

<div class="stat-number">

154

</div>

<p>

Residents + Visitors

</p>

</div>

<div class="card">

<h3>

Today's Exits

</h3>

<div class="stat-number">

149

</div>

<p>

Vehicles Recorded

</p>

</div>

<div class="card">

<h3>

Pending Visitors

</h3>

<div class="stat-number">

8

</div>

<p>

Awaiting Approval

</p>

</div>

<div class="card">

<h3>

Blocked Attempts

</h3>

<div class="stat-number">

2

</div>

<p>

Security Events

</p>

</div>

</div>

<!-- ======================
    CHART PLACEHOLDER
======================= -->

<div class="card mt-5">

<h2>

Weekly Traffic Overview

</h2>

<div class="chart-placeholder">

<h3>

Traffic Chart

</h3>

<p>

Chart.js integration will be added here.

</p>

</div>

</div>

<!-- ======================
    NOTIFICATIONS
======================= -->

<div class="notification-card mt-5">

<h2>

System Notifications

</h2>

<ul>

<li>

🟢 North Gate RFID Reader Connected

</li>

<li>

🟢 AI Camera Online

</li>

<li>

🟡 Database Backup Scheduled Tonight

</li>

<li>

🔵 Software Version 1.0 Loaded

</li>

</ul>

</div>

<!-- ======================
    QUICK ADMIN ACTIONS
======================= -->

<div class="dashboard-grid mt-5">

<div class="action-card">

<i class="bi bi-person-plus-fill"></i>

<h3>

Add Resident

</h3>

<a href="#" class="btn btn-primary">

Open

</a>

</div>

<div class="action-card">

<i class="bi bi-credit-card-fill"></i>

<h3>

Assign RFID

</h3>

<a href="#" class="btn btn-primary">

Open

</a>

</div>

<div class="action-card">

<i class="bi bi-car-front-fill"></i>

<h3>

Register Vehicle

</h3>

<a href="#" class="btn btn-primary">

Open

</a>

</div>

<div class="action-card">

<i class="bi bi-file-earmark-bar-graph-fill"></i>

<h3>

Generate Report

</h3>

<a href="#" class="btn btn-primary">

Open

</a>

</div>

</div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>