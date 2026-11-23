<?php
$title = $title ?? 'Restaurant Discovery - Customer Portal';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <div class="card-header">
        <h2>Restaurant Discovery</h2>
        <p class="subtitle">Find dining venues, check real-time availability, and reserve tables across our multi-tenant platform.</p>
    </div>

    <form method="GET" action="<?= htmlspecialchars(\App\Core\Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="search-form">
        <div class="grid-form">
            <div class="form-group">
                <label for="search-query">Search Venue</label>
                <input type="text" id="search-query" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by name or keyword..." class="form-control">
            </div>
            <div class="form-group">
                <label for="cuisine-filter">Cuisine</label>
                <select id="cuisine-filter" name="cuisine" class="form-control">
                    <option value="">All Cuisines</option>
                    <option value="Italian" <?= (($_GET['cuisine'] ?? '') === 'Italian') ? 'selected' : '' ?>>Italian</option>
                    <option value="Contemporary" <?= (($_GET['cuisine'] ?? '') === 'Contemporary') ? 'selected' : '' ?>>Contemporary Dining</option>
                    <option value="Cafe" <?= (($_GET['cuisine'] ?? '') === 'Cafe') ? 'selected' : '' ?>>Cafe &amp; Bistro</option>
                </select>
            </div>
            <div class="form-group">
                <label for="dietary-filter">Dietary Preference</label>
                <select id="dietary-filter" name="dietary" class="form-control">
                    <option value="">Any Dietary</option>
                    <option value="vegan" <?= (($_GET['dietary'] ?? '') === 'vegan') ? 'selected' : '' ?>>Vegan</option>
                    <option value="vegetarian" <?= (($_GET['dietary'] ?? '') === 'vegetarian') ? 'selected' : '' ?>>Vegetarian</option>
                    <option value="gluten-free" <?= (($_GET['dietary'] ?? '') === 'gluten-free') ? 'selected' : '' ?>>Gluten-Free</option>
                    <option value="halal" <?= (($_GET['dietary'] ?? '') === 'halal') ? 'selected' : '' ?>>Halal</option>
                </select>
            </div>
            <div class="form-group">
                <label for="date-filter">Date</label>
                <input type="date" id="date-filter" name="date" value="<?= htmlspecialchars($_GET['date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" class="form-control">
            </div>
            <div class="form-group">
                <label for="party-size-filter">Party Size</label>
                <input type="number" id="party-size-filter" name="party_size" min="1" max="20" value="<?= htmlspecialchars((string) ($_GET['party_size'] ?? 2), ENT_QUOTES, 'UTF-8') ?>" class="form-control">
            </div>
        </div>
        <div class="action-group">
            <button type="submit" class="btn-primary">Search Availability</button>
            <a href="<?= htmlspecialchars(\App\Core\Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Reset Filters</a>
        </div>
    </form>
</section>

<div class="restaurants-list">
    <?php if (empty($data)): ?>
        <section class="card empty-state">
            <h3>No restaurants found</h3>
            <p class="subtitle">Try adjusting your filters or search terms.</p>
        </section>
    <?php else: ?>
        <div class="grid-features">
            <?php foreach ($data as $restaurant): ?>
                <div class="card card-restaurant">
                    <div class="restaurant-header">
                        <h3><?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="badge badge-accent"><?= htmlspecialchars($restaurant['cuisine'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <p class="subtitle"><?= htmlspecialchars($restaurant['location'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($restaurant['phone'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Hours:</strong> <?= htmlspecialchars($restaurant['operating_hours'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Parking:</strong> <?= htmlspecialchars($restaurant['parking'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="rating-bar">
                        <span class="stat-value-accent"><?= number_format($restaurant['average_rating'], 1) ?> &#9733;</span>
                        <span class="subtitle">(<?= (int) $restaurant['review_count'] ?> verified reviews)</span>
                    </div>
                    <?php if (!empty($restaurant['dietary_tags'])): ?>
                        <div class="tags-group">
                            <?php foreach ($restaurant['dietary_tags'] as $tag): ?>
                                <span class="badge badge-info"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="action-group">
                        <a href="<?= htmlspecialchars(\App\Core\Request::url('/discover/' . urlencode($restaurant['id'])), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">View Details &amp; Menu</a>
                        <a href="<?= htmlspecialchars(\App\Core\Request::url('/bookings/new?restaurant_id=' . urlencode($restaurant['id'])), ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">Reserve Table</a>
                        <a href="<?= htmlspecialchars(\App\Core\Request::url('/waitlist/' . urlencode($restaurant['id']) . '/join'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Join Waitlist</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($totalPages) && $totalPages > 1): ?>
    <nav class="pagination-nav" aria-label="Page navigation">
        <ul class="pagination">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                    <a href="?page=<?= $p ?>&amp;q=<?= urlencode($_GET['q'] ?? '') ?>&amp;cuisine=<?= urlencode($_GET['cuisine'] ?? '') ?>&amp;dietary=<?= urlencode($_GET['dietary'] ?? '') ?>" class="page-link"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
