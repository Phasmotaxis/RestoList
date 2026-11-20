<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Middleware\RoleMiddleware;
use PDOException;

/**
 * Inventory Controller: Restaurant inventory management.
 *
 * Provides CRUD for inventory items scoped to restaurant. Tracks quantity on hand,
 * reorder levels, and supplier linkage. Flags low-stock items for procurement.
 */
class InventoryController
{
    /**
     * List inventory items for a restaurant.
     *
     * Requires User role. Filters by category, search, and low-stock status.
     * Multi-tenant scoped: users see only their assigned restaurant's inventory.
     *
     * @param Request $request HTTP request (GET params: q, category, restaurant_id).
     * @param Response $response HTTP response object.
     *
     * @return void Renders inventory list view.
     */
    public function index(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_USER);
        $restaurantId = (int) ($_GET['restaurant_id'] ?? 0);

        $query = 'SELECT i.*, s.name as supplier_name FROM inventory_items i LEFT JOIN suppliers s ON i.supplier_id = s.id WHERE i.restaurant_id = :restaurant_id';
        $params = ['restaurant_id' => $restaurantId];

        if (!empty($_GET['q'])) {
            $query .= ' AND i.name LIKE :search';
            $params['search'] = '%' . trim((string) $_GET['q']) . '%';
        }

        if (!empty($_GET['category'])) {
            $query .= ' AND i.category = :category';
            $params['category'] = trim((string) $_GET['category']);
        }

        if (isset($_GET['low_stock']) && $_GET['low_stock'] === '1') {
            $query .= ' AND i.quantity_on_hand <= i.reorder_level';
        }

        $query .= ' ORDER BY i.name ASC';
        $items = Database::executeQuery($query, $params)->fetchAll();
        $lowStockCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM inventory_items WHERE restaurant_id = :restaurant_id AND quantity_on_hand <= reorder_level', ['restaurant_id' => $restaurantId])->fetch()['count'] ?? 0);

        require_once __DIR__ . '/../Views/inventory/index.php';
    }

    /**
     * Show create inventory item form.
     *
     * Requires User role.
     *
     * @param Request $request HTTP request object (GET params: restaurant_id).
     * @param Response $response HTTP response object.
     *
     * @return void Renders create form view.
     */
    public function create(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_USER);
        $restaurantId = (int) ($_GET['restaurant_id'] ?? 0);
        $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
        $errors = [];
        $old = [
            'name' => '',
            'category' => '',
            'unit_of_measure' => '',
            'quantity_on_hand' => '0',
            'reorder_level' => '0',
            'unit_cost' => '0',
            'supplier_id' => '',
        ];
        require_once __DIR__ . '/../Views/inventory/form.php';
    }

    /**
     * Store new inventory item.
     *
     * Requires User role and valid CSRF token. Validates item details and creates record.
     * Logs audit trail with full item details.
     *
     * @param Request $request HTTP request with POST body.
     * @param Response $response HTTP response object.
     *
     * @return void Redirects on success or re-renders form with errors.
     */
    public function store(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_USER);
        Session::verifyCsrf();
        $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);

        $old = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? '')),
            'unit_of_measure' => trim((string) ($_POST['unit_of_measure'] ?? '')),
            'quantity_on_hand' => (float) ($_POST['quantity_on_hand'] ?? 0),
            'reorder_level' => (float) ($_POST['reorder_level'] ?? 0),
            'unit_cost' => (float) ($_POST['unit_cost'] ?? 0),
            'supplier_id' => !empty($_POST['supplier_id']) ? (int) $_POST['supplier_id'] : null,
        ];

        $errors = array_filter([
            'name' => empty($old['name']) ? 'Item name is required.' : null,
            'unit_cost' => $old['unit_cost'] < 0 ? 'Unit cost cannot be negative.' : null,
        ]);

        if ($errors !== []) {
            $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
            $response->status(422);
            require_once __DIR__ . '/../Views/inventory/form.php';
            return;
        }

        try {
            Database::executeQuery(
                'INSERT INTO inventory_items (restaurant_id, name, category, unit_of_measure, quantity_on_hand, reorder_level, unit_cost, supplier_id) VALUES (:restaurant_id, :name, :category, :unit_of_measure, :quantity_on_hand, :reorder_level, :unit_cost, :supplier_id)',
                [
                    'restaurant_id' => $restaurantId,
                    'name' => $old['name'],
                    'category' => $old['category'],
                    'unit_of_measure' => $old['unit_of_measure'],
                    'quantity_on_hand' => $old['quantity_on_hand'],
                    'reorder_level' => $old['reorder_level'],
                    'unit_cost' => $old['unit_cost'],
                    'supplier_id' => $old['supplier_id'],
                ]
            );

            Audit::log('CREATE', 'inventory_item', $old['name'], ['item' => $old]);
            header('Location: /admin/restaurants/' . $restaurantId . '/inventory', true, 302);
            exit;
        } catch (PDOException $e) {
            $errors['_general'] = 'Failed to create inventory item. Please try again.';
            $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
            $response->status(422);
            require_once __DIR__ . '/../Views/inventory/form.php';
        }
    }

    /**
     * Show edit inventory item form.
     *
     * Requires User role. Fetches item by ID.
     *
     * @param Request $request HTTP request object (params: id, restaurant_id).
     * @param Response $response HTTP response object.
     *
     * @return void Renders edit form view.
     */
    public function edit(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_USER);
        $id = (int) ($_GET['id'] ?? 0);
        $restaurantId = (int) ($_GET['restaurant_id'] ?? 0);

        $old = Database::executeQuery('SELECT * FROM inventory_items WHERE id = :id AND restaurant_id = :restaurant_id', ['id' => $id, 'restaurant_id' => $restaurantId])->fetch();
        if (!$old) {
            $response->status(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
        $errors = [];
        require_once __DIR__ . '/../Views/inventory/form.php';
    }

    /**
     * Update inventory item details.
     *
     * Requires User role and valid CSRF token. Validates and updates record.
     * Logs audit trail with old/new values.
     *
     * @param Request $request HTTP request with POST body.
     * @param Response $response HTTP response object.
     *
     * @return void Redirects on success or re-renders form with errors.
     */
    public function update(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_USER);
        Session::verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);

        $existing = Database::executeQuery('SELECT * FROM inventory_items WHERE id = :id AND restaurant_id = :restaurant_id', ['id' => $id, 'restaurant_id' => $restaurantId])->fetch();
        if (!$existing) {
            $response->status(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $new = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? '')),
            'unit_of_measure' => trim((string) ($_POST['unit_of_measure'] ?? '')),
            'quantity_on_hand' => (float) ($_POST['quantity_on_hand'] ?? 0),
            'reorder_level' => (float) ($_POST['reorder_level'] ?? 0),
            'unit_cost' => (float) ($_POST['unit_cost'] ?? 0),
            'supplier_id' => !empty($_POST['supplier_id']) ? (int) $_POST['supplier_id'] : null,
        ];

        $errors = array_filter([
            'name' => empty($new['name']) ? 'Item name is required.' : null,
            'unit_cost' => $new['unit_cost'] < 0 ? 'Unit cost cannot be negative.' : null,
        ]);

        if ($errors !== []) {
            $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
            $response->status(422);
            require_once __DIR__ . '/../Views/inventory/form.php';
            return;
        }

        try {
            Database::executeQuery(
                'UPDATE inventory_items SET name = :name, category = :category, unit_of_measure = :unit_of_measure, quantity_on_hand = :quantity_on_hand, reorder_level = :reorder_level, unit_cost = :unit_cost, supplier_id = :supplier_id WHERE id = :id',
                [
                    'id' => $id,
                    'name' => $new['name'],
                    'category' => $new['category'],
                    'unit_of_measure' => $new['unit_of_measure'],
                    'quantity_on_hand' => $new['quantity_on_hand'],
                    'reorder_level' => $new['reorder_level'],
                    'unit_cost' => $new['unit_cost'],
                    'supplier_id' => $new['supplier_id'],
                ]
            );

            Audit::log('UPDATE', 'inventory_item', $id, ['old' => $existing, 'new' => $new]);
            header('Location: /admin/restaurants/' . $restaurantId . '/inventory', true, 302);
            exit;
        } catch (PDOException $e) {
            $errors['_general'] = 'Failed to update inventory item. Please try again.';
            $suppliers = Database::executeQuery('SELECT id, name FROM suppliers WHERE is_active = true ORDER BY name ASC')->fetchAll();
            $response->status(422);
            require_once __DIR__ . '/../Views/inventory/form.php';
        }
    }
}
