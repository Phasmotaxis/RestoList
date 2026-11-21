<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Slot Availability Controller: Real-time 15-minute boundary slot availability calculation.
 *
 * Implements privacy-preserving table availability discovery for diners.
 * Hides internal floor plan coordinates and table IDs, exposing only abstract capacity counts
 * (e.g., "2 tables available for 4 people") and colored status indicators (green/yellow/red).
 */
class SlotAvailabilityController
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
     * Query real-time available booking slots for a restaurant, date, and party size.
     *
     * Route: GET /discover/:id/availability
     *
     * @param Request $request HTTP request with date and party_size query parameters.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function getSlots(Request $request, Response $response): void
    {
        $orgId = $request->getParam('id') ?? $request->getParam('restaurant_id') ?? $request->getParam('org_id');
        if (!$orgId) {
            $response->json(['error' => 'Restaurant ID is required'], 400);
            return;
        }

        $pdo = Database::getConnection();

        // Verify restaurant exists
        $restStmt = $pdo->prepare('SELECT id, name FROM tenants WHERE (id = :id OR subdomain = :id) AND is_active = 1');
        $restStmt->execute([':id' => $orgId]);
        $restaurant = $restStmt->fetch();
        if (!$restaurant) {
            $response->json(['error' => 'Restaurant not found'], 404);
            return;
        }
        $orgId = $restaurant['id'];

        // Extract and sanitize query parameters
        $date = trim((string) ($request->getParam('date') ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $partySize = max(1, min(20, (int) ($request->getParam('party_size') ?? 2)));

        // Retrieve active tables for this restaurant
        $tableStmt = $pdo->prepare('SELECT id, capacity, is_combinable FROM dining_tables WHERE org_id = :org_id AND archived_at IS NULL ORDER BY capacity ASC');
        $tableStmt->execute([':org_id' => $orgId]);
        $allTables = $tableStmt->fetchAll();

        // Single tables with enough capacity
        $singleTables = array_filter($allTables, fn($t) => (int) $t['capacity'] >= $partySize);

        // Combinable tables
        $combinableTables = array_values(array_filter($allTables, fn($t) => (int) ($t['is_combinable'] ?? 0) === 1));

        // Generate 15-minute slot times from 11:00 to 22:00
        $slots = [];
        $startTime = new \DateTime("{$date} 11:00:00", new \DateTimeZone('UTC'));
        $endTime = new \DateTime("{$date} 22:00:00", new \DateTimeZone('UTC'));
        $interval = new \DateInterval('PT15M');

        // Preload all booked slots for the target date to avoid N+1 queries
        $dayStart = "{$date} 00:00:00";
        $dayEnd = "{$date} 23:59:59";
        $slotQuery = $pdo->prepare('SELECT table_id, slot_start_utc FROM reservation_slots WHERE org_id = :org_id AND slot_start_utc BETWEEN :start AND :end');
        $slotQuery->execute([
            ':org_id' => $orgId,
            ':start' => $dayStart,
            ':end' => $dayEnd,
        ]);

        $occupiedMap = [];
        while ($row = $slotQuery->fetch()) {
            $occupiedMap[$row['slot_start_utc']][$row['table_id']] = true;
        }

        $current = clone $startTime;
        while ($current <= $endTime) {
            $slotStartUtc = $current->format('Y-m-d H:i:s');
            $timeDisplay = $current->format('H:i');

            $occupiedForThisSlot = $occupiedMap[$slotStartUtc] ?? [];

            // Count available single tables
            $freeSingleCount = 0;
            foreach ($singleTables as $tbl) {
                if (!isset($occupiedForThisSlot[$tbl['id']])) {
                    $freeSingleCount++;
                }
            }

            // Check combinable sets if needed
            $combinableAvailable = false;
            if ($freeSingleCount === 0 && !empty($combinableTables)) {
                $freeCombinableCap = 0;
                foreach ($combinableTables as $ct) {
                    if (!isset($occupiedForThisSlot[$ct['id']])) {
                        $freeCombinableCap += (int) $ct['capacity'];
                    }
                }
                if ($freeCombinableCap >= $partySize) {
                    $combinableAvailable = true;
                }
            }

            $totalAvailableUnits = $freeSingleCount + ($combinableAvailable ? 1 : 0);
            $isAvailable = $totalAvailableUnits > 0;

            $statusColor = 'red';
            if ($totalAvailableUnits > 1) {
                $statusColor = 'green';
            } elseif ($totalAvailableUnits === 1) {
                $statusColor = 'yellow';
            }

            $message = $isAvailable
                ? "{$totalAvailableUnits} table" . ($totalAvailableUnits > 1 ? 's' : '') . " available for {$partySize} people"
                : 'Fully booked';

            $slots[] = [
                'time' => $timeDisplay,
                'slot_start_utc' => $slotStartUtc,
                'available' => $isAvailable,
                'tables_available' => $totalAvailableUnits,
                'status' => $statusColor,
                'message' => $message,
            ];

            $current->add($interval);
        }

        $responsePayload = [
            'restaurant_id' => $orgId,
            'restaurant_name' => $restaurant['name'],
            'date' => $date,
            'party_size' => $partySize,
            'slots' => $slots,
        ];

        if ($this->isJsonRequest()) {
            $response->json($responsePayload, 200);
            return;
        }

        $title = "Availability: {$restaurant['name']} - Customer Portal";
        require_once __DIR__ . '/../Views/customer/booking.php';
    }
}
