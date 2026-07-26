<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>
<div class="gh-landing-center">
    <div class="text-center">
        <div class="gh-buttons">
            <a class="btn gh-primary gh-big-btn" href="<?= e(url('login.php')) ?>">Login</a>
            <a class="btn gh-gold gh-big-btn" href="<?= e(url('visitor/register.php')) ?>">Visitor</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
