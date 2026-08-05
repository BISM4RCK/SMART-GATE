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
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<?php
$appearance = SiteSettingsModel::all();
$ap = fn($k,$d) => e($appearance[$k] ?? $d);
?>
<style>
:root{--gh-dashboard-bg:<?=$ap('dashboard_bg','#f5f6f8')?>;--gh-login-bg:<?=$ap('landing_login_bg','#10263f')?>;--gh-login-text:<?=$ap('landing_login_text','#ffffff')?>;--gh-login-w:<?=$ap('landing_login_width','240')?>px;--gh-login-h:<?=$ap('landing_login_height','240')?>px;--gh-visitor-bg:<?=$ap('landing_visitor_bg','#d9b45f')?>;--gh-visitor-text:<?=$ap('landing_visitor_text','#10263f')?>;--gh-visitor-w:<?=$ap('landing_visitor_width','240')?>px;--gh-visitor-h:<?=$ap('landing_visitor_height','240')?>px;--gh-walkin-bg:<?=$ap('guard_walkin_bg','#10263f')?>;--gh-walkin-text:<?=$ap('guard_walkin_text','#ffffff')?>;--gh-walkin-w:<?=$ap('guard_walkin_width','100')?>%;--gh-walkin-h:<?=$ap('guard_walkin_height','175')?>px;--gh-logs-bg:<?=$ap('guard_logs_bg','#ffffff')?>;--gh-logs-text:<?=$ap('guard_logs_text','#0f172a')?>;--gh-logs-w:<?=$ap('guard_logs_width','100')?>%;--gh-logs-h:<?=$ap('guard_logs_height','175')?>px;--gh-blacklist-bg:<?=$ap('guard_blacklist_bg','#ffffff')?>;--gh-blacklist-text:<?=$ap('guard_blacklist_text','#0f172a')?>;--gh-blacklist-w:<?=$ap('guard_blacklist_width','100')?>%;--gh-blacklist-h:<?=$ap('guard_blacklist_height','175')?>px;--gh-override-bg:<?=$ap('admin_override_bg','#10263f')?>;--gh-override-text:<?=$ap('admin_override_text','#ffffff')?>;--gh-override-w:<?=$ap('admin_override_width','100')?>%;--gh-override-h:<?=$ap('admin_override_height','175')?>px}
.gh-app-body{background:var(--gh-dashboard-bg)}
.gh-landing-card .gh-big-btn:nth-child(1){background:var(--gh-login-bg)!important;color:var(--gh-login-text)!important;width:var(--gh-login-w);height:var(--gh-login-h);min-width:var(--gh-login-w);min-height:var(--gh-login-h)}
.gh-landing-card .gh-big-btn:nth-child(2){background:var(--gh-visitor-bg)!important;color:var(--gh-visitor-text)!important;width:var(--gh-visitor-w);height:var(--gh-visitor-h);min-width:var(--gh-visitor-w);min-height:var(--gh-visitor-h)}
.guard-action-walkin{background:var(--gh-walkin-bg)!important;color:var(--gh-walkin-text)!important;width:var(--gh-walkin-w);min-height:var(--gh-walkin-h)}
.guard-action-logs{background:var(--gh-logs-bg)!important;color:var(--gh-logs-text)!important;width:var(--gh-logs-w);min-height:var(--gh-logs-h)}
.guard-action-blacklist{background:var(--gh-blacklist-bg)!important;color:var(--gh-blacklist-text)!important;width:var(--gh-blacklist-w);min-height:var(--gh-blacklist-h)}
.admin-action-override{background:var(--gh-override-bg)!important;color:var(--gh-override-text)!important;width:var(--gh-override-w);min-height:var(--gh-override-h)}
.guard-action-walkin:hover,.guard-action-logs:hover,.guard-action-blacklist:hover,.admin-action-override:hover{color:inherit}
</style>
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



<?php
/* BISM4RCK/KUN3H0 2026 */
?>
