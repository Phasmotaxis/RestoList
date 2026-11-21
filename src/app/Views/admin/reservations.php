<?php
use App\Core\Session;

$title = $title ?? 'Reservations - Admin';
require_once __DIR__ . '/../layouts/header.php';
$reservations = $reservations ?? [];
$availableTables = $availableTables ?? [];
$orgId = $orgId ?? (string) Session::get('tenant_id');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Reservations & Table Bookings</h2>
            <p class="subtitle">Real-time booking ledger with atomic 15-minute slot allocation</p>
        </div>
        <a href="/admin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>New Walk-in / Reservation</h3>
    <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/reservations" class="form-grid-admin" novalidate>
        <?= Session::csrfField() ?>
        <div>
            <label for="party_size" class="form-label">Party Size (Guests)</label>
            <input type="number" id="party_size" name="party_size" class="form-input" required min="1" max="20" value="2">
        </div>
        <div>
            <label for="table_id" class="form-label">Assigned Table</label>
            <select id="table_id" name="table_id" class="form-input" required>
                <?php foreach ($availableTables as $tbl): ?>
                    <option value="<?= htmlspecialchars((string)$tbl['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string)$tbl['label'], ENT_QUOTES, 'UTF-8') ?> (<?= (int)$tbl['capacity'] ?>p) - <?= htmlspecialchars((string)($tbl['zone_name'] ?? 'General'), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="start_at_utc" class="form-label">Start Time (UTC)</label>
            <input type="datetime-local" id="start_at_utc" name="start_at_utc" class="form-input" required value="<?= date('Y-m-d\TH:i') ?>">
        </div>
        <div>
            <label for="notes" class="form-label">Special Notes / Requests</label>
            <input type="text" id="notes" name="notes" class="form-input" placeholder="e.g. Window seat, anniversary, high chair">
        </div>
        <div class="form-full">
            <button type="submit" class="btn-primary">Confirm Booking</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Booking Ledger</h3>
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th>Start Time</th>
                    <th>Tables</th>
                    <th>Party</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No reservations scheduled for this period.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reservations as $res): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$res['start_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><strong><?= htmlspecialchars((string)($res['table_labels'] ?? 'TBD'), ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= (int)$res['party_size'] ?> guests</td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars((string)$res['status'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)$res['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars((string)($res['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($res['status'] !== 'cancelled'): ?>
                                    <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/reservations/<?= htmlspecialchars((string)$res['id'], ENT_QUOTES, 'UTF-8') ?>/confirm" class="inline-form">
                                        <?= Session::csrfField() ?>
                                        <button type="submit" class="btn-sm"><?= $res['status'] === 'confirmed' ? 'Seat' : 'Confirm' ?></button>
                                    </form>
                                    <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/reservations/<?= htmlspecialchars((string)$res['id'], ENT_QUOTES, 'UTF-8') ?>/cancel" class="inline-form">
                                        <?= Session::csrfField() ?>
                                        <button type="submit" class="btn-danger-sm" onclick="return confirm('Cancel this reservation and release tables?');">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
