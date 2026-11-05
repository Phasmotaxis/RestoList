<?php
$title = 'Tenant Organizations - SuperAdmin';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2>Tenant Organizations</h2>
            <p class="subtitle">Global multi-tenant provisioning and quota management</p>
        </div>
        <a href="/superadmin/dashboard" style="color: var(--color-primary); font-weight: 500;">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Provision New Tenant Organization</h3>
    <form method="POST" action="/superadmin/organizations" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; align-items: end; margin-top: 1rem;">
        <div>
            <label for="name" style="display: block; margin-bottom: 0.25rem;">Organization Name</label>
            <input type="text" id="name" name="name" required placeholder="e.g. CvSU Silang Campus" style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
        </div>
        <div>
            <label for="subdomain" style="display: block; margin-bottom: 0.25rem;">Subdomain Slug</label>
            <input type="text" id="subdomain" name="subdomain" required placeholder="e.g. cvsu-silang" style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
        </div>
        <div>
            <button type="submit" style="background: var(--color-primary); color: white; border: none; padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); cursor: pointer; font-weight: 500;">Provision Tenant</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Existing Tenant Organizations</h3>
    <div style="overflow-x: auto; margin-top: 0.5rem;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--color-border);">
                    <th style="padding: 0.5rem;">Tenant ID</th>
                    <th style="padding: 0.5rem;">Organization Name</th>
                    <th style="padding: 0.5rem;">Subdomain</th>
                    <th style="padding: 0.5rem;">Status</th>
                    <th style="padding: 0.5rem;">Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($tenants ?? []) as $t): ?>
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <td style="padding: 0.5rem;"><code><?= htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td style="padding: 0.5rem; font-weight: 500;"><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 0.5rem;"><code><?= htmlspecialchars($t['subdomain'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td style="padding: 0.5rem;"><span style="color: <?= !empty($t['is_active']) ? '#16a34a' : '#dc2626' ?>; font-weight: bold;"><?= !empty($t['is_active']) ? 'Active' : 'Disabled' ?></span></td>
                        <td style="padding: 0.5rem;"><?= htmlspecialchars($t['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
