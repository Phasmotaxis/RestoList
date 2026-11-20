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
        Database::executeQuery(
            'INSERT INTO audit_logs (id, tenant_id, user_id, action, entity, entity_id, details, ip_address) VALUES (:id, :tenant_id, :user_id, :action, :entity, :entity_id, :details, :ip)',
            [
                'id' => 'log-' . bin2hex(random_bytes(8)),
                'tenant_id' => $tenantId,
                'user_id' => Session::get('user_id'),
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'details' => $details,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ]
        );
    }
}
