<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_SHORT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="<?= $user ? 'gh-app-body' : 'gh-landing-body' ?>">
<?php include __DIR__ . '/navbar.php'; ?>

<?php if ($user): ?>
<div class="gh-app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="gh-main">
<?php else: ?>
<main class="gh-landing-main">
<?php endif; ?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <?php flash(); ?>
