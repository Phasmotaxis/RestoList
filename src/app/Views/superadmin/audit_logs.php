<?php
$title = 'Global Audit Logs - SuperAdmin';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2>Global Security Audit Stream</h2>
            <p class="subtitle">System-wide immutable security and administrative event ledger</p>
        </div>
        <a href="/superadmin/dashboard" style="color: var(--color-primary); font-weight: 500;">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--color-border);">
                    <th style="padding: 0.5rem;">Event ID</th>
                    <th style="padding: 0.5rem;">Action</th>
                    <th style="padding: 0.5rem;">Tenant</th>
                    <th style="padding: 0.5rem;">Entity</th>
                    <th style="padding: 0.5rem;">Details</th>
                    <th style="padding: 0.5rem;">IP Address</th>
                    <th style="padding: 0.5rem;">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($logs ?? []) as $log): ?>
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <td style="padding: 0.5rem;"><code><?= htmlspecialchars($log['id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td style="padding: 0.5rem;"><code style="font-weight: 600;"><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td style="padding: 0.5rem;"><?= htmlspecialchars($log['tenant_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 0.5rem;"><?= htmlspecialchars($log['entity'] . ':' . ($log['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 0.5rem;"><?= htmlspecialchars($log['details'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 0.5rem;"><code><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td style="padding: 0.5rem;"><?= htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
