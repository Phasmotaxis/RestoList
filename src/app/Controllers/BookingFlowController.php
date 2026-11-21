<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\TableAllocator;

/**
 * Booking Flow Controller: Customer online table reservation lifecycle.
 *
 * Implements end-to-end customer reservation workflow:
 * - Real-time table allocation via TableAllocator (atomic double-booking prevention)
 * - Input validation (email RFC 5322, party size 1-20, no past bookings)
 * - Booking confirmation with booking token and cancellation link
 * - Email confirmation flow (status update to confirmed)
 * - Self-service cancellation with atomic slot release
 */
class BookingFlowController
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
     * Show booking form or schema.
     *
     * Route: GET /bookings/new
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function showForm(Request $request, Response $response): void
    {
        $orgId = $request->getParam('restaurant_id') ?? $request->getParam('org_id');
        $pdo = Database::getConnection();

        $restaurant = null;
        if ($orgId) {
            $stmt = $pdo->prepare('SELECT id, name FROM tenants WHERE (id = :id OR subdomain = :id) AND is_active = 1');
            $stmt->execute([':id' => $orgId]);
            $restaurant = $stmt->fetch() ?: null;
        }

        if ($this->isJsonRequest()) {
            $response->json([
                'fields' => [
                    'restaurant_id' => 'string (required)',
                    'customer_email' => 'email (required)',
                    'party_size' => 'integer 1-20 (required)',
                    'start_at_utc' => 'ISO 8601 UTC datetime (required)',
                    'customer_name' => 'string (optional)',
                    'customer_phone' => 'string (optional)',
                    'notes' => 'string (optional)',
                ],
                'restaurant' => $restaurant,
            ]);
            return;
        }

        $title = 'Make a Reservation - Customer Portal';
        require_once __DIR__ . '/../Views/customer/booking.php';
    }

    /**
     * Create reservation with atomic table allocation.
     *
     * Route: POST /bookings
     *
     * @param Request $request HTTP request with booking payload.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function create(Request $request, Response $response): void
    {
        $body = $request->getBody();
        $pdo = Database::getConnection();

        $orgId = trim((string) ($body['org_id'] ?? $body['restaurant_id'] ?? $request->getParam('restaurant_id') ?? ''));
        $email = trim((string) ($body['customer_email'] ?? $body['email'] ?? ''));
        $partySize = (int) ($body['party_size'] ?? 0);
        $startAtStr = trim((string) ($body['start_at_utc'] ?? $body['start_at'] ?? ''));
        $customerName = trim((string) ($body['customer_name'] ?? $body['name'] ?? ''));
        $customerPhone = trim((string) ($body['customer_phone'] ?? $body['phone'] ?? ''));
        $notes = trim((string) ($body['notes'] ?? ''));
        $tableIds = $body['table_ids'] ?? [];

        // Validation envelope
        $errors = [];

        if ($orgId === '') {
            $errors['restaurant_id'] = 'Restaurant selection is required';
        } else {
            $restStmt = $pdo->prepare('SELECT id, name FROM tenants WHERE (id = :id OR subdomain = :id) AND is_active = 1');
            $restStmt->execute([':id' => $orgId]);
            $rest = $restStmt->fetch();
            if (!$rest) {
                $errors['restaurant_id'] = 'Selected restaurant not found or inactive';
            } else {
                $orgId = $rest['id'];
            }
        }

        if ($email === '' || Validator::email($email) !== null) {
            $errors['customer_email'] = 'A valid email address is required';
        }

        if ($partySize < 1 || $partySize > 20) {
            $errors['party_size'] = 'Party size must be between 1 and 20';
        }

        if ($startAtStr === '') {
            $errors['start_at_utc'] = 'Start time is required';
        } else {
            try {
                $startTime = new \DateTime($startAtStr, new \DateTimeZone('UTC'));
                // Discretize to 15-minute boundary
                $min = (int) $startTime->format('i');
                $snappedMin = (int) floor($min / 15) * 15;
                $startTime->setTime((int) $startTime->format('H'), $snappedMin, 0);

                $now = new \DateTime('now', new \DateTimeZone('UTC'));
                // Past booking validation with 15-minute grace tolerance for clock drift
                $graceWindow = (clone $now)->sub(new \DateInterval('PT15M'));
                if ($startTime < $graceWindow) {
                    $errors['start_at_utc'] = 'Cannot book in the past';
                }
            } catch (\Throwable $e) {
                $errors['start_at_utc'] = 'Invalid date format (ISO 8601 UTC required)';
            }
        }

        if (!empty($errors)) {
            $response->json(['error' => 'Validation failed', 'fields' => $errors], 422);
            return;
        }

        /** @var \DateTime $startTime */
        $endTime = (clone $startTime)->add(new \DateInterval('PT90M')); // Standard 90-minute dining duration
        $startAt = $startTime->format('Y-m-d H:i:s');
        $endAt = $endTime->format('Y-m-d H:i:s');

        // Automatic table selection if not explicitly assigned
        if (empty($tableIds) || !is_array($tableIds)) {
            $tableIds = $this->findAvailableTableIds($orgId, $partySize, $startAt, $endAt);
            if (empty($tableIds)) {
                $response->json([
                    'error' => 'Validation failed',
                    'fields' => ['start_at_utc' => 'No tables available for selected time and party size'],
                ], 422);
                return;
            }
        }

        // Link or create diner user record
        $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND tenant_id = :org_id');
        $userStmt->execute([':email' => $email, ':org_id' => $orgId]);
        $userRow = $userStmt->fetch();

        $customerId = null;
        if ($userRow) {
            $customerId = $userRow['id'];
        } else {
            $customerId = bin2hex(random_bytes(18));
            $nameParts = explode(' ', $customerName, 2);
            $firstName = $nameParts[0] !== '' ? $nameParts[0] : 'Guest';
            $lastName = $nameParts[1] ?? 'Diner';
            $insUser = $pdo->prepare('INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask, created_at) VALUES (?, ?, ?, ?, ?, ?, 4, CURRENT_TIMESTAMP)');
            $insUser->execute([
                $customerId,
                $orgId,
                $email,
                password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
                $firstName,
                $lastName,
            ]);
        }

        // Build reservation notes with contact details
        $combinedNotes = "Guest: {$customerName} | Phone: {$customerPhone}";
        if ($notes !== '') {
            $combinedNotes .= " | Notes: {$notes}";
        }

        // Atomically allocate reservation
        try {
            $reservationId = TableAllocator::allocateReservation(
                $orgId,
                $tableIds,
                $startAt,
                $endAt,
                $partySize,
                $customerId,
                $combinedNotes
            );
        } catch (\Throwable $e) {
            $response->json([
                'error' => 'Reservation conflict',
                'message' => 'Table already booked for the selected time window. Please choose another slot.',
                'details' => $e->getMessage(),
            ], 409);
            return;
        }

        // Audit log
        Audit::log(
            $orgId,
            $customerId,
            'CREATE',
            'reservation',
            $reservationId,
            "Reservation {$reservationId} allocated for party of {$partySize} at {$startAt}"
        );

        $responseData = [
            'id' => $reservationId,
            'token' => $reservationId,
            'org_id' => $orgId,
            'table_ids' => $tableIds,
            'party_size' => $partySize,
            'customer_email' => $email,
            'customer_name' => $customerName,
            'start_at_utc' => $startAt,
            'end_at_utc' => $endAt,
            'status' => 'pending',
            'confirmation_url' => "/bookings/{$reservationId}/confirm",
            'cancellation_url' => "/bookings/{$reservationId}/cancel",
            'view_url' => "/bookings/{$reservationId}",
        ];

        if (!headers_sent()) {
            header("Location: /bookings/{$reservationId}");
        }
        $response->json($responseData, 201);
    }

    /**
     * Show booking confirmation details.
     *
     * Route: GET /bookings/:token
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function showConfirmation(Request $request, Response $response): void
    {
        $token = $request->getParam('token') ?? $request->getParam('id');
        if (!$token) {
            $response->json(['error' => 'Booking token is required'], 400);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT r.*, t.name as restaurant_name, u.email as user_email, u.first_name, u.last_name FROM reservations r INNER JOIN tenants t ON r.org_id = t.id LEFT JOIN users u ON r.customer_id = u.id WHERE r.id = :id');
        $stmt->execute([':id' => $token]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $response->json(['error' => 'Reservation not found'], 404);
            return;
        }

        $responseData = [
            'id' => $booking['id'],
            'token' => $booking['id'],
            'org_id' => $booking['org_id'],
            'restaurant_name' => $booking['restaurant_name'],
            'party_size' => (int) $booking['party_size'],
            'customer_email' => $booking['user_email'] ?? '',
            'start_at_utc' => $booking['start_at'],
            'end_at_utc' => $booking['end_at'],
            'status' => $booking['status'],
            'notes' => $booking['notes'],
            'created_at' => $booking['created_at'],
            'cancellation_url' => "/bookings/{$booking['id']}/cancel",
            'confirmation_url' => "/bookings/{$booking['id']}/confirm",
        ];

        if ($this->isJsonRequest()) {
            $response->json($responseData, 200);
            return;
        }

        $title = 'Booking Confirmation - Customer Portal';
        require_once __DIR__ . '/../Views/customer/booking_confirmation.php';
    }

    /**
     * Confirm reservation via email link.
     *
     * Route: GET /bookings/:token/confirm
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function confirmEmail(Request $request, Response $response): void
    {
        $token = $request->getParam('token') ?? $request->getParam('id');
        if (!$token) {
            $response->json(['error' => 'Booking token is required'], 400);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, org_id, status FROM reservations WHERE id = :id');
        $stmt->execute([':id' => $token]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $response->json(['error' => 'Reservation not found'], 404);
            return;
        }

        $updateStmt = $pdo->prepare('UPDATE reservations SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $updateStmt->execute([':status' => 'confirmed', ':id' => $token]);

        Audit::log(
            $booking['org_id'],
            null,
            'CONFIRM',
            'reservation',
            $token,
            "Reservation {$token} confirmed via email link verification"
        );

        $response->json([
            'message' => 'Reservation confirmed successfully',
            'id' => $token,
            'status' => 'confirmed',
        ], 200);
    }

    /**
     * Cancel reservation and atomically release all booked slots.
     *
     * Route: POST /bookings/:token/cancel
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function cancel(Request $request, Response $response): void
    {
        $token = $request->getParam('token') ?? $request->getParam('id');
        if (!$token) {
            $response->json(['error' => 'Booking token is required'], 400);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, org_id, status FROM reservations WHERE id = :id');
        $stmt->execute([':id' => $token]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $response->json(['error' => 'Reservation not found'], 404);
            return;
        }

        try {
            TableAllocator::cancelReservation($token);
        } catch (\Throwable $e) {
            $response->json(['error' => 'Failed to cancel reservation', 'details' => $e->getMessage()], 500);
            return;
        }

        Audit::log(
            $booking['org_id'],
            null,
            'CANCEL',
            'reservation',
            $token,
            "Reservation {$token} cancelled and slots released"
        );

        $response->json([
            'message' => 'Reservation cancelled successfully',
            'id' => $token,
            'status' => 'cancelled',
        ], 200);
    }

    /**
     * Locate available table(s) matching party capacity for target window.
     *
     * @param string $orgId Organization identifier.
     * @param int $partySize Guest count.
     * @param string $startAt Window start time (UTC).
     * @param string $endAt Window end time (UTC).
     *
     * @return array<string> List of matched table IDs or empty array if none available.
     */
    private function findAvailableTableIds(string $orgId, int $partySize, string $startAt, string $endAt): array
    {
        $pdo = Database::getConnection();

        // 1. Check for single tables of adequate capacity
        $stmt = $pdo->prepare('SELECT id, capacity FROM dining_tables WHERE org_id = :org_id AND capacity >= :capacity AND archived_at IS NULL ORDER BY capacity ASC');
        $stmt->execute([':org_id' => $orgId, ':capacity' => $partySize]);
        $candidates = $stmt->fetchAll();

        foreach ($candidates as $candidate) {
            $tableId = $candidate['id'];
            $conflictStmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM reservation_slots WHERE org_id = :org_id AND table_id = :table_id AND slot_start_utc >= :start AND slot_start_utc < :end');
            $conflictStmt->execute([
                ':org_id' => $orgId,
                ':table_id' => $tableId,
                ':start' => $startAt,
                ':end' => $endAt,
            ]);
            $conflict = (int) ($conflictStmt->fetch()['cnt'] ?? 0);
            if ($conflict === 0) {
                return [$tableId];
            }
        }

        // 2. Check combinable tables if single table not found
        $combStmt = $pdo->prepare('SELECT id, capacity FROM dining_tables WHERE org_id = :org_id AND is_combinable = 1 AND archived_at IS NULL ORDER BY capacity DESC');
        $combStmt->execute([':org_id' => $orgId]);
        $combCandidates = $combStmt->fetchAll();

        $selectedCombIds = [];
        $accumulatedCap = 0;

        foreach ($combCandidates as $ct) {
            $tableId = $ct['id'];
            $conflictStmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM reservation_slots WHERE org_id = :org_id AND table_id = :table_id AND slot_start_utc >= :start AND slot_start_utc < :end');
            $conflictStmt->execute([
                ':org_id' => $orgId,
                ':table_id' => $tableId,
                ':start' => $startAt,
                ':end' => $endAt,
            ]);
            $conflict = (int) ($conflictStmt->fetch()['cnt'] ?? 0);
            if ($conflict === 0) {
                $selectedCombIds[] = $tableId;
                $accumulatedCap += (int) $ct['capacity'];
                if ($accumulatedCap >= $partySize) {
                    return $selectedCombIds;
                }
            }
        }

        return [];
    }
}
