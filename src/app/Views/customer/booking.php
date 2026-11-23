<?php
$title = $title ?? 'Reserve a Table - Customer Portal';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <div class="card-header">
        <h2>Reserve a Table</h2>
        <p class="subtitle">Real-time table reservation with instant atomic confirmation.</p>
    </div>

    <form method="POST" action="<?= htmlspecialchars(\App\Core\Request::url('/bookings'), ENT_QUOTES, 'UTF-8') ?>" class="booking-form">
        <input type="hidden" name="restaurant_id" value="<?= htmlspecialchars($_GET['restaurant_id'] ?? $restaurant['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="grid-form">
            <div class="form-group">
                <label for="book-email">Email Address (for confirmation)</label>
                <input type="email" id="book-email" name="customer_email" required class="form-control" placeholder="diner@example.com">
            </div>
            <div class="form-group">
                <label for="book-name">Full Name</label>
                <input type="text" id="book-name" name="customer_name" class="form-control" placeholder="Jane Doe">
            </div>
            <div class="form-group">
                <label for="book-phone">Contact Phone</label>
                <input type="tel" id="book-phone" name="customer_phone" class="form-control" placeholder="+63 912 345 6789">
            </div>
            <div class="form-group">
                <label for="book-party">Party Size (1 - 20)</label>
                <input type="number" id="book-party" name="party_size" min="1" max="20" required value="<?= htmlspecialchars((string) ($_GET['party_size'] ?? 2), ENT_QUOTES, 'UTF-8') ?>" class="form-control">
            </div>
            <div class="form-group">
                <label for="book-start">Date &amp; Time (UTC)</label>
                <input type="datetime-local" id="book-start" name="start_at_utc" required class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label for="book-notes">Special Requests / Dietary Needs</label>
            <textarea id="book-notes" name="notes" rows="2" class="form-control" placeholder="e.g. Birthday celebration, booth preference, peanut allergy..."></textarea>
        </div>

        <div class="action-group">
            <button type="submit" class="btn-primary">Confirm Reservation</button>
            <a href="<?= htmlspecialchars(\App\Core\Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
