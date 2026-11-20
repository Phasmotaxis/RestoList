<?php
$title = 'Login - EIMS';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card card-auth">
    <h2>Authentication</h2>
    <?php if (!empty($error)): ?>
        <div class="alert-error" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <form method="POST" action="/login">
        <?= \App\Core\Session::csrfField() ?>
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" name="email" class="form-input" required autocomplete="email">
        </div>
        <div class="form-group-lg">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-input" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-primary">Sign In</button>
    </form>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
