<?php
require_once __DIR__ . '/../includes/functions.php';
http_response_code(501);
echo json_encode(['message' => 'Starter endpoint. Add vehicle registration logic here.']);
