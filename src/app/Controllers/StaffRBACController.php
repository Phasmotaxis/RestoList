<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Audit.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';

use App\Core\Audit;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Middleware\RoleMiddleware;

/**
 * Staff RBAC Controller: Staff provisioning, role enforcement, and access revocation.
 *
 * Implements Subdomain 5 per docs/SPECIFICATION.md Section 2.2.B.
 * Enforces role boundaries: FrontOfHouse and Kitchen cannot provision staff;
 * Manager (Admin) cannot elevate users to SuperAdmin.
 */
class StaffRBACController
{
    private const ASSIGNABLE_ROLES = [
        RoleMiddleware::ROLE_ADMIN => 'Manager / Admin',
        RoleMiddleware::ROLE_USER => 'User',
        RoleMiddleware::ROLE_FRONTOFHOUSE => 'Front of House',
        RoleMiddleware::ROLE_KITCHEN => 'Kitchen'
    ];

    private function isJsonRequest(): bool
    {
        return (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
            || (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
            || (isset($_GET['format']) && $_GET['format'] === 'json');
    }

    private function getAuthorizedOrgId(Request $request, array $params = []): string
    {
        $requestedOrgId = (string) ($params['id'] ?? $request->getParam('id') ?? Session::get('tenant_id') ?? Session::get('org_id') ?? '');
        $sessionTenantId = (string) (Session::get('tenant_id') ?? Session::get('org_id') ?? '');
        $userMask = (int) (Session::get('role_mask') ?? 0);

        if (($userMask & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN) {
            return $requestedOrgId ?: $sessionTenantId;
        }

        if ($requestedOrgId === '') {
            $requestedOrgId = $sessionTenantId;
        }

        if ($sessionTenantId !== '' && $requestedOrgId !== '' && $requestedOrgId !== $sessionTenantId) {
            http_response_code(403);
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Forbidden', 'message' => 'Cross-tenant access prohibited']);
                exit;
            }
            require_once __DIR__ . '/../Views/errors/403.php';
            exit;
        }

        return $requestedOrgId;
    }

    /**
     * List all staff members for the restaurant.
     * Restricted to Admin (Manager) only. FrontOfHouse and Kitchen get 403.
     */
    public function index(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        $stmt = Database::executeQuery(
            'SELECT id, email, first_name, last_name, role_mask, created_at
             FROM users
             WHERE tenant_id = :org_id
             ORDER BY last_name ASC, first_name ASC',
            ['org_id' => $orgId]
        );
        $staff = $stmt->fetchAll();

        foreach ($staff as &$member) {
            $mask = (int) $member['role_mask'];
            $member['role_mask'] = $mask;
            $member['role_label'] = self::ASSIGNABLE_ROLES[$mask] ?? 'Custom Role';
        }
        unset($member);

        $invitesStmt = Database::executeQuery(
            'SELECT id, email, created_at, expires_at
             FROM org_invites
             WHERE org_id = :org_id AND claimed_by_user_id IS NULL AND expires_at > CURRENT_TIMESTAMP
             ORDER BY created_at DESC',
            ['org_id' => $orgId]
        );
        $pendingInvites = $invitesStmt->fetchAll();

        if ($this->isJsonRequest()) {
            $response->json([
                'org_id' => $orgId,
                'staff' => $staff,
                'pending_invites' => $pendingInvites
            ], 200);
            return;
        }

        $title = 'Staff & Role-Based Access Control';
        require_once __DIR__ . '/../Views/admin/staff.php';
    }

    /**
     * Show staff invite/creation form.
     */
    public function create(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);

        if ($this->isJsonRequest()) {
            $response->json([
                'assignable_roles' => self::ASSIGNABLE_ROLES,
                'fields' => [
                    'email' => 'valid email string (required)',
                    'role_mask' => 'integer: 2 (Admin), 4 (User), 8 (FrontOfHouse), 16 (Kitchen)',
                    'first_name' => 'string (optional)',
                    'last_name' => 'string (optional)'
                ]
            ], 200);
            return;
        }

        $title = 'Invite Staff Member';
        require_once __DIR__ . '/../Views/admin/staff.php';
    }

    /**
     * Invite new staff member.
     */
    public function invite(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $body = $request->getBody();

        $email = trim((string) ($body['email'] ?? ''));
        $roleMask = (int) ($body['role_mask'] ?? RoleMiddleware::ROLE_FRONTOFHOUSE);

        $fields = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fields['email'] = 'Valid email address is required';
        }

        // Privilege escalation defense: cannot assign SuperAdmin
        if (!array_key_exists($roleMask, self::ASSIGNABLE_ROLES) || ($roleMask & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN) {
            $fields['role_mask'] = 'Invalid role selected. SuperAdmin role cannot be assigned';
        }

        if (!empty($fields)) {
            $response->json(['error' => 'Validation failed', 'fields' => $fields], 422);
            return;
        }

        $inviteId = 'inv-' . bin2hex(random_bytes(8));
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        Database::executeQuery(
            'INSERT INTO org_invites (id, org_id, email, token_hash, expires_at, created_at)
             VALUES (:id, :org_id, :email, :token_hash, :expires_at, CURRENT_TIMESTAMP)',
            [
                'id' => $inviteId,
                'org_id' => $orgId,
                'email' => $email,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt
            ]
        );

        Audit::record($orgId, 'INVITE_STAFF', 'org_invites', $inviteId, json_encode([
            'email' => $email,
            'role_mask' => $roleMask,
            'role_label' => self::ASSIGNABLE_ROLES[$roleMask]
        ]));

        $response->json([
            'id' => $inviteId,
            'email' => $email,
            'role_mask' => $roleMask,
            'role_label' => self::ASSIGNABLE_ROLES[$roleMask],
            'token' => $rawToken,
            'expires_at' => $expiresAt,
            'status' => 'invited'
        ], 201);
    }

    /**
     * Revoke staff member access.
     */
    public function revokeAccess(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $userId = (string) ($params['user_id'] ?? $request->getParam('user_id') ?? '');

        $stmt = Database::executeQuery(
            'SELECT * FROM users WHERE id = :id AND tenant_id = :org_id',
            ['id' => $userId, 'org_id' => $orgId]
        );
        $user = $stmt->fetch();

        if (!$user) {
            $response->json(['error' => 'Staff user not found in this organization'], 404);
            return;
        }

        // Protection: Cannot revoke a SuperAdmin via tenant admin endpoint
        if (((int) $user['role_mask'] & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN) {
            $response->json(['error' => 'SuperAdmin privileges cannot be modified by tenant admins'], 403);
            return;
        }

        Database::executeQuery(
            'UPDATE users SET role_mask = 0 WHERE id = :id AND tenant_id = :org_id',
            ['id' => $userId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'REVOKE_STAFF', 'users', $userId, 'Staff permissions revoked (role_mask set to 0)');

        $response->json(['status' => 'revoked', 'user_id' => $userId], 200);
    }

    /**
     * Update staff role assignment.
     */
    public function updateRole(Request $request, Response $response, array $params = []): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_ADMIN);
        $orgId = $this->getAuthorizedOrgId($request, $params);
        $userId = (string) ($params['user_id'] ?? $request->getParam('user_id') ?? '');
        $body = $request->getBody();

        $newRole = (int) ($body['role_mask'] ?? 0);

        if (!array_key_exists($newRole, self::ASSIGNABLE_ROLES) || ($newRole & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN) {
            $response->json(['error' => 'Validation failed', 'fields' => ['role_mask' => 'Invalid or forbidden role mask']], 422);
            return;
        }

        $stmt = Database::executeQuery(
            'SELECT * FROM users WHERE id = :id AND tenant_id = :org_id',
            ['id' => $userId, 'org_id' => $orgId]
        );
        $user = $stmt->fetch();

        if (!$user) {
            $response->json(['error' => 'Staff user not found in this organization'], 404);
            return;
        }

        if (((int) $user['role_mask'] & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN) {
            $response->json(['error' => 'SuperAdmin privileges cannot be modified by tenant admins'], 403);
            return;
        }

        Database::executeQuery(
            'UPDATE users SET role_mask = :role WHERE id = :id AND tenant_id = :org_id',
            ['role' => $newRole, 'id' => $userId, 'org_id' => $orgId]
        );

        Audit::record($orgId, 'UPDATE_ROLE', 'users', $userId, json_encode([
            'before' => (int) $user['role_mask'],
            'after' => $newRole,
            'role_label' => self::ASSIGNABLE_ROLES[$newRole]
        ]));

        $response->json([
            'status' => 'updated',
            'user_id' => $userId,
            'role_mask' => $newRole,
            'role_label' => self::ASSIGNABLE_ROLES[$newRole]
        ], 200);
    }
}
