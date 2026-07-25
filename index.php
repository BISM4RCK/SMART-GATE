<?php
include 'includes/header.php';
?>

<?php

require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Smart Gate Management System";

require_once __DIR__ . '/includes/header.php';

?>

<!-- ==========================
     HERO SECTION
=========================== -->

<section class="hero">

    <div class="hero-text">

        <span class="badge badge-primary">
            Smart Community Security Platform
        </span>

        <h1>
            RFID Gate Access<br>
            AI Plate Recognition<br>
            Visitor Management
        </h1>

        <p>
            A complete gated community management platform with RFID,
            automatic license plate recognition, visitor pre-registration,
            guard monitoring, and administrator analytics.
        </p>

        <div class="hero-buttons">

            <a href="login.php" class="btn btn-primary">

                <i class="bi bi-box-arrow-in-right"></i>

                Resident Login

            </a>

            <a href="visitor/register.php" class="btn btn-outline">

                <i class="bi bi-person-plus"></i>

                Visitor Registration

            </a>

        </div>

    </div>

    <div class="hero-card">

        <h3>System Status</h3>

        <div class="dashboard-grid">

            <div class="stat-card">

                <div class="stat-title">

                    Gates

                </div>

                <div class="stat-number">

                    2

                </div>

                <span class="badge badge-success">

                    Online

                </span>

            </div>

            <div class="stat-card">

                <div class="stat-title">

                    Cameras

                </div>

                <div class="stat-number">

                    4

                </div>

                <span class="badge badge-success">

                    Active

                </span>

            </div>

            <div class="stat-card">

                <div class="stat-title">

                    RFID Readers

                </div>

                <div class="stat-number">

                    2

                </div>

                <span class="badge badge-success">

                    Connected

                </span>

            </div>

            <div class="stat-card">

                <div class="stat-title">

                    AI Detection

                </div>

                <div class="stat-number">

                    Ready

                </div>

                <span class="badge badge-success">

                    Running

                </span>

            </div>

        </div>

    </div>

</section>

<!-- ==========================
     FEATURES
=========================== -->

<section class="section">

    <div class="section-title">

        <h2>

            Everything Needed For A Modern Smart Gate

        </h2>

        <p>

            Built specifically for gated communities,
            subdivisions, villages,
            condominiums, and private estates.

        </p>

    </div>

    <div class="dashboard-grid">

        <div class="action-card">

            <i class="bi bi-credit-card-2-front-fill"></i>

            <h3>

                RFID Access

            </h3>

            <p>

                Fast contactless entry using RFID stickers
                and RFID cards.

            </p>

        </div>

        <div class="action-card">

            <i class="bi bi-car-front-fill"></i>

            <h3>

                Plate Recognition

            </h3>

            <p>

                AI automatically recognizes resident
                vehicle plates before opening the gate.

            </p>

        </div>

        <div class="action-card">

            <i class="bi bi-person-vcard-fill"></i>

            <h3>

                Visitor Booking

            </h3>

            <p>

                Residents approve visitors before
                arrival using the web portal.

            </p>

        </div>

        <div class="action-card">

            <i class="bi bi-camera-video-fill"></i>

            <h3>

                Live Cameras

            </h3>

            <p>

                Monitor every gate using integrated
                IP cameras.

            </p>

        </div>

        <div class="action-card">

            <i class="bi bi-speedometer2"></i>

            <h3>

                Guard Dashboard

            </h3>

            <p>

                Live access logs,
                manual override,
                QR verification,
                and alerts.

            </p>

        </div>

        <div class="action-card">

            <i class="bi bi-graph-up-arrow"></i>

            <h3>

                Analytics

            </h3>

            <p>

                Daily entries,
                resident statistics,
                visitor history,
                and reports.

            </p>

        </div>

    </div>

</section>
<!-- ==========================
     LIVE STATISTICS
=========================== -->

<section class="section">

    <div class="section-title">

        <h2>

            Community Overview

        </h2>

        <p>

            Real-time monitoring of your subdivision's access control system.

        </p>

    </div>

    <div class="dashboard-grid">

        <div class="stat-card">

            <div class="stat-title">

                Registered Residents

            </div>

            <div class="stat-number">

                1,284

            </div>

            <span class="stat-change">

                <i class="bi bi-arrow-up"></i>

                +18 This Month

            </span>

        </div>

        <div class="stat-card">

            <div class="stat-title">

                Registered Vehicles

            </div>

            <div class="stat-number">

                1,942

            </div>

            <span class="stat-change">

                <i class="bi bi-arrow-up"></i>

                +33 This Month

            </span>

        </div>

        <div class="stat-card">

            <div class="stat-title">

                Visitors Today

            </div>

            <div class="stat-number">

                67

            </div>

            <span class="badge badge-success">

                Normal Traffic

            </span>

        </div>

        <div class="stat-card">

            <div class="stat-title">

                Successful Entries

            </div>

            <div class="stat-number">

                99.8%

            </div>

            <span class="badge badge-success">

                Excellent

            </span>

        </div>

    </div>

</section>


<!-- ==========================
     HOW IT WORKS
=========================== -->

<section class="section">

    <div class="section-title">

        <h2>

            How The Smart Gate Works

        </h2>

        <p>

            A seamless process designed to maximize both security and convenience.

        </p>

    </div>

    <div class="dashboard-grid">

        <div class="card">

            <h3>

                1. Vehicle Approaches

            </h3>

            <p>

                Cameras and RFID readers continuously monitor the entrance lane.

            </p>

        </div>

        <div class="card">

            <h3>

                2. Vehicle Identified

            </h3>

            <p>

                RFID tags or AI plate recognition identify the resident automatically.

            </p>

        </div>

        <div class="card">

            <h3>

                3. Verification

            </h3>

            <p>

                The database verifies registration, visitor bookings,
                blacklists, and access permissions.

            </p>

        </div>

        <div class="card">

            <h3>

                4. Gate Opens

            </h3>

            <p>

                The barrier opens automatically while the system stores
                photos, timestamps, and access logs.

            </p>

        </div>

    </div>

</section>


<!-- ==========================
     WHY CHOOSE US
=========================== -->

<section class="section">

    <div class="section-title">

        <h2>

            Designed For Modern Communities

        </h2>

    </div>

    <div class="dashboard-grid">

        <div class="card">

            <i class="bi bi-shield-lock-fill"></i>

            <h3>

                Maximum Security

            </h3>

            <p>

                RFID authentication combined with AI license plate recognition
                significantly reduces unauthorized access.

            </p>

        </div>

        <div class="card">

            <i class="bi bi-lightning-charge-fill"></i>

            <h3>

                Faster Entry

            </h3>

            <p>

                Residents enter within seconds without stopping to register.

            </p>

        </div>

        <div class="card">

            <i class="bi bi-phone-fill"></i>

            <h3>

                Visitor Booking

            </h3>

            <p>

                Residents pre-register visitors before arrival,
                reducing waiting times at the gate.

            </p>

        </div>

        <div class="card">

            <i class="bi bi-cloud-check-fill"></i>

            <h3>

                Cloud Ready

            </h3>

            <p>

                Centralized logs, backups,
                analytics, and reporting from any device.

            </p>

        </div>

    </div>

</section>


<!-- ==========================
     CALL TO ACTION
=========================== -->

<section class="section">

    <div class="card text-center">

        <h2>

            Ready To Experience Smart Community Security?

        </h2>

        <p class="mt-2">

            Residents can log in to manage visitors,
            vehicles, RFID cards,
            and view their complete access history.

        </p>

        <div class="hero-buttons justify-center mt-4">

            <a href="login.php" class="btn btn-primary">

                <i class="bi bi-box-arrow-in-right"></i>

                Resident Login

            </a>

            <a href="#" class="btn btn-success">

                <i class="bi bi-person-plus-fill"></i>

                Register Visitor

            </a>

        </div>

    </div>

</section>
<!-- ==========================
     CONTACT SECTION
=========================== -->

<section class="section">

    <div class="section-title">

        <h2>

            System Modules

        </h2>

        <p>

            Everything included in the Smart Gate Management System.

        </p>

    </div>

    <div class="dashboard-grid">

        <div class="card">

            <h3>

                Resident Portal

            </h3>

            <ul>

                <li>✔ Vehicle Registration</li>
                <li>✔ RFID Management</li>
                <li>✔ Visitor Booking</li>
                <li>✔ Entry History</li>

            </ul>

        </div>

        <div class="card">

            <h3>

                Guard Portal

            </h3>

            <ul>

                <li>✔ Live Cameras</li>
                <li>✔ Manual Approval</li>
                <li>✔ QR Verification</li>
                <li>✔ Visitor Monitoring</li>

            </ul>

        </div>

        <div class="card">

            <h3>

                Administrator

            </h3>

            <ul>

                <li>✔ User Management</li>
                <li>✔ RFID Database</li>
                <li>✔ Analytics</li>
                <li>✔ Reports</li>

            </ul>

        </div>

    </div>

</section>

<!-- ==========================
     FOOTER
=========================== -->

<footer class="footer">

    <div class="container">

        <h3>

            Smart Gate Management System

        </h3>

        <p class="mt-2">

            RFID Access • AI Plate Recognition • Visitor Management

        </p>

        <div class="quick-actions mt-4">

            <div class="action-card">

                <h4>

                    RFID

                </h4>

                <p>

                    Automatic resident authentication using RFID cards and stickers.

                </p>

            </div>

            <div class="action-card">

                <h4>

                    License Plate Recognition

                </h4>

                <p>

                    AI-powered vehicle identification using CCTV cameras.

                </p>

            </div>

            <div class="action-card">

                <h4>

                    Visitor Booking

                </h4>

                <p>

                    Residents approve guests before they arrive.

                </p>

            </div>

        </div>

        <hr class="mt-5 mb-4">

        <p>

            © <?= date('Y'); ?>

            Smart Gate Management System

            <br>

            Bachelor of Science in Information Technology

            <br>

            Capstone Project

        </p>

    </div>

</footer>

<?php

require_once __DIR__ . '/includes/footer.php';

?>
