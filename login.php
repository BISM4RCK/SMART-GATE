<?php

require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    switch ($user['role']) {

    case 'admin':
        redirect('admin/dashboard.php');
        break;

    case 'guard':
        redirect('guard/dashboard.php');
        break;

    default:
        redirect('resident/dashboard.php');
        break;

}
}

$pageTitle = "Login";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $username = trim($_POST['username'] ?? "");
    $password = $_POST['password'] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        global $pdo;

        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->execute([$username]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {

            $error = "Invalid username or password.";

        } elseif ($user['status'] != 'active') {

            $error = "Your account has been disabled.";

        } elseif (!password_verify($password, $user['password'])) {

            $error = "Invalid username or password.";

        } else {

            $_SESSION['user_id'] = $user['id'];

            $_SESSION['role'] = $user['role'];

            redirect("dashboard.php");

        }

    }

}

require_once __DIR__.'/includes/header.php';

?>

<div class="login-page">

<div class="login-box">

<div class="login-logo">

<i class="bi bi-shield-lock-fill"></i>

</div>

<h2>

Smart Gate Login

</h2>

<p>

Sign in to continue

</p>

<?php if($error): ?>

<div class="badge badge-danger w-100 mb-3">

<?=e($error)?>

</div>

<?php endif; ?>

<form method="POST">

<div class="form-group">

<label>

Username

</label>

<div class="input-group">

<i class="bi bi-person-fill"></i>

<input

type="text"

name="username"

class="form-control"

placeholder="Enter Username"

required

>

</div>

</div>

<div class="form-group mt-3">

<label>

Password

</label>

<div class="input-group">

<i class="bi bi-lock-fill"></i>

<input

type="password"

name="password"

class="form-control"

placeholder="Enter Password"

required

>

</div>

</div>
<div class="mt-4">

<button

type="submit"

class="btn btn-primary w-100">

<i class="bi bi-box-arrow-in-right"></i>

Login

</button>

</div>

<div class="flex justify-between align-center mt-3">

<label class="flex align-center">

<input

type="checkbox"

name="remember"

style="margin-right:10px;">

Remember Me

</label>

<a href="#">

Forgot Password?

</a>

</div>

</form>

<hr class="mt-4 mb-4">

<h3 class="text-center">

Demo Accounts

</h3>

<p class="text-center mb-4">

Use these sample accounts after importing the database.

</p>

<div class="dashboard-grid">

<div class="card">

<h4>

Administrator

</h4>

<p>

Username

</p>

<strong>

admin

</strong>

<hr class="mt-2 mb-2">

<p>

Role

</p>

<span class="badge badge-primary">

Administrator

</span>

</div>

<div class="card">

<h4>

Resident

</h4>

<p>

Username

</p>

<strong>

juan

</strong>

<hr class="mt-2 mb-2">

<p>

Role

</p>

<span class="badge badge-success">

Resident

</span>

</div>

</div>

<div class="mt-4 text-center">

<p>

Don't have an account?

</p>

<a

href="#"

class="btn btn-outline mt-2">

Request Registration

</a>

</div>

</div>

<div class="hero-card">

<h2>

Smart Gate Management System

</h2>

<p class="mt-3">

A next-generation subdivision access control platform
designed to automate entry and exit using RFID,
AI-powered license plate recognition,
visitor booking,
and real-time monitoring.

</p>

<div class="quick-actions mt-4">

<div class="action-card">

<i class="bi bi-credit-card-2-front-fill"></i>

<h4>

RFID Authentication

</h4>

<p>

Fast resident access using RFID cards
and windshield stickers.

</p>

</div>

<div class="action-card">

<i class="bi bi-camera-video-fill"></i>

<h4>

AI Plate Recognition

</h4>

<p>

Automatic vehicle recognition
through IP cameras.

</p>

</div>

<div class="action-card">

<i class="bi bi-person-vcard-fill"></i>

<h4>

Visitor Booking

</h4>

<p>

Residents can pre-register
their visitors before arrival.

</p>

</div>

<div class="action-card">

<i class="bi bi-shield-lock-fill"></i>

<h4>

Secure Access

</h4>

<p>

Every gate transaction is logged
with timestamps,
photos,
and verification data.

</p>

</div>

</div>

</div>
<div class="mt-5 text-center">

<hr class="mb-4">

<p>

Demo Credentials

</p>

<div class="dashboard-grid mt-3">

<div class="card">

<h4>

Administrator

</h4>

<p>

Username

</p>

<strong>

admin

</strong>

<p class="mt-2">

Password

</p>

<strong>

admin123

</strong>

</div>

<div class="card">

<h4>

Resident

</h4>

<p>

Username

</p>

<strong>

juan

</strong>

<p class="mt-2">

Password

</p>

<strong>

juan123

</strong>

</div>

</div>

<p class="mt-4">

© <?=date('Y')?> Smart Gate Management System

</p>

<small>

RFID Access • AI Plate Recognition • Visitor Booking

</small>

</div>

</div>

</div>

<?php

require_once __DIR__.'/includes/footer.php';

?>
