<?php

require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user = current_user();

if(!$user || $user['role'] != 'guard'){

    redirect('../login.php');

}

$pageTitle = "Guard Dashboard";

require_once __DIR__ . '/../includes/header.php';

/*=====================================
    TODAY'S STATISTICS
======================================*/

$todayEntries = $pdo->query("

SELECT COUNT(*)

FROM access_logs

WHERE DATE(access_time)=CURDATE()

AND direction='entry'

")->fetchColumn();

$todayExits = $pdo->query("

SELECT COUNT(*)

FROM access_logs

WHERE DATE(access_time)=CURDATE()

AND direction='exit'

")->fetchColumn();

$pendingVisitors = $pdo->query("

SELECT COUNT(*)

FROM visitor_bookings

WHERE status='pending'

")->fetchColumn();

$activeRFID = $pdo->query("

SELECT COUNT(*)

FROM rfid_tags

WHERE status='active'

")->fetchColumn();

?>

<div class="topbar">

<div>

<h1>

Guard Dashboard

</h1>

<p>

Welcome,

<strong>

<?=e($user['fullname'])?>

</strong>

</p>

</div>

<div class="profile">

<img

src="https://ui-avatars.com/api/?name=<?=urlencode($user['fullname'])?>&background=16a34a&color=ffffff"

alt="Guard"

>

<div>

<h4>

<?=e($user['fullname'])?>

</h4>

<p>

Security Guard

</p>

</div>

</div>

</div>

<!--=====================================
    OVERVIEW
======================================-->

<div class="dashboard-grid">

<div class="stat-card">

<div class="stat-title">

Today's Entries

</div>

<div class="stat-number">

<?=$todayEntries?>

</div>

<span class="badge badge-success">

Entry

</span>

</div>

<div class="stat-card">

<div class="stat-title">

Today's Exits

</div>

<div class="stat-number">

<?=$todayExits?>

</div>

<span class="badge badge-primary">

Exit

</span>

</div>

<div class="stat-card">

<div class="stat-title">

Pending Visitors

</div>

<div class="stat-number">

<?=$pendingVisitors?>

</div>

<span class="badge badge-warning">

Waiting

</span>

</div>

<div class="stat-card">

<div class="stat-title">

Active RFID

</div>

<div class="stat-number">

<?=$activeRFID?>

</div>

<span class="badge badge-success">

Online

</span>

</div>

</div>

<!--=====================================
    QUICK ACTIONS
======================================-->

<div class="quick-actions mt-5">

<div class="action-card">

<i class="bi bi-door-open-fill"></i>

<h3>

Open Gate

</h3>

<p>

Manually trigger the gate barrier.

</p>

<a

href="#"

class="btn btn-success mt-3">

Open

</a>

</div>

<div class="action-card">

<i class="bi bi-camera-video-fill"></i>

<h3>

Live Cameras

</h3>

<p>

View entrance and exit CCTV cameras.

</p>

<a

href="cameras.php"

class="btn btn-primary mt-3">

View

</a>

</div>

<div class="action-card">

<i class="bi bi-person-vcard-fill"></i>

<h3>

Verify Visitor

</h3>

<p>

Approve or reject visitor arrivals.

</p>

<a

href="visitors.php"

class="btn btn-primary mt-3">

Open

</a>

</div>

<div class="action-card">

<i class="bi bi-search"></i>

<h3>

Search Vehicle

</h3>

<p>

Search plate numbers or RFID tags.

</p>

<a

href="search.php"

class="btn btn-primary mt-3">

Search

</a>

</div>

</div>

<!--=====================================
    LIVE GATE STATUS
======================================-->

<div class="section mt-5">

<h2>

Gate Status

</h2>

<div class="gate-panel mt-4">

<div class="gate-card">

<h3>

North Entrance

</h3>

<div class="gate-status">

OPEN

</div>

<p>

RFID Reader: Connected

</p>

<p>

Camera: Online

</p>

<span class="badge badge-success">

Operational

</span>

</div>

<div class="gate-card">

<h3>

South Exit

</h3>

<div class="gate-status">

OPEN

</div>

<p>

Plate Recognition: Active

</p>

<p>

Barrier Motor: Ready

</p>

<span class="badge badge-success">

Operational

</span>

</div>

</div>

</div>
<!--=====================================
    LIVE CAMERA MONITORING
======================================-->

<div class="section mt-5">

<h2>

Live Camera Monitoring

</h2>

<div class="camera-grid mt-4">

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

192.168.1.101

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

192.168.1.102

</small>

</div>

</div>

</div>

</div>

</div>

<!--=====================================
    RECENT RFID SCANS
======================================-->

<?php

$rfidLogs = $pdo->query("

SELECT

access_logs.*,
vehicles.plate_number

FROM access_logs

LEFT JOIN vehicles
ON vehicles.id = access_logs.vehicle_id

WHERE verification='rfid'

ORDER BY access_time DESC

LIMIT 10

")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="section mt-5">

<h2>

Recent RFID Scans

</h2>

<div class="table-card mt-4">

<div class="table-responsive">

<table>

<thead>

<tr>

<th>Plate</th>

<th>Gate</th>

<th>Direction</th>

<th>Time</th>

</tr>

</thead>

<tbody>

<?php if(count($rfidLogs)==0): ?>

<tr>

<td colspan="4">

No RFID scans recorded.

</td>

</tr>

<?php endif; ?>

<?php foreach($rfidLogs as $scan): ?>

<tr>

<td>

<?=e($scan['plate_number'])?>

</td>

<td>

<?=e($scan['gate_id'])?>

</td>

<td>

<?=ucfirst($scan['direction'])?>

</td>

<td>

<?=date('M d, Y h:i A',strtotime($scan['access_time']))?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

<!--=====================================
    PENDING VISITORS
======================================-->

<?php

$pending = $pdo->query("

SELECT

visitor_bookings.*,
users.fullname

FROM visitor_bookings

LEFT JOIN users
ON users.id = visitor_bookings.resident_id

WHERE visitor_bookings.status='pending'

ORDER BY visit_date ASC

LIMIT 10

")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="section mt-5">

<h2>

Pending Visitor Approvals

</h2>

<div class="table-card mt-4">

<div class="table-responsive">

<table>

<thead>

<tr>

<th>Visitor</th>

<th>Resident</th>

<th>Date</th>

<th>Purpose</th>

<th>Status</th>

</tr>

</thead>

<tbody>

<?php if(count($pending)==0): ?>

<tr>

<td colspan="5">

No pending visitor approvals.

</td>

</tr>

<?php endif; ?>

<?php foreach($pending as $visitor): ?>

<tr>

<td>

<?=e($visitor['visitor_name'])?>

</td>

<td>

<?=e($visitor['fullname'])?>

</td>

<td>

<?=date('M d, Y',strtotime($visitor['visit_date']))?>

</td>

<td>

<?=e($visitor['purpose'])?>

</td>

<td>

<span class="badge badge-warning">

<?=ucfirst($visitor['status'])?>

</span>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

<!--=====================================
    BLACKLIST ALERTS
======================================-->

<?php

$blacklisted = $pdo->query("

SELECT *

FROM blacklist

ORDER BY id DESC

LIMIT 5

")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="section mt-5">

<h2>

Blacklisted Vehicles

</h2>

<div class="table-card mt-4">

<div class="table-responsive">

<table>

<thead>

<tr>

<th>Plate Number</th>

<th>Reason</th>

<th>Date Added</th>

</tr>

</thead>

<tbody>

<?php if(count($blacklisted)==0): ?>

<tr>

<td colspan="3">

No blacklisted vehicles.

</td>

</tr>

<?php endif; ?>

<?php foreach($blacklisted as $vehicle): ?>

<tr>

<td>

<?=e($vehicle['plate_number'])?>

</td>

<td>

<?=e($vehicle['reason'])?>

</td>

<td>

<?=date('M d, Y',strtotime($vehicle['created_at']))?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>
<!--=====================================
    LIVE ENTRY / EXIT LOGS
======================================-->

<?php

$liveLogs = $pdo->query("

SELECT

access_logs.*,
vehicles.plate_number

FROM access_logs

LEFT JOIN vehicles
ON vehicles.id = access_logs.vehicle_id

ORDER BY access_time DESC

LIMIT 15

")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="section mt-5">

<h2>

Live Entry / Exit Logs

</h2>

<div class="table-card mt-4">

<div class="table-responsive">

<table>

<thead>

<tr>

<th>Plate</th>

<th>Gate</th>

<th>Direction</th>

<th>Verification</th>

<th>Time</th>

</tr>

</thead>

<tbody>

<?php if(empty($liveLogs)): ?>

<tr>

<td colspan="5">

No gate activity recorded.

</td>

</tr>

<?php endif; ?>

<?php foreach($liveLogs as $log): ?>

<tr>

<td><?=e($log['plate_number'])?></td>

<td><?=e($log['gate_id'])?></td>

<td><?=ucfirst($log['direction'])?></td>

<td><?=strtoupper($log['verification'])?></td>

<td><?=date('M d, Y h:i A',strtotime($log['access_time']))?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

<!--=====================================
    VEHICLE SEARCH
======================================-->

<div class="section mt-5">

<h2>

Vehicle Search

</h2>

<div class="card mt-4">

<form action="search.php" method="GET">

<div class="input-group">

<i class="bi bi-search"></i>

<input

type="text"

name="plate"

class="form-control"

placeholder="Search Plate Number or RFID..."

>

<button

class="btn btn-primary"

type="submit">

Search

</button>

</div>

</form>

</div>

</div>

<!--=====================================
    GATE CONTROL PANEL
======================================-->

<div class="section mt-5">

<h2>

Gate Control Panel

</h2>

<div class="dashboard-grid mt-4">

<div class="action-card">

<i class="bi bi-door-open-fill"></i>

<h3>

Open Entrance Gate

</h3>

<p>

Trigger the entrance barrier.

</p>

<button class="btn btn-success">

OPEN

</button>

</div>

<div class="action-card">

<i class="bi bi-door-closed-fill"></i>

<h3>

Close Entrance Gate

</h3>

<p>

Close the entrance barrier.

</p>

<button class="btn btn-danger">

CLOSE

</button>

</div>

<div class="action-card">

<i class="bi bi-arrow-bar-up"></i>

<h3>

Open Exit Gate

</h3>

<p>

Trigger the exit barrier.

</p>

<button class="btn btn-success">

OPEN

</button>

</div>

<div class="action-card">

<i class="bi bi-arrow-bar-down"></i>

<h3>

Close Exit Gate

</h3>

<p>

Close the exit barrier.

</p>

<button class="btn btn-danger">

CLOSE

</button>

</div>

</div>

</div>

<!--=====================================
    GUARD NOTIFICATIONS
======================================-->

<div class="section mt-5">

<h2>

System Notifications

</h2>

<div class="notification-card mt-4">

<ul>

<li>

🟢 Entrance gate is online.

</li>

<li>

🟢 Exit gate is online.

</li>

<li>

📷 AI Plate Recognition service is connected.

</li>

<li>

💳 RFID reader is ready for scanning.

</li>

<li>

🔒 All security systems are operating normally.

</li>

</ul>

</div>

</div>

<!--=====================================
    SHIFT SUMMARY
======================================-->

<div class="analytics-grid mt-5">

<div class="card">

<h3>

Vehicles Processed

</h3>

<div class="stat-number">

<?=$todayEntries + $todayExits?>

</div>

</div>

<div class="card">

<h3>

Pending Visitors

</h3>

<div class="stat-number">

<?=$pendingVisitors?>

</div>

</div>

<div class="card">

<h3>

Active RFID Tags

</h3>

<div class="stat-number">

<?=$activeRFID?>

</div>

</div>

<div class="card">

<h3>

Guard Status

</h3>

<div class="stat-number">

ON DUTY

</div>

</div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>