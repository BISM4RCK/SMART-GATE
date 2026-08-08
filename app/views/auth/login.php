<?php
/* BISM4RCK-KUN3H0 2026 */
include app_path('views/layouts/header.php');
?>
<div class="row justify-content-center py-4">
    <div class="col-lg-5">
        <div class="gh-card p-4 p-lg-5">
            <h2 class="mb-4 text-center">Login</h2>
            <?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post" action="<?= e(url('login.php')) ?>" class="d-grid gap-3">
                <div><label>Email</label><input class="form-control" type="email" name="email" placeholder="resident@goldenhomes.local" required></div>
                <div><label>Password</label><input class="form-control" type="password" name="password" placeholder="Password123!" required></div>
                <button class="btn gh-primary rounded-pill py-3" type="submit">Sign in</button>
                <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('index.php')) ?>">Back</a>
            </form>
        </div>
    </div>
</div>
<?php
include app_path('views/layouts/footer.php');
