<header class="navbar">

<div class="container nav-container">

<div class="logo">

<div class="logo-icon">

<i class="bi bi-shield-lock-fill"></i>

</div>

<div>

<?=APP_NAME?>

</div>

</div>

<div class="nav-links">

<a href="<?=url()?>">

Home

</a>

<?php if($user): ?>

<span>

<?=e($user['fullname'])?>

</span>

<a href="<?=url('logout.php')?>">

Logout

</a>

<?php else: ?>

<a href="<?=url('login.php')?>">

Login

</a>

<?php endif; ?>

</div>

</div>

</header>