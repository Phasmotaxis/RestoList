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
use App\Services\FeatureFlagService;

/**
 * Dining Zones Controller: Floor plan zone management.
 *
 * Implements zone configuration for Restaurant ERP per docs/SPECIFICATION.md Section 2.2.B.
 * Scoped by multi-tenant organization boundary with role-based access control.
 */
class DiningZonesController
{
    private function isJsonRequest(): bool
    {
        if (php_sapi_name() === 'cli') {
            return true;
        }
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
     * List dining zones for the tenant.
     * Accessible by Admin and FrontOfHouse.
     */
    public function index(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $stmt = Database::executeQuery(
            'SELECT z.*, COUNT(t.id) as table_count, COALESCE(SUM(t.capacity), 0) as total_capacity
             FROM dining_zones z
             LEFT JOIN dining_tables t ON z.id = t.zone_id AND t.archived_at IS NULL
             WHERE z.org_id = :org_id AND z.archived_at IS NULL
             GROUP BY z.id
             ORDER BY z.created_at ASC',
            ['org_id' => $orgId]
        );
        $zones = $stmt->fetchAll();

        if ($this->isJsonRequest()) {
            $response->json([
                'org_id' => $orgId,
                'data' => $zones,
                'count' => count($zones)
            ], 200);
            return;
        }

        $title = 'Dining Zones';
        require_once __DIR__ . '/../Views/admin/zones.php';
    }

    /**
     * Show create zone form or return schema.
     */
    public function create(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        if ($this->isJsonRequest()) {
            $response->json([
                'fields' => [
                    'name' => 'string (1-128 chars, required)',
                    'capacity' => 'integer (non-negative, default 0)'
                ]
            ], 200);
            return;
        }

        $title = 'Create Dining Zone';
        $zone = ['id' => '', 'name' => '', 'capacity' => 0];
        require_once __DIR__ . '/../Views/admin/zones.php';
    }

    /**
     * Store new dining zone.
     */
    public function store(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $body = $request->getBody();

        $name = trim((string) ($body['name'] ?? ''));
        $capacity = isset($body['capacity']) ? (int) $body['capacity'] : 0;

        $fields = [];
        if ($name === '' || strlen($name) > 128) {
            $fields['name'] = 'Zone name must be between 1 and 128 characters';
        }
        if ($capacity < 0) {
            $fields['capacity'] = 'Capacity must be non-negative';
        }

        if (!empty($fields)) {
            $response->json(['error' => 'Validation failed', 'fields' => $fields], 422);
            return;
        }

        $zoneId = 'zone-' . bin2hex(random_bytes(8));
        Database::executeQuery(
            'INSERT INTO dining_zones (id, org_id, name, capacity, created_at) VALUES (:id, :org_id, :name, :capacity, CURRENT_TIMESTAMP)',
            [
                'id' => $zoneId,
                'org_id' => $orgId,
                'name' => $name,
                'capacity' => $capacity
            ]
        );

        Audit::record($orgId, 'CREATE', 'dining_zones', $zoneId, json_encode(['name' => $name, 'capacity' => $capacity]));

        if (!$this->isJsonRequest()) {
            $response->redirect('/admin/zones');
            return;
        }

        $response->setHeader('Location', "/admin/restaurants/{$orgId}/zones/{$zoneId}");
        $response->json([
            'id' => $zoneId,
            'org_id' => $orgId,
            'name' => $name,
            'capacity' => $capacity,
            'created_at' => date('Y-m-d H:i:s')
        ], 201);
    }

    /**
     * Show edit zone form or fetch zone details.
     */
    public function edit(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $zoneId = (string) ($params['zone_id'] ?? $request->getParam('zone_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM dining_zones WHERE id = :id AND org_id = :org_id AND archived_at IS NULL',
            ['id' => $zoneId, 'org_id' => $orgId]
        );
        $zone = $stmt->fetch();

        if (!$zone) {
            $response->json(['error' => 'Dining zone not found'], 404);
            return;
        }

        if ($this->isJsonRequest()) {
            $response->json(['data' => $zone], 200);
            return;
        }

        $title = 'Edit Dining Zone';
        require_once __DIR__ . '/../Views/admin/zones.php';
    }

    /**
     * Update existing dining zone.
     */
    public function update(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $zoneId = (string) ($params['zone_id'] ?? $request->getParam('zone_id') ?? '');
        $body = $request->getBody();

        $stmt = Database::executeQuery(
            'SELECT * FROM dining_zones WHERE id = :id AND org_id = :org_id AND archived_at IS NULL',
            ['id' => $zoneId, 'org_id' => $orgId]
        );
        $existing = $stmt->fetch();

        if (!$existing) {
            $response->json(['error' => 'Dining zone not found'], 404);
            return;
        }

        $name = trim((string) ($body['name'] ?? $existing['name']));
        $capacity = isset($body['capacity']) ? (int) $body['capacity'] : (int) $existing['capacity'];

        $fields = [];
        if ($name === '' || strlen($name) > 128) {
            $fields['name'] = 'Zone name must be between 1 and 128 characters';
        }
        if ($capacity < 0) {
            $fields['capacity'] = 'Capacity must be non-negative';
        }

        if (!empty($fields)) {
            $response->json(['error' => 'Validation failed', 'fields' => $fields], 422);
            return;
        }

        Database::executeQuery(
            'UPDATE dining_zones SET name = :name, capacity = :capacity WHERE id = :id AND org_id = :org_id',
            [
                'id' => $zoneId,
                'org_id' => $orgId,
                'name' => $name,
                'capacity' => $capacity
            ]
        );

        Audit::record($orgId, 'UPDATE', 'dining_zones', $zoneId, json_encode([
            'before' => ['name' => $existing['name'], 'capacity' => $existing['capacity']],
            'after' => ['name' => $name, 'capacity' => $capacity]
        ]));

        if (!$this->isJsonRequest()) {
            $response->redirect('/admin/zones');
            return;
        }

        $response->json([
            'id' => $zoneId,
            'org_id' => $orgId,
            'name' => $name,
            'capacity' => $capacity,
            'status' => 'updated'
        ], 200);
    }

    /**
     * Soft-delete dining zone.
     */
    public function delete(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $zoneId = (string) ($params['zone_id'] ?? $request->getParam('zone_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM dining_zones WHERE id = :id AND org_id = :org_id AND archived_at IS NULL',
            ['id' => $zoneId, 'org_id' => $orgId]
        );
        $existing = $stmt->fetch();

        if (!$existing) {
            $response->json(['error' => 'Dining zone not found'], 404);
            return;
        }

        Database::executeQuery(
            'UPDATE dining_zones SET archived_at = CURRENT_TIMESTAMP WHERE id = :id AND org_id = :org_id',
            ['id' => $zoneId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'DELETE', 'dining_zones', $zoneId, json_encode(['archived_at' => date('Y-m-d H:i:s')]));

        if (!$this->isJsonRequest()) {
            $response->redirect('/admin/zones');
            return;
        }

        $response->json(['status' => 'deleted', 'id' => $zoneId], 200);
    }
}
