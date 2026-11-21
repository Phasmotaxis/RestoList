<?php
use App\Core\Session;

$title = $title ?? 'Waitlist & Walk-ins - Admin';
require_once __DIR__ . '/../layouts/header.php';
$entries = $entries ?? [];
$orgId = $orgId ?? (string) Session::get('tenant_id');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Waitlist & Walk-in Queue</h2>
            <p class="subtitle">Live queue management with automatic backfill on table release</p>
        </div>
        <a href="/admin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Add Party to Waitlist</h3>
    <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/waitlist" class="form-grid-admin" novalidate>
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
                                <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/waitlist/<?= htmlspecialchars((string)$entry['id'], ENT_QUOTES, 'UTF-8') ?>/notify" class="inline-form">
                                    <?= Session::csrfField() ?>
                                    <button type="submit" class="btn-sm">Notify</button>
                                </form>
                                <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/waitlist/<?= htmlspecialchars((string)$entry['id'], ENT_QUOTES, 'UTF-8') ?>/remove" class="inline-form">
                                    <?= Session::csrfField() ?>
                                    <button type="submit" class="btn-danger-sm">Remove</button>
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
