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
 * Tenant Admin Controller: Organization user management and audit oversight.
 *
 * Provides tenant-scoped dashboards, user CRUD operations (create, read, update, delete),
 * and audit log visibility. Admin role cannot assign SuperAdmin (prevents privilege escalation).
 */
class AdminController
{
    private const SORT_COLUMNS = [
        'name' => ['u.last_name', 'u.first_name'],
        'email' => ['u.email'],
        'role' => ['u.role_mask'],
        'created' => ['u.created_at'],
    ];

    private const ROLE_FILTERS = [
        'admin' => RoleMiddleware::ROLE_ADMIN,
        'user' => RoleMiddleware::ROLE_USER,
    ];

    /** Roles a tenant admin may assign. SuperAdmin is never assignable here. */
    private const ASSIGNABLE_ROLES = [RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_USER];

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
     * Render tenant admin dashboard: org overview, user count, recent audit logs.
     *
     * Requires Admin role. Displays tenant info, user count, and 10 recent audit entries.
     *
     * @param Request $request HTTP request object.
     * @param Response $response HTTP response object.
     *
     * @return void Requires admin dashboard view.
     *
     * @throws Exit On role check failure (HTTP 403).
     */
    public function dashboard(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $tenantId = Session::get('tenant_id');

        $tenant = Database::executeQuery('SELECT * FROM tenants WHERE id = :id', ['id' => $tenantId])->fetch();
        $userCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM users WHERE tenant_id = :id', ['id' => $tenantId])->fetch()['count'] ?? 0);
        $recentLogs = Database::executeQuery('SELECT * FROM audit_logs WHERE tenant_id = :id ORDER BY created_at DESC LIMIT 10', ['id' => $tenantId])->fetchAll();

        require_once __DIR__ . '/../Views/dashboards/admin.php';
    }

    /**
     * List tenant users with search, sort, role filter (paginated).
     *
     * Requires Admin role. Renders user list view with search/sort query params.
     *
     * @param Request $request HTTP request (GET params: q, sort, dir, role, page, limit).
     * @param Response $response HTTP response object.
     *
     * @return void Requires users list view.
     */
    public function users(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $this->renderUsers($response, [], [], 200);
    }

    /**
     * Create new tenant user via POST (email, names, password, role).
     *
     * Requires Admin role and valid CSRF token. Validates email, names (UTF-8), password strength,
     * and role (Admin or User only; SuperAdmin cannot be assigned). On validation error, re-renders form with errors.
     * On success, creates user with bcrypt hashed password and logs audit trail.
     *
     * @param Request $request HTTP request with POST body (email, first_name, last_name, password, role_mask).
     * @param Response $response HTTP response object.
     *
     * @return void Redirects on success or re-renders form with errors.
     *
     * @throws Exit On CSRF failure or role check failure (HTTP 403).
     */
    public function createUser(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        Session::verifyCsrf();
        $tenantId = Session::get('tenant_id');

        $old = [
            'email' => trim((string) ($_POST['email'] ?? '')),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'role_mask' => (string) ($_POST['role_mask'] ?? RoleMiddleware::ROLE_USER),
        ];
        $password = (string) ($_POST['password'] ?? '');
        $roleMask = (int) $old['role_mask'];

        $errors = array_filter([
            'email' => Validator::email($old['email']),
            'first_name' => Validator::name($old['first_name'], 'First name'),
            'last_name' => Validator::name($old['last_name'], 'Last name'),
            'password' => Validator::password($password),
            'role_mask' => in_array($roleMask, self::ASSIGNABLE_ROLES, true) ? null : 'Choose a valid role.',
        ]);
        if ($errors !== []) {
            $this->renderUsers($response, $errors, $old, 422);
            return;
        }

        $id = 'usr-' . bin2hex(random_bytes(6));
        try {
            Database::executeQuery(
                'INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask) VALUES (:id, :tenant_id, :email, :password_hash, :first_name, :last_name, :role_mask)',
                [
                    'id' => $id,
                    'tenant_id' => $tenantId,
                    'email' => $old['email'],
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                    'first_name' => $old['first_name'],
                    'last_name' => $old['last_name'],
                    'role_mask' => $roleMask,
                ]
            );
        } catch (PDOException $e) {
            if (Validator::isUniqueViolation($e)) {
                $this->renderUsers($response, ['email' => 'This email address is already registered.'], $old, 422);
                return;
            }
            throw $e;
        }

        Audit::record((string) $tenantId, 'USER_CREATE', 'users', $id, 'Admin provisioned a tenant user');

        if ($this->isJsonRequest()) {
            $response->json(['status' => 'created', 'user_id' => $id], 201);
            return;
        }

        $response->redirect('/admin/users');
    }

    public function editUser(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $user = $this->findManageableUser((string) ($_GET['id'] ?? ''));
        if ($user === null) {
            $this->renderNotFound($response);
            return;
        }
        $this->renderEdit($response, $user, [], 200);
    }

    public function updateUser(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        Session::verifyCsrf();
        $tenantId = (string) Session::get('tenant_id');

        $current = $this->findManageableUser((string) ($_POST['id'] ?? ''));
        if ($current === null) {
            $this->renderNotFound($response);
            return;
        }

        $submitted = [
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'role_mask' => (int) ($_POST['role_mask'] ?? 0),
        ];
        $password = (string) ($_POST['password'] ?? '');

        $errors = array_filter([
            'first_name' => Validator::name($submitted['first_name'], 'First name'),
            'last_name' => Validator::name($submitted['last_name'], 'Last name'),
            'role_mask' => $this->roleError($current, $submitted['role_mask']),
            'password' => $password !== '' ? Validator::password($password) : null,
        ]);
        if ($errors !== []) {
            $this->renderEdit($response, array_merge($current, $submitted), $errors, 422);
            return;
        }

        $labels = ['first_name' => 'First name', 'last_name' => 'Last name', 'role_mask' => 'Role mask'];
        $changes = [];
        foreach ($labels as $field => $label) {
            if ((string) $current[$field] !== (string) $submitted[$field]) {
                $changes[] = "{$label}: {$current[$field]} -> {$submitted[$field]}";
            }
        }
        if ($password !== '') {
            $changes[] = 'Password: updated';
        }

        if ($changes !== []) {
            $sql = 'UPDATE users SET first_name = :first_name, last_name = :last_name, role_mask = :role_mask';
            $params = [
                'first_name' => $submitted['first_name'],
                'last_name' => $submitted['last_name'],
                'role_mask' => $submitted['role_mask'],
                'id' => $current['id'],
                'tenant_id' => $tenantId,
            ];
            if ($password !== '') {
                $sql .= ', password_hash = :password_hash';
                $params['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
            }
            $sql .= ' WHERE id = :id AND tenant_id = :tenant_id';
            Database::executeQuery($sql, $params);
            foreach ($changes as $change) {
                Audit::record($tenantId, 'USER_UPDATE', 'users', (string) $current['id'], $change);
            }
        }

        $response->redirect('/admin/users');
    }

    public function deleteUser(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        Session::verifyCsrf();
        $tenantId = (string) Session::get('tenant_id');

        $current = $this->findManageableUser((string) ($_POST['id'] ?? ''));
        if ($current === null) {
            $this->renderNotFound($response);
            return;
        }

        $errors = [];
        if ((string) $current['id'] === (string) Session::get('user_id')) {
            $errors['_form'] = 'You cannot delete your own account.';
        } elseif (($_POST['confirm'] ?? '') !== '1') {
            $errors['confirm'] = 'Tick the confirmation box to delete this user.';
        }
        if ($errors !== []) {
            $this->renderEdit($response, $current, $errors, 422);
            return;
        }

        Database::executeQuery(
            'DELETE FROM users WHERE id = :id AND tenant_id = :tenant_id',
            ['id' => $current['id'], 'tenant_id' => $tenantId]
        );
        Audit::record($tenantId, 'USER_DELETE', 'users', (string) $current['id'], 'Admin deleted a tenant user');

        $response->redirect('/admin/users');
    }

    /**
     * Loads a user only when it belongs to the caller's tenant and is not a SuperAdmin.
     */
    private function findManageableUser(string $id): ?array
    {
        $row = Database::executeQuery(
            'SELECT id, email, first_name, last_name, role_mask FROM users WHERE id = :id AND tenant_id = :tenant_id',
            ['id' => $id, 'tenant_id' => Session::get('tenant_id')]
        )->fetch();

        if ($row === false) {
            return null;
        }
        if (((int) $row['role_mask'] & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN) {
            return null;
        }
        return $row;
    }

    private function roleError(array $current, int $role): ?string
    {
        if (!in_array($role, self::ASSIGNABLE_ROLES, true)) {
            return 'Choose a valid role.';
        }
        if ((string) $current['id'] === (string) Session::get('user_id') && $role !== (int) $current['role_mask']) {
            return 'You cannot change your own role.';
        }
        return null;
    }

    private function renderUsers(Response $response, array $errors, array $old, int $status): void
    {
        $tenantId = Session::get('tenant_id');
        $list = ListQuery::fromRequest(self::SORT_COLUMNS, self::ROLE_FILTERS);

        $sql = 'SELECT u.id, u.email, u.first_name, u.last_name, u.role_mask, u.created_at FROM users u WHERE u.tenant_id = :tenant_id';
        $params = ['tenant_id' => $tenantId];
        if ($list['q'] !== '') {
            // Each named placeholder is used once; PDO with emulated prepares off rejects reuse.
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
            $response->json($errors === [] ? ['users' => $users] : ['errors' => $errors], $status);
        }

        $sortLabels = ['name' => 'Name', 'email' => 'Email', 'role' => 'Role', 'created' => 'Registered'];
        http_response_code($status);
        require_once __DIR__ . '/../Views/admin/users.php';
    }

    private function renderEdit(Response $response, array $user, array $errors, int $status): void
    {
        http_response_code($status);
        require_once __DIR__ . '/../Views/admin/user_edit.php';
    }

    private function renderNotFound(Response $response): void
    {
        $response->setStatusCode(404);
        require_once __DIR__ . '/../Views/errors/404.php';
    }
}
