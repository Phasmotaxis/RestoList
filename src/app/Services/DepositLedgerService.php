<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

/**
 * Deposit Ledger Service: Immutable centavo-based customer deposit tracking.
 *
 * Records all deposit transactions (holds, forfeits, refunds, applications) in append-only deposit_ledger table.
 * Calculates running balances from ledger sum for deposit accounting in POS and table reservations.
 * Enforces strict non-negative balance invariants and positive transaction amounts.
 */
class DepositLedgerService
{
    /**
     * Record customer deposit hold (freeze funds for upcoming reservation or order).
     *
     * Creates ledger entry with positive amount (funds moved into hold).
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     * @param int $amountCentavos Deposit amount in centavos (must be > 0).
     * @param string $reason Transaction reason/reference.
     *
     * @return string Generated ledger ID.
     *
     * @throws InvalidArgumentException If amount is not positive centavos.
     */
    public static function holdDeposit(
        string $orgId,
        string $customerId,
        int $amountCentavos,
        string $reason = ''
    ): string {
        if ($amountCentavos <= 0) {
            throw new InvalidArgumentException('Deposit hold amount must be positive centavos');
        }
        return self::recordLedgerEntry($orgId, $customerId, $amountCentavos, 'hold', $reason);
    }

    /**
     * Record deposit forfeit (retain funds for customer no-show or policy violation).
     *
     * Deducts funds from customer held balance. Enforces non-negative balance invariant.
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     * @param int $amountCentavos Amount to forfeit in centavos (must be > 0).
     * @param string $reason Forfeit reason/policy reference.
     *
     * @return string Generated ledger ID.
     *
     * @throws InvalidArgumentException If amount <= 0 or exceeds held balance.
     */
    public static function forfeitDeposit(
        string $orgId,
        string $customerId,
        int $amountCentavos,
        string $reason = ''
    ): string {
        if ($amountCentavos <= 0) {
            throw new InvalidArgumentException('Forfeit amount must be positive centavos');
        }

        return self::executeAtomicDeduction($orgId, $customerId, $amountCentavos, 'forfeit', $reason);
    }

    /**
     * Record deposit refund (return funds to customer).
     *
     * Deducts funds from held deposit. Enforces that refund cannot exceed held balance.
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     * @param int $amountCentavos Amount to refund in centavos (must be > 0).
     * @param string $reason Refund reason.
     *
     * @return string Generated ledger ID.
     *
     * @throws InvalidArgumentException If amount <= 0 or exceeds held balance.
     */
    public static function refundDeposit(
        string $orgId,
        string $customerId,
        int $amountCentavos,
        string $reason = ''
    ): string {
        if ($amountCentavos <= 0) {
            throw new InvalidArgumentException('Refund amount must be positive centavos');
        }

        return self::executeAtomicDeduction($orgId, $customerId, $amountCentavos, 'refund', $reason);
    }

    /**
     * Record deposit refund by reservation identifier or customer identifier.
     *
     * @param string $orgId Organization identifier.
     * @param string $reservationOrCustomerId Target reservation or customer identifier.
     * @param int $amountCentavos Refund amount in centavos (must be > 0).
     * @param string $reason Refund reason.
     *
     * @return string Generated ledger ID.
     *
     * @throws InvalidArgumentException If amount <= 0 or exceeds held balance.
     */
    public static function recordRefund(
        string $orgId,
        string $reservationOrCustomerId,
        int $amountCentavos,
        string $reason = ''
    ): string {
        if ($amountCentavos <= 0) {
            throw new InvalidArgumentException('Refund amount must be positive centavos');
        }

        $customerId = self::resolveCustomerId($orgId, $reservationOrCustomerId);
        return self::executeAtomicDeduction($orgId, $customerId, $amountCentavos, 'refund', $reason);
    }

    /**
     * Record deposit applied to customer bill / settlement (deduct funds).
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     * @param int $amountCentavos Amount applied in centavos (must be > 0).
     * @param string $reason Transaction reference.
     *
     * @return string Generated ledger ID.
     *
     * @throws InvalidArgumentException If amount <= 0 or exceeds held balance.
     */
    public static function applyDeposit(
        string $orgId,
        string $customerId,
        int $amountCentavos,
        string $reason = ''
    ): string {
        if ($amountCentavos <= 0) {
            throw new InvalidArgumentException('Applied amount must be positive centavos');
        }

        return self::executeAtomicDeduction($orgId, $customerId, $amountCentavos, 'applied', $reason);
    }

    /**
     * Calculate current deposit balance for customer.
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     *
     * @return int Current balance in centavos (0 if customer not found).
     */
    public static function getBalance(string $orgId, string $customerId): int
    {
        $resolvedCustId = self::resolveCustomerId($orgId, $customerId);

        $row = Database::executeQuery(
            'SELECT COALESCE(SUM(amount_centavos), 0) as total FROM deposit_ledger WHERE org_id = :org_id AND customer_id = :customer_id',
            ['org_id' => $orgId, 'customer_id' => $resolvedCustId]
        )->fetch();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Retrieve deposit transaction history for customer (paginated).
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     * @param int $limit Maximum rows per page (default 100).
     * @param int $offset Pagination offset (default 0).
     *
     * @return array<int, array> Ledger rows.
     */
    public static function getLedger(string $orgId, string $customerId, int $limit = 100, int $offset = 0): array
    {
        $resolvedCustId = self::resolveCustomerId($orgId, $customerId);

        return Database::executeQuery(
            'SELECT * FROM deposit_ledger WHERE org_id = :org_id AND customer_id = :customer_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
            [
                'org_id' => $orgId,
                'customer_id' => $resolvedCustId,
                'limit' => $limit,
                'offset' => $offset
            ]
        )->fetchAll();
    }

    /**
     * Execute atomic deduction (refund, forfeit, applied) with strict balance protection.
     *
     * Prevents race conditions and balance overdraw via database transaction locking.
     */
    private static function executeAtomicDeduction(
        string $orgId,
        string $customerId,
        int $amountCentavos,
        string $action,
        string $reason
    ): string {
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
                    ['id' => $customerId]
                );
            }

            $currentBalance = self::getBalance($orgId, $customerId);
            if ($amountCentavos > $currentBalance) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw new InvalidArgumentException(
                    "Deposit deduction ({$amountCentavos} centavos) exceeds current held balance ({$currentBalance} centavos)"
                );
            }

            $ledgerId = self::recordLedgerEntry($orgId, $customerId, -$amountCentavos, $action, $reason);
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
     * Helper to resolve customer ID if a reservation ID or customer ID is provided.
     */
    private static function resolveCustomerId(string $orgId, string $targetId): string
    {
        try {
            $stmt = Database::executeQuery(
                'SELECT customer_id FROM reservations WHERE org_id = :org_id AND id = :target_id',
                ['org_id' => $orgId, 'target_id' => $targetId]
            );
            $linkedCust = $stmt->fetchColumn();
            if ($linkedCust) {
                return (string) $linkedCust;
            }
        } catch (\Throwable $e) {
            // Not a reservation or table not found
        }
        return $targetId;
    }

    /**
     * Internal: Record immutable ledger entry (shared by hold/forfeit/refund).
     */
    private static function recordLedgerEntry(
        string $orgId,
        string $customerId,
        int $amountCentavos,
        string $action,
        string $reason
    ): string {
        $ledgerId = bin2hex(random_bytes(18));

        Database::executeQuery(
            'INSERT INTO deposit_ledger (id, org_id, customer_id, amount_centavos, action, reason, created_at) VALUES (:id, :org_id, :customer_id, :amount_centavos, :action, :reason, CURRENT_TIMESTAMP)',
            [
                'id' => $ledgerId,
                'org_id' => $orgId,
                'customer_id' => $customerId,
                'amount_centavos' => $amountCentavos,
                'action' => $action,
                'reason' => $reason
            ]
        );

        return $ledgerId;
    }
}
