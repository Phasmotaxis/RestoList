<?php
use App\Core\Session;

$title = 'Edit Tenant User - Admin';
require_once __DIR__ . '/../layouts/header.php';
$errors = $errors ?? [];
$e = static fn (string $field): string => htmlspecialchars((string) ($errors[$field] ?? ''), ENT_QUOTES, 'UTF-8');
$v = static fn (string $field): string => htmlspecialchars((string) ($user[$field] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Edit Tenant User</h2>
            <p class="subtitle">Email is fixed after provisioning. Changes are recorded in the audit log.</p>
        </div>
        <a href="/admin/users" class="link-nav">&larr; Back to Roster</a>
    </div>
</section>

<section class="card">
    <?php if (isset($errors['_form'])): ?>
        <div class="alert-error" role="alert"><?= $e('_form') ?></div>
    <?php endif; ?>
    <form method="POST" action="/admin/users/update" class="form-grid-admin" novalidate>
        <?= Session::csrfField() ?>
        <input type="hidden" name="id" value="<?= $v('id') ?>">
        <div>
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" id="first_name" name="first_name" class="form-input" required maxlength="128" value="<?= $v('first_name') ?>" aria-describedby="first_name_error">
            <?php if (isset($errors['first_name'])): ?><p id="first_name_error" class="field-error"><?= $e('first_name') ?></p><?php endif; ?>
        </div>
        <div>
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" id="last_name" name="last_name" class="form-input" required maxlength="128" value="<?= $v('last_name') ?>" aria-describedby="last_name_error">
            <?php if (isset($errors['last_name'])): ?><p id="last_name_error" class="field-error"><?= $e('last_name') ?></p><?php endif; ?>
        </div>
        <div>
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" class="form-input" value="<?= $v('email') ?>" disabled>
        </div>
        <div>
            <label for="role_mask" class="form-label">Role</label>
            <select id="role_mask" name="role_mask" class="form-input" aria-describedby="role_mask_error">
                <option value="4" <?= (int) ($user['role_mask'] ?? 4) === 4 ? 'selected' : '' ?>>User</option>
                <option value="2" <?= (int) ($user['role_mask'] ?? 0) === 2 ? 'selected' : '' ?>>Admin</option>
            </select>
            <?php if (isset($errors['role_mask'])): ?><p id="role_mask_error" class="field-error"><?= $e('role_mask') ?></p><?php endif; ?>
        </div>
        <div>
            <label for="password" class="form-label">New Password (leave blank to keep current password)</label>
            <input type="password" id="password" name="password" class="form-input" minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password_error">
            <?php if (isset($errors['password'])): ?><p id="password_error" class="field-error"><?= $e('password') ?></p><?php endif; ?>
        </div>
        <div>
            <button type="submit" class="btn-primary">Save Changes</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Remove User From Tenant</h3>
    <form method="POST" action="/admin/users/delete" novalidate>
        <?= Session::csrfField() ?>
        <input type="hidden" name="id" value="<?= $v('id') ?>">
        <div class="form-group">
            <input type="checkbox" id="confirm" name="confirm" value="1" aria-describedby="confirm_error">
            <label for="confirm" class="form-label">I understand this permanently deletes the account for <?= $v('email') ?>.</label>
            <?php if (isset($errors['confirm'])): ?><p id="confirm_error" class="field-error"><?= $e('confirm') ?></p><?php endif; ?>
        </div>
        <button type="submit" class="btn-danger-outline">Delete User</button>
    </form>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
