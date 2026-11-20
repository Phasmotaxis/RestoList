<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Deposit Ledger Service: Immutable centavo-based customer deposit tracking.
 *
 * Records all deposit transactions (holds, forfeits, refunds) in append-only deposit_ledger table.
 * Calculates running balances from ledger sum for deposit accounting in POS and table reservations.
 * Supports refund workflows, no-show penalties, and customer prepayment management.
 */
class DepositLedgerService
{
    /**
     * Record customer deposit hold (freeze funds for upcoming reservation or order).
     *
     * Creates ledger entry with positive amount (funds moved into hold). Used when customer
     * prepays for table reservation or group order. Action = 'hold'.
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     * @param int $amountCentavos Deposit amount in centavos (1 peso = 100 centavos).
     * @param string $reason Transaction reason/reference (e.g. 'reservation #12345', default empty).
     *
     * @return string Generated ledger ID.
     *
     * @throws PDOException If INSERT fails.
     *
     * @see DepositLedgerService::refundDeposit() Reverse a hold (refund).
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
     * Record deposit forfeit (retain funds for customer no-show or policy violation).
     *
     * Creates ledger entry with negative amount (funds removed from customer balance).
     * Action = 'forfeit'. Used when customer misses reservation or violates cancellation terms.
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     * @param int $amountCentavos Amount to forfeit in centavos.
     * @param string $reason Forfeit reason/policy reference (default empty).
     *
     * @return string Generated ledger ID.
     *
     * @throws PDOException If INSERT fails.
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
     * Record deposit refund (return funds to customer).
     *
     * Creates ledger entry with negative amount (funds returned). Action = 'refund'.
     * Used when customer cancels reservation, complaint adjustment, or end-of-event balance settlement.
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     * @param int $amountCentavos Amount to refund in centavos.
     * @param string $reason Refund reason (e.g. 'cancellation', 'complaint credit', default empty).
     *
     * @return string Generated ledger ID.
     *
     * @throws PDOException If INSERT fails.
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
     * Calculate current deposit balance for customer.
     *
     * Sums all amount_centavos in deposit_ledger for customer. Positive balance = funds held,
     * negative balance = customer owes (rare, typically means refund pending).
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     *
     * @return int Current balance in centavos (0 if customer not found).
     */
    public static function getBalance(string $orgId, string $customerId): int
    {
        $row = Database::executeQuery(
            'SELECT SUM(amount_centavos) as total FROM deposit_ledger WHERE org_id = :org_id AND customer_id = :customer_id',
            ['org_id' => $orgId, 'customer_id' => $customerId]
        )->fetch();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Retrieve deposit transaction history for customer (paginated).
     *
     * Returns all ledger entries in descending timestamp order (newest first).
     * Used for customer statements and admin audit trails.
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     * @param int $limit Maximum rows per page (default 100).
     * @param int $offset Pagination offset (default 0).
     *
     * @return array<int, array> Ledger rows: [id, org_id, customer_id, amount_centavos, action, reason, created_at, ...].
     */
    public static function getLedger(string $orgId, string $customerId, int $limit = 100, int $offset = 0): array
    {
        return Database::executeQuery(
            'SELECT * FROM deposit_ledger WHERE org_id = :org_id AND customer_id = :customer_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
            [
                'org_id' => $orgId,
                'customer_id' => $customerId,
                'limit' => $limit,
                'offset' => $offset
            ]
        )->fetchAll();
    }

    /**
     * Internal: Record immutable ledger entry (shared by hold/forfeit/refund).
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $customerId Customer primary key.
     * @param int $amountCentavos Signed amount (positive or negative).
     * @param string $action Transaction action type (hold, forfeit, refund).
     * @param string $reason Transaction reason/reference.
     *
     * @return string Generated ledger ID.
     *
     * @throws PDOException If INSERT fails.
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
