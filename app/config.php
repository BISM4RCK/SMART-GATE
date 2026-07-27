<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'GOLDEN HOMES Subdivision');
define('APP_SHORT', 'GOLDEN HOMES');

$script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = rtrim($script, '/');
if ($basePath === '/' || $basePath === '.') {
    $basePath = '';
}
define('BASE_URL', $basePath);

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'smart_gate');
define('DB_USER', 'root');
define('DB_PASS', '');
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
