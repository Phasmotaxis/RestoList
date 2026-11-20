<?php
use App\Core\Session;

$title = 'Edit Organization - SuperAdmin';
require_once __DIR__ . '/../layouts/header.php';
$errors = $errors ?? [];
$e = static fn (string $field): string => htmlspecialchars((string) ($errors[$field] ?? ''), ENT_QUOTES, 'UTF-8');
$v = static fn (string $field): string => htmlspecialchars((string) ($tenant[$field] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Edit Tenant Organization</h2>
            <p class="subtitle">Subdomain is fixed after provisioning. Changes are recorded in the audit log.</p>
        </div>
        <a href="/superadmin/organizations" class="link-nav">&larr; Back to Organizations</a>
    </div>
</section>

<section class="card">
    <form method="POST" action="/superadmin/organizations/update" class="form-grid-org" novalidate>
        <?= Session::csrfField() ?>
        <input type="hidden" name="id" value="<?= $v('id') ?>">
        <div>
            <label for="name" class="form-label">Organization Name</label>
            <input type="text" id="name" name="name" class="form-input" required maxlength="255" value="<?= $v('name') ?>" aria-describedby="name_error">
            <?php if (isset($errors['name'])): ?><p id="name_error" class="field-error"><?= $e('name') ?></p><?php endif; ?>
        </div>
        <div>
            <label for="subdomain" class="form-label">Subdomain Slug</label>
            <input type="text" id="subdomain" class="form-input" value="<?= $v('subdomain') ?>" disabled>
        </div>
        <div>
            <input type="checkbox" id="is_active" name="is_active" value="1" <?= !empty($tenant['is_active']) ? 'checked' : '' ?>>
            <label for="is_active" class="form-label">Organization is active</label>
        </div>
        <div>
            <button type="submit" class="btn-primary">Save Changes</button>
        </div>
    </form>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
