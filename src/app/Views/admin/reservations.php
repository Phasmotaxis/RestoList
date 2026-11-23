<?php
declare(strict_types=1);

use App\Core\Session;
use App\Core\Request;

$title = $title ?? 'Reservations - Admin';
require_once __DIR__ . '/../layouts/header.php';
$reservations = $reservations ?? [];
$availableTables = $availableTables ?? [];
$orgId = $orgId ?? (string) Session::get('tenant_id');

$currentDate = htmlspecialchars((string)($_GET['date'] ?? ''), ENT_QUOTES, 'UTF-8');
$currentStatus = htmlspecialchars((string)($_GET['status'] ?? ''), ENT_QUOTES, 'UTF-8');
$currentQ = htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8');
$currentSort = htmlspecialchars((string)($_GET['sort'] ?? 'start_at'), ENT_QUOTES, 'UTF-8');
$currentDir = htmlspecialchars((string)($_GET['dir'] ?? 'ASC'), ENT_QUOTES, 'UTF-8');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Reservations & Table Bookings</h2>
            <p class="subtitle">Real-time booking ledger with atomic 15-minute slot allocation</p>
        </div>
        <a href="<?= htmlspecialchars(Request::url('/admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>New Walk-in / Reservation</h3>
    <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/reservations'), ENT_QUOTES, 'UTF-8') ?>" class="form-grid-admin" novalidate>
        <?= Session::csrfField() ?>
        <div>
            <label for="party_size" class="form-label">Party Size (Guests)</label>
            <input type="number" id="party_size" name="party_size" class="form-input" required min="1" max="20" value="2">
        </div>
        <div>
            <label for="table_id" class="form-label">Assigned Table</label>
            <select id="table_id" name="table_id" class="form-input" required>
                <?php if (empty($availableTables)): ?>
                    <option value="">-- No tables configured --</option>
                <?php else: ?>
                    <?php foreach ($availableTables as $tbl): ?>
                        <option value="<?= htmlspecialchars((string)$tbl['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)$tbl['label'], ENT_QUOTES, 'UTF-8') ?> (<?= (int)$tbl['capacity'] ?>p) - <?= htmlspecialchars((string)($tbl['zone_name'] ?? 'General'), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
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
    <h3>Filter & Search Booking Ledger</h3>
    <form method="GET" action="<?= htmlspecialchars(Request::url('/admin/reservations'), ENT_QUOTES, 'UTF-8') ?>" class="form-grid-admin">
        <div>
            <label for="filter_q" class="form-label">Search Keyword / Notes</label>
            <input type="text" id="filter_q" name="q" value="<?= $currentQ ?>" class="form-input" placeholder="Search notes...">
        </div>
        <div>
            <label for="filter_date" class="form-label">Date (YYYY-MM-DD)</label>
            <input type="date" id="filter_date" name="date" value="<?= $currentDate ?>" class="form-input">
        </div>
        <div>
            <label for="filter_status" class="form-label">Status Filter</label>
            <select id="filter_status" name="status" class="form-input">
                <option value="">All Statuses</option>
                <option value="confirmed" <?= $currentStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="seated" <?= $currentStatus === 'seated' ? 'selected' : '' ?>>Seated</option>
                <option value="completed" <?= $currentStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $currentStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                <option value="no_show" <?= $currentStatus === 'no_show' ? 'selected' : '' ?>>No-Show</option>
            </select>
        </div>
        <div>
            <label for="filter_sort" class="form-label">Sort By</label>
            <select id="filter_sort" name="sort" class="form-input">
                <option value="start_at" <?= $currentSort === 'start_at' ? 'selected' : '' ?>>Start Time</option>
                <option value="party_size" <?= $currentSort === 'party_size' ? 'selected' : '' ?>>Party Size</option>
                <option value="created_at" <?= $currentSort === 'created_at' ? 'selected' : '' ?>>Date Created</option>
                <option value="status" <?= $currentSort === 'status' ? 'selected' : '' ?>>Status</option>
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
            <a href="<?= htmlspecialchars(Request::url('/admin/reservations'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Reset</a>
        </div>
    </form>
</section>

<section class="card">
    <h3>Booking Ledger Roster</h3>
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th>Start Time</th>
                    <th>Tables</th>
                    <th>Party</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Lifecycle Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No reservations scheduled for this filter criteria.</td>
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
                            <td><?= htmlspecialchars((string)($res['notes'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($res['status'] === 'confirmed'): ?>
                                    <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/reservations/' . urlencode((string)$res['id']) . '/confirm'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                                        <?= Session::csrfField() ?>
                                        <button type="submit" class="btn-sm">Seat Guests</button>
                                    </form>
                                    <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/reservations/' . urlencode((string)$res['id']) . '/no-show'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                                        <?= Session::csrfField() ?>
                                        <button type="submit" class="btn-danger-sm" onclick="return confirm('Mark this reservation as No-Show?');">No-Show</button>
                                    </form>
                                    <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/reservations/' . urlencode((string)$res['id']) . '/cancel'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                                        <?= Session::csrfField() ?>
                                        <button type="submit" class="btn-danger-sm" onclick="return confirm('Cancel this reservation and release tables?');">Cancel</button>
                                    </form>
                                <?php elseif ($res['status'] === 'seated'): ?>
                                    <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/reservations/' . urlencode((string)$res['id']) . '/checkout'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                                        <?= Session::csrfField() ?>
                                        <button type="submit" class="btn-sm">Checkout &amp; Settle</button>
                                    </form>
                                    <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/reservations/' . urlencode((string)$res['id']) . '/cancel'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                                        <?= Session::csrfField() ?>
                                        <button type="submit" class="btn-danger-sm" onclick="return confirm('Cancel this reservation?');">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Settled</span>
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
