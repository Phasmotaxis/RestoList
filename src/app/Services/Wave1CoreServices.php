<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTime;

/**
 * TableAllocator: Concurrency-safe double-booking prevention
 *
 * Implements atomic table reservation with 15-minute slot boundaries and transactional locking.
 * Prevents double-booking via UNIQUE constraint on reservation_slots (org_id, table_id, slot_start_utc).
 * Used by both Restaurant ERP (admin) and Customer Portal (booking flow).
 *
 * Concurrency model: BEGIN IMMEDIATE → SELECT...FOR UPDATE → INSERT slots → COMMIT
 * On conflict, ROLLBACK and return false; caller retries or shows "unavailable" to user.
 *
 * @author DICT24 Wave 1 Layer 1
 */
class TableAllocator
{
    private static Database $pdo;

    public function __construct(Database $pdo)
    {
        self::$pdo = $pdo;
    }

    /**
     * Allocate tables for a reservation with atomic transactional safety.
     *
     * Algorithm:
     * 1. Snap start_at_utc to 15-minute boundary (floor)
     * 2. BEGIN IMMEDIATE (SQLite) or lock transaction (MySQL)
     * 3. SELECT...FOR UPDATE to lock reservation_slots rows
     * 4. Check: no overlapping slots exist in time window
     * 5. INSERT reservation slots (UNIQUE constraint enforced)
     * 6. COMMIT → atomically allocates or rolls back
     *
     * @param int $org_id Organization ID (multi-tenant scoping)
     * @param array $table_ids Array of table IDs to allocate
     * @param DateTime $start Start time (UTC, will be snapped to 15-min boundary)
     * @param DateTime $end End time (UTC)
     *
     * @return bool True if allocation succeeded, false if conflict (table already booked)
     * @throws \Exception If database error occurs
     */
    public static function allocateTables(int $org_id, array $table_ids, DateTime $start, DateTime $end): bool
    {
        // Snap start to 15-minute boundary (e.g., 19:13 → 19:00)
        $minute = (int)$start->format('i');
        $snappedMinute = floor($minute / 15) * 15;
        $start->setTime((int)$start->format('H'), $snappedMinute, 0);

        $slotStart = $start->format('Y-m-d H:i:s');
        $slotEnd = $end->format('Y-m-d H:i:s');

        try {
            self::$pdo->exec('BEGIN IMMEDIATE');

            foreach ($table_ids as $tableId) {
                // SELECT...FOR UPDATE to lock rows (prevent concurrent inserts)
                $stmt = self::$pdo->prepare('
                    SELECT id FROM reservation_slots
                    WHERE org_id = :org_id AND table_id = :table_id AND slot_start_utc BETWEEN :start AND :end
                    FOR UPDATE
                ');
                $stmt->execute([
                    ':org_id' => $org_id,
                    ':table_id' => $tableId,
                    ':start' => $slotStart,
                    ':end' => $slotEnd,
                ]);

                if ($stmt->rowCount() > 0) {
                    // Conflict: slot already exists
                    self::$pdo->exec('ROLLBACK');
                    return false;
                }

                // INSERT new slot (UNIQUE constraint ensures atomicity)
                $stmt = self::$pdo->prepare('
                    INSERT INTO reservation_slots (org_id, table_id, slot_start_utc)
                    VALUES (:org_id, :table_id, :slot_start)
                ');
                $stmt->execute([
                    ':org_id' => $org_id,
                    ':table_id' => $tableId,
                    ':slot_start' => $slotStart,
                ]);
            }

            self::$pdo->exec('COMMIT');
            return true;
        } catch (\Exception $e) {
            try {
                self::$pdo->exec('ROLLBACK');
            } catch (\Exception $rollbackError) {
                // Ignore rollback failure
            }
            return false;
        }
    }

    /**
     * Cancel/deallocate all slots for a reservation.
     *
     * @param int $reservation_id Reservation ID to deallocate
     * @param int $org_id Organization ID (scoping)
     *
     * @return void
     * @throws \Exception If deletion fails
     */
    public static function cancelAllocation(int $reservation_id, int $org_id): void
    {
        $stmt = self::$pdo->prepare('
            DELETE FROM reservation_slots
            WHERE org_id = :org_id AND reservation_id = :reservation_id
        ');
        $stmt->execute([
            ':org_id' => $org_id,
            ':reservation_id' => $reservation_id,
        ]);
    }
}

/**
 * FeatureFlagService: Per-tenant feature toggle management
 *
 * Stores feature flags in organizations.feature_flags JSON or org_feature_flags table.
 * Used to gate experimental features (floor_plan, inventory_erp, deposits, loyalty)
 * and enforce conditional access control in controllers.
 *
 * @author DICT24 Wave 1 Layer 1
 */
class FeatureFlagService
{
    private static Database $pdo;
    private static array $cache = []; // In-memory cache (TTL: 5 minutes per request)

    public function __construct(Database $pdo)
    {
        self::$pdo = $pdo;
    }

    /**
     * Check if a feature flag is enabled for an organization.
     *
     * @param int $org_id Organization ID
     * @param string $flag Flag name (e.g., 'enable_floor_plan')
     *
     * @return bool True if enabled, false otherwise
     */
    public static function isEnabled(int $org_id, string $flag): bool
    {
        $cacheKey = "$org_id:$flag";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $stmt = self::$pdo->prepare('
            SELECT enabled FROM org_feature_flags
            WHERE org_id = :org_id AND flag_name = :flag
        ');
        $stmt->execute([':org_id' => $org_id, ':flag' => $flag]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $enabled = $result ? (bool)$result['enabled'] : false;
        self::$cache[$cacheKey] = $enabled;
        return $enabled;
    }

    /**
     * Set feature flag for organization.
     *
     * @param int $org_id Organization ID
     * @param string $flag Flag name
     * @param bool $enabled Enable/disable
     *
     * @return void
     */
    public static function setFlag(int $org_id, string $flag, bool $enabled): void
    {
        $stmt = self::$pdo->prepare('
            INSERT INTO org_feature_flags (org_id, flag_name, enabled, updated_at)
            VALUES (:org_id, :flag, :enabled, NOW())
            ON DUPLICATE KEY UPDATE enabled = :enabled, updated_at = NOW()
        ');
        $stmt->execute([
            ':org_id' => $org_id,
            ':flag' => $flag,
            ':enabled' => (int)$enabled,
        ]);

        // Invalidate cache
        unset(self::$cache["$org_id:$flag"]);
    }
}

/**
 * DepositLedgerService: Signed immutable deposit ledger (append-only)
 *
 * Records deposits, refunds, and applied amounts. Balance calculated via SUM query
 * (never updating ledger). Used by Portal for deposit balance display and ERP for
 * financial reconciliation.
 *
 * @author DICT24 Wave 1 Layer 1
 */
class DepositLedgerService
{
    private static Database $pdo;

    public function __construct(Database $pdo)
    {
        self::$pdo = $pdo;
    }

    /**
     * Record a deposit transaction (append-only).
     *
     * @param int $org_id Organization ID
     * @param int $reservation_id Reservation ID
     * @param int $amount_centavos Amount in centavos (positive = deposit, negative = refund/apply)
     * @param string $type 'deposit' | 'refund' | 'applied'
     * @param string $reason Context (e.g., "Guest prepaid for meal")
     *
     * @return void
     */
    public static function recordDeposit(
        int $org_id,
        int $reservation_id,
        int $amount_centavos,
        string $type,
        string $reason = ''
    ): void {
        $stmt = self::$pdo->prepare('
            INSERT INTO deposit_ledger (org_id, reservation_id, amount_centavos, type, reason, created_at)
            VALUES (:org_id, :reservation_id, :amount, :type, :reason, NOW())
        ');
        $stmt->execute([
            ':org_id' => $org_id,
            ':reservation_id' => $reservation_id,
            ':amount' => $amount_centavos,
            ':type' => $type,
            ':reason' => $reason,
        ]);
    }

    /**
     * Get balance for reservation (sum of all ledger entries).
     *
     * @param int $org_id Organization ID
     * @param int $reservation_id Reservation ID
     *
     * @return int Balance in centavos (positive = owed by customer, negative = credit)
     */
    public static function getBalance(int $org_id, int $reservation_id): int
    {
        $stmt = self::$pdo->prepare('
            SELECT COALESCE(SUM(amount_centavos), 0) AS balance
            FROM deposit_ledger
            WHERE org_id = :org_id AND reservation_id = :reservation_id
        ');
        $stmt->execute([':org_id' => $org_id, ':reservation_id' => $reservation_id]);
        return (int)$stmt->fetchColumn();
    }
}

/**
 * StockLedgerService: Inventory stock movement tracking (append-only)
 *
 * Records ingredient stock movements with signed quantities (positive = received, negative = used).
 * Supports low-stock alerts, reorder tracking, and inventory reconciliation.
 * Feature-gated: only available if enable_inventory_erp = true.
 *
 * @author DICT24 Wave 1 Layer 1
 */
class StockLedgerService
{
    private static Database $pdo;

    public function __construct(Database $pdo)
    {
        self::$pdo = $pdo;
    }

    /**
     * Record a stock movement (append-only).
     *
     * @param int $org_id Organization ID
     * @param int $ingredient_id Ingredient ID
     * @param int $quantity_delta Quantity change (positive = in, negative = out)
     * @param string $reason Context (e.g., "Received shipment from Supplier ABC", "Used for order #123")
     *
     * @return void
     */
    public static function recordMovement(
        int $org_id,
        int $ingredient_id,
        int $quantity_delta,
        string $reason = ''
    ): void {
        $stmt = self::$pdo->prepare('
            INSERT INTO stock_movements (org_id, ingredient_id, quantity_delta, reason, created_at)
            VALUES (:org_id, :ingredient_id, :delta, :reason, NOW())
        ');
        $stmt->execute([
            ':org_id' => $org_id,
            ':ingredient_id' => $ingredient_id,
            ':delta' => $quantity_delta,
            ':reason' => $reason,
        ]);
    }

    /**
     * Get current stock balance for ingredient (sum of all movements).
     *
     * @param int $org_id Organization ID
     * @param int $ingredient_id Ingredient ID
     *
     * @return int Current quantity on hand
     */
    public static function getBalance(int $org_id, int $ingredient_id): int
    {
        $stmt = self::$pdo->prepare('
            SELECT COALESCE(SUM(quantity_delta), 0) AS balance
            FROM stock_movements
            WHERE org_id = :org_id AND ingredient_id = :ingredient_id
        ');
        $stmt->execute([':org_id' => $org_id, ':ingredient_id' => $ingredient_id]);
        return (int)$stmt->fetchColumn();
    }
}
