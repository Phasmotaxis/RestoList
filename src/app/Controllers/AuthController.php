<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\LoginThrottle;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\RoleMiddleware;

class AuthController
{
    /**
     * Bcrypt hash of a random string nobody knows. Unknown emails verify against it so they pay
     * the same bcrypt cost as a real account. A constant avoids depending on per-process state.
     */
    private const DUMMY_HASH = '$2y$10$aWpO3NylBq3SqsmQm1GCruhcUeyROHLVGpi7H6mW9tXfkU5McxBH6';

    public function showLogin(Request $request, Response $response): void
    {
        Session::start();
        $userId = Session::get('user_id');
        if ($userId !== null) {
            $mask = (int) Session::get('role_mask', 0);
            if (($mask & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN) {
                $response->redirect('/superadmin/dashboard');
                return;
            }
            if (($mask & RoleMiddleware::ROLE_ADMIN) === RoleMiddleware::ROLE_ADMIN) {
                $response->redirect('/admin/dashboard');
                return;
            }
            $response->redirect('/user/dashboard');
            return;
        }

        require_once __DIR__ . '/../Views/auth/login.php';
    }

    public function login(Request $request, Response $response): void
    {
        Session::start();
        Session::verifyCsrf();
        $body = $request->getBody();
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Email and password are required.';
            require_once __DIR__ . '/../Views/auth/login.php';
            return;
        }

        $ip = LoginThrottle::resolveClientIp();
        if (LoginThrottle::isBlocked($ip, $email)) {
            $error = 'Too many failed attempts. Try again in 15 minutes.';
            http_response_code(429);
            require_once __DIR__ . '/../Views/auth/login.php';
            return;
        }

        try {
            $stmt = Database::executeQuery(
                'SELECT id, tenant_id, email, password_hash, first_name, last_name, role_mask FROM users WHERE email = :email LIMIT 1',
                ['email' => $email]
            );
            $user = $stmt->fetch();

            // Verify against a dummy hash when the email is unknown, so both paths take similar time.
            $hash = $user ? (string) $user['password_hash'] : self::DUMMY_HASH;
            $passwordOk = password_verify($password, $hash);

            if ($user && $passwordOk) {
                LoginThrottle::reset($email);
                Session::regenerate();
                Session::set('user_id', $user['id']);
                Session::set('tenant_id', $user['tenant_id']);
                Session::set('email', $user['email']);
                Session::set('first_name', $user['first_name']);
                Session::set('last_name', $user['last_name']);
                Session::set('role_mask', (int) $user['role_mask']);

                // Record audit trail
                try {
                    Database::executeQuery(
                        'INSERT INTO audit_logs (id, tenant_id, user_id, action, entity, entity_id, details, ip_address) VALUES (:id, :tenant_id, :user_id, :action, :entity, :entity_id, :details, :ip)',
                        [
                            'id' => 'log-' . bin2hex(random_bytes(8)),
                            'tenant_id' => $user['tenant_id'],
                            'user_id' => $user['id'],
                            'action' => 'AUTH_LOGIN',
                            'entity' => 'users',
                            'entity_id' => $user['id'],
                            'details' => 'User logged in successfully',
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                        ]
                    );
                } catch (\Throwable $e) {
                    error_log('[WARN] Failed to write login audit log: ' . $e->getMessage());
                }

                $mask = (int) $user['role_mask'];
                if (($mask & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN) {
                    $response->redirect('/superadmin/dashboard');
                    return;
                }
                if (($mask & RoleMiddleware::ROLE_ADMIN) === RoleMiddleware::ROLE_ADMIN) {
                    $response->redirect('/admin/dashboard');
                    return;
                }
                $response->redirect('/user/dashboard');
                return;
            }
        } catch (\Throwable $e) {
            error_log('[ERROR] Login authentication error: ' . $e->getMessage());
        }

        LoginThrottle::recordFailure($ip, $email);
        $error = 'Invalid email or password.';
        http_response_code(401);
        require_once __DIR__ . '/../Views/auth/login.php';
    }

    public function logout(Request $request, Response $response): void
    {
        Session::start();
        if ($request->getMethod() === 'POST') {
            Session::verifyCsrf();
        }
        $userId = Session::get('user_id');
        $tenantId = Session::get('tenant_id');

        if ($userId !== null && $tenantId !== null) {
            try {
                Database::executeQuery(
                    'INSERT INTO audit_logs (id, tenant_id, user_id, action, entity, entity_id, details, ip_address) VALUES (:id, :tenant_id, :user_id, :action, :entity, :entity_id, :details, :ip)',
                    [
                        'id' => 'log-' . bin2hex(random_bytes(8)),
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'action' => 'AUTH_LOGOUT',
                        'entity' => 'users',
                        'entity_id' => $userId,
                        'details' => 'User logged out',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    ]
                );
            } catch (\Throwable $e) {
                // Non-fatal audit log failure
            }
        }

        Session::destroy();
        $response->redirect('/login');
    }
}
