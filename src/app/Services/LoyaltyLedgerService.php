<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

/**
 * Loyalty Ledger Service: Append-only diner loyalty points tracking.
 *
 * Records all loyalty point transactions (earn, redeem, bonus adjustments) in the immutable
 * loyalty_ledger table. Calculates net points balances from the ledger sum.
 * Enforces transaction-level atomic locking to prevent race-condition double spending.
 * Feature-gated via enable_loyalty.
 */
class LoyaltyLedgerService
{
    /**
     * Award loyalty points to a customer.
     *
     * @param string $orgId Organization identifier.
     * @param string $customerId Customer user primary key or email address.
     * @param int $points Number of points to award (must be positive).
     * @param string $reason Audit reason / reference.
     *
     * @return string Generated ledger entry ID.
     */
    public static function earnPoints(
        string $orgId,
        string $customerId,
        int $points,
        string $reason = ''
    ): string {
        if ($points <= 0) {
            throw new RuntimeException('Points to award must be a positive integer');
        }
        $resolvedCustId = self::resolveCustomerId($orgId, $customerId);
        return self::recordLedgerEntry($orgId, $resolvedCustId, $points, $reason);
    }

    /**
     * Redeem diner loyalty points for rewards.
     *
     * Enforces atomic transaction concurrency guards (BEGIN IMMEDIATE on SQLite,
     * SELECT FOR UPDATE on MySQL) to eliminate double-spend race conditions.
     *
     * @param string $orgId Organization identifier.
     * @param string $customerId Customer user primary key or email address.
     * @param int $points Number of points to redeem (must be positive).
     * @param string $reason Reward description / audit reference.
     *
     * @return string Generated ledger entry ID.
     *
     * @throws RuntimeException If points <= 0 or requested points exceed current balance.
     */
    public static function redeemPoints(
        string $orgId,
        string $customerId,
        int $points,
        string $reason = ''
    ): string {
        if ($points <= 0) {
            throw new RuntimeException('Points to redeem must be a positive integer');
        }

        $resolvedCustId = self::resolveCustomerId($orgId, $customerId);

        $pdo = Database::getConnection();
        $isDriverSqlite = (Database::getDriverName() === 'sqlite');

        if ($isDriverSqlite) {
            $pdo->exec('BEGIN IMMEDIATE');
        } else {
            $pdo->beginTransaction();
        }

        try {
            // For MySQL, issue SELECT FOR UPDATE to acquire pessimistic row lock
            if (!$isDriverSqlite) {
                Database::executeQuery(
                    'SELECT id FROM users WHERE id = :id FOR UPDATE',
                    ['id' => $resolvedCustId]
                );
            }

            $balance = self::getBalance($orgId, $resolvedCustId);
            if ($balance < $points) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw new RuntimeException("Insufficient points: requested {$points}, available {$balance}");
            }

            $ledgerId = self::recordLedgerEntry($orgId, $resolvedCustId, -abs($points), $reason);
            $pdo->commit();
            return $ledgerId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get net loyalty points balance for customer.
     *
     * @param string $orgId Organization identifier.
     * @param string $customerId Customer user primary key or email address.
     *
     * @return int Current net point balance (0 if none found).
     */
    public static function getBalance(string $orgId, string $customerId): int
    {
        $resolvedCustId = self::resolveCustomerId($orgId, $customerId);

        $stmt = Database::executeQuery(
            'SELECT COALESCE(SUM(points_delta), 0) as total FROM loyalty_ledger WHERE org_id = :org_id AND customer_id = :customer_id',
            ['org_id' => $orgId, 'customer_id' => $resolvedCustId]
        );
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Retrieve loyalty transaction history for customer.
     *
     * @param string $orgId Organization identifier.
     * @param string $customerId Customer user primary key or email address.
     * @param int $limit Max entries to retrieve.
     * @param int $offset Pagination offset.
     *
     * @return array<int, array> List of ledger entries.
     */
    public static function getLedger(
        string $orgId,
        string $customerId,
        int $limit = 100,
        int $offset = 0
    ): array {
        $resolvedCustId = self::resolveCustomerId($orgId, $customerId);

        $stmt = Database::executeQuery(
            'SELECT * FROM loyalty_ledger WHERE org_id = :org_id AND customer_id = :customer_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
            [
                'org_id' => $orgId,
                'customer_id' => $resolvedCustId,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        return $stmt->fetchAll();
    }

    /**
     * Resolve customer ID if an email is supplied.
     */
    private static function resolveCustomerId(string $orgId, string $targetId): string
    {
        if (str_contains($targetId, '@')) {
            try {
                $stmt = Database::executeQuery(
                    'SELECT id FROM users WHERE email = :email AND (tenant_id = :org_id OR tenant_id IS NULL) LIMIT 1',
                    ['email' => $targetId, 'org_id' => $orgId]
                );
                $foundId = $stmt->fetchColumn();
                if ($foundId) {
                    return (string) $foundId;
                }
            } catch (\Throwable $e) {
                // Ignore fallback
            }
        }
        return $targetId;
    }

    /**
     * Record immutable ledger row.
     *
     * @param string $orgId Organization identifier.
     * @param string $customerId Customer user primary key.
     * @param int $pointsDelta Signed points delta (+earn, -redeem).
     * @param string $reason Audit reason / reference.
     *
     * @return string Generated ledger entry ID.
     */
    private static function recordLedgerEntry(
        string $orgId,
        string $customerId,
        int $pointsDelta,
        string $reason
    ): string {
        $ledgerId = bin2hex(random_bytes(18));

        Database::executeQuery(
            'INSERT INTO loyalty_ledger (id, org_id, customer_id, points_delta, reason, created_at) VALUES (:id, :org_id, :customer_id, :points_delta, :reason, CURRENT_TIMESTAMP)',
            [
                'id' => $ledgerId,
                'org_id' => $orgId,
                'customer_id' => $customerId,
                'points_delta' => $pointsDelta,
                'reason' => $reason,
            ]
        );

        return $ledgerId;
    }
}
