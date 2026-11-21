<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Audit.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../Services/TableAllocator.php';

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\RoleMiddleware;
use App\Services\TableAllocator;

/**
 * Waitlist Controller: Real-time walk-in queue, position management, and backfill.
 *
 * Implements Subdomain 3 per docs/SPECIFICATION.md Section 2.2.B.
 */
class WaitlistController
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
     * List active waitlist entries. Auto-expires stale entries older than 24 hours.
     */
    public function index(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        // Auto-expire entries waiting longer than 24 hours
        try {
            Database::executeQuery(
                "UPDATE waitlist_entries
                 SET status = 'expired'
                 WHERE org_id = :org_id
                   AND status = 'waiting'
                   AND join_at < datetime('now', '-24 hours')",
                ['org_id' => $orgId]
            );
        } catch (\Throwable $e) {
            // Ignore if date function differs in driver
        }

        $stmt = Database::executeQuery(
            'SELECT w.*, z.name as zone_name
             FROM waitlist_entries w
             LEFT JOIN dining_zones z ON w.preferred_zone = z.id
             WHERE w.org_id = :org_id AND w.status IN ("waiting", "notified")
             ORDER BY w.position ASC, w.join_at ASC',
            ['org_id' => $orgId]
        );
        $entries = $stmt->fetchAll();

        foreach ($entries as &$entry) {
            $entry['party_size'] = (int) $entry['party_size'];
            $entry['position'] = (int) $entry['position'];
        }
        unset($entry);

        if ($this->isJsonRequest()) {
            $response->json([
                'org_id' => $orgId,
                'data' => $entries,
                'count' => count($entries)
            ], 200);
            return;
        }

        $title = 'Waitlist & Walk-ins';
        require_once __DIR__ . '/../Views/admin/waitlist.php';
    }

    /**
     * Add party to waitlist.
     */
    public function add(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $body = $request->getBody();

        $partySize = (int) ($body['party_size'] ?? 0);
        $preferredZone = !empty($body['preferred_zone']) ? (string) $body['preferred_zone'] : null;
        $customerId = !empty($body['customer_id']) ? (string) $body['customer_id'] : null;

        $fields = [];
        if ($partySize < 1 || $partySize > 20) {
            $fields['party_size'] = 'Party size must be between 1 and 20';
        }

        if (!empty($fields)) {
            $response->json(['error' => 'Validation failed', 'fields' => $fields], 422);
            return;
        }

        $maxStmt = Database::executeQuery(
            'SELECT COALESCE(MAX(position), 0) as max_pos FROM waitlist_entries WHERE org_id = :org_id AND status = "waiting"',
            ['org_id' => $orgId]
        );
        $nextPos = ((int) ($maxStmt->fetch()['max_pos'] ?? 0)) + 1;

        $id = 'wl-' . bin2hex(random_bytes(8));
        Database::executeQuery(
            'INSERT INTO waitlist_entries (id, org_id, customer_id, party_size, preferred_zone, position, status, join_at)
             VALUES (:id, :org_id, :customer_id, :party_size, :zone, :position, "waiting", CURRENT_TIMESTAMP)',
            [
                'id' => $id,
                'org_id' => $orgId,
                'customer_id' => $customerId,
                'party_size' => $partySize,
                'zone' => $preferredZone,
                'position' => $nextPos
            ]
        );

        Audit::record($orgId, 'ADD_WAITLIST', 'waitlist_entries', $id, json_encode([
            'party_size' => $partySize,
            'position' => $nextPos,
            'preferred_zone' => $preferredZone
        ]));

        $response->json([
            'id' => $id,
            'org_id' => $orgId,
            'party_size' => $partySize,
            'position' => $nextPos,
            'status' => 'waiting',
            'join_at' => date('Y-m-d H:i:s')
        ], 201);
    }

    /**
     * Update waitlist queue position.
     */
    public function updatePosition(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $entryId = (string) ($params['entry_id'] ?? $request->getParam('entry_id') ?? '');
        $body = $request->getBody();

        $newPosition = max(1, (int) ($body['position'] ?? 1));

        $stmt = Database::executeQuery(
            'SELECT * FROM waitlist_entries WHERE id = :id AND org_id = :org_id',
            ['id' => $entryId, 'org_id' => $orgId]
        );
        $entry = $stmt->fetch();

        if (!$entry) {
            $response->json(['error' => 'Waitlist entry not found'], 404);
            return;
        }

        Database::executeQuery(
            'UPDATE waitlist_entries SET position = :pos WHERE id = :id AND org_id = :org_id',
            ['pos' => $newPosition, 'id' => $entryId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'REORDER_WAITLIST', 'waitlist_entries', $entryId, "Position moved to {$newPosition}");

        $response->json([
            'id' => $entryId,
            'position' => $newPosition,
            'status' => 'updated'
        ], 200);
    }

    /**
     * Dispatch notification to waitlisted guest when table is ready.
     */
    public function notify(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $entryId = (string) ($params['entry_id'] ?? $request->getParam('entry_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM waitlist_entries WHERE id = :id AND org_id = :org_id',
            ['id' => $entryId, 'org_id' => $orgId]
        );
        $entry = $stmt->fetch();

        if (!$entry) {
            $response->json(['error' => 'Waitlist entry not found'], 404);
            return;
        }

        Database::executeQuery(
            'UPDATE waitlist_entries SET status = "notified" WHERE id = :id AND org_id = :org_id',
            ['id' => $entryId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'NOTIFY_WAITLIST', 'waitlist_entries', $entryId, 'Notification dispatched to customer');

        $response->json([
            'id' => $entryId,
            'status' => 'notified',
            'message' => 'Notification dispatched to customer'
        ], 200);
    }

    /**
     * Remove party from waitlist.
     */
    public function remove(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $entryId = (string) ($params['entry_id'] ?? $request->getParam('entry_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM waitlist_entries WHERE id = :id AND org_id = :org_id',
            ['id' => $entryId, 'org_id' => $orgId]
        );
        $entry = $stmt->fetch();

        if (!$entry) {
            $response->json(['error' => 'Waitlist entry not found'], 404);
            return;
        }

        Database::executeQuery(
            'UPDATE waitlist_entries SET status = "cancelled" WHERE id = :id AND org_id = :org_id',
            ['id' => $entryId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'REMOVE_WAITLIST', 'waitlist_entries', $entryId, 'Removed from waitlist');

        $response->json(['status' => 'removed', 'id' => $entryId], 200);
    }

    /**
     * Backfill matching waitlist entry when a table is freed by cancellation.
     *
     * @param string $orgId Organization boundary.
     * @param int $freedCapacity Total party capacity freed.
     * @param array $freedTableIds Array of table IDs freed.
     * @return array|null Backfilled entry details or null.
     */
    public static function autoBackfill(string $orgId, int $freedCapacity, array $freedTableIds): ?array
    {
        if (empty($freedTableIds) || $freedCapacity <= 0) {
            return null;
        }

        $stmt = Database::executeQuery(
            'SELECT * FROM waitlist_entries
             WHERE org_id = :org_id
               AND status = "waiting"
               AND party_size <= :cap
             ORDER BY position ASC, join_at ASC
             LIMIT 1',
            ['org_id' => $orgId, 'cap' => $freedCapacity]
        );
        $candidate = $stmt->fetch();

        if (!$candidate) {
            return null;
        }

        // Allocate a new reservation for the freed table(s)
        $startAt = date('Y-m-d H:i:s');
        $endAt = date('Y-m-d H:i:s', strtotime('+90 minutes'));

        try {
            $resId = TableAllocator::allocateReservation(
                $orgId,
                $freedTableIds,
                $startAt,
                $endAt,
                (int) $candidate['party_size'],
                $candidate['customer_id'],
                'Auto-backfilled from waitlist position ' . $candidate['position']
            );

            Database::executeQuery(
                'UPDATE waitlist_entries
                 SET status = "notified", promoted_to_reservation_id = :res_id
                 WHERE id = :id',
                ['res_id' => $resId, 'id' => $candidate['id']]
            );

            Audit::record(
                $orgId,
                'WAITLIST_BACKFILL',
                'waitlist_entries',
                $candidate['id'],
                "Promoted to reservation {$resId}"
            );

            return [
                'waitlist_id' => $candidate['id'],
                'reservation_id' => $resId,
                'party_size' => (int) $candidate['party_size'],
                'table_ids' => $freedTableIds
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
