<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Starter endpoint for RFID verification.']);
