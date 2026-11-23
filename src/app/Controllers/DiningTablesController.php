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
 * Dining Tables Controller: Table configuration, floor plan positioning, and occupancy status.
 *
 * Implements table management per docs/SPECIFICATION.md Section 2.2.B Subdomain 1.
 */
class DiningTablesController
{
    private const SORT_ALLOWLIST = [
        'code' => 't.code',
        'label' => 't.label',
        'capacity' => 't.capacity',
        'status' => 't.status',
        'zone_id' => 't.zone_id',
        'created_at' => 't.created_at',
    ];

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
     * List all dining tables with zone association and current occupancy state.
     */
    public function index(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $q = trim((string) ($request->getParam('q') ?? ''));
        $sort = (string) ($_GET['sort'] ?? $request->getParam('sort') ?? '');
        $dir = strtoupper((string) ($_GET['dir'] ?? $request->getParam('dir') ?? 'ASC'));
        $sortDir = ($dir === 'DESC') ? 'DESC' : 'ASC';
        $sortCol = self::SORT_ALLOWLIST[$sort] ?? null;
        $orderBy = $sortCol !== null ? "{$sortCol} {$sortDir}" : 't.zone_id ASC, t.label ASC';

        $where = ['t.org_id = :org_id', 't.archived_at IS NULL'];
        $bindings = ['org_id' => $orgId];
        if ($q !== '') {
            $where[] = '(t.label LIKE :q OR z.name LIKE :q)';
            $bindings['q'] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
        }
        $whereSql = implode(' AND ', $where);

        $tablesStmt = Database::executeQuery(
            "SELECT t.*, z.name as zone_name
             FROM (SELECT dt.*, dt.label as code, '' as status FROM dining_tables dt) t
             LEFT JOIN dining_zones z ON t.zone_id = z.id
             WHERE {$whereSql}
             ORDER BY {$orderBy}",
            $bindings
        );
        $tables = $tablesStmt->fetchAll();

        $zones = Database::executeQuery(
            'SELECT id, name FROM dining_zones WHERE org_id = :org_id AND archived_at IS NULL ORDER BY name ASC',
            ['org_id' => $orgId]
        )->fetchAll();

        $now = gmdate('Y-m-d H:i:s');
        $activeResStmt = Database::executeQuery(
            'SELECT rt.table_id, r.status
             FROM reservation_tables rt
             JOIN reservations r ON rt.reservation_id = r.id
             WHERE r.org_id = :org_id
               AND r.status IN ("confirmed", "seated")
               AND r.start_at <= :now
               AND r.end_at >= :now',
            ['org_id' => $orgId, 'now' => $now]
        );
        $activeMap = [];
        foreach ($activeResStmt->fetchAll() as $row) {
            $activeMap[$row['table_id']] = $row['status'];
        }

        foreach ($tables as &$table) {
            $tableId = $table['id'];
            if (isset($activeMap[$tableId])) {
                $table['occupancy_status'] = $activeMap[$tableId] === 'seated' ? 'occupied' : 'reserved';
                $table['status_class'] = $activeMap[$tableId] === 'seated' ? 'status-occupied' : 'status-reserved';
            } else {
                $table['occupancy_status'] = 'available';
                $table['status_class'] = 'status-available';
            }
            $table['x_coord'] = (int) $table['x_coord'];
            $table['y_coord'] = (int) $table['y_coord'];
            $table['capacity'] = (int) $table['capacity'];
            $table['is_combinable'] = (int) $table['is_combinable'];
        }
        unset($table);

        if ($this->isJsonRequest()) {
            $response->json([
                'org_id' => $orgId,
                'data' => $tables,
                'count' => count($tables)
            ], 200);
            return;
        }

        $title = 'Dining Tables & Floor Plan';
        require_once __DIR__ . '/../Views/admin/tables.php';
    }

    /**
     * Show create table form or schema.
     */
    public function create(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $zones = Database::executeQuery(
            'SELECT id, name FROM dining_zones WHERE org_id = :org_id AND archived_at IS NULL ORDER BY name ASC',
            ['org_id' => $orgId]
        )->fetchAll();

        if ($this->isJsonRequest()) {
            $response->json([
                'fields' => [
                    'label' => 'string (1-64 chars, required)',
                    'capacity' => 'integer (1-20, required)',
                    'zone_id' => 'string (optional zone ID)',
                    'is_combinable' => 'boolean/integer (0 or 1, default 0)',
                    'x_coord' => 'integer (0-11, default 0)',
                    'y_coord' => 'integer (0-7, default 0)'
                ],
                'available_zones' => $zones
            ], 200);
            return;
        }

        $title = 'Create Dining Table';
        $table = ['id' => '', 'label' => '', 'capacity' => 4, 'zone_id' => '', 'is_combinable' => 0, 'x_coord' => 0, 'y_coord' => 0];
        require_once __DIR__ . '/../Views/admin/tables.php';
    }

    /**
     * Store a newly created dining table.
     */
    public function store(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $body = $request->getBody();

        $label = trim((string) ($body['label'] ?? $body['table_number'] ?? ''));
        $capacity = (int) ($body['capacity'] ?? 0);
        $zoneId = !empty($body['zone_id']) ? (string) $body['zone_id'] : null;
        $isCombinable = !empty($body['is_combinable']) ? 1 : 0;
        $xCoord = isset($body['x_coord']) ? (int) $body['x_coord'] : 0;
        $yCoord = isset($body['y_coord']) ? (int) $body['y_coord'] : 0;

        $fields = [];
        if ($label === '' || strlen($label) > 64) {
            $fields['label'] = 'Table label is required and must not exceed 64 characters';
        }
        if ($capacity < 1 || $capacity > 20) {
            $fields['capacity'] = 'Table capacity must be between 1 and 20';
        }
        if ($xCoord < 0 || $xCoord > 11) {
            $fields['x_coord'] = 'Grid column coordinate must be between 0 and 11';
        }
        if ($yCoord < 0 || $yCoord > 7) {
            $fields['y_coord'] = 'Grid row coordinate must be between 0 and 7';
        }

        if ($zoneId !== null) {
            $zoneCheck = Database::executeQuery(
                'SELECT id FROM dining_zones WHERE id = :id AND org_id = :org_id AND archived_at IS NULL',
                ['id' => $zoneId, 'org_id' => $orgId]
            )->fetch();
            if (!$zoneCheck) {
                $fields['zone_id'] = 'Selected dining zone does not exist in this organization';
            }
        }

        if (!empty($fields)) {
            $response->json(['error' => 'Validation failed', 'fields' => $fields], 422);
            return;
        }

        $tableId = 'tbl-' . bin2hex(random_bytes(8));
        Database::executeQuery(
            'INSERT INTO dining_tables (id, org_id, zone_id, label, capacity, x_coord, y_coord, is_combinable, created_at)
             VALUES (:id, :org_id, :zone_id, :label, :capacity, :x, :y, :combinable, CURRENT_TIMESTAMP)',
            [
                'id' => $tableId,
                'org_id' => $orgId,
                'zone_id' => $zoneId,
                'label' => $label,
                'capacity' => $capacity,
                'x' => $xCoord,
                'y' => $yCoord,
                'combinable' => $isCombinable
            ]
        );

        Audit::record($orgId, 'CREATE', 'dining_tables', $tableId, json_encode([
            'label' => $label,
            'capacity' => $capacity,
            'zone_id' => $zoneId,
            'x_coord' => $xCoord,
            'y_coord' => $yCoord,
            'is_combinable' => $isCombinable
        ]));

        if (!$this->isJsonRequest()) {
            $response->redirect('/admin/tables');
            return;
        }

        $response->setHeader('Location', "/admin/restaurants/{$orgId}/tables/{$tableId}");
        $response->json([
            'id' => $tableId,
            'org_id' => $orgId,
            'zone_id' => $zoneId,
            'label' => $label,
            'capacity' => $capacity,
            'x_coord' => $xCoord,
            'y_coord' => $yCoord,
            'is_combinable' => $isCombinable,
            'created_at' => gmdate('Y-m-d H:i:s')
        ], 201);
    }

    /**
     * Show edit table form or fetch table JSON.
     */
    public function edit(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $tableId = (string) ($params['table_id'] ?? $request->getParam('table_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM dining_tables WHERE id = :id AND org_id = :org_id AND archived_at IS NULL',
            ['id' => $tableId, 'org_id' => $orgId]
        );
        $table = $stmt->fetch();

        if (!$table) {
            $response->json(['error' => 'Dining table not found'], 404);
            return;
        }

        if ($this->isJsonRequest()) {
            $response->json(['data' => $table], 200);
            return;
        }

        $zones = Database::executeQuery(
            'SELECT id, name FROM dining_zones WHERE org_id = :org_id AND archived_at IS NULL ORDER BY name ASC',
            ['org_id' => $orgId]
        )->fetchAll();

        $title = 'Edit Dining Table';
        require_once __DIR__ . '/../Views/admin/tables.php';
    }

    /**
     * Update dining table attributes.
     */
    public function update(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $tableId = (string) ($params['table_id'] ?? $request->getParam('table_id') ?? '');
        $body = $request->getBody();

        $stmt = Database::executeQuery(
            'SELECT * FROM dining_tables WHERE id = :id AND org_id = :org_id AND archived_at IS NULL',
            ['id' => $tableId, 'org_id' => $orgId]
        );
        $existing = $stmt->fetch();

        if (!$existing) {
            $response->json(['error' => 'Dining table not found'], 404);
            return;
        }

        $label = trim((string) ($body['label'] ?? $body['table_number'] ?? $existing['label']));
        $capacity = isset($body['capacity']) ? (int) $body['capacity'] : (int) $existing['capacity'];
        $zoneId = array_key_exists('zone_id', $body) ? ($body['zone_id'] ?: null) : $existing['zone_id'];
        $isCombinable = isset($body['is_combinable']) ? ((int) $body['is_combinable'] ? 1 : 0) : (int) $existing['is_combinable'];
        $xCoord = isset($body['x_coord']) ? (int) $body['x_coord'] : (int) $existing['x_coord'];
        $yCoord = isset($body['y_coord']) ? (int) $body['y_coord'] : (int) $existing['y_coord'];

        $fields = [];
        if ($label === '' || strlen($label) > 64) {
            $fields['label'] = 'Table label is required and must not exceed 64 characters';
        }
        if ($capacity < 1 || $capacity > 20) {
            $fields['capacity'] = 'Table capacity must be between 1 and 20';
        }
        if ($xCoord < 0 || $xCoord > 11) {
            $fields['x_coord'] = 'Grid column coordinate must be between 0 and 11';
        }
        if ($yCoord < 0 || $yCoord > 7) {
            $fields['y_coord'] = 'Grid row coordinate must be between 0 and 7';
        }

        if (!empty($fields)) {
            $response->json(['error' => 'Validation failed', 'fields' => $fields], 422);
            return;
        }

        // Mutation guard: prevent archiving if future active reservations exist
        if (!empty($body['archived']) || !empty($body['is_archived']) || !empty($body['archived_at'])) {
            $now = gmdate('Y-m-d H:i:s');
            $activeResStmt = Database::executeQuery(
                'SELECT COUNT(*) as cnt FROM reservations r
                 JOIN reservation_tables rt ON r.id = rt.reservation_id
                 WHERE rt.table_id = :table_id AND r.org_id = :org_id
                   AND r.end_at > :now
                   AND r.status IN (\'pending\', \'confirmed\', \'seated\')',
                ['table_id' => $tableId, 'org_id' => $orgId, 'now' => $now]
            );
            $resCount = (int) ($activeResStmt->fetch()['cnt'] ?? 0);
            if ($resCount > 0) {
                $response->json(['error' => 'Cannot archive table: active future reservations exist.'], 422);
                return;
            }
        }

        // Mutation guard: prevent reducing table capacity below party size of active future bookings
        if ($capacity < (int) $existing['capacity']) {
            $now = gmdate('Y-m-d H:i:s');
            $capCheckStmt = Database::executeQuery(
                'SELECT COUNT(*) as cnt FROM reservations r
                 JOIN reservation_tables rt ON r.id = rt.reservation_id
                 WHERE rt.table_id = :table_id AND r.org_id = :org_id
                   AND r.end_at > :now
                   AND r.status IN (\'pending\', \'confirmed\', \'seated\')
                   AND r.party_size > :capacity',
                ['table_id' => $tableId, 'org_id' => $orgId, 'now' => $now, 'capacity' => $capacity]
            );
            $capConflict = (int) ($capCheckStmt->fetch()['cnt'] ?? 0);
            if ($capConflict > 0) {
                $response->json(['error' => 'Cannot reduce table capacity below party size of active future reservations.'], 422);
                return;
            }
        }

        Database::executeQuery(
            'UPDATE dining_tables SET label = :label, capacity = :capacity, zone_id = :zone_id,
                    is_combinable = :combinable, x_coord = :x, y_coord = :y
             WHERE id = :id AND org_id = :org_id',
            [
                'id' => $tableId,
                'org_id' => $orgId,
                'label' => $label,
                'capacity' => $capacity,
                'zone_id' => $zoneId,
                'combinable' => $isCombinable,
                'x' => $xCoord,
                'y' => $yCoord
            ]
        );

        Audit::record($orgId, 'UPDATE', 'dining_tables', $tableId, json_encode([
            'before' => $existing,
            'after' => ['label' => $label, 'capacity' => $capacity, 'zone_id' => $zoneId, 'x_coord' => $xCoord, 'y_coord' => $yCoord]
        ]));

        if (!$this->isJsonRequest()) {
            $response->redirect('/admin/tables');
            return;
        }

        $response->json(['status' => 'updated', 'id' => $tableId], 200);
    }

    /**
     * Soft-delete dining table.
     */
    public function delete(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $tableId = (string) ($params['table_id'] ?? $request->getParam('table_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM dining_tables WHERE id = :id AND org_id = :org_id AND archived_at IS NULL',
            ['id' => $tableId, 'org_id' => $orgId]
        );
        $existing = $stmt->fetch();

        if (!$existing) {
            $response->json(['error' => 'Dining table not found'], 404);
            return;
        }

        // Mutation guard: block archive if active future reservations exist
        $now = gmdate('Y-m-d H:i:s');
        $activeResStmt = Database::executeQuery(
            'SELECT COUNT(*) as cnt FROM reservations r
             JOIN reservation_tables rt ON r.id = rt.reservation_id
             WHERE rt.table_id = :table_id AND r.org_id = :org_id
               AND r.end_at > :now
               AND r.status IN (\'pending\', \'confirmed\', \'seated\')',
            ['table_id' => $tableId, 'org_id' => $orgId, 'now' => $now]
        );
        $resCount = (int) ($activeResStmt->fetch()['cnt'] ?? 0);
        if ($resCount > 0) {
            $response->json(['error' => 'Cannot archive table: active future reservations exist.'], 422);
            return;
        }

        Database::executeQuery(
            'UPDATE dining_tables SET archived_at = :archived_at WHERE id = :id AND org_id = :org_id',
            ['archived_at' => $now, 'id' => $tableId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'DELETE', 'dining_tables', $tableId, json_encode(['archived_at' => $now]));

        if (!$this->isJsonRequest()) {
            $response->redirect('/admin/tables');
            return;
        }

        $response->json(['status' => 'deleted', 'id' => $tableId], 200);
    }

    /**
     * Archive table alias (per mutation guard contract).
     */
    public function archive(Request $request, Response $response, array $params = []): void
    {
        $this->delete($request, $response, $params);
    }

    /**
     * Get table layout coordinates.
     */
    public function getCoordinates(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $tableId = (string) ($params['table_id'] ?? $request->getParam('table_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT id, label, x_coord, y_coord, zone_id FROM dining_tables WHERE id = :id AND org_id = :org_id AND archived_at IS NULL',
            ['id' => $tableId, 'org_id' => $orgId]
        );
        $table = $stmt->fetch();

        if (!$table) {
            $response->json(['error' => 'Dining table not found'], 404);
            return;
        }

        $response->json([
            'id' => $table['id'],
            'label' => $table['label'],
            'x_coord' => (int) $table['x_coord'],
            'y_coord' => (int) $table['y_coord'],
            'zone_id' => $table['zone_id']
        ], 200);
    }

    /**
     * Update table layout coordinates (drag-drop repositioning).
     */
    public function updateCoordinates(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $tableId = (string) ($params['table_id'] ?? $request->getParam('table_id') ?? '');
        $body = $request->getBody();

        $xCoord = (int) ($body['x_coord'] ?? -1);
        $yCoord = (int) ($body['y_coord'] ?? -1);

        if ($xCoord < 0 || $xCoord > 11 || $yCoord < 0 || $yCoord > 7) {
            $response->json(['error' => 'Invalid coordinates. Must be 0 <= x <= 11 and 0 <= y <= 7'], 422);
            return;
        }

        Database::executeQuery(
            'UPDATE dining_tables SET x_coord = :x, y_coord = :y WHERE id = :id AND org_id = :org_id AND archived_at IS NULL',
            ['id' => $tableId, 'org_id' => $orgId, 'x' => $xCoord, 'y' => $yCoord]
        );

        Audit::record($orgId, 'UPDATE_COORDINATES', 'dining_tables', $tableId, json_encode(['x_coord' => $xCoord, 'y_coord' => $yCoord]));

        $response->json([
            'id' => $tableId,
            'x_coord' => $xCoord,
            'y_coord' => $yCoord,
            'status' => 'updated'
        ], 200);
    }
}
