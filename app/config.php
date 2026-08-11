<?php
/* BISM4RCK-KUN3H0 2026 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'GOLDEN HOMES Subdivision');
define('APP_SHORT', 'GOLDEN HOMES');

$documentRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__)));
$appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$basePath = '';
if ($documentRoot && $appRoot && str_starts_with($appRoot, $documentRoot)) {
    $basePath = rtrim(substr($appRoot, strlen($documentRoot)), '/');
}
if ($basePath === '/' ) $basePath='';

define('BASE_URL', $basePath);

// BISM4RCK-KUN3H0 2026
define('ESP32_API_KEY', getenv('SMART_GATE_ESP32_KEY') ?: '2jGpAbQGBVW9qJU89UQjDxNAjNMtj2q-JsuvL9dE8Ig');
define('ESP32_DEVICE_ID', getenv('SMART_GATE_ESP32_DEVICE_ID') ?: '180503');

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'smart_gate');
define('DB_USER', 'root');
define('DB_PASS', '');
