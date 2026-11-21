<?php
$title = $title ?? 'Restaurant Details - Customer Portal';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <div class="card-header">
        <h2><?= htmlspecialchars($restaurantData['name'] ?? 'Restaurant Details', ENT_QUOTES, 'UTF-8') ?></h2>
        <span class="badge badge-accent"><?= htmlspecialchars($restaurantData['cuisine'] ?? 'Contemporary', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <p class="subtitle"><?= htmlspecialchars($restaurantData['location'] ?? '', ENT_QUOTES, 'UTF-8') ?> &bull; Phone: <?= htmlspecialchars($restaurantData['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <div class="grid-features">
        <div class="card card-stat">
            <h4>Operating Hours</h4>
            <p><?= htmlspecialchars($restaurantData['operating_hours'] ?? '11:00 - 22:00 UTC', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="card card-stat">
            <h4>Parking Facilities</h4>
            <p><?= htmlspecialchars($restaurantData['parking'] ?? 'Available on-site', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="card card-stat">
            <h4>Diner Rating</h4>
            <p class="stat-value-accent"><?= number_format($restaurantData['average_rating'] ?? 0.0, 1) ?> &#9733;</p>
            <p class="subtitle"><?= (int) ($restaurantData['review_count'] ?? 0) ?> verified reviews</p>
        </div>
    </div>
    <div class="action-group">
        <a href="/bookings/new?restaurant_id=<?= urlencode($restaurantData['id']) ?>" class="btn-primary">Book a Table &rarr;</a>
        <a href="/waitlist/<?= urlencode($restaurantData['id']) ?>/join" class="btn-secondary">Join Live Waitlist</a>
        <a href="/discover" class="btn-secondary">&larr; Back to Discovery</a>
    </div>
</section>

<section class="card">
    <h3>Live Menu</h3>
    <p class="subtitle">Real-time menu synchronized with kitchen inventory. Items 86'd by kitchen staff are labeled unavailable.</p>

    <?php if (empty($restaurantData['menu_preview'])): ?>
        <p class="subtitle">No menu items listed yet.</p>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Description</th>
                        <th>Dietary / Allergens</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($restaurantData['menu_preview'] as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if (!empty($item['dietary_tags'])): ?>
                                    <span class="badge badge-info"><?= htmlspecialchars($item['dietary_tags'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['allergen_tags'])): ?>
                                    <span class="badge badge-danger"><?= htmlspecialchars($item['allergen_tags'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>$<?= number_format(((int) $item['price_centavos']) / 100.0, 2) ?></td>
                            <td>
                                <?php if ((int) $item['is_available'] === 1): ?>
                                    <span class="badge badge-success">Available</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">86'd (Sold Out)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <h3>Diner Reviews</h3>
    <?php if (empty($restaurantData['reviews'])): ?>
        <p class="subtitle">No reviews submitted yet. Be the first to leave a review after your meal!</p>
    <?php else: ?>
        <div class="reviews-list">
            <?php foreach ($restaurantData['reviews'] as $rev): ?>
                <div class="card card-review">
                    <div class="review-header">
                        <strong><?= htmlspecialchars($rev['first_name'] ?? 'Verified Diner', ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="stat-value-accent"><?= (int) $rev['rating'] ?> / 5 &#9733;</span>
                        <span class="subtitle"><?= htmlspecialchars($rev['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <p><?= htmlspecialchars($rev['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if (!empty($rev['restaurant_response'])): ?>
                        <div class="restaurant-response">
                            <strong>Response from Management:</strong>
                            <p><?= htmlspecialchars($rev['restaurant_response'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <hr>

    <h4>Leave a Review</h4>
    <form method="POST" action="/reviews" class="review-form">
        <input type="hidden" name="org_id" value="<?= htmlspecialchars($restaurantData['id'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="grid-form">
            <div class="form-group">
                <label for="rev-email">Your Email (required)</label>
                <input type="email" id="rev-email" name="customer_email" required class="form-control" placeholder="guest@example.com">
            </div>
            <div class="form-group">
                <label for="rev-rating">Rating</label>
                <select id="rev-rating" name="rating" required class="form-control">
                    <option value="5">5 - Exceptional</option>
                    <option value="4">4 - Very Good</option>
                    <option value="3">3 - Satisfactory</option>
                    <option value="2">2 - Fair</option>
                    <option value="1">1 - Poor</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="rev-comment">Review Comments (minimum 10 characters)</label>
            <textarea id="rev-comment" name="comment" required minlength="10" rows="3" class="form-control" placeholder="Share your dining experience..."></textarea>
        </div>
        <button type="submit" class="btn-primary">Submit Review</button>
    </form>
</section>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
