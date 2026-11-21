<?php
use App\Core\Session;

$title = $title ?? 'Review Moderation - Admin';
require_once __DIR__ . '/../layouts/header.php';
$reviews = $reviews ?? [];
$orgId = $orgId ?? (string) Session::get('tenant_id');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Review Moderation & Feedback</h2>
            <p class="subtitle">Moderation queue for verified guest reviews and official responses</p>
        </div>
        <a href="/admin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Feedback Queue</h3>
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Response</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No reviews pending moderation.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <tr>
                            <td><?= htmlspecialchars(substr((string)$rev['created_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($rev['reviewer_email'] ?? 'Anonymous Guest'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>⭐ <?= (int)$rev['rating'] ?>/5</td>
                            <td>
                                <?= htmlspecialchars((string)($rev['comment'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($rev['is_spam_suspect'])): ?>
                                    <span class="badge badge-danger">⚠️ Suspect</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($rev['restaurant_response'])): ?>
                                    <em><?= htmlspecialchars((string)$rev['restaurant_response'], ENT_QUOTES, 'UTF-8') ?></em>
                                <?php else: ?>
                                    <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/reviews/<?= htmlspecialchars((string)$rev['id'], ENT_QUOTES, 'UTF-8') ?>/reply" class="reply-form">
                                        <?= Session::csrfField() ?>
                                        <input type="text" name="reply" placeholder="Write official response..." required class="form-input-sm">
                                        <button type="submit" class="btn-sm">Reply</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/reviews/<?= htmlspecialchars((string)$rev['id'], ENT_QUOTES, 'UTF-8') ?>/reject" class="inline-form">
                                    <?= Session::csrfField() ?>
                                    <button type="submit" class="btn-danger-sm" onclick="return confirm('Purge this review?');">Reject</button>
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
