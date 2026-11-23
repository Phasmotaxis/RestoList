<?php
$title = $title ?? 'Reservation Confirmed - Customer Portal';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <div class="card-header">
        <h2>Reservation Details</h2>
        <span class="badge badge-success"><?= htmlspecialchars(strtoupper($responseData['status'] ?? 'CONFIRMED'), ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <p class="subtitle">Thank you for dining with us! Your reservation is securely held in our system.</p>

    <div class="grid-features">
        <div class="card card-stat">
            <h4>Restaurant</h4>
            <p><?= htmlspecialchars($responseData['restaurant_name'] ?? 'Restaurant', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="card card-stat">
            <h4>Party Size</h4>
            <p><?= (int) ($responseData['party_size'] ?? 1) ?> Guests</p>
        </div>
        <div class="card card-stat">
            <h4>Date &amp; Time (UTC)</h4>
            <p><?= htmlspecialchars($responseData['start_at_utc'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="card card-stat">
            <h4>Booking Reference</h4>
            <code><?= htmlspecialchars($responseData['id'] ?? '', ENT_QUOTES, 'UTF-8') ?></code>
        </div>
    </div>

    <?php if (!empty($responseData['notes'])): ?>
        <p><strong>Notes / Requests:</strong> <?= htmlspecialchars($responseData['notes'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="action-group">
        <form method="POST" action="<?= htmlspecialchars(\App\Core\Request::url('/bookings/' . urlencode($responseData['id']) . '/cancel'), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
            <button type="submit" class="btn-secondary link-danger">Cancel Reservation</button>
        </form>
        <a href="<?= htmlspecialchars(\App\Core\Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">Return to Discovery</a>
    </div>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
