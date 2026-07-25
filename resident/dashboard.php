<?php

require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user = current_user();

if(!$user || $user['role'] != 'resident'){

    redirect('../login.php');

}

$pageTitle = "Resident Dashboard";

require_once __DIR__ . '/../includes/header.php';

/*=====================================
    RESIDENT VEHICLES
=====================================*/

$stmt = $pdo->prepare("

SELECT *

FROM vehicles

WHERE resident_id=?

ORDER BY id DESC

");

$stmt->execute([$user['id']]);

$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*=====================================
    VISITOR BOOKINGS
=====================================*/

$stmt = $pdo->prepare("

SELECT COUNT(*)

FROM visitor_bookings

WHERE resident_id=?

");

$stmt->execute([$user['id']]);

$totalVisitors = $stmt->fetchColumn();

/*=====================================
    RFID TAGS
=====================================*/

$stmt = $pdo->prepare("

SELECT COUNT(*)

FROM rfid_tags

LEFT JOIN vehicles

ON vehicles.id = rfid_tags.vehicle_id

WHERE vehicles.resident_id=?

");

$stmt->execute([$user['id']]);

$totalRFID = $stmt->fetchColumn();

?>

<div class="topbar">

<div>

<h1>

Resident Dashboard

</h1>

<p>

Welcome back,

<strong>

<?=e($user['fullname'])?>

</strong>

</p>

</div>

<div class="profile">

<img

src="https://ui-avatars.com/api/?name=<?=urlencode($user['fullname'])?>&background=2563eb&color=ffffff"

alt="Resident"

>

<div>

<h4>

<?=e($user['fullname'])?>

</h4>

<p>

Resident

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

Registered Vehicles

</div>

<div class="stat-number">

<?=count($vehicles)?>

</div>

<span class="badge badge-success">

Active

</span>

</div>

<div class="stat-card">

<div class="stat-title">

Visitor Bookings

</div>

<div class="stat-number">

<?=$totalVisitors?>

</div>

<span class="badge badge-primary">

Total

</span>

</div>

<div class="stat-card">

<div class="stat-title">

RFID Tags

</div>

<div class="stat-number">

<?=$totalRFID?>

</div>

<span class="badge badge-success">

Assigned

</span>

</div>

<div class="stat-card">

<div class="stat-title">

Account Status

</div>

<div class="stat-number">

<?=strtoupper($user['status'])?>

</div>

<span class="badge badge-success">

Verified

</span>

</div>

</div>

<!--=====================================
    QUICK ACTIONS
======================================-->

<div class="quick-actions mt-5">

<div class="action-card">

<i class="bi bi-person-vcard-fill"></i>

<h3>

Book Visitor

</h3>

<p>

Create a visitor booking before your guest arrives.

</p>

<a

href="../resident/book-visitor.php"

class="btn btn-primary mt-3">

Book Now

</a>

</div>

<div class="action-card">

<i class="bi bi-car-front-fill"></i>

<h3>

My Vehicles

</h3>

<p>

Manage your registered vehicles and plate numbers.

</p>

<a

href="../resident/vehicles.php"

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

View the RFID stickers assigned to your vehicles.

</p>

<a

href="../resident/rfid.php"

class="btn btn-primary mt-3">

Open

</a>

</div>

<div class="action-card">

<i class="bi bi-clock-history"></i>

<h3>

Entry History

</h3>

<p>

See every entry and exit recorded by the system.

</p>

<a

href="../resident/history.php"

class="btn btn-primary mt-3">

View

</a>

</div>

</div>
<!--=====================================
    MY REGISTERED VEHICLES
======================================-->

<div class="section mt-5">

<h2>

My Registered Vehicles

</h2>

<div class="vehicle-grid mt-4">

<?php if(count($vehicles)==0): ?>

<div class="card">

<h3>

No Vehicles Registered

</h3>

<p>

You currently have no registered vehicles.

</p>

<a href="vehicles.php" class="btn btn-primary mt-3">

Register Vehicle

</a>

</div>

<?php endif; ?>

<?php foreach($vehicles as $vehicle): ?>

<div class="vehicle-card">

<div class="vehicle-plate">

<?=e($vehicle['plate_number'])?>

</div>

<div class="vehicle-owner">

<?=e($vehicle['brand'])?>

<?=e($vehicle['model'])?>

</div>

<div class="vehicle-details">

<div>

Color

<strong>

<?=e($vehicle['color'])?>

</strong>

</div>

<div>

Year

<strong>

<?=e($vehicle['year'])?>

</strong>

</div>

<div>

Status

<span class="badge badge-success">

<?=ucfirst($vehicle['status'])?>

</span>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

<!--=====================================
    UPCOMING VISITOR BOOKINGS
======================================-->

<?php

$stmt = $pdo->prepare("

SELECT *

FROM visitor_bookings

WHERE resident_id=?

AND visit_date>=CURDATE()

ORDER BY visit_date ASC

LIMIT 5

");

$stmt->execute([$user['id']]);

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="section mt-5">

<h2>

Upcoming Visitors

</h2>

<div class="table-card mt-4">

<div class="table-responsive">

<table>

<thead>

<tr>

<th>Name</th>

<th>Date</th>

<th>Purpose</th>

<th>Status</th>

</tr>

</thead>

<tbody>

<?php if(count($bookings)==0): ?>

<tr>

<td colspan="4">

No upcoming visitors.

</td>

</tr>

<?php endif; ?>

<?php foreach($bookings as $booking): ?>

<tr>

<td>

<?=e($booking['visitor_name'])?>

</td>

<td>

<?=date('M d, Y',strtotime($booking['visit_date']))?>

</td>

<td>

<?=e($booking['purpose'])?>

</td>

<td>

<span class="badge badge-warning">

<?=ucfirst($booking['status'])?>

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
    RECENT GATE ACCESS
======================================-->

<?php

$stmt = $pdo->prepare("

SELECT

access_logs.*,

vehicles.plate_number

FROM access_logs

LEFT JOIN vehicles

ON vehicles.id=access_logs.vehicle_id

WHERE vehicles.resident_id=?

ORDER BY access_time DESC

LIMIT 8

");

$stmt->execute([$user['id']]);

$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="section mt-5">

<h2>

Recent Gate Activity

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

<th>Date & Time</th>

</tr>

</thead>

<tbody>

<?php if(count($history)==0): ?>

<tr>

<td colspan="5">

No gate activity found.

</td>

</tr>

<?php endif; ?>

<?php foreach($history as $log): ?>

<tr>

<td>

<?=e($log['plate_number'])?>

</td>

<td>

<?=e($log['gate_id'])?>

</td>

<td>

<?=ucfirst($log['direction'])?>

</td>

<td>

<?=strtoupper($log['verification'])?>

</td>

<td>

<?=date('M d, Y h:i A',strtotime($log['access_time']))?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>
<!--=====================================
    MY RFID TAGS
======================================-->

<?php

$stmt = $pdo->prepare("

SELECT

rfid_tags.*,
vehicles.plate_number

FROM rfid_tags

LEFT JOIN vehicles
ON vehicles.id = rfid_tags.vehicle_id

WHERE vehicles.resident_id=?

ORDER BY rfid_tags.id DESC

LIMIT 5

");

$stmt->execute([$user['id']]);

$rfids = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="section mt-5">

<h2>

My RFID Tags

</h2>

<div class="dashboard-grid mt-4">

<?php if(count($rfids)==0): ?>

<div class="card">

<h3>

No RFID Assigned

</h3>

<p>

Your account does not yet have an RFID tag assigned.

</p>

</div>

<?php endif; ?>

<?php foreach($rfids as $tag): ?>

<div class="card">

<h3>

<?=e($tag['plate_number'])?>

</h3>

<p>

RFID Number

</p>

<strong>

<?=e($tag['rfid_code'])?>

</strong>

<div class="mt-3">

<span class="badge badge-success">

<?=ucfirst($tag['status'])?>

</span>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

<!--=====================================
    PROFILE SUMMARY
======================================-->

<div class="section mt-5">

<h2>

My Profile

</h2>

<div class="card mt-4">

<div class="dashboard-grid">

<div>

<strong>

Full Name

</strong>

<p>

<?=e($user['fullname'])?>

</p>

</div>

<div>

<strong>

Username

</strong>

<p>

<?=e($user['username'])?>

</p>

</div>

<div>

<strong>

Role

</strong>

<p>

Resident

</p>

</div>

<div>

<strong>

Status

</strong>

<p>

<?=ucfirst($user['status'])?>

</p>

</div>

</div>

</div>

</div>

<!--=====================================
    NOTIFICATIONS
======================================-->

<div class="section mt-5">

<h2>

Notifications

</h2>

<div class="notification-card mt-4">

<ul>

<li>

🚗 Your vehicle registrations are active.

</li>

<li>

💳 RFID tags are ready for gate access.

</li>

<li>

👥 Remember to book visitors before they arrive.

</li>

<li>

🔒 Keep your account information up to date.

</li>

</ul>

</div>

</div>

<!--=====================================
    QUICK SHORTCUTS
======================================-->

<div class="section mt-5">

<h2>

Quick Shortcuts

</h2>

<div class="dashboard-grid mt-4">

<div class="action-card">

<i class="bi bi-plus-circle-fill"></i>

<h3>

New Visitor

</h3>

<a href="book-visitor.php" class="btn btn-primary mt-3">

Create Booking

</a>

</div>

<div class="action-card">

<i class="bi bi-car-front-fill"></i>

<h3>

Manage Vehicles

</h3>

<a href="vehicles.php" class="btn btn-primary mt-3">

Open

</a>

</div>

<div class="action-card">

<i class="bi bi-clock-history"></i>

<h3>

Access History

</h3>

<a href="history.php" class="btn btn-primary mt-3">

View Logs

</a>

</div>

<div class="action-card">

<i class="bi bi-person-circle"></i>

<h3>

My Profile

</h3>

<a href="profile.php" class="btn btn-primary mt-3">

View Profile

</a>

</div>

</div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>
