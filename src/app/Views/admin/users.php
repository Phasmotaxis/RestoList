<?php
use App\Core\Session;

$title = 'Tenant Users - Admin';
require_once __DIR__ . '/../layouts/header.php';
$errors = $errors ?? [];
$old = $old ?? [];
$e = static fn (string $field): string => htmlspecialchars((string) ($errors[$field] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Tenant User Management</h2>
            <p class="subtitle">Scoped user provisioning and roster for your organization</p>
        </div>
        <a href="/admin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Provision User Within Tenant</h3>
    <?php if (isset($errors['_form'])): ?>
        <div class="alert-error" role="alert"><?= $e('_form') ?></div>
    <?php endif; ?>
    <form method="POST" action="/admin/users" class="form-grid-admin" novalidate>
        <?= Session::csrfField() ?>
        <div>
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" id="first_name" name="first_name" class="form-input" required maxlength="128" value="<?= htmlspecialchars((string) ($old['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Juan" aria-describedby="first_name_error">
            <?php if (isset($errors['first_name'])): ?><p id="first_name_error" class="field-error"><?= $e('first_name') ?></p><?php endif; ?>
        </div>
        <div>
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" id="last_name" name="last_name" class="form-input" required maxlength="128" value="<?= htmlspecialchars((string) ($old['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Dela Cruz" aria-describedby="last_name_error">
            <?php if (isset($errors['last_name'])): ?><p id="last_name_error" class="field-error"><?= $e('last_name') ?></p><?php endif; ?>
        </div>
        <div>
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" name="email" class="form-input" required maxlength="255" value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="user@cvsu.edu.ph" aria-describedby="email_error">
            <?php if (isset($errors['email'])): ?><p id="email_error" class="field-error"><?= $e('email') ?></p><?php endif; ?>
        </div>
        <div>
            <label for="password" class="form-label">Initial Password (12 characters minimum)</label>
            <input type="password" id="password" name="password" class="form-input" required minlength="12" autocomplete="new-password" aria-describedby="password_error">
            <?php if (isset($errors['password'])): ?><p id="password_error" class="field-error"><?= $e('password') ?></p><?php endif; ?>
        </div>
        <div>
            <label for="role_mask" class="form-label">Role</label>
            <select id="role_mask" name="role_mask" class="form-input" aria-describedby="role_mask_error">
                <option value="4" <?= (string) ($old['role_mask'] ?? '4') === '4' ? 'selected' : '' ?>>User</option>
                <option value="2" <?= (string) ($old['role_mask'] ?? '') === '2' ? 'selected' : '' ?>>Admin</option>
            </select>
            <?php if (isset($errors['role_mask'])): ?><p id="role_mask_error" class="field-error"><?= $e('role_mask') ?></p><?php endif; ?>
        </div>
        <div>
            <button type="submit" class="btn-primary">Provision User</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Tenant User Roster</h3>
    <form method="GET" action="/admin/users" class="form-grid-admin" role="search">
        <div>
            <label for="q" class="form-label">Search email or name</label>
            <input type="search" id="q" name="q" class="form-input" maxlength="100" value="<?= htmlspecialchars($list['q'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label for="role" class="form-label">Role</label>
            <select id="role" name="role" class="form-input">
                <option value="" <?= $list['role'] === '' ? 'selected' : '' ?>>All roles</option>
                <option value="admin" <?= $list['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="user" <?= $list['role'] === 'user' ? 'selected' : '' ?>>User</option>
            </select>
        </div>
        <div>
            <label for="sort" class="form-label">Sort by</label>
            <select id="sort" name="sort" class="form-input">
                <?php foreach ($sortLabels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $list['sort'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="dir" class="form-label">Direction</label>
            <select id="dir" name="dir" class="form-input">
                <option value="ASC" <?= $list['dir'] === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                <option value="DESC" <?= $list['dir'] === 'DESC' ? 'selected' : '' ?>>Descending</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn-secondary">Apply</button>
        </div>
    </form>

    <?php if (empty($users)): ?>
        <p class="subtitle">No users match the current search or filter.</p>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role Mask</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <?php
                    $mask = (int) ($u['role_mask'] ?? 4);
                    $roleLabel = ($mask & 2) ? 'Admin' : 'User';
                    ?>
                    <tr>
                        <td><code><?= htmlspecialchars($u['id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td class="text-bold"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge-role"><?= $roleLabel ?> (<?= $mask ?>)</span></td>
                        <td><?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a href="/admin/users/edit?id=<?= urlencode($u['id']) ?>" class="link-nav">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
