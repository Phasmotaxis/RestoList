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
    private function isJsonRequest(): bool
    {
        return (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || isset($_GET['format']) && $_GET['format'] === 'json';
    }

    public function dashboard(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);

        $tenantsCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM tenants')->fetch()['count'] ?? 0);
        $adminsCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM users WHERE (role_mask & :mask) > 0', [
            'mask' => RoleMiddleware::ROLE_ADMIN,
        ])->fetch()['count'] ?? 0);
        $usersCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM users')->fetch()['count'] ?? 0);
        $logsCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM audit_logs')->fetch()['count'] ?? 0);

        require_once __DIR__ . '/../Views/dashboards/superadmin.php';
    }

    public function organizations(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);

        $tenants = Database::executeQuery('SELECT * FROM tenants ORDER BY created_at DESC')->fetchAll();

        if ($this->isJsonRequest()) {
            $response->json(['tenants' => $tenants]);
            return;
        }

        require_once __DIR__ . '/../Views/superadmin/organizations.php';
    }

    public function createOrganization(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);

        $body = $request->getBody();
        $name = trim((string) ($body['name'] ?? ''));
        $subdomain = strtolower(trim((string) ($body['subdomain'] ?? '')));

        if ($name === '' || $subdomain === '') {
            $response->json(['error' => 'Organization name and subdomain are required.'], 400);
            return;
        }

        $id = 'tenant-' . bin2hex(random_bytes(6));
        Database::executeQuery(
            'INSERT INTO tenants (id, name, subdomain, is_active) VALUES (:id, :name, :subdomain, 1)',
            [
                'id' => $id,
                'name' => $name,
                'subdomain' => $subdomain,
            ]
        );

        // Record audit
        try {
            Database::executeQuery(
                'INSERT INTO audit_logs (id, tenant_id, user_id, action, entity, entity_id, details, ip_address) VALUES (:id, :tenant_id, :user_id, :action, :entity, :entity_id, :details, :ip)',
                [
                    'id' => 'log-' . bin2hex(random_bytes(8)),
                    'tenant_id' => $id,
                    'user_id' => \App\Core\Session::get('user_id'),
                    'action' => 'TENANT_CREATE',
                    'entity' => 'tenants',
                    'entity_id' => $id,
                    'details' => "Provisioned organization {$name} ({$subdomain})",
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ]
            );
        } catch (\Throwable $e) {
            // Non-fatal
        }

        if ($this->isJsonRequest()) {
            $response->json(['status' => 'created', 'tenant_id' => $id], 201);
            return;
        }

        http_response_code(201);
        $response->redirect('/superadmin/organizations');
    }

    public function users(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);

        $sql = 'SELECT u.id, u.email, u.first_name, u.last_name, u.role_mask, u.tenant_id, u.created_at, t.name as tenant_name
                FROM users u
                JOIN tenants t ON u.tenant_id = t.id
                ORDER BY t.name ASC, u.last_name ASC';
        $users = Database::executeQuery($sql)->fetchAll();

        if ($this->isJsonRequest()) {
            $response->json(['users' => $users]);
            return;
        }

        require_once __DIR__ . '/../Views/superadmin/users.php';
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

        if ($this->isJsonRequest()) {
            $response->json(['audit_logs' => $logs]);
            return;
        }

        require_once __DIR__ . '/../Views/superadmin/audit_logs.php';
    }
}
