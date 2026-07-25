<?php
$pageTitle = 'Guard Login';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-box">
  <div class="auth-card">
    <h1>Guard Login</h1>
    <p class="small">Local-hosted login for the guard portal.</p>

    <form method="post" action="<?= e(url('api/login.php')) ?>" class="grid" style="margin-top:18px">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="role" value="guard">
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Login</button>
            <a class="btn secondary" href="<?= e(url('index.php')) ?>">Back to home</a>
        </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
