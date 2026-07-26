<?php
require_once __DIR__ . '/includes/functions.php';
logout_user();
session_regenerate_id(true);
flash_set('success', 'You have been logged out.');
redirect('index.php');
