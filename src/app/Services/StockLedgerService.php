<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Stock Ledger Service: Immutable ingredient inventory tracking.
 *
 * Records all inventory movements (additions, removals, adjustments) in append-only stock_movements table.
 * Updates current on_hand quantity in ingredients table after each movement. Supports inventory audits,
 * reorder alerts, and cost reconciliation for restaurant locations and supplier management.
 */
class StockLedgerService
{
    /**
     * Record ingredient quantity change (positive = add stock, negative = remove stock).
     *
     * Inserts immutable row into stock_movements with quantity delta and reason.
     * Immediately updates ingredients.on_hand with the delta. Reason field captures context:
     * 'receipt' (purchase order arrival), 'adjustment' (physical count reconciliation),
     * 'waste' (spoilage/damage), 'sample' (staff tasting), 'menu_use' (consumption).
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $ingredientId Ingredient primary key.
     * @param float $quantityDelta Amount to add (positive) or remove (negative).
     * @param string $reason Movement reason for audit trail (default empty string).
     *
     * @return string Generated movement ID (log-* format).
     *
     * @throws PDOException If INSERT or UPDATE fails (ingredient not found, constraint violation).
     */
    public static function recordMovement(
        string $orgId,
        string $ingredientId,
        float $quantityDelta,
        string $reason = ''
    ): string {
        $movementId = bin2hex(random_bytes(18));

        // Insert append-only ledger entry.
        Database::executeQuery(
            'INSERT INTO stock_movements (id, org_id, ingredient_id, quantity_delta, reason, created_at) VALUES (:id, :org_id, :ingredient_id, :quantity_delta, :reason, CURRENT_TIMESTAMP)',
            [
                'id' => $movementId,
                'org_id' => $orgId,
                'ingredient_id' => $ingredientId,
                'quantity_delta' => $quantityDelta,
                'reason' => $reason
            ]
        );

        // Update running on_hand quantity in ingredients table.
        Database::executeQuery(
            'UPDATE ingredients SET on_hand = on_hand + :delta WHERE id = :id AND org_id = :org_id',
            [
                'delta' => $quantityDelta,
                'id' => $ingredientId,
                'org_id' => $orgId
            ]
        );

        return $movementId;
    }

    /**
     * Retrieve current on-hand quantity for ingredient.
     *
     * Queries ingredients.on_hand for current stock level. Returns 0.0 if ingredient not found
     * (safe default for stock checks).
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $ingredientId Ingredient primary key.
     *
     * @return float Current on-hand quantity (units depend on ingredient.unit field: kg, liters, portions, etc.).
     */
    public static function getOnHand(string $orgId, string $ingredientId): float
    {
        $row = Database::executeQuery(
            'SELECT on_hand FROM ingredients WHERE org_id = :org_id AND id = :id',
            ['org_id' => $orgId, 'id' => $ingredientId]
        )->fetch();

        return (float) ($row['on_hand'] ?? 0);
    }

    /**
     * Retrieve movement history for ingredient (paginated ledger).
     *
     * Returns recent stock movements in descending timestamp order (newest first).
     * Useful for reconciliation audits and variance investigation.
     *
     * @param string $orgId Tenant/location identifier.
     * @param string $ingredientId Ingredient primary key.
     * @param int $limit Maximum rows to return (default 100).
     * @param int $offset Pagination offset (default 0).
     *
     * @return array<int, array> List of movement rows: [id, org_id, ingredient_id, quantity_delta, reason, created_at, ...].
     */
    public static function getMovementHistory(
        string $orgId,
        string $ingredientId,
        int $limit = 100,
        int $offset = 0
    ): array {
        return Database::executeQuery(
            'SELECT * FROM stock_movements WHERE org_id = :org_id AND ingredient_id = :ingredient_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
            [
                'org_id' => $orgId,
                'ingredient_id' => $ingredientId,
                'limit' => $limit,
                'offset' => $offset
            ]
        )->fetchAll();
    }

    /**
     * Retrieve ingredients below reorder point.
     *
     * Queries non-archived ingredients where on_hand <= reorder_level.
     * Returns results sorted by on_hand ascending (most critical first).
     * Used by procurement dashboards to trigger purchase requisitions.
     *
     * @param string $orgId Tenant/location identifier.
     *
     * @return array<int, array> Low-stock ingredient rows: [id, name, unit, on_hand, reorder_level, ...].
     */
    public static function getLowStockIngredients(string $orgId): array
    {
        return Database::executeQuery(
            'SELECT id, name, unit, on_hand, reorder_level FROM ingredients WHERE org_id = :org_id AND archived_at IS NULL AND on_hand <= reorder_level ORDER BY on_hand ASC',
            ['org_id' => $orgId]
        )->fetchAll();
    }
}
