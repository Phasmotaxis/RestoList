<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\RoleMiddleware;
use App\Services\TableAllocator;
use App\Services\AuditLogger;

/**
 * RestaurantController: ERP Dashboard, Reservations, Waitlist, Menu Management
 *
 * Wave 2: All restaurant-facing operations scoped to org_id.
 * Routes: /erp/dashboard, /erp/reservations, /erp/waitlist, /erp/menu, /erp/sweep
 *
 * @author DICT24 Wave 2
 */
class RestaurantController
{
    /**
     * GET /erp/dashboard — Live table board, bookings, waitlist summary
     */
    public function dashboard(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceOrgRole(RoleMiddleware::ROLE_ADMIN | RoleMiddleware::ROLE_FRONTOFHOUSE | RoleMiddleware::ROLE_KITCHEN);

        $orgId = (int) Session::get('org_id', 0);
        if ($orgId === 0) {
            http_response_code(403);
            exit('No organization context');
        }

        try {
            // Live table statuses
            $stmt = Database::executeQuery(
                'SELECT id, code, capacity, status FROM dining_tables WHERE org_id = :org_id AND archived_at IS NULL ORDER BY code',
                ['org_id' => $orgId]
            );
            $tables = $stmt->fetchAll() ?: [];

            // Today's bookings
            $today = gmdate('Y-m-d');
            $stmt = Database::executeQuery(
                'SELECT id, booking_code, guest_name, party_size, start_at, status FROM reservations WHERE org_id = :org_id AND DATE(start_at) = :today AND status IN ("pending", "confirmed", "seated") ORDER BY start_at',
                ['org_id' => $orgId, 'today' => $today]
            );
            $bookings = $stmt->fetchAll() ?: [];

            // Waitlist queue
            $stmt = Database::executeQuery(
                'SELECT id, guest_name, party_size, status FROM waitlist_entries WHERE org_id = :org_id AND status IN ("waiting", "notified") ORDER BY created_at LIMIT 10',
                ['org_id' => $orgId]
            );
            $waitlist = $stmt->fetchAll() ?: [];

            $canSweep = (int) Session::get('role_mask', 0) & RoleMiddleware::ROLE_ADMIN ? true : false;

            require_once __DIR__ . '/../Views/erp/dashboard.php';
        } catch (\Throwable $e) {
            error_log('[ERROR] Dashboard load failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Failed to load dashboard');
        }
    }

    /**
     * GET /erp/reservations — List with search, filter, sort
     */
    public function listReservations(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceOrgRole(RoleMiddleware::ROLE_ADMIN | RoleMiddleware::ROLE_FRONTOFHOUSE | RoleMiddleware::ROLE_KITCHEN);

        $orgId = (int) Session::get('org_id', 0);
        $q = trim((string) ($_GET['q'] ?? ''));
        $filter = trim((string) ($_GET['filter'] ?? ''));
        $sort = trim((string) ($_GET['sort'] ?? 'start_at'));
        $dir = strtoupper(trim((string) ($_GET['dir'] ?? 'ASC'))) === 'DESC' ? 'DESC' : 'ASC';
        $page = max(0, (int) ($_GET['page'] ?? 0));

        $allowedSorts = ['start_at', 'guest_name', 'party_size', 'status'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'start_at';
        }

        $sql = 'SELECT id, booking_code, guest_name, party_size, start_at, end_at, status FROM reservations WHERE org_id = :org_id';
        $params = ['org_id' => $orgId];

        if ($q) {
            $sql .= ' AND guest_name LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        if ($filter && in_array($filter, ['pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show'])) {
            $sql .= ' AND status = :filter';
            $params['filter'] = $filter;
        }

        $sql .= " ORDER BY $sort $dir LIMIT 50 OFFSET :offset";
        $params['offset'] = $page * 50;

        try {
            $stmt = Database::executeQuery($sql, $params);
            $reservations = $stmt->fetchAll() ?: [];

            require_once __DIR__ . '/../Views/erp/reservations.php';
        } catch (\Throwable $e) {
            error_log('[ERROR] List reservations failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Failed to load reservations');
        }
    }

    /**
     * POST /erp/reservations — Create walk-in booking
     */
    public function createReservation(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceOrgRole(RoleMiddleware::ROLE_ADMIN | RoleMiddleware::ROLE_FRONTOFHOUSE);
        Session::verifyCsrf();

        $orgId = (int) Session::get('org_id', 0);
        $body = $request->getBody();

        $guestName = trim((string) ($body['guest_name'] ?? ''));
        $guestPhone = trim((string) ($body['guest_phone'] ?? ''));
        $guestEmail = trim((string) ($body['guest_email'] ?? ''));
        $partySize = (int) ($body['party_size'] ?? 0);
        $startAtStr = trim((string) ($body['start_at'] ?? ''));
        $preferredZoneId = (int) ($body['preferred_zone_id'] ?? 0) ?: null;

        if (empty($guestName) || empty($guestPhone) || empty($guestEmail) || $partySize < 1 || $partySize > 50) {
            $response->setStatusCode(422);
            $response->json(['error' => 'Invalid guest info or party size']);
            return;
        }

        // Parse and validate start_at (must be snapped to :00, :15, :30, :45)
        try {
            $startAt = new \DateTime($startAtStr, new \DateTimeZone('UTC'));
            $minutes = (int) $startAt->format('i');
            if (!in_array($minutes, [0, 15, 30, 45])) {
                $response->setStatusCode(422);
                $response->json(['error' => 'Start time must be on :00, :15, :30, or :45 minute']);
                return;
            }
        } catch (\Throwable $e) {
            $response->setStatusCode(422);
            $response->json(['error' => 'Invalid start_at format']);
            return;
        }

        try {
            $allocator = new TableAllocator();
            $result = $allocator->allocate(
                orgId: $orgId,
                partySize: $partySize,
                startAt: $startAt->format('Y-m-d H:i:s'),
                preferredZoneId: $preferredZoneId
            );

            if (!$result['success']) {
                $response->setStatusCode(422);
                $response->json(['error' => $result['error'] ?? 'No available tables']);
                return;
            }

            $response->setStatusCode(201);
            $response->json([
                'success' => true,
                'booking_code' => $result['booking_code'],
                'redirect' => '/booking/' . $result['booking_code'],
            ]);
        } catch (\Throwable $e) {
            error_log('[ERROR] Create reservation failed: ' . $e->getMessage());
            $response->setStatusCode(500);
            $response->json(['error' => 'Failed to create reservation']);
        }
    }

    /**
     * POST /erp/reservations/{id}/status — Transition status
     */
    public function updateReservationStatus(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceOrgRole(RoleMiddleware::ROLE_ADMIN | RoleMiddleware::ROLE_FRONTOFHOUSE);
        Session::verifyCsrf();

        $orgId = (int) Session::get('org_id', 0);
        $reservationId = trim((string) ($_POST['reservation_id'] ?? ''));
        $newStatus = trim((string) ($_POST['status'] ?? ''));

        $validStatuses = ['pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show'];
        if (!in_array($newStatus, $validStatuses)) {
            $response->setStatusCode(422);
            $response->json(['error' => 'Invalid status']);
            return;
        }

        try {
            $stmt = Database::executeQuery(
                'SELECT status FROM reservations WHERE id = :id AND org_id = :org_id LIMIT 1',
                ['id' => $reservationId, 'org_id' => $orgId]
            );
            $res = $stmt->fetch();

            if (!$res) {
                $response->setStatusCode(404);
                $response->json(['error' => 'Reservation not found']);
                return;
            }

            // Update status
            Database::executeQuery(
                'UPDATE reservations SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id',
                ['status' => $newStatus, 'id' => $reservationId]
            );

            // Release slots if cancelled/no_show/completed
            if (in_array($newStatus, ['cancelled', 'no_show', 'completed'])) {
                $allocator = new TableAllocator();
                $allocator->releaseSlots($reservationId);
            }

            // Log audit
            AuditLogger::log(
                orgId: $orgId,
                actorUserId: (int) Session::get('user_id', 0),
                action: 'UPDATE_RESERVATION_STATUS',
                entityType: 'reservations',
                entityId: $reservationId,
                after: ['status' => $newStatus]
            );

            $response->json(['success' => true, 'status' => $newStatus]);
        } catch (\Throwable $e) {
            error_log('[ERROR] Update reservation status failed: ' . $e->getMessage());
            $response->setStatusCode(500);
            $response->json(['error' => 'Failed to update reservation']);
        }
    }

    /**
     * GET /erp/menu — List menu items with filters
     */
    public function listMenu(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceOrgRole(RoleMiddleware::ROLE_ADMIN | RoleMiddleware::ROLE_FRONTOFHOUSE | RoleMiddleware::ROLE_KITCHEN);

        $orgId = (int) Session::get('org_id', 0);
        $category = trim((string) ($_GET['category'] ?? ''));
        $sort = trim((string) ($_GET['sort'] ?? 'name'));
        $dir = strtoupper(trim((string) ($_GET['dir'] ?? 'ASC'))) === 'DESC' ? 'DESC' : 'ASC';

        $allowedSorts = ['name', 'price_centavos', 'category'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        $sql = 'SELECT id, name, category, price_centavos, description, dietary_tags, allergen_warnings, is_available, archived_at FROM menu_items WHERE org_id = :org_id AND archived_at IS NULL';
        $params = ['org_id' => $orgId];

        if ($category) {
            $sql .= ' AND category = :category';
            $params['category'] = $category;
        }

        $sql .= " ORDER BY $sort $dir";

        try {
            $stmt = Database::executeQuery($sql, $params);
            $items = $stmt->fetchAll() ?: [];

            require_once __DIR__ . '/../Views/erp/menu.php';
        } catch (\Throwable $e) {
            error_log('[ERROR] List menu failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Failed to load menu');
        }
    }

    /**
     * POST /erp/sweep — Run no-show sweep and backfill waitlist
     */
    public function sweep(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceOrgRole(RoleMiddleware::ROLE_ADMIN);
        Session::verifyCsrf();

        $orgId = (int) Session::get('org_id', 0);

        try {
            $now = gmdate('Y-m-d H:i:s');

            // Mark seated reservations past end_at as no_show
            $stmt = Database::executeQuery(
                'SELECT id FROM reservations WHERE org_id = :org_id AND status = "seated" AND end_at < :now',
                ['org_id' => $orgId, 'now' => $now]
            );
            $noShowIds = $stmt->fetchAll() ?: [];

            $swept = 0;
            foreach ($noShowIds as $row) {
                Database::executeQuery(
                    'UPDATE reservations SET status = "no_show", updated_at = CURRENT_TIMESTAMP WHERE id = :id',
                    ['id' => $row['id']]
                );
                $allocator = new TableAllocator();
                $allocator->releaseSlots($row['id']);
                $swept++;
            }

            // Backfill: promote notified waitlist entries to seated (via new reservations)
            $stmt = Database::executeQuery(
                'SELECT id, party_size, preferred_zone_id FROM waitlist_entries WHERE org_id = :org_id AND status = "notified" ORDER BY created_at LIMIT 5',
                ['org_id' => $orgId]
            );
            $toSeat = $stmt->fetchAll() ?: [];

            $backfilled = 0;
            $allocator = new TableAllocator();
            foreach ($toSeat as $entry) {
                $allocResult = $allocator->allocate(
                    orgId: $orgId,
                    partySize: (int) $entry['party_size'],
                    startAt: $now,
                    preferredZoneId: (int) ($entry['preferred_zone_id'] ?? null)
                );

                if ($allocResult['success']) {
                    Database::executeQuery(
                        'UPDATE waitlist_entries SET status = "seated" WHERE id = :id',
                        ['id' => $entry['id']]
                    );
                    $backfilled++;
                }
            }

            AuditLogger::log(
                orgId: $orgId,
                actorUserId: (int) Session::get('user_id', 0),
                action: 'SWEEP',
                entityType: 'reservations',
                entityId: 'bulk',
                after: ['swept' => $swept, 'backfilled' => $backfilled]
            );

            $response->json(['success' => true, 'swept' => $swept, 'backfilled' => $backfilled]);
        } catch (\Throwable $e) {
            error_log('[ERROR] Sweep failed: ' . $e->getMessage());
            $response->setStatusCode(500);
            $response->json(['error' => 'Failed to run sweep']);
        }
    }

    /**
     * Enforce org role; exit with 403 if not granted
     */
    private function enforceOrgRole(int $requiredMask): void
    {
        $mask = (int) Session::get('role_mask', 0);
        if (($mask & $requiredMask) === 0) {
            http_response_code(403);
            exit('Access denied');
        }
    }
}
