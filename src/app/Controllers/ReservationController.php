<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Audit.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../Services/TableAllocator.php';
require_once __DIR__ . '/../Services/FeatureFlagService.php';
require_once __DIR__ . '/../Services/DepositLedgerService.php';
require_once __DIR__ . '/../Services/LoyaltyLedgerService.php';
require_once __DIR__ . '/WaitlistController.php';

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\RoleMiddleware;
use App\Services\DepositLedgerService;
use App\Services\FeatureFlagService;
use App\Services\LoyaltyLedgerService;
use App\Services\TableAllocator;

/**
 * Reservation Controller: Booking lifecycle, 15-minute slot allocation, and cancellation.
 *
 * Implements Subdomain 2 per docs/SPECIFICATION.md Section 2.2.B and Section 4.
 */
class ReservationController
{
    private const SORT_ALLOWLIST = [
        'created_at' => 'r.created_at',
        'start_at' => 'r.start_at',
        'end_at' => 'r.end_at',
        'party_size' => 'r.party_size',
        'status' => 'r.status',
        'id' => 'r.id',
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
     * List reservations with date/status filtering and pagination.
     * Accessible by Admin, FrontOfHouse, and Kitchen (read-only for kitchen).
     */
    public function index(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([
            RoleMiddleware::ROLE_ADMIN,
            RoleMiddleware::ROLE_FRONTOFHOUSE,
            RoleMiddleware::ROLE_KITCHEN
        ]);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $date = (string) ($request->getParam('date') ?? '');
        $status = (string) ($request->getParam('status') ?? '');
        $q = trim((string) ($request->getParam('q') ?? ''));
        $page = max(1, (int) ($request->getParam('page') ?? 1));
        $perPage = min(100, max(1, (int) ($request->getParam('per_page') ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = ['r.org_id = :org_id'];
        $bindings = ['org_id' => $orgId];

        if ($date !== '') {
            $where[] = "DATE(r.start_at) = :res_date";
            $bindings['res_date'] = $date;
        }
        if ($status !== '') {
            $where[] = "r.status = :status";
            $bindings['status'] = $status;
        }
        if ($q !== '') {
            $where[] = "r.notes LIKE :q";
            $bindings['q'] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
        }

        $whereClause = implode(' AND ', $where);

        $totalStmt = Database::executeQuery(
            "SELECT COUNT(*) as total FROM reservations r WHERE {$whereClause}",
            $bindings
        );
        $total = (int) ($totalStmt->fetch()['total'] ?? 0);
        $pages = (int) ceil($total / $perPage);

        $sort = (string) ($_GET['sort'] ?? $request->getParam('sort') ?? '');
        $dir = strtoupper((string) ($_GET['dir'] ?? $request->getParam('dir') ?? 'ASC'));
        $sortDir = ($dir === 'DESC') ? 'DESC' : 'ASC';
        $sortCol = self::SORT_ALLOWLIST[$sort] ?? self::SORT_ALLOWLIST['start_at'];
        $orderBy = "{$sortCol} {$sortDir}";

        $stmt = Database::executeQuery(
            "SELECT r.*,
                    GROUP_CONCAT(t.label, ', ') as table_labels,
                    GROUP_CONCAT(t.id, ',') as table_ids
             FROM reservations r
             LEFT JOIN reservation_tables rt ON r.id = rt.reservation_id
             LEFT JOIN dining_tables t ON rt.table_id = t.id
             WHERE {$whereClause}
             GROUP BY r.id
             ORDER BY {$orderBy}
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );
        $reservations = $stmt->fetchAll();

        foreach ($reservations as &$res) {
            $res['party_size'] = (int) $res['party_size'];
            $res['table_ids'] = !empty($res['table_ids']) ? explode(',', $res['table_ids']) : [];
        }
        unset($res);

        if ($this->isJsonRequest()) {
            $response->json([
                'org_id' => $orgId,
                'data' => $reservations,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'pages' => $pages
                ]
            ], 200);
            return;
        }

        $availableTables = Database::executeQuery(
            'SELECT t.id, t.label, t.capacity, z.name as zone_name
             FROM dining_tables t
             LEFT JOIN dining_zones z ON t.zone_id = z.id
             WHERE t.org_id = :org_id AND t.archived_at IS NULL
             ORDER BY t.label ASC',
            ['org_id' => $orgId]
        )->fetchAll();

        $title = 'Reservations Management';
        require_once __DIR__ . '/../Views/admin/reservations.php';
    }

    /**
     * Show reservation creation form.
     */
    public function create(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $tables = Database::executeQuery(
            'SELECT t.id, t.label, t.capacity, z.name as zone_name
             FROM dining_tables t
             LEFT JOIN dining_zones z ON t.zone_id = z.id
             WHERE t.org_id = :org_id AND t.archived_at IS NULL
             ORDER BY t.label ASC',
            ['org_id' => $orgId]
        )->fetchAll();

        if ($this->isJsonRequest()) {
            $response->json([
                'available_tables' => $tables,
                'fields' => [
                    'table_ids' => 'array of table IDs (required)',
                    'party_size' => 'integer (1-20, required)',
                    'start_at_utc' => 'datetime string (required)',
                    'end_at_utc' => 'datetime string (optional, defaults to +90m)',
                    'notes' => 'string (optional)'
                ]
            ], 200);
            return;
        }

        $title = 'New Reservation';
        require_once __DIR__ . '/../Views/admin/reservations.php';
    }

    /**
     * Store new reservation with atomic double-booking prevention.
     */
    public function store(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $body = $request->getBody();

        $tableIds = (array) ($body['table_ids'] ?? []);
        if (empty($tableIds) && !empty($body['table_id'])) {
            $tableIds = [(string) $body['table_id']];
        }

        $partySize = (int) ($body['party_size'] ?? 0);
        $rawStart = (string) ($body['start_at_utc'] ?? $body['start_at'] ?? '');
        $rawEnd = (string) ($body['end_at_utc'] ?? $body['end_at'] ?? '');
        $notes = (string) ($body['notes'] ?? '');
        $customerId = !empty($body['customer_id']) ? (string) $body['customer_id'] : null;
        $customerEmail = trim((string) ($body['customer_email'] ?? ''));

        $fields = [];
        if (empty($tableIds)) {
            $fields['table_ids'] = 'At least one table must be selected';
        }
        if ($partySize < 1 || $partySize > 20) {
            $fields['party_size'] = 'Party size must be between 1 and 20';
        }

        if ($rawStart === '') {
            $fields['start_at_utc'] = 'Start time is required';
        }

        $startDt = null;
        $endDt = null;
        if ($rawStart !== '') {
            try {
                $startDt = new \DateTime($rawStart, new \DateTimeZone('UTC'));
                // Snap to 15-minute floor boundary
                $min = (int) $startDt->format('i');
                $startDt->setTime((int) $startDt->format('H'), (int) floor($min / 15) * 15, 0);

                if ($rawEnd !== '') {
                    $endDt = new \DateTime($rawEnd, new \DateTimeZone('UTC'));
                    $endMin = (int) $endDt->format('i');
                    $endDt->setTime((int) $endDt->format('H'), (int) ceil($endMin / 15) * 15, 0);
                } else {
                    $endDt = clone $startDt;
                    $endDt->modify('+90 minutes');
                }

                if ($endDt <= $startDt) {
                    $fields['end_at_utc'] = 'End time must be after start time';
                }
            } catch (\Throwable $e) {
                $fields['start_at_utc'] = 'Invalid datetime format for reservation';
            }
        }

        if (!empty($fields)) {
            $response->json(['error' => 'Validation failed', 'fields' => $fields], 422);
            return;
        }

        $startAt = $startDt->format('Y-m-d H:i:s');
        $endAt = $endDt->format('Y-m-d H:i:s');

        try {
            $resId = TableAllocator::allocateReservation(
                $orgId,
                $tableIds,
                $startAt,
                $endAt,
                $partySize,
                $customerId,
                $notes
            );
        } catch (\Throwable $e) {
            $response->json([
                'error' => 'Conflict: Table already reserved or double-booking detected',
                'detail' => $e->getMessage()
            ], 409);
            return;
        }

        Audit::record($orgId, 'CREATE', 'reservations', $resId, json_encode([
            'table_ids' => $tableIds,
            'party_size' => $partySize,
            'start_at_utc' => $startAt,
            'end_at_utc' => $endAt,
            'customer_email' => $customerEmail
        ]));

        if (!$this->isJsonRequest()) {
            $response->redirect('/admin/reservations');
            return;
        }

        $response->setHeader('Location', "/admin/restaurants/{$orgId}/reservations/{$resId}");
        $response->json([
            'id' => $resId,
            'org_id' => $orgId,
            'table_ids' => $tableIds,
            'party_size' => $partySize,
            'start_at_utc' => $startAt,
            'end_at_utc' => $endAt,
            'status' => 'confirmed',
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s')
        ], 201);
    }

    /**
     * Show reservation edit form or details.
     */
    public function edit(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $resId = (string) ($params['res_id'] ?? $params['id'] ?? $request->getParam('res_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT r.*,
                    GROUP_CONCAT(t.id, ",") as table_ids,
                    GROUP_CONCAT(t.label, ", ") as table_labels
             FROM reservations r
             LEFT JOIN reservation_tables rt ON r.id = rt.reservation_id
             LEFT JOIN dining_tables t ON rt.table_id = t.id
             WHERE r.id = :id AND r.org_id = :org_id
             GROUP BY r.id',
            ['id' => $resId, 'org_id' => $orgId]
        );
        $res = $stmt->fetch();

        if (!$res) {
            $response->json(['error' => 'Reservation not found'], 404);
            return;
        }

        $res['table_ids'] = !empty($res['table_ids']) ? explode(',', $res['table_ids']) : [];

        if ($this->isJsonRequest()) {
            $response->json(['data' => $res], 200);
            return;
        }

        $title = 'Edit Reservation';
        require_once __DIR__ . '/../Views/admin/reservations.php';
    }

    /**
     * Update reservation status or notes.
     */
    public function update(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $resId = (string) ($params['res_id'] ?? $params['id'] ?? $request->getParam('res_id') ?? '');
        $body = $request->getBody();

        $stmt = Database::executeQuery(
            'SELECT * FROM reservations WHERE id = :id AND org_id = :org_id',
            ['id' => $resId, 'org_id' => $orgId]
        );
        $existing = $stmt->fetch();

        if (!$existing) {
            $response->json(['error' => 'Reservation not found'], 404);
            return;
        }

        $notes = isset($body['notes']) ? (string) $body['notes'] : (string) $existing['notes'];
        $partySize = isset($body['party_size']) ? (int) $body['party_size'] : (int) $existing['party_size'];
        $status = isset($body['status']) ? (string) $body['status'] : (string) $existing['status'];

        Database::executeQuery(
            'UPDATE reservations SET notes = :notes, party_size = :party_size, status = :status, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND org_id = :org_id',
            ['notes' => $notes, 'party_size' => $partySize, 'status' => $status, 'id' => $resId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'UPDATE', 'reservations', $resId, json_encode([
            'before' => ['status' => $existing['status'], 'notes' => $existing['notes']],
            'after' => ['status' => $status, 'notes' => $notes]
        ]));

        $response->json(['id' => $resId, 'status' => $status, 'notes' => $notes], 200);
    }

    /**
     * Cancel reservation, release slot locks, and trigger waitlist backfill.
     */
    public function cancel(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $resId = (string) ($params['res_id'] ?? $params['id'] ?? $request->getParam('res_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM reservations WHERE id = :id AND org_id = :org_id',
            ['id' => $resId, 'org_id' => $orgId]
        );
        $res = $stmt->fetch();

        if (!$res) {
            $response->json(['error' => 'Reservation not found'], 404);
            return;
        }

        // Release slots and update status
        TableAllocator::cancelReservation($resId);

        // Fetch freed table IDs for waitlist backfill
        $tablesStmt = Database::executeQuery(
            'SELECT table_id FROM reservation_tables WHERE reservation_id = :id',
            ['id' => $resId]
        );
        $freedTableIds = array_column($tablesStmt->fetchAll(), 'table_id');

        // Check waitlist for backfill candidate
        $backfilledEntry = WaitlistController::autoBackfill($orgId, (int) $res['party_size'], $freedTableIds);

        Audit::record($orgId, 'CANCEL', 'reservations', $resId, json_encode([
            'slots_released' => true,
            'freed_tables' => $freedTableIds,
            'backfilled' => $backfilledEntry
        ]));

        if (!$this->isJsonRequest()) {
            $response->redirect('/admin/reservations');
            return;
        }

        $response->json([
            'id' => $resId,
            'status' => 'cancelled',
            'slots_released' => true,
            'backfilled' => $backfilledEntry
        ], 200);
    }

    /**
     * Confirm/seat a reservation.
     */
    public function confirm(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $resId = (string) ($params['res_id'] ?? $params['id'] ?? $request->getParam('res_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM reservations WHERE id = :id AND org_id = :org_id',
            ['id' => $resId, 'org_id' => $orgId]
        );
        $res = $stmt->fetch();

        if (!$res) {
            $response->json(['error' => 'Reservation not found'], 404);
            return;
        }

        $newStatus = ($res['status'] === 'confirmed') ? 'seated' : 'confirmed';
        Database::executeQuery(
            'UPDATE reservations SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND org_id = :org_id',
            ['status' => $newStatus, 'id' => $resId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'CONFIRM', 'reservations', $resId, "Status updated to {$newStatus}");

        if (!$this->isJsonRequest()) {
            $response->redirect('/admin/reservations');
            return;
        }

        $response->json([
            'id' => $resId,
            'status' => $newStatus
        ], 200);
    }

    /**
     * Complete diner reservation checkout, settle deposit, and accrue loyalty points.
     */
    public function checkout(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $resId = (string) ($params['res_id'] ?? $params['id'] ?? $request->getParam('res_id') ?? '');
        $body = $request->getBody();

        $stmt = Database::executeQuery(
            'SELECT * FROM reservations WHERE id = :id AND org_id = :org_id',
            ['id' => $resId, 'org_id' => $orgId]
        );
        $res = $stmt->fetch();

        if (!$res) {
            $response->json(['error' => 'Reservation not found'], 404);
            return;
        }

        if (in_array($res['status'], ['completed', 'cancelled'], true)) {
            $response->json(['error' => "Reservation already {$res['status']}"], 422);
            return;
        }

        $customerId = $res['customer_id'] ?? null;
        $partySize = (int) ($res['party_size'] ?? 1);
        $billCentavos = (int) ($body['bill_centavos'] ?? ($partySize * 2500));

        // Complete reservation and free table slots
        TableAllocator::completeReservation($resId);

        $depositApplied = 0;
        // If deposits feature enabled, apply held deposit toward the bill
        if (FeatureFlagService::isEnabled($orgId, 'deposits') || FeatureFlagService::isEnabled($orgId, 'enable_deposits')) {
            if ($customerId) {
                $depositBalance = DepositLedgerService::getBalance($orgId, $customerId);
                if ($depositBalance > 0) {
                    $depositApplied = min($depositBalance, $billCentavos);
                    DepositLedgerService::applyDeposit(
                        $orgId,
                        $customerId,
                        $depositApplied,
                        "Applied deposit to reservation {$resId} checkout"
                    );
                }
            }
        }

        $pointsAwarded = 0;
        // If loyalty feature enabled, award points for completed dining
        if (FeatureFlagService::isEnabled($orgId, 'loyalty') || FeatureFlagService::isEnabled($orgId, 'enable_loyalty')) {
            if ($customerId) {
                $pointsAwarded = max(50, (int) round($billCentavos / 100));
                LoyaltyLedgerService::earnPoints(
                    $orgId,
                    $customerId,
                    $pointsAwarded,
                    "Reservation {$resId} checkout loyalty reward"
                );
            }
        }

        Audit::record($orgId, 'CHECKOUT', 'reservations', $resId, json_encode([
            'status' => 'completed',
            'bill_centavos' => $billCentavos,
            'deposit_applied_centavos' => $depositApplied,
            'loyalty_points_awarded' => $pointsAwarded
        ]));

        if (!$this->isJsonRequest()) {
            $response->redirect('/admin/reservations');
            return;
        }

        $response->json([
            'id' => $resId,
            'status' => 'completed',
            'bill_centavos' => $billCentavos,
            'deposit_applied_centavos' => $depositApplied,
            'loyalty_points_awarded' => $pointsAwarded
        ], 200);
    }

    /**
     * Mark reservation as no-show and release slots.
     */
    public function noShow(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::hasAnyRole([RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_FRONTOFHOUSE]);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $resId = (string) ($params['res_id'] ?? $params['id'] ?? $request->getParam('res_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM reservations WHERE id = :id AND org_id = :org_id',
            ['id' => $resId, 'org_id' => $orgId]
        );
        $res = $stmt->fetch();

        if (!$res) {
            if ($this->isJsonRequest()) {
                $response->json(['error' => 'Reservation not found'], 404);
                return;
            }
            $response->redirect('/admin/reservations');
            return;
        }

        // Release slots and update status
        TableAllocator::cancelReservation($resId);

        Database::executeQuery(
            'UPDATE reservations SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND org_id = :org_id',
            ['status' => 'no_show', 'id' => $resId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'NO_SHOW', 'reservations', $resId, 'Guest marked as no-show');

        if (!$this->isJsonRequest()) {
            $response->redirect('/admin/reservations');
            return;
        }

        $response->json([
            'id' => $resId,
            'status' => 'no_show',
            'slots_released' => true
        ], 200);
    }
}
