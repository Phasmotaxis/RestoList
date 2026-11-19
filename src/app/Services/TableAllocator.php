<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Exception;

/**
 * TableAllocator: Atomic reservation booking with two-layer double-booking prevention.
 *
 * Layer 1: Transactional row-level locks (FOR UPDATE / BEGIN IMMEDIATE)
 * Layer 2: 15-minute discretized slot bucketing with UNIQUE constraint
 */
class TableAllocator
{
    /**
     * Allocate a reservation atomically across one or more tables.
     *
     * @param string $orgId
     * @param array $tableIds List of table IDs to reserve
     * @param string $startAt ISO 8601 datetime (UTC)
     * @param string $endAt ISO 8601 datetime (UTC)
     * @param int $partySize
     * @param string|null $customerId
     * @param string|null $notes
     * @return string Reservation ID
     * @throws Exception if double-booking detected or lock fails
     */
    public static function allocateReservation(
        string $orgId,
        array $tableIds,
        string $startAt,
        string $endAt,
        int $partySize,
        ?string $customerId = null,
        ?string $notes = null
    ): string {
        if (empty($tableIds)) {
            throw new Exception('At least one table must be specified');
        }

        $pdo = Database::getConnection();
        $reservationId = bin2hex(random_bytes(18));

        try {
            $pdo->beginTransaction();

            // Layer 1: Lock all candidate tables in deterministic order
            $tableIdsSorted = $tableIds;
            sort($tableIdsSorted);
            $placeholders = implode(',', array_fill(0, count($tableIdsSorted), '?'));

            if (Database::getDriverName() === 'sqlite') {
                // SQLite: BEGIN IMMEDIATE is implicit via transaction
            } else {
                // MySQL: SELECT FOR UPDATE
                $lockQuery = "SELECT id FROM dining_tables WHERE org_id = ? AND id IN ({$placeholders}) FOR UPDATE";
                $lockStmt = $pdo->prepare($lockQuery);
                $lockParams = array_merge([$orgId], $tableIdsSorted);
                $lockStmt->execute($lockParams);
            }

            // Verify: No overlapping active reservations on these tables
            foreach ($tableIds as $tableId) {
                $overlapStmt = $pdo->prepare(
                    'SELECT COUNT(*) as cnt FROM reservations r
                     INNER JOIN reservation_tables rt ON r.id = rt.reservation_id
                     WHERE rt.table_id = ? AND r.org_id = ?
                     AND r.status IN (\'pending\', \'confirmed\', \'seated\')
                     AND r.start_at < ? AND r.end_at > ?'
                );
                $overlapStmt->execute([$tableId, $orgId, $endAt, $startAt]);
                $row = $overlapStmt->fetch();
                if ((int) ($row['cnt'] ?? 0) > 0) {
                    throw new Exception("Double-booking detected on table {$tableId}");
                }
            }

            // Layer 2: Discretize into 15-minute buckets and lock via UNIQUE constraint
            $startTime = new \DateTime($startAt, new \DateTimeZone('UTC'));
            $endTime = new \DateTime($endAt, new \DateTimeZone('UTC'));
            $slots = self::getSlotBuckets($startTime, $endTime);

            $slotStmt = $pdo->prepare(
                'INSERT INTO reservation_slots (org_id, table_id, slot_start_utc) VALUES (?, ?, ?)'
            );

            foreach ($tableIds as $tableId) {
                foreach ($slots as $slot) {
                    try {
                        $slotStmt->execute([$orgId, $tableId, $slot]);
                    } catch (\PDOException $e) {
                        if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                            throw new Exception("Slot conflict on table {$tableId}");
                        }
                        throw $e;
                    }
                }
            }

            // Create reservation record
            $resStmt = $pdo->prepare(
                'INSERT INTO reservations (id, org_id, customer_id, party_size, start_at, end_at, status, notes, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $resStmt->execute([
                $reservationId,
                $orgId,
                $customerId,
                $partySize,
                $startAt,
                $endAt,
                'pending',
                $notes
            ]);

            // Link tables to reservation
            $linkStmt = $pdo->prepare(
                'INSERT INTO reservation_tables (reservation_id, table_id) VALUES (?, ?)'
            );
            foreach ($tableIds as $tableId) {
                $linkStmt->execute([$reservationId, $tableId]);
            }

            $pdo->commit();
            return $reservationId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get available 15-minute slots for a given table and time window.
     */
    public static function getAvailableSlots(
        string $orgId,
        string $tableId,
        string $startAt,
        string $endAt
    ): array {
        $startTime = new \DateTime($startAt, new \DateTimeZone('UTC'));
        $endTime = new \DateTime($endAt, new \DateTimeZone('UTC'));
        $slots = self::getSlotBuckets($startTime, $endTime);

        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($slots), '?'));

        $stmt = $pdo->prepare(
            "SELECT slot_start_utc FROM reservation_slots
             WHERE org_id = ? AND table_id = ? AND slot_start_utc IN ({$placeholders})"
        );

        $params = array_merge([$orgId, $tableId], $slots);
        $stmt->execute($params);

        $occupiedSlots = [];
        while ($row = $stmt->fetch()) {
            $occupiedSlots[] = $row['slot_start_utc'];
        }

        return array_diff($slots, $occupiedSlots);
    }

    /**
     * Cancel a reservation and free up its slots.
     */
    public static function cancelReservation(string $reservationId): void
    {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            // Update reservation status
            $pdo->prepare('UPDATE reservations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
                ->execute(['cancelled', $reservationId]);

            // Clean up slot buckets
            $pdo->prepare(
                'DELETE FROM reservation_slots
                 WHERE (org_id, table_id, slot_start_utc) IN (
                    SELECT rs.org_id, rs.table_id, rs.slot_start_utc
                    FROM reservation_slots rs
                    INNER JOIN reservation_tables rt ON rs.table_id = rt.table_id
                    WHERE rt.reservation_id = ?
                 )'
            )->execute([$reservationId]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Generate 15-minute discretized slot buckets between start and end time.
     * @return array List of slot_start_utc strings (ISO 8601)
     */
    private static function getSlotBuckets(\DateTime $start, \DateTime $end): array
    {
        $slots = [];
        $current = clone $start;
        $interval = new \DateInterval('PT15M');

        while ($current < $end) {
            $slots[] = $current->format('Y-m-d H:i:s');
            $current->add($interval);
        }

        return $slots;
    }
}
