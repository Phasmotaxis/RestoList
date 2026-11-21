<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

/**
 * Waitlist Portal Controller: Remote waitlist join, live position tracking, and cancellation.
 *
 * Provides self-service walk-in and remote queue registration for customers.
 * Tracks queue position relative to other active waiting guests and calculates estimated wait times.
 */
class WaitlistPortalController
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
     * Show remote waitlist join form.
     *
     * Route: GET /waitlist/:restaurant_id/join
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function showForm(Request $request, Response $response): void
    {
        $orgId = $request->getParam('restaurant_id') ?? $request->getParam('id');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('SELECT id, name FROM tenants WHERE (id = :id OR subdomain = :id) AND is_active = 1');
        $stmt->execute([':id' => $orgId]);
        $restaurant = $stmt->fetch();

        if (!$restaurant) {
            $response->json(['error' => 'Restaurant not found'], 404);
            return;
        }

        // Fetch zones for preferred zone dropdown
        $zoneStmt = $pdo->prepare('SELECT id, name FROM dining_zones WHERE org_id = :org_id AND archived_at IS NULL');
        $zoneStmt->execute([':org_id' => $restaurant['id']]);
        $zones = $zoneStmt->fetchAll();

        if ($this->isJsonRequest()) {
            $response->json([
                'restaurant_id' => $restaurant['id'],
                'restaurant_name' => $restaurant['name'],
                'available_zones' => $zones,
                'fields' => [
                    'customer_email' => 'email (required)',
                    'party_size' => 'integer 1-20 (required)',
                    'preferred_zone' => 'zone_id (optional)',
                ],
            ]);
            return;
        }

        $title = "Join Waitlist: {$restaurant['name']} - Customer Portal";
        require_once __DIR__ . '/../Views/customer/waitlist.php';
    }

    /**
     * Join waitlist queue remotely.
     *
     * Route: POST /waitlist/:restaurant_id/join
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function join(Request $request, Response $response): void
    {
        $orgId = $request->getParam('restaurant_id') ?? $request->getParam('id');
        $body = $request->getBody();
        $pdo = Database::getConnection();

        $email = trim((string) ($body['customer_email'] ?? $body['email'] ?? ''));
        $partySize = (int) ($body['party_size'] ?? 0);
        $preferredZone = trim((string) ($body['preferred_zone'] ?? ''));

        // Validation
        $errors = [];
        if ($email === '' || Validator::email($email) !== null) {
            $errors['customer_email'] = 'A valid email address is required';
        }

        if ($partySize < 1 || $partySize > 20) {
            $errors['party_size'] = 'Party size must be between 1 and 20';
        }

        $restStmt = $pdo->prepare('SELECT id, name FROM tenants WHERE (id = :id OR subdomain = :id) AND is_active = 1');
        $restStmt->execute([':id' => $orgId]);
        $restaurant = $restStmt->fetch();

        if (!$restaurant) {
            $errors['restaurant_id'] = 'Restaurant not found or inactive';
        } else {
            $orgId = $restaurant['id'];
        }

        if (!empty($errors)) {
            $response->json(['error' => 'Validation failed', 'fields' => $errors], 422);
            return;
        }

        // Calculate next queue position
        $posStmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 AS next_pos FROM waitlist_entries WHERE org_id = :org_id AND status = \'waiting\'');
        $posStmt->execute([':org_id' => $orgId]);
        $position = (int) ($posStmt->fetch()['next_pos'] ?? 1);

        $entryId = bin2hex(random_bytes(18));

        // Find or create customer
        $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND tenant_id = :org_id');
        $userStmt->execute([':email' => $email, ':org_id' => $orgId]);
        $userRow = $userStmt->fetch();
        $customerId = $userRow ? $userRow['id'] : null;

        $insStmt = $pdo->prepare('INSERT INTO waitlist_entries (id, org_id, customer_id, party_size, preferred_zone, position, status, join_at) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
        $insStmt->execute([
            $entryId,
            $orgId,
            $customerId,
            $partySize,
            $preferredZone !== '' ? $preferredZone : null,
            $position,
            'waiting',
        ]);

        Audit::log(
            $orgId,
            $customerId,
            'CREATE',
            'waitlist_entry',
            $entryId,
            "Guest {$email} joined waitlist at position {$position} for party of {$partySize}"
        );

        $trackingUrl = "/waitlist/{$entryId}/position";
        $cancelUrl = "/waitlist/{$entryId}/cancel";

        if (!headers_sent()) {
            header("Location: {$trackingUrl}");
        }
        $response->json([
            'id' => $entryId,
            'token' => $entryId,
            'restaurant_id' => $orgId,
            'restaurant_name' => $restaurant['name'],
            'party_size' => $partySize,
            'position' => $position,
            'status' => 'waiting',
            'estimated_wait_minutes' => $position * 15,
            'tracking_url' => $trackingUrl,
            'cancel_url' => $cancelUrl,
        ], 201);
    }

    /**
     * Track live waitlist queue position.
     *
     * Route: GET /waitlist/:token/position
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function trackPosition(Request $request, Response $response): void
    {
        $token = $request->getParam('token') ?? $request->getParam('id');
        if (!$token) {
            $response->json(['error' => 'Waitlist token is required'], 400);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT w.*, t.name as restaurant_name, u.email as customer_email FROM waitlist_entries w INNER JOIN tenants t ON w.org_id = t.id LEFT JOIN users u ON w.customer_id = u.id WHERE w.id = :id');
        $stmt->execute([':id' => $token]);
        $entry = $stmt->fetch();

        if (!$entry) {
            $response->json(['error' => 'Waitlist entry not found'], 404);
            return;
        }

        // Calculate live position based on still-waiting entries created before or at this time
        $livePos = (int) $entry['position'];
        if ($entry['status'] === 'waiting') {
            $calcStmt = $pdo->prepare('SELECT COUNT(*) as ahead FROM waitlist_entries WHERE org_id = :org_id AND status = \'waiting\' AND join_at < :join_at');
            $calcStmt->execute([
                ':org_id' => $entry['org_id'],
                ':join_at' => $entry['join_at'],
            ]);
            $ahead = (int) ($calcStmt->fetch()['ahead'] ?? 0);
            $livePos = $ahead + 1;
        }

        $payload = [
            'id' => $entry['id'],
            'token' => $entry['id'],
            'restaurant_id' => $entry['org_id'],
            'restaurant_name' => $entry['restaurant_name'],
            'party_size' => (int) $entry['party_size'],
            'position' => $livePos,
            'status' => $entry['status'],
            'estimated_wait_minutes' => $entry['status'] === 'waiting' ? max(5, $livePos * 15) : 0,
            'join_at' => $entry['join_at'],
            'cancel_url' => "/waitlist/{$entry['id']}/cancel",
        ];

        if ($this->isJsonRequest()) {
            $response->json($payload, 200);
            return;
        }

        $title = 'Waitlist Position - Customer Portal';
        require_once __DIR__ . '/../Views/customer/waitlist.php';
    }

    /**
     * Cancel waitlist entry (remove self from queue).
     *
     * Route: POST /waitlist/:token/cancel
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
            $response->json(['error' => 'Waitlist token is required'], 400);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, org_id, status FROM waitlist_entries WHERE id = :id');
        $stmt->execute([':id' => $token]);
        $entry = $stmt->fetch();

        if (!$entry) {
            $response->json(['error' => 'Waitlist entry not found'], 404);
            return;
        }

        $upd = $pdo->prepare('UPDATE waitlist_entries SET status = \'cancelled\' WHERE id = :id');
        $upd->execute([':id' => $token]);

        Audit::log(
            $entry['org_id'],
            null,
            'CANCEL',
            'waitlist_entry',
            $token,
            "Guest left waitlist queue {$token}"
        );

        $response->json([
            'message' => 'Successfully removed from waitlist',
            'id' => $token,
            'status' => 'cancelled',
        ], 200);
    }
}
