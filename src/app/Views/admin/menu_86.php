<?php
use App\Core\Session;

$title = $title ?? 'Menu & 86-List - Admin';
require_once __DIR__ . '/../layouts/header.php';
$items = $items ?? [];
$orgId = $orgId ?? (string) Session::get('tenant_id');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Menu Catalog & 86-List</h2>
            <p class="subtitle">Real-time dish availability toggle for kitchen and front-of-house coordination</p>
        </div>
        <a href="/admin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Menu Items & Live Availability</h3>
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Price</th>
                    <th>Dietary / Allergens</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No menu items configured for this restaurant.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td>$<?= number_format(((int)$item['price_centavos']) / 100, 2) ?></td>
                            <td><?= htmlspecialchars((string)($item['dietary_tags'] ?? $item['allergen_tags'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ((int)$item['is_available'] === 1): ?>
                                    <span class="badge badge-success">Available</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">86-ed (Sold Out)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/menu/<?= htmlspecialchars((string)$item['id'], ENT_QUOTES, 'UTF-8') ?>/toggle-86" class="inline-form">
                                    <?= Session::csrfField() ?>
                                    <button type="submit" class="btn-sm">
                                        <?= (int)$item['is_available'] === 1 ? 'Mark 86' : 'Mark Available' ?>
                                    </button>
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
