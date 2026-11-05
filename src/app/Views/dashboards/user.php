<?php
$title = 'User Workspace - EIMS';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <h2>Welcome, <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="subtitle">Organization: <strong><?= htmlspecialchars($user['tenant_name'] ?? 'Assigned Tenant', ENT_QUOTES, 'UTF-8') ?></strong> &bull; Email: <?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
</section>

<section class="card">
    <h3>Account & Privacy Operations (RA 10173)</h3>
    <p>Under the Philippine Data Privacy Act of 2012 (RA 10173), you have the right to data portability and visibility over your personal information records.</p>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
        <a href="/user/export" style="background: var(--color-primary); color: white; padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); text-decoration: none; font-weight: 500;">Export My Personal Data (CSV) &darr;</a>
        <form method="POST" action="/logout" style="display: inline;">
            <button type="submit" style="background: none; border: 1px solid var(--color-border); color: var(--color-danger, #dc2626); padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); cursor: pointer; font-weight: 500;">Sign Out</button>
        </form>
    </div>
</section>

<section class="card">
    <h3>Your Recent Activity Log</h3>
    <?php if (empty($activities)): ?>
        <p class="subtitle">No recent user activity recorded.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; margin-top: 0.5rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--color-border);">
                        <th style="padding: 0.5rem;">Action</th>
                        <th style="padding: 0.5rem;">Details</th>
                        <th style="padding: 0.5rem;">IP Address</th>
                        <th style="padding: 0.5rem;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $act): ?>
                        <tr style="border-bottom: 1px solid var(--color-border);">
                            <td style="padding: 0.5rem;"><code><?= htmlspecialchars($act['action'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($act['details'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($act['ip_address'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($act['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
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
