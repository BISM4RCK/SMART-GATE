<?php

/*=========================================
    SMART GATE CONFIG
==========================================*/

session_start();

date_default_timezone_set('Asia/Manila');

/*=========================================
    DATABASE
==========================================*/

define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_gate');
define('DB_USER', 'root');
define('DB_PASS', '');

/*=========================================
    WEBSITE
==========================================*/

define('APP_NAME', 'Smart Gate Management System');
define('BASE_URL', 'http://localhost/smart-gate');

/*=========================================
    PDO CONNECTION
==========================================*/

try{

    $pdo = new PDO(

        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",

        DB_USER,

        DB_PASS

    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

}catch(PDOException $e){

    die("Database Connection Failed : ".$e->getMessage());

}