<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Starter endpoint for approving visitor requests.']);
