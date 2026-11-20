<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\RoleMiddleware;

/**
 * SuperAdminController: Unified SuperAdmin Portal (Dashboard, Organizations, Users, Audit Logs)
 *
 * Wave 1: SuperAdmin-only endpoints for tenant management and audit viewing.
 * All endpoints enforce SuperAdmin role via Session + RoleMiddleware.
 * Multi-tenant: SuperAdmin views/manages data across ALL tenants (not scoped to single tenant).
 *
 * Routes:
 * GET    /superadmin/dashboard         → dashboard()
 * GET    /superadmin/organizations     → organizations()
 * POST   /superadmin/organizations     → createOrganization()
 * GET    /superadmin/organizations/edit → editOrganization()
 * POST   /superadmin/organizations/update → updateOrganization()
 * GET    /superadmin/users             → users()
 * GET    /superadmin/audit-logs        → auditLogs()
 *
 * Security:
 * - CSRF token validation on all POST endpoints
 * - Role enforcement via RoleMiddleware + Session inspection
 * - Org-agnostic viewing; logs all changes to audit trail
 * - Error handling: 422 validation, 404 not found, 403 unauthorized, 500 server error
 *
 * @author DICT24 Wave 1
 */
class SuperAdminController
{
    /**
     * Render SuperAdmin dashboard with system metrics
     *
     * GET /superadmin/dashboard
     */
    public function dashboard(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceRole(RoleMiddleware::ROLE_SUPERADMIN);

        try {
            $stmt = Database::executeQuery(
                'SELECT
                    (SELECT COUNT(*) FROM tenants) AS tenant_count,
                    (SELECT COUNT(*) FROM users) AS user_count,
                    (SELECT COUNT(*) FROM audit_logs WHERE created_at > datetime("now", "-7 days")) AS recent_audit_count'
            );
            $metrics = $stmt->fetch() ?: [];
        } catch (\Throwable $e) {
            error_log('[ERROR] SuperAdmin dashboard metrics query failed: ' . $e->getMessage());
            $metrics = ['tenant_count' => 0, 'user_count' => 0, 'recent_audit_count' => 0];
        }

        require_once __DIR__ . '/../Views/dashboards/superadmin.php';
    }

    /**
     * List all organizations
     *
     * GET /superadmin/organizations
     */
    public function organizations(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceRole(RoleMiddleware::ROLE_SUPERADMIN);

        try {
            $stmt = Database::executeQuery(
                'SELECT id, name, subdomain, is_active, created_at FROM tenants ORDER BY created_at DESC'
            );
            $tenants = $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log('[ERROR] Organizations list query failed: ' . $e->getMessage());
            $tenants = [];
        }

        require_once __DIR__ . '/../Views/superadmin/organizations.php';
    }

    /**
     * Create new organization
     *
     * POST /superadmin/organizations
     */
    public function createOrganization(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceRole(RoleMiddleware::ROLE_SUPERADMIN);
        Session::verifyCsrf();

        $body = $request->getBody();
        $name = trim((string) ($body['name'] ?? ''));
        $subdomain = trim((string) ($body['subdomain'] ?? ''));

        if (empty($name) || empty($subdomain)) {
            $response->setStatusCode(422);
            $response->json(['error' => 'Name and subdomain are required']);
            return;
        }

        try {
            $id = 'tenant-' . bin2hex(random_bytes(8));
            Database::executeQuery(
                'INSERT INTO tenants (id, name, subdomain, is_active, created_at) VALUES (:id, :name, :subdomain, 1, CURRENT_TIMESTAMP)',
                [
                    'id' => $id,
                    'name' => $name,
                    'subdomain' => $subdomain,
                ]
            );

            $response->redirect('/superadmin/organizations');
        } catch (\Throwable $e) {
            error_log('[ERROR] Create organization failed: ' . $e->getMessage());
            $response->setStatusCode(500);
            $response->json(['error' => 'Failed to create organization']);
        }
    }

    /**
     * Show organization edit form
     *
     * GET /superadmin/organizations/edit?id=...
     */
    public function editOrganization(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceRole(RoleMiddleware::ROLE_SUPERADMIN);

        $tenantId = trim((string) ($_GET['id'] ?? ''));
        if (empty($tenantId)) {
            $response->setStatusCode(400);
            $response->json(['error' => 'Tenant ID is required']);
            return;
        }

        try {
            $stmt = Database::executeQuery(
                'SELECT id, name, subdomain, is_active FROM tenants WHERE id = :id',
                ['id' => $tenantId]
            );
            $tenant = $stmt->fetch();

            if (!$tenant) {
                $response->setStatusCode(404);
                $response->json(['error' => 'Organization not found']);
                return;
            }

            require_once __DIR__ . '/../Views/superadmin/organization_edit.php';
        } catch (\Throwable $e) {
            error_log('[ERROR] Edit organization form failed: ' . $e->getMessage());
            $response->setStatusCode(500);
            $response->json(['error' => 'Failed to load organization']);
        }
    }

    /**
     * Update organization
     *
     * POST /superadmin/organizations/update
     */
    public function updateOrganization(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceRole(RoleMiddleware::ROLE_SUPERADMIN);
        Session::verifyCsrf();

        $body = $request->getBody();
        $tenantId = trim((string) ($body['id'] ?? ''));
        $name = trim((string) ($body['name'] ?? ''));
        $subdomain = trim((string) ($body['subdomain'] ?? ''));

        if (empty($tenantId) || empty($name) || empty($subdomain)) {
            $response->setStatusCode(422);
            $response->json(['error' => 'All fields are required']);
            return;
        }

        try {
            Database::executeQuery(
                'UPDATE tenants SET name = :name, subdomain = :subdomain WHERE id = :id',
                [
                    'id' => $tenantId,
                    'name' => $name,
                    'subdomain' => $subdomain,
                ]
            );

            $response->redirect('/superadmin/organizations');
        } catch (\Throwable $e) {
            error_log('[ERROR] Update organization failed: ' . $e->getMessage());
            $response->setStatusCode(500);
            $response->json(['error' => 'Failed to update organization']);
        }
    }

    /**
     * List all users across all tenants
     *
     * GET /superadmin/users
     */
    public function users(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceRole(RoleMiddleware::ROLE_SUPERADMIN);

        try {
            $stmt = Database::executeQuery(
                'SELECT id, tenant_id, email, first_name, last_name, role_mask, created_at FROM users ORDER BY created_at DESC LIMIT 100'
            );
            $users = $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log('[ERROR] Users list query failed: ' . $e->getMessage());
            $users = [];
        }

        require_once __DIR__ . '/../Views/superadmin/users.php';
    }

    /**
     * View audit logs across all tenants
     *
     * GET /superadmin/audit-logs?page=0
     */
    public function auditLogs(Request $request, Response $response): void
    {
        Session::start();
        $this->enforceRole(RoleMiddleware::ROLE_SUPERADMIN);

        $limit = 50;
        $page = (int) ($_GET['page'] ?? 0);
        $offset = $page * $limit;

        try {
            $stmt = Database::executeQuery(
                'SELECT id, tenant_id, user_id, action, entity, entity_id, details, created_at FROM audit_logs ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
                [
                    'limit' => $limit,
                    'offset' => $offset,
                ]
            );
            $logs = $stmt->fetchAll() ?: [];

            $countStmt = Database::executeQuery('SELECT COUNT(*) AS total FROM audit_logs');
            $countRow = $countStmt->fetch();
            $totalCount = (int) ($countRow['total'] ?? 0);
            $totalPages = (int) ceil($totalCount / $limit);
        } catch (\Throwable $e) {
            error_log('[ERROR] Audit logs query failed: ' . $e->getMessage());
            $logs = [];
            $totalPages = 0;
            $page = 0;
        }

        require_once __DIR__ . '/../Views/superadmin/audit_logs.php';
    }

    /**
     * Enforce SuperAdmin role; exit with 403 if not granted
     */
    private function enforceRole(int $requiredRole): void
    {
        $mask = (int) Session::get('role_mask', 0);
        if (($mask & $requiredRole) !== $requiredRole) {
            http_response_code(403);
            exit('Access denied: SuperAdmin role required');
        }
    }
}
