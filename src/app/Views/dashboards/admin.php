<?php
$title = 'Tenant Administration - EIMS';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <h2>Tenant Administration: <?= htmlspecialchars($tenant['name'] ?? 'Organization', ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="subtitle">Subdomain: <code><?= htmlspecialchars($tenant['subdomain'] ?? 'default', ENT_QUOTES, 'UTF-8') ?></code></p>
</section>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="margin-bottom: 0;">
        <h3>Scoped Users</h3>
        <p style="font-size: 2rem; font-weight: bold; color: var(--color-primary);"><?= (int) ($userCount ?? 0) ?></p>
        <p class="subtitle">Users within this organization</p>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <h3>Organization Status</h3>
        <p style="font-size: 1.5rem; font-weight: bold; color: #16a34a;"><?= !empty($tenant['is_active']) ? 'ACTIVE' : 'SUSPENDED' ?></p>
        <p class="subtitle">Tenant operational status</p>
    </div>
</div>

<section class="card">
    <h3>Administration Actions</h3>
    <ul style="list-style-type: none; padding-left: 0; display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
        <li><a href="/admin/users" style="color: var(--color-primary); font-weight: 500;">Manage Tenant Users &rarr;</a></li>
        <li><form method="POST" action="/logout" style="display: inline;"><button type="submit" style="background: none; border: none; color: var(--color-danger, #dc2626); cursor: pointer; font-weight: 500; font-size: 1rem; padding: 0;">Logout &rarr;</button></form></li>
    </ul>
</section>

<section class="card">
    <h3>Recent Tenant Audit Activity</h3>
    <?php if (empty($recentLogs)): ?>
        <p class="subtitle">No recent audit logs for this organization.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; margin-top: 0.5rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--color-border);">
                        <th style="padding: 0.5rem;">Action</th>
                        <th style="padding: 0.5rem;">Entity</th>
                        <th style="padding: 0.5rem;">Details</th>
                        <th style="padding: 0.5rem;">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr style="border-bottom: 1px solid var(--color-border);">
                            <td style="padding: 0.5rem;"><code><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($log['entity'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($log['details'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
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
