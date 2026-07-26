<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) redirect('dashboard.php');

$pageTitle = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $account = login_with_credentials($email, $password);

    if (!$account) {
        $error = 'Invalid email or password.';
    } else {
        add_log('Login', $account['role'] . ' signed in.');
        flash_set('success', 'Welcome back, ' . $account['name'] . '.');
        redirect('dashboard.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6 col-xl-5">
        <div class="gh-card p-4 p-lg-5">
            <div class="text-center mb-4">
                <div class="display-4 mb-2"><i class="bi bi-box-arrow-in-right"></i></div>
                <h2 class="gh-section-title mb-1">Login</h2>
                <div class="gh-muted">Use your account email and password.</div>
            </div>

            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <form method="post" class="d-grid gap-3">
                <div>
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" class="form-control" placeholder="name@goldenhomes.local" required>
                </div>
                <div>
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" class="form-control" placeholder="Password123!" required>
                </div>
                <button class="btn gh-primary gh-action-btn" type="submit"><i class="bi bi-box-arrow-in-right me-1"></i>Sign in</button>
                <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('index.php')) ?>">Back to home</a>
            </form>

            <div class="mt-4 p-3 rounded-4 bg-light border small">
                <div class="fw-semibold mb-2">Demo accounts</div>
                <div>resident@goldenhomes.local</div>
                <div>guard@goldenhomes.local</div>
                <div>admin@goldenhomes.local</div>
                <div class="mt-2">Password: <strong>Password123!</strong></div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
