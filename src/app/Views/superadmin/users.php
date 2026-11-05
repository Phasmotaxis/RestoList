<?php
$title = 'Global User Directory - SuperAdmin';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2>Global User Directory</h2>
            <p class="subtitle">Cross-tenant visibility across all organization administrators and users</p>
        </div>
        <a href="/superadmin/dashboard" style="color: var(--color-primary); font-weight: 500;">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--color-border);">
                    <th style="padding: 0.5rem;">User ID</th>
                    <th style="padding: 0.5rem;">Name</th>
                    <th style="padding: 0.5rem;">Email</th>
                    <th style="padding: 0.5rem;">Tenant Organization</th>
                    <th style="padding: 0.5rem;">Role Mask</th>
                    <th style="padding: 0.5rem;">Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($users ?? []) as $u): ?>
                    <?php
                    $mask = (int) ($u['role_mask'] ?? 4);
                    $roleLabel = ($mask & 1) ? 'SuperAdmin' : (($mask & 2) ? 'Admin' : 'User');
                    ?>
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <td style="padding: 0.5rem;"><code><?= htmlspecialchars($u['id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td style="padding: 0.5rem; font-weight: 500;"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 0.5rem;"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 0.5rem;"><?= htmlspecialchars($u['tenant_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 0.5rem;"><span style="background: rgba(37, 99, 235, 0.1); color: var(--color-primary); padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600;"><?= $roleLabel ?> (<?= $mask ?>)</span></td>
                        <td style="padding: 0.5rem;"><?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
