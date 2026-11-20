<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Core\ListQuery;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Middleware\RoleMiddleware;
use PDOException;

/**
 * SuperAdmin Controller: Cross-Tenant System Administration.
 *
 * Provides global visibility and management across all tenants (restaurants, locations, institutions).
 * SuperAdmin manages tenant CRUD, assigns admins across tenants, and views system-wide audit logs.
 * No tenant isolation: all data visible to SuperAdmin.
 */
class SuperAdminController
{
    private const USER_SORT_COLUMNS = [
        'name' => ['u.last_name', 'u.first_name'],
        'email' => ['u.email'],
        'tenant' => ['t.name'],
        'role' => ['u.role_mask'],
        'created' => ['u.created_at'],
    ];

    private const USER_ROLE_FILTERS = [
        'superadmin' => RoleMiddleware::ROLE_SUPERADMIN,
        'admin' => RoleMiddleware::ROLE_ADMIN,
        'user' => RoleMiddleware::ROLE_USER,
    ];

    /**
     * Helper: Detect JSON request format (Accept header or ?format=json).
     *
     * @return bool true if Accept header contains application/json or ?format=json parameter present.
     */
    private function isJsonRequest(): bool
    {
        return (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || isset($_GET['format']) && $_GET['format'] === 'json';
    }

    /**
     * Render SuperAdmin global dashboard: cross-tenant metrics and system overview.
     *
     * Requires SuperAdmin role. Displays counts of tenants, admins, users, and audit log entries
     * across entire system (not tenant-scoped).
     *
     * @param Request $request HTTP request object.
     * @param Response $response HTTP response object.
     *
     * @return void Requires superadmin dashboard view.
     *
     * @throws Exit On role check failure (HTTP 403).
     */
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
        $this->renderOrganizations($response, [], [], 200);
    }

    public function createOrganization(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);
        Session::verifyCsrf();

        $old = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'subdomain' => strtolower(trim((string) ($_POST['subdomain'] ?? ''))),
        ];

        $errors = array_filter([
            'name' => Validator::name($old['name'], 'Organization name', 255),
            'subdomain' => Validator::subdomain($old['subdomain']),
        ]);
        if ($errors !== []) {
            $this->renderOrganizations($response, $errors, $old, 422);
            return;
        }

        $id = 'tenant-' . bin2hex(random_bytes(6));
        try {
            Database::executeQuery(
                'INSERT INTO tenants (id, name, subdomain, is_active) VALUES (:id, :name, :subdomain, 1)',
                ['id' => $id, 'name' => $old['name'], 'subdomain' => $old['subdomain']]
            );
        } catch (PDOException $e) {
            if (Validator::isUniqueViolation($e)) {
                $this->renderOrganizations($response, ['subdomain' => 'This subdomain is already in use.'], $old, 422);
                return;
            }
            throw $e;
        }

        Audit::record($id, 'TENANT_CREATE', 'tenants', $id, "Provisioned organization {$old['name']} ({$old['subdomain']})");

        if ($this->isJsonRequest()) {
            $response->json(['status' => 'created', 'tenant_id' => $id], 201);
            return;
        }

        $response->redirect('/superadmin/organizations');
    }

    public function editOrganization(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);
        $tenant = $this->findTenant((string) ($_GET['id'] ?? ''));
        if ($tenant === null) {
            $this->renderNotFound($response);
            return;
        }
        $this->renderEditOrganization($response, $tenant, [], 200);
    }

    public function updateOrganization(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);
        Session::verifyCsrf();

        $current = $this->findTenant((string) ($_POST['id'] ?? ''));
        if ($current === null) {
            $this->renderNotFound($response);
            return;
        }

        $submitted = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'is_active' => isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0,
        ];
        $errors = array_filter([
            'name' => Validator::name($submitted['name'], 'Organization name', 255),
        ]);
        if ($errors !== []) {
            $this->renderEditOrganization($response, array_merge($current, $submitted), $errors, 422);
            return;
        }

        $changes = [];
        if ($current['name'] !== $submitted['name']) {
            $changes[] = "Name: {$current['name']} -> {$submitted['name']}";
        }
        if ((int) $current['is_active'] !== $submitted['is_active']) {
            $changes[] = 'Active: ' . ($current['is_active'] ? 'yes' : 'no') . ' -> ' . ($submitted['is_active'] ? 'yes' : 'no');
        }

        if ($changes !== []) {
            Database::executeQuery(
                'UPDATE tenants SET name = :name, is_active = :is_active, updated_at = CURRENT_TIMESTAMP WHERE id = :id',
                ['name' => $submitted['name'], 'is_active' => $submitted['is_active'], 'id' => $current['id']]
            );
            foreach ($changes as $change) {
                Audit::record((string) $current['id'], 'TENANT_UPDATE', 'tenants', (string) $current['id'], $change);
            }
        }

        $response->redirect('/superadmin/organizations');
    }

    public function users(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_SUPERADMIN);

        $list = ListQuery::fromRequest(self::USER_SORT_COLUMNS, self::USER_ROLE_FILTERS);
        $sql = 'SELECT u.id, u.email, u.first_name, u.last_name, u.role_mask, u.tenant_id, u.created_at, t.name as tenant_name
                FROM users u
                JOIN tenants t ON u.tenant_id = t.id
                WHERE 1 = 1';
        $params = [];
        if ($list['q'] !== '') {
            // Each named placeholder is used once; emulated prepares are off, so reuse is not allowed.
            $sql .= " AND (u.email LIKE :q_email ESCAPE '!' OR u.first_name LIKE :q_first ESCAPE '!' OR u.last_name LIKE :q_last ESCAPE '!')";
            $params += ['q_email' => $list['likePattern'], 'q_first' => $list['likePattern'], 'q_last' => $list['likePattern']];
        }
        if ($list['roleMask'] !== null) {
            $sql .= ' AND u.role_mask = :role_mask';
            $params['role_mask'] = $list['roleMask'];
        }
        $sql .= " ORDER BY {$list['orderBy']}";
        $users = Database::executeQuery($sql, $params)->fetchAll();

        if ($this->isJsonRequest()) {
            $response->json(['users' => $users]);
            return;
        }

        $sortLabels = ['name' => 'Name', 'email' => 'Email', 'tenant' => 'Tenant', 'role' => 'Role', 'created' => 'Created'];
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

    private function findTenant(string $id): ?array
    {
        $row = Database::executeQuery('SELECT id, name, subdomain, is_active FROM tenants WHERE id = :id', ['id' => $id])->fetch();
        return $row === false ? null : $row;
    }

    private function renderOrganizations(Response $response, array $errors, array $old, int $status): void
    {
        $tenants = Database::executeQuery('SELECT * FROM tenants ORDER BY created_at DESC')->fetchAll();

        if ($this->isJsonRequest()) {
            $response->json($errors === [] ? ['tenants' => $tenants] : ['errors' => $errors], $status);
        }

        http_response_code($status);
        require_once __DIR__ . '/../Views/superadmin/organizations.php';
    }

    private function renderEditOrganization(Response $response, array $tenant, array $errors, int $status): void
    {
        http_response_code($status);
        require_once __DIR__ . '/../Views/superadmin/organization_edit.php';
    }

    private function renderNotFound(Response $response): void
    {
        $response->setStatusCode(404);
        require_once __DIR__ . '/../Views/errors/404.php';
    }
}
