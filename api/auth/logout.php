<?php
/* BISM4RCK-KUN3H0 2026 */
require_once __DIR__ . '/_bootstrap.php';
Auth::logout();
json_response(['ok' => true, 'message' => 'Logged out']);
