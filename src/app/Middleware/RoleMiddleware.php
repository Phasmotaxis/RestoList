<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

class RoleMiddleware
{
    public const ROLE_SUPERADMIN = 1 << 0; // 1
    public const ROLE_ADMIN       = 1 << 1; // 2
    public const ROLE_USER        = 1 << 2; // 4

    public static function checkPermission(int $userMask, int $requiredMask): bool
    {
        if (($userMask & self::ROLE_SUPERADMIN) === self::ROLE_SUPERADMIN) {
            return true;
        }
        return ($userMask & $requiredMask) === $requiredMask;
    }

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
