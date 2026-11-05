<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\RoleMiddleware;

class AdminController
{
    private function isJsonRequest(): bool
    {
        return (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || isset($_GET['format']) && $_GET['format'] === 'json';
    }

    public function dashboard(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $tenantId = Session::get('tenant_id');

        $tenant = Database::executeQuery('SELECT * FROM tenants WHERE id = :id', ['id' => $tenantId])->fetch();
        $userCount = (int) (Database::executeQuery('SELECT COUNT(*) as count FROM users WHERE tenant_id = :id', ['id' => $tenantId])->fetch()['count'] ?? 0);
        $recentLogs = Database::executeQuery('SELECT * FROM audit_logs WHERE tenant_id = :id ORDER BY created_at DESC LIMIT 10', ['id' => $tenantId])->fetchAll();

        require_once __DIR__ . '/../Views/dashboards/admin.php';
    }

    public function users(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $tenantId = Session::get('tenant_id');

        $users = Database::executeQuery(
            'SELECT id, email, first_name, last_name, role_mask, created_at FROM users WHERE tenant_id = :id ORDER BY last_name ASC',
            ['id' => $tenantId]
        )->fetchAll();

        if ($this->isJsonRequest()) {
            $response->json(['users' => $users]);
            return;
        }

        require_once __DIR__ . '/../Views/admin/users.php';
    }

    public function createUser(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $tenantId = Session::get('tenant_id');

        $body = $request->getBody();
        $email = trim((string) ($body['email'] ?? ''));
        $firstName = trim((string) ($body['first_name'] ?? ''));
        $lastName = trim((string) ($body['last_name'] ?? ''));
        $password = (string) ($_POST['password'] ?? 'Password123!');
        $roleMask = isset($body['role_mask']) ? (int) $body['role_mask'] : RoleMiddleware::ROLE_USER;

        if ($email === '' || $firstName === '' || $lastName === '') {
            $response->json(['error' => 'Email, first name, and last name are required.'], 400);
            return;
        }

        $id = 'usr-' . bin2hex(random_bytes(6));
        $hash = password_hash($password, PASSWORD_BCRYPT);

        Database::executeQuery(
            'INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask) VALUES (:id, :tenant_id, :email, :password_hash, :first_name, :last_name, :role_mask)',
            [
                'id' => $id,
                'tenant_id' => $tenantId,
                'email' => $email,
                'password_hash' => $hash,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role_mask' => $roleMask,
            ]
        );

        // Record audit log
        try {
            Database::executeQuery(
                'INSERT INTO audit_logs (id, tenant_id, user_id, action, entity, entity_id, details, ip_address) VALUES (:id, :tenant_id, :user_id, :action, :entity, :entity_id, :details, :ip)',
                [
                    'id' => 'log-' . bin2hex(random_bytes(8)),
                    'tenant_id' => $tenantId,
                    'user_id' => Session::get('user_id'),
                    'action' => 'USER_CREATE',
                    'entity' => 'users',
                    'entity_id' => $id,
                    'details' => "Admin provisioned user {$email}",
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ]
            );
        } catch (\Throwable $e) {
            // Non-fatal
        }

        if ($this->isJsonRequest()) {
            $response->json(['status' => 'created', 'user_id' => $id], 201);
            return;
        }

        http_response_code(201);
        $response->redirect('/admin/users');
    }
}
