<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

/**
 * Role-Based Access Control Middleware: Bitwise RBAC with superadmin override.
 *
 * Enforces permission checks using bitwise masks (ROLE_SUPERADMIN=1, ROLE_ADMIN=2, ROLE_USER=4).
 * SuperAdmin (mask & 1) bypasses all permission checks. Other roles checked against required mask.
 * Used to guard route handlers by role: only users with sufficient role_mask proceed.
 */
class RoleMiddleware
{
    public const ROLE_SUPERADMIN = 1 << 0; // 1
    public const ROLE_ADMIN       = 1 << 1; // 2
    public const ROLE_USER        = 1 << 2; // 4

    /**
     * Check if user role mask satisfies required permission.
     *
     * SuperAdmin (bit 0 set) always returns true (bypass check). Other roles checked
     * using bitwise AND: (userMask & requiredMask) === requiredMask.
     *
     * @param int $userMask Bitwise role mask from user record (session role_mask).
     * @param int $requiredMask Required permission mask (e.g. ROLE_ADMIN).
     *
     * @return bool true if user has permission or is superadmin; false otherwise.
     *
     * @example checkPermission(2, 2) returns true (Admin has Admin permission).
     * @example checkPermission(1, 2) returns true (SuperAdmin bypasses check).
     * @example checkPermission(4, 2) returns false (User lacks Admin permission).
     */
    public static function checkPermission(int $userMask, int $requiredMask): bool
    {
        if (($userMask & self::ROLE_SUPERADMIN) === self::ROLE_SUPERADMIN) {
            return true;
        }
        return ($userMask & $requiredMask) === $requiredMask;
    }

    /**
     * Enforce role-based access control: check auth AND required permission.
     *
     * Calls AuthMiddleware to ensure authenticated, then checks user's role_mask against
     * requiredMask via checkPermission(). If permission denied, renders 403 Forbidden
     * view and exits. No return value on denial (caller never reached).
     *
     * @param int $requiredMask Required permission mask (e.g. ROLE_ADMIN, ROLE_SUPERADMIN).
     *
     * @return void Exits with HTTP 403 if permission denied; returns normally if allowed.
     */
    public static function handle(int $requiredMask): void
    {
        AuthMiddleware::handle();
        $userMask = (int) (Session::get('role_mask') ?? 0);

        if (!self::checkPermission($userMask, $requiredMask)) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit;
        }
    }
}
