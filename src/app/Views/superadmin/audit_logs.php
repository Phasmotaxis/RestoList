<?php
$title = 'Global Audit Logs - SuperAdmin';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Global Security Audit Stream</h2>
            <p class="subtitle">System-wide immutable security and administrative event ledger</p>
        </div>
        <a href="/superadmin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Event ID</th>
                    <th>Action</th>
                    <th>Tenant</th>
                    <th>Entity</th>
                    <th>Details</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($logs ?? []) as $log): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($log['id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><code class="code-action"><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= htmlspecialchars($log['tenant_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($log['entity'] . ':' . ($log['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($log['details'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><code><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
