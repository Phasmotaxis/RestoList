<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Menu Sync Controller: Real-time menu synchronization and 86-list item availability.
 *
 * Exposes live restaurant menu synchronized with kitchen ERP operations.
 * Automatically respects the 86-list (marking unavailable items as is_available=false),
 * formats centavo prices into currency strings, and surfaces dietary and allergen tags.
 */
class MenuSyncController
{
    /**
     * Detect JSON request format.
     *
     * @return bool
     */
    private function isJsonRequest(): bool
    {
        return (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || (isset($_GET['format']) && $_GET['format'] === 'json');
    }

    /**
     * Retrieve live menu for a restaurant respecting 86-list item availability.
     *
     * Routes: GET /menu/:restaurant_id, GET /discover/:id/menu
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function getMenu(Request $request, Response $response): void
    {
        $restaurantId = $request->getParam('restaurant_id') ?? $request->getParam('id') ?? $request->getParam('org_id');
        if (!$restaurantId) {
            $response->json(['error' => 'Restaurant ID is required'], 400);
            return;
        }

        $pdo = Database::getConnection();

        // Verify restaurant
        $restStmt = $pdo->prepare('SELECT id, name, subdomain FROM tenants WHERE (id = :id OR subdomain = :id) AND is_active = 1');
        $restStmt->execute([':id' => $restaurantId]);
        $restaurant = $restStmt->fetch();

        if (!$restaurant) {
            $response->json(['error' => 'Restaurant not found'], 404);
            return;
        }

        $orgId = $restaurant['id'];
        $availableOnly = (bool) ($request->getParam('available_only') ?? false);

        $sql = 'SELECT id, org_id, name, description, price_centavos, is_available, dietary_tags, allergen_tags, created_at FROM menu_items WHERE org_id = :org_id AND archived_at IS NULL';
        if ($availableOnly) {
            $sql .= ' AND is_available = 1';
        }
        $sql .= ' ORDER BY name ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':org_id' => $orgId]);
        $rawItems = $stmt->fetchAll();

        $items = [];
        foreach ($rawItems as $row) {
            $priceCentavos = (int) $row['price_centavos'];
            $priceFormatted = sprintf('$%.2f', $priceCentavos / 100.0);
            $isAvailable = (int) $row['is_available'] === 1;

            $items[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'] ?? '',
                'price_centavos' => $priceCentavos,
                'price_formatted' => $priceFormatted,
                'is_available' => $isAvailable,
                'dietary_tags' => !empty($row['dietary_tags']) ? array_map('trim', explode(',', $row['dietary_tags'])) : [],
                'allergen_tags' => !empty($row['allergen_tags']) ? array_map('trim', explode(',', $row['allergen_tags'])) : [],
                'created_at' => $row['created_at'],
            ];
        }

        $payload = [
            'restaurant_id' => $orgId,
            'restaurant_name' => $restaurant['name'],
            'total_items' => count($items),
            'items' => $items,
        ];

        if ($this->isJsonRequest()) {
            $response->json($payload, 200);
            return;
        }

        $title = "Menu: {$restaurant['name']} - Customer Portal";
        require_once __DIR__ . '/../Views/customer/restaurant.php';
    }
}
