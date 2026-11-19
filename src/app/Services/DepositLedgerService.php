<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * DepositLedgerService: Immutable centavo-based deposit tracking.
 * Records all hold/forfeit/refund actions for audit compliance.
 */
class DepositLedgerService
{
    /**
     * Record a deposit hold (freeze funds pre-payment).
     */
    public static function holdDeposit(
        string $orgId,
        string $customerId,
        int $amountCentavos,
        string $reason = ''
    ): string {
        return self::recordLedgerEntry($orgId, $customerId, $amountCentavos, 'hold', $reason);
    }

    /**
     * Record a deposit forfeit (customer no-show, funds kept).
     */
    public static function forfeitDeposit(
        string $orgId,
        string $customerId,
        int $amountCentavos,
        string $reason = ''
    ): string {
        return self::recordLedgerEntry($orgId, $customerId, -$amountCentavos, 'forfeit', $reason);
    }

    /**
     * Record a deposit refund (return funds).
     */
    public static function refundDeposit(
        string $orgId,
        string $customerId,
        int $amountCentavos,
        string $reason = ''
    ): string {
        return self::recordLedgerEntry($orgId, $customerId, -$amountCentavos, 'refund', $reason);
    }

    /**
     * Get current balance for a customer (sum of all ledger entries).
     */
    public static function getBalance(string $orgId, string $customerId): int
    {
        $row = Database::executeQuery(
            'SELECT SUM(amount_centavos) as total FROM deposit_ledger
             WHERE org_id = :org_id AND customer_id = :customer_id',
            ['org_id' => $orgId, 'customer_id' => $customerId]
        )->fetch();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Get ledger history for a customer.
     */
    public static function getLedger(string $orgId, string $customerId, int $limit = 100, int $offset = 0): array
    {
        return Database::executeQuery(
            'SELECT * FROM deposit_ledger
             WHERE org_id = :org_id AND customer_id = :customer_id
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset',
            [
                'org_id' => $orgId,
                'customer_id' => $customerId,
                'limit' => $limit,
                'offset' => $offset
            ]
        )->fetchAll();
    }

    /**
     * Internal: Record immutable ledger entry.
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
            'INSERT INTO deposit_ledger (id, org_id, customer_id, amount_centavos, action, reason, created_at)
             VALUES (:id, :org_id, :customer_id, :amount_centavos, :action, :reason, CURRENT_TIMESTAMP)',
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
