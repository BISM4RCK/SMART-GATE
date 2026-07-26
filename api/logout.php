<?php
require_once __DIR__ . '/_bootstrap.php';

logout_user();
json_response(['ok' => true, 'message' => 'Logged out']);
