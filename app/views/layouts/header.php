<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
/* BISM4RCK/KUN3H0 2026 */
$user = current_user();
$unreadCount = $user ? unread_notifications_count((int)$user['id']) : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? APP_NAME) ?> | <?= e(APP_SHORT) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="<?= $user ? 'gh-app-body' : 'gh-landing-body' ?>">
<?php include app_path('views/partials/topbar.php'); ?>
<?php if ($user): ?>
<div class="gh-app-shell">
    <?php include app_path('views/partials/sidebar.php'); ?>
    <main class="gh-main">
        <div class="container-fluid px-3 px-lg-4 py-4">
<?php else: ?>
<main class="gh-landing-main">
    <div class="container-fluid px-3 px-lg-4 py-4">
<?php endif; ?>
<?php flash(); ?>
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
