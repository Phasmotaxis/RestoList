<?php
declare(strict_types=1);

use App\Core\Session;
use App\Core\Request;

$title = $title ?? 'Menu & 86-List - Admin';
require_once __DIR__ . '/../layouts/header.php';
$items = $items ?? [];
$orgId = $orgId ?? (string) Session::get('tenant_id');

$currentQ = htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8');
$currentSort = htmlspecialchars((string)($_GET['sort'] ?? 'name'), ENT_QUOTES, 'UTF-8');
$currentDir = htmlspecialchars((string)($_GET['dir'] ?? 'ASC'), ENT_QUOTES, 'UTF-8');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Menu Catalog & 86-List</h2>
            <p class="subtitle">Real-time dish availability toggle for kitchen and front-of-house coordination</p>
        </div>
        <a href="<?= htmlspecialchars(Request::url('/admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Add New Menu Item</h3>
    <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/menu'), ENT_QUOTES, 'UTF-8') ?>" class="form-grid-admin" novalidate>
        <?= Session::csrfField() ?>
        <div>
            <label for="item_name" class="form-label">Item Name</label>
            <input type="text" id="item_name" name="name" class="form-input" required placeholder="e.g. Truffle Risotto">
        </div>
        <div>
            <label for="price_centavos" class="form-label">Price (in centavos / cents)</label>
            <input type="number" id="price_centavos" name="price_centavos" class="form-input" required min="0" placeholder="e.g. 1850 for $18.50">
        </div>
        <div>
            <label for="dietary_tags" class="form-label">Dietary Tags</label>
            <input type="text" id="dietary_tags" name="dietary_tags" class="form-input" placeholder="e.g. Vegetarian, Gluten-Free">
        </div>
        <div>
            <label for="allergen_tags" class="form-label">Allergen Tags</label>
            <input type="text" id="allergen_tags" name="allergen_tags" class="form-input" placeholder="e.g. Dairy, Nuts, Shellfish">
        </div>
        <div class="form-full">
            <label for="item_desc" class="form-label">Description</label>
            <input type="text" id="item_desc" name="description" class="form-input" placeholder="Short description of ingredients or preparation">
        </div>
        <div class="form-full">
            <button type="submit" class="btn-primary">Save Menu Item</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Filter & Search Menu Catalog</h3>
    <form method="GET" action="<?= htmlspecialchars(Request::url('/admin/menu'), ENT_QUOTES, 'UTF-8') ?>" class="form-grid-admin">
        <div>
            <label for="filter_q" class="form-label">Search Dish Name / Description</label>
            <input type="text" id="filter_q" name="q" value="<?= $currentQ ?>" class="form-input" placeholder="Search menu...">
        </div>
        <div>
            <label for="filter_sort" class="form-label">Sort By</label>
            <select id="filter_sort" name="sort" class="form-input">
                <option value="name" <?= $currentSort === 'name' ? 'selected' : '' ?>>Dish Name</option>
                <option value="price_centavos" <?= $currentSort === 'price_centavos' ? 'selected' : '' ?>>Price</option>
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
            <a href="<?= htmlspecialchars(Request::url('/admin/menu'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Reset</a>
        </div>
    </form>
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
                            <td>
                                <strong><?= htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($item['description'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars((string)$item['description'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </td>
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
                                <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/menu/' . urlencode((string)$item['id']) . '/toggle-86'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                                    <?= Session::csrfField() ?>
                                    <button type="submit" class="btn-sm">
                                        <?= (int)$item['is_available'] === 1 ? 'Mark 86' : 'Mark Available' ?>
                                    </button>
                                </form>
                                <form method="POST" action="<?= htmlspecialchars(Request::url('/admin/restaurants/' . urlencode($orgId) . '/menu/' . urlencode((string)$item['id']) . '/delete'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form" onsubmit="return confirm('Archive this menu item?');">
                                    <?= Session::csrfField() ?>
                                    <button type="submit" class="btn-danger-sm">Archive</button>
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
