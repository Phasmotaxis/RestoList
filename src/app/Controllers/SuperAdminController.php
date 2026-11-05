<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\RoleMiddleware;

/**
 * SuperAdmin Controller
 *
 * Provides global cross-tenant visibility and management across all
 * tenant organizations, tenant administrators, users, and audit logs.
 */
class SuperAdminController
{
    public function dashboard(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);

        $tenantsCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM tenants')->fetch()['count'] ?? 0);
        $adminsCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM users WHERE (role_mask & :mask) = :mask', [
            'mask' => RoleMiddleware::ROLE_ADMIN,
        ])->fetch()['count'] ?? 0);
        $usersCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM users')->fetch()['count'] ?? 0);
        $logsCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM audit_logs')->fetch()['count'] ?? 0);

        require_once __DIR__ . '/../Views/dashboards/superadmin.php';
    }

    public function listTenants(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);
        $tenants = Database::executeQuery('SELECT * FROM tenants ORDER BY created_at DESC')->fetchAll();
        $response->json(['tenants' => $tenants]);
    }

    public function listAllAdmins(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);
        $sql = 'SELECT u.id, u.email, u.first_name, u.last_name, u.role_mask, u.tenant_id, t.name as tenant_name
                FROM users u
                JOIN tenants t ON u.tenant_id = t.id
                WHERE (u.role_mask & :mask) = :mask
                ORDER BY t.name ASC, u.last_name ASC';
        $admins = Database::executeQuery($sql, ['mask' => RoleMiddleware::ROLE_ADMIN])->fetchAll();
        $response->json(['organization_admins' => $admins]);
    }

    public function listAllUsers(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);
        $sql = 'SELECT u.id, u.email, u.first_name, u.last_name, u.role_mask, u.tenant_id, t.name as tenant_name
                FROM users u
                JOIN tenants t ON u.tenant_id = t.id
                ORDER BY t.name ASC, u.last_name ASC';
        $users = Database::executeQuery($sql)->fetchAll();
        $response->json(['all_users' => $users]);
    }

    public function auditLogs(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);
        $sql = 'SELECT a.*, t.name as tenant_name
                FROM audit_logs a
                LEFT JOIN tenants t ON a.tenant_id = t.id
                ORDER BY a.created_at DESC
                LIMIT 100';
        $logs = Database::executeQuery($sql)->fetchAll();
        $response->json(['audit_logs' => $logs]);
    }
}
