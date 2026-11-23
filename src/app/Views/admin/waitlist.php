<?php
declare(strict_types=1);

use App\Core\Session;
use App\Core\Request;

$title = $title ?? 'Waitlist & Walk-ins - Admin';
require_once __DIR__ . '/../layouts/header.php';
$entries = $entries ?? [];
$orgId = $orgId ?? (string) Session::get('tenant_id');

$currentQ = htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8');
$currentStatus = htmlspecialchars((string)($_GET['status'] ?? ''), ENT_QUOTES, 'UTF-8');
$currentSort = htmlspecialchars((string)($_GET['sort'] ?? 'position'), ENT_QUOTES, 'UTF-8');
$currentDir = htmlspecialchars((string)($_GET['dir'] ?? 'ASC'), ENT_QUOTES, 'UTF-8');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Waitlist & Walk-in Queue</h2>
            <p class="subtitle">Live queue management with automatic backfill on table release</p>
        </div>
        <a href="<?= htmlspecialchars(Request::url('/admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Add Party to Waitlist</h3>
    <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/waitlist'), ENT_QUOTES, 'UTF-8') ?>" class="form-grid-admin" novalidate>
        <?= Session::csrfField() ?>
        <div>
            <label for="party_size" class="form-label">Party Size</label>
            <input type="number" id="party_size" name="party_size" class="form-input" required min="1" max="20" value="2">
        </div>
        <div>
            <label for="preferred_zone" class="form-label">Zone Preference</label>
            <input type="text" id="preferred_zone" name="preferred_zone" class="form-input" placeholder="Any / Outdoor / Main">
        </div>
        <div class="form-full">
            <button type="submit" class="btn-primary">Add to Waitlist</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Filter & Search Waitlist Queue</h3>
    <form method="GET" action="<?= htmlspecialchars(Request::url('/admin/waitlist'), ENT_QUOTES, 'UTF-8') ?>" class="form-grid-admin">
        <div>
            <label for="filter_q" class="form-label">Search Zone / Note</label>
            <input type="text" id="filter_q" name="q" value="<?= $currentQ ?>" class="form-input" placeholder="Search preference...">
        </div>
        <div>
            <label for="filter_status" class="form-label">Status Filter</label>
            <select id="filter_status" name="status" class="form-input">
                <option value="">Active (Waiting &amp; Notified)</option>
                <option value="waiting" <?= $currentStatus === 'waiting' ? 'selected' : '' ?>>Waiting Only</option>
                <option value="notified" <?= $currentStatus === 'notified' ? 'selected' : '' ?>>Notified Only</option>
                <option value="cancelled" <?= $currentStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div>
            <label for="filter_sort" class="form-label">Sort By</label>
            <select id="filter_sort" name="sort" class="form-input">
                <option value="position" <?= $currentSort === 'position' ? 'selected' : '' ?>>Queue Position</option>
                <option value="party_size" <?= $currentSort === 'party_size' ? 'selected' : '' ?>>Party Size</option>
                <option value="created_at" <?= $currentSort === 'created_at' ? 'selected' : '' ?>>Time Joined</option>
            </select>
        </div>
        <div>
            <label for="filter_dir" class="form-label">Sort Direction</label>
            <select id="filter_dir" name="dir" class="form-input">
                <option value="ASC" <?= $currentDir === 'ASC' ? 'selected' : '' ?>>Ascending &uarr;</option>
                <option value="DESC" <?= $currentDir === 'DESC' ? 'selected' : '' ?>>Descending &darr;</option>
            </select>
        </div>
        <div class="form-full">
            <button type="submit" class="btn-primary">Apply Filters</button>
            <a href="<?= htmlspecialchars(Request::url('/admin/waitlist'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Reset</a>
        </div>
    </form>
</section>

<section class="card">
    <h3>Active Queue</h3>
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Party Size</th>
                    <th>Joined At</th>
                    <th>Preferred Zone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No parties currently in waitlist.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><strong>#<?= (int)$entry['position'] ?></strong></td>
                            <td><?= (int)$entry['party_size'] ?> guests</td>
                            <td><?= htmlspecialchars(substr((string)$entry['join_at'], 11, 5), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($entry['zone_name'] ?? $entry['preferred_zone'] ?? 'Any'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars((string)$entry['status'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)$entry['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($entry['status'] === 'waiting'): ?>
                                    <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/waitlist/' . urlencode((string)$entry['id']) . '/notify'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                                        <?= Session::csrfField() ?>
                                        <button type="submit" class="btn-sm">Notify</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/waitlist/' . urlencode((string)$entry['id']) . '/remove'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                                    <?= Session::csrfField() ?>
                                    <button type="submit" class="btn-danger-sm" onclick="return confirm('Remove party from waitlist?');">Remove</button>
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
