<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Audit.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\RoleMiddleware;

/**
 * Menu Item Controller: Menu item management and real-time 86-list toggling.
 *
 * Implements Subdomain 4 per docs/SPECIFICATION.md Section 2.2.B.
 */
class MenuItemController
{
    private function isJsonRequest(): bool
    {
        return (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
            || (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
            || (isset($_GET['format']) && $_GET['format'] === 'json');
    }

    private function getAuthorizedOrgId(Request $request, array $params = []): string
    {
        $requestedOrgId = (string) ($params['id'] ?? $request->getParam('id') ?? Session::get('tenant_id') ?? Session::get('org_id') ?? '');
        $sessionTenantId = (string) (Session::get('tenant_id') ?? Session::get('org_id') ?? '');
        $userMask = (int) (Session::get('role_mask') ?? 0);

        if (($userMask & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN) {
            return $requestedOrgId ?: $sessionTenantId;
        }

        if ($requestedOrgId === '') {
            $requestedOrgId = $sessionTenantId;
        }

        if ($sessionTenantId !== '' && $requestedOrgId !== '' && $requestedOrgId !== $sessionTenantId) {
            http_response_code(403);
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Forbidden', 'message' => 'Cross-tenant access prohibited']);
                exit;
            }
            require_once __DIR__ . '/../Views/errors/403.php';
            exit;
        }

        return $requestedOrgId;
    }

    /**
     * List all menu items for the tenant.
     */
    public function index(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([
            RoleMiddleware::ROLE_ADMIN,
            RoleMiddleware::ROLE_FRONTOFHOUSE,
            RoleMiddleware::ROLE_KITCHEN
        ]);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $stmt = Database::executeQuery(
            'SELECT * FROM menu_items WHERE org_id = :org_id AND archived_at IS NULL ORDER BY name ASC',
            ['org_id' => $orgId]
        );
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $item['price_centavos'] = (int) $item['price_centavos'];
            $item['is_available'] = (int) $item['is_available'];
        }
        unset($item);

        if ($this->isJsonRequest()) {
            $response->json([
                'org_id' => $orgId,
                'data' => $items,
                'count' => count($items)
            ], 200);
            return;
        }

        $title = 'Menu & 86-List';
        require_once __DIR__ . '/../Views/admin/menu_86.php';
    }

    /**
     * List currently 86-ed (unavailable) menu items.
     * Accessible by Admin, FrontOfHouse, and Kitchen.
     */
    public function list86(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([
            RoleMiddleware::ROLE_ADMIN,
            RoleMiddleware::ROLE_FRONTOFHOUSE,
            RoleMiddleware::ROLE_KITCHEN
        ]);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $stmt = Database::executeQuery(
            'SELECT * FROM menu_items WHERE org_id = :org_id AND is_available = 0 AND archived_at IS NULL ORDER BY name ASC',
            ['org_id' => $orgId]
        );
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $item['price_centavos'] = (int) $item['price_centavos'];
            $item['is_available'] = (int) $item['is_available'];
        }
        unset($item);

        if ($this->isJsonRequest()) {
            $response->json([
                'org_id' => $orgId,
                'data' => $items,
                'count' => count($items)
            ], 200);
            return;
        }

        $title = '86-List';
        require_once __DIR__ . '/../Views/admin/menu_86.php';
    }

    /**
     * Toggle 86 status for a menu item.
     * Authorized for Kitchen and Admin only. FrontOfHouse cannot toggle 86 status.
     */
    public function toggle86(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_KITCHEN]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $itemId = (string) ($params['item_id'] ?? $request->getParam('item_id') ?? '');
        $body = $request->getBody();
        $reason = trim((string) ($body['reason'] ?? ''));

        $stmt = Database::executeQuery(
            'SELECT * FROM menu_items WHERE id = :id AND org_id = :org_id AND archived_at IS NULL',
            ['id' => $itemId, 'org_id' => $orgId]
        );
        $item = $stmt->fetch();

        if (!$item) {
            $response->json(['error' => 'Menu item not found'], 404);
            return;
        }

        $newStatus = ((int) $item['is_available'] === 1) ? 0 : 1;

        Database::executeQuery(
            'UPDATE menu_items SET is_available = :status WHERE id = :id AND org_id = :org_id',
            ['status' => $newStatus, 'id' => $itemId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'TOGGLE_86', 'menu_items', $itemId, json_encode([
            'before' => (int) $item['is_available'],
            'after' => $newStatus,
            'reason' => $reason
        ]));

        $response->json([
            'id' => $itemId,
            'name' => $item['name'],
            'is_available' => $newStatus,
            'status_label' => ($newStatus === 1 ? 'available' : '86')
        ], 200);
    }

    /**
     * Store new menu item.
     */
    public function store(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $body = $request->getBody();

        $name = trim((string) ($body['name'] ?? ''));
        $description = (string) ($body['description'] ?? '');
        $priceCentavos = (int) ($body['price_centavos'] ?? 0);
        $dietaryTags = (string) ($body['dietary_tags'] ?? '');
        $allergenTags = (string) ($body['allergen_tags'] ?? '');

        $fields = [];
        if ($name === '' || strlen($name) > 255) {
            $fields['name'] = 'Item name is required and must not exceed 255 characters';
        }
        if ($priceCentavos < 0) {
            $fields['price_centavos'] = 'Price in centavos must be non-negative';
        }

        if (!empty($fields)) {
            $response->json(['error' => 'Validation failed', 'fields' => $fields], 422);
            return;
        }

        $itemId = 'item-' . bin2hex(random_bytes(8));
        Database::executeQuery(
            'INSERT INTO menu_items (id, org_id, name, description, price_centavos, is_available, dietary_tags, allergen_tags, created_at)
             VALUES (:id, :org_id, :name, :desc, :price, 1, :dietary, :allergens, CURRENT_TIMESTAMP)',
            [
                'id' => $itemId,
                'org_id' => $orgId,
                'name' => $name,
                'desc' => $description,
                'price' => $priceCentavos,
                'dietary' => $dietaryTags,
                'allergens' => $allergenTags
            ]
        );

        Audit::record($orgId, 'CREATE', 'menu_items', $itemId, json_encode([
            'name' => $name,
            'price_centavos' => $priceCentavos
        ]));

        $response->json([
            'id' => $itemId,
            'org_id' => $orgId,
            'name' => $name,
            'price_centavos' => $priceCentavos,
            'is_available' => 1
        ], 201);
    }
}
