<?php
declare(strict_types=1);

define('APP_NAME', 'Smart Gate');
define('APP_ENV', 'local');

// Change these for your XAMPP/LAMP setup
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'smart_gate');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL if deployed inside htdocs as /smart_gate_starter
define('BASE_URL', '/smart_gate_starter');

// Local upload directory
define('UPLOAD_DIR', __DIR__ . '/../uploads');

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0775, true);
}
