<?php
$title = $title ?? 'Restaurant Analytics - Admin';
require_once __DIR__ . '/../layouts/header.php';
$metrics = $metrics ?? [];
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Restaurant Operational Analytics</h2>
            <p class="subtitle">Real-time metrics on seat turnover, booking trends, and revenue</p>
        </div>
        <a href="/admin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card-grid">
    <div class="card metric-card">
        <h4 class="metric-title">Seat Turnover</h4>
        <div class="metric-value"><?= htmlspecialchars((string)($metrics['turnover_rate'] ?? '0.0'), ENT_QUOTES, 'UTF-8') ?>x</div>
        <p class="metric-desc"><?= (int)($metrics['total_guests'] ?? 0) ?> seated / <?= (int)($metrics['total_capacity'] ?? 0) ?> seats</p>
    </div>
    <div class="card metric-card">
        <h4 class="metric-title">No-Show / Cancel Rate</h4>
        <div class="metric-value"><?= htmlspecialchars((string)($metrics['no_show_rate_percent'] ?? '0.0'), ENT_QUOTES, 'UTF-8') ?>%</div>
        <p class="metric-desc"><?= (int)($metrics['no_shows'] ?? 0) ?> of <?= (int)($metrics['total_reservations'] ?? 0) ?> bookings</p>
    </div>
    <div class="card metric-card">
        <h4 class="metric-title">Total Revenue</h4>
        <div class="metric-value"><?= htmlspecialchars((string)($metrics['revenue_formatted'] ?? '$0.00'), ENT_QUOTES, 'UTF-8') ?></div>
        <p class="metric-desc">Aggregated deposit ledger</p>
    </div>
    <div class="card metric-card">
        <h4 class="metric-title">Active Reservations</h4>
        <div class="metric-value"><?= (int)($metrics['total_reservations'] ?? 0) ?></div>
        <p class="metric-desc">Scheduled for today</p>
    </div>
</section>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
