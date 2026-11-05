# Subsystem Contract: Role-Based Access Control (RBAC)

- **Parent Specification**: [System Architecture](../architecture.md)
- **Active Task Ledger**: [Subsystem Tasks](../../ai/tasks.md)
- **Changelog Tracker**: [Project Changelog](../../../CHANGELOG.md)
- **Target Implementation**: `src/app/Middleware/RoleMiddleware.php`

---

## 1. Interface Specification
Role-Based Access Control governs access to protected route handlers using an integer bitmask permission model.

```php
namespace App\Middleware;

interface RoleMiddlewareInterface
{
    public const ROLE_SUPERADMIN = 1 << 0; // 1
    public const ROLE_ADMIN       = 1 << 1; // 2
    public const ROLE_USER        = 1 << 2; // 4

    public static function checkPermission(int $userMask, int $requiredMask): bool;
    public static function handle(int $requiredMask): void;
}
```

## 2. Invariants
1. **Mathematical Representation**: Roles and granular privileges are represented as powers of two.
2. **Evaluation Logic**: Access is granted if and only if `($userMask & $requiredMask) === $requiredMask` (or non-zero for any-role access).
3. **Session Verification**: The middleware inspects the active session state (`$_SESSION['user']['role_mask']`). If missing or invalid, execution aborts with HTTP 401 Unauthorized or redirects to login.
4. **Denial Interception**: If the evaluated mask lacks required privileges, the middleware aborts execution with HTTP 403 Forbidden and renders `src/app/Views/errors/403.php`.
