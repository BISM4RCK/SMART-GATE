<?php
require_once __DIR__ . '/functions.php';
$user = current_user();
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/navbar.php'; ?>
    <main class="app-main">
