<?php
/* BISM4RCK-KUN3H0 2026 */
require_once __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();
redirect(dashboard_url(current_user()));
