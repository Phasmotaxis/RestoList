<?php
$title = $title ?? 'Waitlist Portal - Customer Portal';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <div class="card-header">
        <h2>Live Waitlist Queue</h2>
        <p class="subtitle">Remote queue registration and live position tracking.</p>
    </div>

    <?php if (isset($entry)): ?>
        <div class="grid-features">
            <div class="card card-stat">
                <h4>Queue Position</h4>
                <p class="stat-value-accent">#<?= (int) ($livePos ?? $entry['position']) ?></p>
                <p class="subtitle">Status: <?= htmlspecialchars(strtoupper($entry['status']), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="card card-stat">
                <h4>Party Size</h4>
                <p class="stat-value"><?= (int) $entry['party_size'] ?> Guests</p>
                <p class="subtitle"><?= htmlspecialchars($entry['restaurant_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="card card-stat">
                <h4>Est. Wait Time</h4>
                <p class="stat-value-success"><?= max(5, ((int) ($livePos ?? $entry['position'])) * 15) ?> mins</p>
                <p class="subtitle">Joined: <?= htmlspecialchars($entry['join_at'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <div class="action-group">
            <form method="POST" action="<?= htmlspecialchars(\App\Core\Request::url('/waitlist/' . urlencode($entry['id']) . '/cancel'), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Leave waitlist queue?');">
                <button type="submit" class="btn-secondary link-danger">Leave Queue</button>
            </form>
            <a href="<?= htmlspecialchars(\App\Core\Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">Browse Other Restaurants</a>
        </div>
    <?php else: ?>
        <form method="POST" action="<?= htmlspecialchars(\App\Core\Request::url('/waitlist/' . urlencode($restaurant['id'] ?? '') . '/join'), ENT_QUOTES, 'UTF-8') ?>" class="waitlist-form">
            <div class="grid-form">
                <div class="form-group">
                    <label for="wl-email">Email Address</label>
                    <input type="email" id="wl-email" name="customer_email" required class="form-control" placeholder="guest@example.com">
                </div>
                <div class="form-group">
                    <label for="wl-party">Party Size</label>
                    <input type="number" id="wl-party" name="party_size" min="1" max="20" required value="2" class="form-control">
                </div>
                <?php if (!empty($zones)): ?>
                    <div class="form-group">
                        <label for="wl-zone">Preferred Seating Zone (Optional)</label>
                        <select id="wl-zone" name="preferred_zone" class="form-control">
                            <option value="">Any Zone</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?= htmlspecialchars($zone['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($zone['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            <div class="action-group">
                <button type="submit" class="btn-primary">Join Waitlist</button>
                <a href="<?= htmlspecialchars(\App\Core\Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
