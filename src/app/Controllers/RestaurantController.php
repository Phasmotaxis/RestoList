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
 * Restaurant Controller: Organization & Restaurant provisioning and tenant profile settings.
 *
 * Implements base restaurant operations for Userspace 2 per docs/SPECIFICATION.md Section 2.2.B.
 */
class RestaurantController
{
    private function isJsonRequest(): bool
    {
        return (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
            || (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
            || (isset($_GET['format']) && $_GET['format'] === 'json');
    }

    /**
     * List all restaurants (SuperAdmin sees all, Admin sees their own).
     */
    public function index(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_SUPERADMIN, RoleMiddleware::ROLE_ADMIN]);
        $userMask = (int) (Session::get('role_mask') ?? 0);
        $sessionTenantId = (string) (Session::get('tenant_id') ?? Session::get('org_id') ?? '');

        if (($userMask & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN) {
            $stmt = Database::executeQuery('SELECT * FROM tenants ORDER BY name ASC');
        } else {
            $stmt = Database::executeQuery('SELECT * FROM tenants WHERE id = :id', ['id' => $sessionTenantId]);
        }
        $restaurants = $stmt->fetchAll();

        if ($this->isJsonRequest()) {
            $response->json(['data' => $restaurants, 'count' => count($restaurants)], 200);
            return;
        }

        $title = 'Restaurants';
        require_once __DIR__ . '/../Views/superadmin/organizations.php';
    }

    /**
     * Show create restaurant form or schema.
     */
    public function create(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);

        if ($this->isJsonRequest()) {
            $response->json([
                'fields' => [
                    'name' => 'string (1-255 chars, required)',
                    'subdomain' => 'string (1-64 chars, lowercase alphanumeric, required)'
                ]
            ], 200);
            return;
        }

        $title = 'Create Restaurant';
        require_once __DIR__ . '/../Views/superadmin/organizations.php';
    }

    /**
     * Store new restaurant / tenant organization.
     */
    public function store(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);
        $body = $request->getBody();

        $name = trim((string) ($body['name'] ?? ''));
        $subdomain = strtolower(trim((string) ($body['subdomain'] ?? '')));

        if ($subdomain === '' && $name !== '') {
            $subdomain = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
            $subdomain = trim($subdomain, '-');
        }

        $fields = [];
        if ($name === '' || strlen($name) > 255) {
            $fields['name'] = 'Restaurant name is required and must not exceed 255 characters';
        }
        if ($subdomain === '' || strlen($subdomain) > 64 || !preg_match('/^[a-z0-9-]+$/', $subdomain)) {
            $fields['subdomain'] = 'Subdomain must be 1-64 alphanumeric characters and hyphens';
        }

        if (!empty($fields)) {
            $response->json(['error' => 'Validation failed', 'fields' => $fields], 422);
            return;
        }

        $existing = Database::executeQuery('SELECT id FROM tenants WHERE subdomain = :sub', ['sub' => $subdomain])->fetch();
        if ($existing) {
            $response->json(['error' => 'Validation failed', 'fields' => ['subdomain' => 'Subdomain is already registered']], 422);
            return;
        }

        $id = 'tenant-' . bin2hex(random_bytes(8));
        Database::executeQuery(
            'INSERT INTO tenants (id, name, subdomain, is_active, created_at, updated_at)
             VALUES (:id, :name, :sub, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
            ['id' => $id, 'name' => $name, 'sub' => $subdomain]
        );

        Audit::record($id, 'CREATE', 'tenants', $id, json_encode(['name' => $name, 'subdomain' => $subdomain]));

        $response->setHeader('Location', "/admin/restaurants/{$id}");
        $response->json([
            'id' => $id,
            'name' => $name,
            'subdomain' => $subdomain,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ], 201);
    }

    /**
     * Show restaurant details and overview.
     */
    public function show(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_SUPERADMIN, RoleMiddleware::ROLE_ADMIN]);
        $id = (string) ($params['id'] ?? $request->getParam('id') ?? Session::get('tenant_id') ?? '');

        $stmt = Database::executeQuery('SELECT * FROM tenants WHERE id = :id', ['id' => $id]);
        $restaurant = $stmt->fetch();

        if (!$restaurant) {
            $response->json(['error' => 'Restaurant not found'], 404);
            return;
        }

        $tableCount = (int) (Database::executeQuery(
            'SELECT COUNT(*) as cnt FROM dining_tables WHERE org_id = :id AND archived_at IS NULL',
            ['id' => $id]
        )->fetch()['cnt'] ?? 0);

        $zoneCount = (int) (Database::executeQuery(
            'SELECT COUNT(*) as cnt FROM dining_zones WHERE org_id = :id AND archived_at IS NULL',
            ['id' => $id]
        )->fetch()['cnt'] ?? 0);

        $response->json([
            'restaurant' => $restaurant,
            'stats' => [
                'tables' => $tableCount,
                'zones' => $zoneCount
            ]
        ], 200);
    }

    /**
     * Edit restaurant form.
     */
    public function edit(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_SUPERADMIN, RoleMiddleware::ROLE_ADMIN]);
        $id = (string) ($params['id'] ?? $request->getParam('id') ?? Session::get('tenant_id') ?? '');

        $stmt = Database::executeQuery('SELECT * FROM tenants WHERE id = :id', ['id' => $id]);
        $restaurant = $stmt->fetch();

        if (!$restaurant) {
            $response->json(['error' => 'Restaurant not found'], 404);
            return;
        }

        $response->json(['data' => $restaurant], 200);
    }

    /**
     * Update restaurant details.
     */
    public function update(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_SUPERADMIN, RoleMiddleware::ROLE_ADMIN]);
        $id = (string) ($params['id'] ?? $request->getParam('id') ?? Session::get('tenant_id') ?? '');
        $body = $request->getBody();

        $stmt = Database::executeQuery('SELECT * FROM tenants WHERE id = :id', ['id' => $id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            $response->json(['error' => 'Restaurant not found'], 404);
            return;
        }

        $name = trim((string) ($body['name'] ?? $existing['name']));
        Database::executeQuery(
            'UPDATE tenants SET name = :name, updated_at = CURRENT_TIMESTAMP WHERE id = :id',
            ['name' => $name, 'id' => $id]
        );

        Audit::record($id, 'UPDATE', 'tenants', $id, json_encode(['name' => $name]));

        $response->json(['status' => 'updated', 'id' => $id, 'name' => $name], 200);
    }

    /**
     * Delete / deactivate restaurant.
     */
    public function delete(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);
        $id = (string) ($params['id'] ?? $request->getParam('id') ?? '');

        Database::executeQuery(
            'UPDATE tenants SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id',
            ['id' => $id]
        );

        Audit::record($id, 'DEACTIVATE', 'tenants', $id, 'Restaurant deactivated');

        $response->json(['status' => 'deactivated', 'id' => $id], 200);
    }
}
