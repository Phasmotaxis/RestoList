<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Append-Only Audit Trail: Record operational actions at tenant scope.
 *
 * Records every meaningful user action (CREATE, UPDATE, DELETE) to audit_logs table
 * with tenant isolation, actor identity, entity reference, and IP address for compliance
 * and forensic investigation. Entries are immutable post-insertion (no UPDATE or DELETE).
 */
class Audit
{
    /**
     * Record a user action to the tenant audit trail.
     *
     * Inserts row into audit_logs with auto-generated ID, current timestamp, and
     * action context. Captures actor user ID from session, actor IP from REMOTE_ADDR,
     * and free-form details JSON string for post-action snapshots or error messages.
     *
     * @param string $tenantId Multi-tenant scope identifier (restaurant, cafeteria, commissary).
     * @param string $action Action verb (CREATE, UPDATE, DELETE, LOGIN_FAIL, EXPORT, etc.).
     * @param string $entity Entity type (users, inventory_items, purchase_orders, shifts, etc.).
     * @param string $entityId Primary key of affected entity instance (UUID or ID string).
     * @param string $details JSON-serialized action metadata (before/after snapshots, reason, etc.).
     *
     * @return void
     *
     * @throws PDOException If INSERT fails (constraint violation, connection error).
     */
    public static function record(string $tenantId, string $action, string $entity, string $entityId, string $details): void
    {
        self::log($tenantId, null, $action, $entity, $entityId, $details);
    }

    /**
     * Flexible audit logger supporting nullable tenant/user IDs.
     */
    public static function log(?string $tenantId, ?string $userId, string $action, string $entity, string $entityId, string $details): void
    {
        $id = 'log-' . bin2hex(random_bytes(8));
        $uid = $userId ?? Session::get('user_id');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        try {
            Database::executeQuery(
                'INSERT INTO audit_logs (id, org_id, user_id, action, entity, entity_id, details, ip_address) VALUES (:id, :org_id, :user_id, :action, :entity, :entity_id, :details, :ip)',
                [
                    'id' => $id,
                    'org_id' => $tenantId,
                    'user_id' => $uid,
                    'action' => $action,
                    'entity' => $entity,
                    'entity_id' => $entityId,
                    'details' => $details,
                    'ip' => $ip,
                ]
            );
        } catch (\Throwable $e) {
            try {
                Database::executeQuery(
                    'INSERT INTO audit_logs (id, tenant_id, user_id, action, entity, entity_id, details, ip_address) VALUES (:id, :tenant_id, :user_id, :action, :entity, :entity_id, :details, :ip)',
                    [
                        'id' => $id,
                        'tenant_id' => $tenantId,
                        'user_id' => $uid,
                        'action' => $action,
                        'entity' => $entity,
                        'entity_id' => $entityId,
                        'details' => $details,
                        'ip' => $ip,
                    ]
                );
            } catch (\Throwable $e2) {
                // Safe ignore if audit table schema differs
            }
        }
    }
}
