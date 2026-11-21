<?php
use App\Core\Session;

$title = $title ?? 'Dining Tables & Floor Plan - Admin';
require_once __DIR__ . '/../layouts/header.php';
$tables = $tables ?? [];
$zones = $zones ?? [];
$orgId = $orgId ?? (string) Session::get('tenant_id');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Dining Tables & Floor Plan</h2>
            <p class="subtitle">Table allocation, layout grid, and live occupancy status</p>
        </div>
        <a href="/admin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Live Floor Plan Grid</h3>
    <div class="floor-plan-legend">
        <span class="legend-item"><span class="legend-badge status-available"></span> Available</span>
        <span class="legend-item"><span class="legend-badge status-reserved"></span> Reserved</span>
        <span class="legend-item"><span class="legend-badge status-occupied"></span> Occupied</span>
    </div>
    <div class="floor-plan-grid">
        <?php foreach ($tables as $t):
            $colClass = 'gc-' . min(12, max(1, (int)($t['x_coord'] ?? 0) + 1));
            $rowClass = 'gr-' . min(8, max(1, (int)($t['y_coord'] ?? 0) + 1));
            $statusClass = $t['status_class'] ?? 'status-available';
        ?>
            <div class="floor-plan-table <?= $colClass ?> <?= $rowClass ?> <?= $statusClass ?>">
                <div class="table-label"><?= htmlspecialchars((string)$t['label'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="table-meta"><?= (int)$t['capacity'] ?>p <?= !empty($t['is_combinable']) ? '🔗' : '' ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="card">
    <h3>Add New Dining Table</h3>
    <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/tables" class="form-grid-admin" novalidate>
        <?= Session::csrfField() ?>
        <div>
            <label for="label" class="form-label">Table Label / Number</label>
            <input type="text" id="label" name="label" class="form-input" required maxlength="64" placeholder="e.g. T-1, Patio-4">
        </div>
        <div>
            <label for="capacity" class="form-label">Seat Capacity</label>
            <input type="number" id="capacity" name="capacity" class="form-input" required min="1" max="20" value="4">
        </div>
        <div>
            <label for="zone_id" class="form-label">Dining Zone</label>
            <select id="zone_id" name="zone_id" class="form-input">
                <option value="">-- Unassigned --</option>
                <?php foreach ($zones as $z): ?>
                    <option value="<?= htmlspecialchars((string)$z['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$z['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="is_combinable" class="form-label">Combinable Table?</label>
            <select id="is_combinable" name="is_combinable" class="form-input">
                <option value="0">Standalone</option>
                <option value="1">Combinable (Can merge for large parties)</option>
            </select>
        </div>
        <div>
            <label for="x_coord" class="form-label">Grid Column (0–11)</label>
            <input type="number" id="x_coord" name="x_coord" class="form-input" min="0" max="11" value="0">
        </div>
        <div>
            <label for="y_coord" class="form-label">Grid Row (0–7)</label>
            <input type="number" id="y_coord" name="y_coord" class="form-input" min="0" max="7" value="0">
        </div>
        <div class="form-full">
            <button type="submit" class="btn-primary">Add Table</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Table Roster</h3>
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th>Table Label</th>
                    <th>Zone</th>
                    <th>Capacity</th>
                    <th>Combinable</th>
                    <th>Grid Pos</th>
                    <th>Current Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tables)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No tables registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tables as $table): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string)$table['label'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars((string)($table['zone_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)$table['capacity'] ?> seats</td>
                            <td><?= !empty($table['is_combinable']) ? 'Yes' : 'No' ?></td>
                            <td>(<?= (int)$table['x_coord'] ?>, <?= (int)$table['y_coord'] ?>)</td>
                            <td><span class="status-badge <?= htmlspecialchars((string)($table['status_class'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($table['occupancy_status'] ?? 'available'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td>
                                <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/tables/<?= htmlspecialchars((string)$table['id'], ENT_QUOTES, 'UTF-8') ?>/delete" class="inline-form">
                                    <?= Session::csrfField() ?>
                                    <button type="submit" class="btn-danger-sm" onclick="return confirm('Archive this table?');">Delete</button>
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
