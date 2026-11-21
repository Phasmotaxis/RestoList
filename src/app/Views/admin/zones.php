<?php
use App\Core\Session;

$title = $title ?? 'Dining Zones - Admin';
require_once __DIR__ . '/../layouts/header.php';
$errors = $errors ?? [];
$zones = $zones ?? [];
$orgId = $orgId ?? (string) Session::get('tenant_id');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Dining Zones Management</h2>
            <p class="subtitle">Configure restaurant floor zones and capacities</p>
        </div>
        <a href="/admin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Add New Dining Zone</h3>
    <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/zones" class="form-grid-admin" novalidate>
        <?= Session::csrfField() ?>
        <div>
            <label for="name" class="form-label">Zone Name</label>
            <input type="text" id="name" name="name" class="form-input" required maxlength="128" placeholder="e.g. Main Dining, Patio, Bar" aria-describedby="name_error">
        </div>
        <div>
            <label for="capacity" class="form-label">Target Capacity</label>
            <input type="number" id="capacity" name="capacity" class="form-input" min="0" value="0" placeholder="e.g. 50">
        </div>
        <div class="form-full">
            <button type="submit" class="btn-primary">Create Zone</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Existing Dining Zones</h3>
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th>Zone Name</th>
                    <th>Tables Count</th>
                    <th>Total Capacity</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($zones)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No dining zones registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($zones as $zone): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string)$zone['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= (int)($zone['table_count'] ?? 0) ?></td>
                            <td><?= (int)($zone['total_capacity'] ?? $zone['capacity']) ?> seats</td>
                            <td><?= htmlspecialchars(substr((string)$zone['created_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/zones/<?= htmlspecialchars((string)$zone['id'], ENT_QUOTES, 'UTF-8') ?>/delete" class="inline-form">
                                    <?= Session::csrfField() ?>
                                    <button type="submit" class="btn-danger-sm" onclick="return confirm('Archive this zone?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
