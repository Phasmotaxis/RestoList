<?php
use App\Core\Session;

$title = 'Tenant Organizations - SuperAdmin';
require_once __DIR__ . '/../layouts/header.php';
$errors = $errors ?? [];
$old = $old ?? [];
$e = static fn (string $field): string => htmlspecialchars((string) ($errors[$field] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Tenant Organizations</h2>
            <p class="subtitle">Global multi-tenant provisioning and quota management</p>
        </div>
        <a href="/superadmin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Provision New Tenant Organization</h3>
    <form method="POST" action="/superadmin/organizations" class="form-grid-org" novalidate>
        <?= Session::csrfField() ?>
        <div>
            <label for="name" class="form-label">Organization Name</label>
            <input type="text" id="name" name="name" class="form-input" required maxlength="255" value="<?= htmlspecialchars((string) ($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. CvSU Silang Campus" aria-describedby="name_error">
            <?php if (isset($errors['name'])): ?><p id="name_error" class="field-error"><?= $e('name') ?></p><?php endif; ?>
        </div>
        <div>
            <label for="subdomain" class="form-label">Subdomain Slug</label>
            <input type="text" id="subdomain" name="subdomain" class="form-input" required maxlength="64" value="<?= htmlspecialchars((string) ($old['subdomain'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. cvsu-silang" aria-describedby="subdomain_error">
            <?php if (isset($errors['subdomain'])): ?><p id="subdomain_error" class="field-error"><?= $e('subdomain') ?></p><?php endif; ?>
        </div>
        <div>
            <button type="submit" class="btn-primary">Provision Tenant</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Existing Tenant Organizations</h3>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tenant ID</th>
                    <th>Organization Name</th>
                    <th>Subdomain</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($tenants ?? []) as $t): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td class="text-bold"><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><code><?= htmlspecialchars($t['subdomain'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><span class="<?= !empty($t['is_active']) ? 'badge-status-active' : 'badge-status-danger' ?>"><?= !empty($t['is_active']) ? 'Active' : 'Disabled' ?></span></td>
                        <td><?= htmlspecialchars($t['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a href="/superadmin/organizations/edit?id=<?= urlencode($t['id']) ?>" class="link-nav">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
