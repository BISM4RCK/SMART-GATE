<?php
session_start();
date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'GOLDEN HOMES Subdivision');
define('APP_SHORT', 'GOLDEN HOMES');

/*
|--------------------------------------------------------------------------
| Automatically detect host
|--------------------------------------------------------------------------
*/

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ? "https://"
    : "http://";

$host = $_SERVER['HTTP_HOST'];

// If your project folder changes, only edit this.
$projectFolder = "/smart-gate";

define('BASE_URL', $protocol . $host . $projectFolder);

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'smart_gate');
define('DB_USER', 'root');
define('DB_PASS', '');