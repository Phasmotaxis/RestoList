<?php
$title = 'Global User Directory - SuperAdmin';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Global User Directory</h2>
            <p class="subtitle">Cross-tenant visibility across all organization administrators and users</p>
        </div>
        <a href="/superadmin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <form method="GET" action="/superadmin/users" class="form-grid-admin" role="search">
        <div>
            <label for="q" class="form-label">Search email or name</label>
            <input type="search" id="q" name="q" class="form-input" maxlength="100" value="<?= htmlspecialchars($list['q'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label for="role" class="form-label">Role</label>
            <select id="role" name="role" class="form-input">
                <option value="" <?= $list['role'] === '' ? 'selected' : '' ?>>All roles</option>
                <option value="superadmin" <?= $list['role'] === 'superadmin' ? 'selected' : '' ?>>SuperAdmin</option>
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
</section>

<section class="card">
    <?php if (empty($users)): ?>
        <p class="subtitle">No users match the current search or filter.</p>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Tenant Organization</th>
                    <th>Role Mask</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <?php
                    $mask = (int) ($u['role_mask'] ?? 4);
                    $roleLabel = ($mask & 1) ? 'SuperAdmin' : (($mask & 2) ? 'Admin' : 'User');
                    ?>
                    <tr>
                        <td><code><?= htmlspecialchars($u['id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td class="text-bold"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['tenant_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge-role"><?= $roleLabel ?> (<?= $mask ?>)</span></td>
                        <td><?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
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
