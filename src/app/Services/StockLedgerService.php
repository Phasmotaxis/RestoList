<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * StockLedgerService: Immutable inventory movement tracking.
 * Records all ingredient additions/removals for audit compliance.
 */
class StockLedgerService
{
    /**
     * Record ingredient movement (positive = add, negative = remove).
     */
    public static function recordMovement(
        string $orgId,
        string $ingredientId,
        float $quantityDelta,
        string $reason = ''
    ): string {
        $movementId = bin2hex(random_bytes(18));

        Database::executeQuery(
            'INSERT INTO stock_movements (id, org_id, ingredient_id, quantity_delta, reason, created_at)
             VALUES (:id, :org_id, :ingredient_id, :quantity_delta, :reason, CURRENT_TIMESTAMP)',
            [
                'id' => $movementId,
                'org_id' => $orgId,
                'ingredient_id' => $ingredientId,
                'quantity_delta' => $quantityDelta,
                'reason' => $reason
            ]
        );

        // Update on_hand in ingredients table
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
     * Get current on-hand quantity for an ingredient.
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
     * Get movement history for an ingredient.
     */
    public static function getMovementHistory(
        string $orgId,
        string $ingredientId,
        int $limit = 100,
        int $offset = 0
    ): array {
        return Database::executeQuery(
            'SELECT * FROM stock_movements
             WHERE org_id = :org_id AND ingredient_id = :ingredient_id
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset',
            [
                'org_id' => $orgId,
                'ingredient_id' => $ingredientId,
                'limit' => $limit,
                'offset' => $offset
            ]
        )->fetchAll();
    }

    /**
     * Check ingredients below reorder level.
     */
    public static function getLowStockIngredients(string $orgId): array
    {
        return Database::executeQuery(
            'SELECT id, name, unit, on_hand, reorder_level
             FROM ingredients
             WHERE org_id = :org_id AND archived_at IS NULL AND on_hand <= reorder_level
             ORDER BY on_hand ASC',
            ['org_id' => $orgId]
        )->fetchAll();
    }
}
