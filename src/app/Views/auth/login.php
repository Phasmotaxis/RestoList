<?php
$title = 'Login - EIMS';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card" style="max-width: 480px; margin: 2rem auto;">
    <h2>Authentication</h2>
    <form method="POST" action="/login">
        <div style="margin-bottom: 1rem;">
            <label for="email" style="display: block; margin-bottom: 0.5rem;">Email Address</label>
            <input type="email" id="email" name="email" required style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
        </div>
        <div style="margin-bottom: 1.5rem;">
            <label for="password" style="display: block; margin-bottom: 0.5rem;">Password</label>
            <input type="password" id="password" name="password" required style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
        </div>
        <button type="submit" style="background: var(--color-primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-sm); cursor: pointer;">Sign In</button>
    </form>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
