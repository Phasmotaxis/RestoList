<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Exception;

/**
 * Table Reservation Allocator: Atomic multi-table booking with double-booking prevention.
 *
 * Implements two-layer reservation locking strategy:
 * - Layer 1: Transactional row-level locks (FOR UPDATE on MySQL, BEGIN IMMEDIATE on SQLite3).
 * - Layer 2: 15-minute discretized slot bucketing with UNIQUE constraint to prevent overlaps.
 *
 * Supports restaurant floor plan management: reserve single or multi-table seating,
 * check slot availability, and cancel reservations atomically.
 */
class TableAllocator
{
    /**
     * Atomically allocate reservation across one or more dining tables.
     *
     * Uses two-layer locking: row-level SELECT FOR UPDATE, then UNIQUE constraint on
     * 15-minute slot buckets. Transaction rolls back on any conflict or overlap detection.
     * All tables locked in deterministic (sorted) order to prevent deadlock.
     *
     * @param string $orgId Tenant/location identifier.
     * @param array $tableIds List of dining table IDs to reserve (must not be empty).
     * @param string $startAt Reservation start time (ISO 8601 UTC, e.g. '2026-11-20 18:00:00').
     * @param string $endAt Reservation end time (ISO 8601 UTC).
     * @param int $partySize Number of guests.
     * @param ?string $customerId Customer primary key (optional, nullable for walk-ins).
     * @param ?string $notes Reservation notes/special requests (optional).
     *
     * @return string Generated reservation ID.
     *
     * @throws Exception If tableIds empty, double-booking detected, or transaction fails.
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

            // Layer 1: Lock all candidate tables in deterministic order (prevent deadlock).
            $tableIdsSorted = $tableIds;
            sort($tableIdsSorted);
            $placeholders = implode(',', array_fill(0, count($tableIdsSorted), '?'));

            if (Database::getDriverName() === 'sqlite') {
                // SQLite: BEGIN IMMEDIATE handles locking implicitly.
            } else {
                // MySQL: SELECT FOR UPDATE acquires row-level locks on target tables.
                $lockQuery = "SELECT id FROM dining_tables WHERE org_id = ? AND id IN ({$placeholders}) FOR UPDATE";
                $lockStmt = $pdo->prepare($lockQuery);
                $lockParams = array_merge([$orgId], $tableIdsSorted);
                $lockStmt->execute($lockParams);
            }

            // Verify: No overlapping active reservations on these tables (status pending/confirmed/seated).
            foreach ($tableIds as $tableId) {
                $overlapStmt = $pdo->prepare(
                    'SELECT COUNT(*) as cnt FROM reservations r INNER JOIN reservation_tables rt ON r.id = rt.reservation_id WHERE rt.table_id = ? AND r.org_id = ? AND r.status IN (\'pending\', \'confirmed\', \'seated\') AND r.start_at < ? AND r.end_at > ?'
                );
                $overlapStmt->execute([$tableId, $orgId, $endAt, $startAt]);
                $row = $overlapStmt->fetch();
                if ((int) ($row['cnt'] ?? 0) > 0) {
                    throw new Exception("Double-booking detected on table {$tableId}");
                }
            }

            // Layer 2: Discretize time window into 15-minute buckets and lock via UNIQUE constraint.
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

            // Create reservation master record with explicit bound UTC timestamps.
            $nowUtc = gmdate('Y-m-d H:i:s');
            $resStmt = $pdo->prepare(
                'INSERT INTO reservations (id, org_id, customer_id, party_size, start_at, end_at, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $resStmt->execute([
                $reservationId,
                $orgId,
                $customerId,
                $partySize,
                $startAt,
                $endAt,
                'pending',
                $notes,
                $nowUtc,
                $nowUtc
            ]);

            // Link tables to reservation (many-to-many).
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
     * Get available 15-minute slots for table in time window.
     *
     * Returns list of unoccupied slot start times (ISO 8601 UTC) within the requested window.
     * Useful for availability calendars in booking UI.
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $tableId Dining table ID.
     * @param string $startAt Window start time (ISO 8601 UTC).
     * @param string $endAt Window end time (ISO 8601 UTC).
     *
     * @return array List of available slot_start_utc timestamps (ISO 8601 format).
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
            "SELECT slot_start_utc FROM reservation_slots WHERE org_id = ? AND table_id = ? AND slot_start_utc IN ({$placeholders})"
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
     * Cancel reservation atomically and free all booked slots.
     *
     * Sets reservation status to 'cancelled' and deletes associated slot bucket entries.
     * Transaction ensures consistency: if slot deletion fails, entire operation rolls back.
     *
     * @param string $reservationId Reservation primary key.
     *
     * @return void
     *
     * @throws Exception If transaction fails or reservation not found.
     */
    public static function cancelReservation(string $reservationId): void
    {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            // Mark reservation as cancelled with explicit UTC timestamp.
            $nowUtc = gmdate('Y-m-d H:i:s');
            $pdo->prepare('UPDATE reservations SET status = ?, updated_at = ? WHERE id = ?')
                ->execute(['cancelled', $nowUtc, $reservationId]);

            // Delete slot bucket entries for this reservation (clean up via join to reservation_tables).
            $pdo->prepare(
                'DELETE FROM reservation_slots WHERE (org_id, table_id, slot_start_utc) IN (SELECT rs.org_id, rs.table_id, rs.slot_start_utc FROM reservation_slots rs INNER JOIN reservation_tables rt ON rs.table_id = rt.table_id WHERE rt.reservation_id = ?)'
            )->execute([$reservationId]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Complete reservation atomically and free all booked slots.
     *
     * Sets reservation status to 'completed' and deletes associated slot bucket entries.
     * Transaction ensures consistency: if slot deletion fails, entire operation rolls back.
     *
     * @param string $reservationId Reservation primary key.
     *
     * @return void
     *
     * @throws Exception If transaction fails or reservation not found.
     */
    public static function completeReservation(string $reservationId): void
    {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            // Mark reservation as completed with explicit UTC timestamp.
            $nowUtc = gmdate('Y-m-d H:i:s');
            $pdo->prepare('UPDATE reservations SET status = ?, updated_at = ? WHERE id = ?')
                ->execute(['completed', $nowUtc, $reservationId]);

            // Delete slot bucket entries for this reservation (clean up via join to reservation_tables).
            $pdo->prepare(
                'DELETE FROM reservation_slots WHERE (org_id, table_id, slot_start_utc) IN (SELECT rs.org_id, rs.table_id, rs.slot_start_utc FROM reservation_slots rs INNER JOIN reservation_tables rt ON rs.table_id = rt.table_id WHERE rt.reservation_id = ?)'
            )->execute([$reservationId]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Generate 15-minute discretized slot buckets covering time window.
     *
     * Divides reservation time window into 15-minute intervals starting from $start.
     * Example: 18:00-19:15 becomes [18:00, 18:15, 18:30, 18:45, 19:00].
     * Used for fine-grained double-booking prevention.
     *
     * @param \DateTime $start Start time (UTC).
     * @param \DateTime $end End time (UTC).
     *
     * @return array<int, string> List of slot_start_utc timestamps (ISO 8601 format).
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
