<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTime;
use DateTimeZone;

/**
 * AuditLogger: Immutable append-only audit trail
 *
 * Captures every state-changing operation (CREATE, UPDATE, DELETE) with actor context,
 * before/after values, and timestamp. Used for compliance, forensics, and multi-tenant isolation.
 *
 * Security: Audit logs are immutable (append-only, no UPDATE/DELETE permissions).
 * Org scoping: Every log entry tied to org_id for multi-tenant audit trail isolation.
 *
 * @author DICT24 Wave 1 Layer 1
 */
class AuditLogger
{
    private static Database $pdo;

    public function __construct(Database $pdo)
    {
        self::$pdo = $pdo;
    }

    /**
     * Log a state-changing operation (CREATE, UPDATE, DELETE).
     *
     * @param int $org_id Organization ID (tenant scoping)
     * @param int $actor_id User ID performing the action
     * @param string $action CREATE | UPDATE | DELETE
     * @param string $entity Entity type (e.g., 'reservation', 'menu_item')
     * @param ?array $before Previous values (null for CREATE)
     * @param ?array $after New values (null for DELETE)
     * @param string $reason Optional context (e.g., "Cancelled per guest request")
     *
     * @return void
     * @throws \Exception If INSERT fails (DB down, permission denied)
     */
    public static function log(
        int $org_id,
        int $actor_id,
        string $action,
        string $entity,
        ?array $before = null,
        ?array $after = null,
        string $reason = ''
    ): void {
        $stmt = self::$pdo->prepare('
            INSERT INTO audit_logs (org_id, actor_id, action, target_entity, before_json, after_json, reason, created_at)
            VALUES (:org_id, :actor_id, :action, :entity, :before, :after, :reason, :created_at)
        ');

        $stmt->execute([
            ':org_id' => $org_id,
            ':actor_id' => $actor_id,
            ':action' => $action,
            ':entity' => $entity,
            ':before' => $before ? json_encode($before) : null,
            ':after' => $after ? json_encode($after) : null,
            ':reason' => $reason,
            ':created_at' => (new DateTime('UTC'))->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Query audit logs for org with optional filters.
     *
     * @param int $org_id Organization ID
     * @param ?string $action Filter by action (CREATE, UPDATE, DELETE)
     * @param ?string $entity Filter by entity type
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     *
     * @return array Audit log rows
     */
    public static function query(
        int $org_id,
        ?string $action = null,
        ?string $entity = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = 'SELECT * FROM audit_logs WHERE org_id = :org_id';
        $params = [':org_id' => $org_id];

        if ($action) {
            $sql .= ' AND action = :action';
            $params[':action'] = $action;
        }
        if ($entity) {
            $sql .= ' AND target_entity = :entity';
            $params[':entity'] = $entity;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute(array_merge($params, [':limit' => $limit, ':offset' => $offset]));
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
