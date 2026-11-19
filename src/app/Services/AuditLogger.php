<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * AuditLogger: Append-only structured audit trail.
 * Records all meaningful actions (create/update/delete) with before/after snapshots.
 */
class AuditLogger
{
    /**
     * Log an action with before/after snapshots.
     */
    public static function log(
        string $orgId,
        ?string $actorId,
        string $action,
        string $targetEntity,
        ?string $targetId,
        ?array $beforeSnapshot = null,
        ?array $afterSnapshot = null,
        ?string $ipAddress = null
    ): string {
        $logId = bin2hex(random_bytes(18));
        $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        Database::executeQuery(
            'INSERT INTO audit_logs (id, tenant_id, user_id, action, entity, entity_id, details, ip_address, created_at)
             VALUES (:id, :org_id, :actor_id, :action, :entity, :entity_id, :details, :ip, CURRENT_TIMESTAMP)',
            [
                'id' => $logId,
                'org_id' => $orgId,
                'actor_id' => $actorId,
                'action' => $action,
                'entity' => $targetEntity,
                'entity_id' => $targetId,
                'details' => json_encode([
                    'before' => $beforeSnapshot,
                    'after' => $afterSnapshot
                ]),
                'ip' => $ipAddress
            ]
        );

        return $logId;
    }

    /**
     * Get audit logs for an organization (SuperAdmin view).
     */
    public static function listByOrg(
        string $orgId,
        int $limit = 100,
        int $offset = 0
    ): array {
        return Database::executeQuery(
            'SELECT id, tenant_id, user_id, action, entity, entity_id, details, ip_address, created_at
             FROM audit_logs
             WHERE tenant_id = :org_id
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset',
            [
                'org_id' => $orgId,
                'limit' => $limit,
                'offset' => $offset
            ]
        )->fetchAll();
    }

    /**
     * Get audit logs by actor (user's own actions).
     */
    public static function listByUser(
        string $orgId,
        string $userId,
        int $limit = 100,
        int $offset = 0
    ): array {
        return Database::executeQuery(
            'SELECT id, tenant_id, user_id, action, entity, entity_id, details, ip_address, created_at
             FROM audit_logs
             WHERE tenant_id = :org_id AND user_id = :user_id
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset',
            [
                'org_id' => $orgId,
                'user_id' => $userId,
                'limit' => $limit,
                'offset' => $offset
            ]
        )->fetchAll();
    }

    /**
     * Count audit logs for pagination.
     */
    public static function countByOrg(string $orgId): int
    {
        $row = Database::executeQuery(
            'SELECT COUNT(*) as cnt FROM audit_logs WHERE tenant_id = :org_id',
            ['org_id' => $orgId]
        )->fetch();

        return (int) ($row['cnt'] ?? 0);
    }
}
