<?php
require_once __DIR__ . '/../includes/functions.php';

$user = current_user();
if ($user) {
    audit_log((int)$user['id'], 'logout', 'authentication', 'User logged out.');
}
logout_user();
redirect('index.php');
