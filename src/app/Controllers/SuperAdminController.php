<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditLogger;

/**
 * SuperAdminController: Unified SuperAdmin Portal (Tenant Management + Feature Flags + Audit Logs + Sessions)
 *
 * Wave 1: SuperAdmin-only endpoints for org lifecycle, feature toggles, audit viewing, and emergency session revocation.
 * All endpoints enforce SuperAdmin role via middleware + Session context.
 * Multi-tenant: SuperAdmin views/manages data across ALL orgs (not scoped to single tenant).
 *
 * Routes:
 * GET    /superadmin/tenants               → listTenants()
 * GET    /superadmin/tenants/create        → createForm()
 * POST   /superadmin/tenants               → store()
 * GET    /superadmin/tenants/:id/edit      → editForm()
 * POST   /superadmin/tenants/:id           → update()
 * POST   /superadmin/tenants/:id/flags/:flag/toggle → toggleFlag()
 * POST   /superadmin/tenants/:id/invite    → issueInvite()
 * GET    /superadmin/audit                 → viewAuditLogs()
 * GET    /superadmin/sessions              → listSessions()
 * POST   /superadmin/sessions/:id/revoke   → revokeSession()
 *
 * Security:
 * - CSRF token validation on all POST endpoints
 * - Org-agnostic viewing (not filtered by tenant_id); logs all changes
 * - Email stubs: invite tokens generated but not sent (logged to audit)
 * - Error handling: 422 validation, 404 not found, 403 unauthorized, 500 server error
 *
 * @author DICT24 Wave 1 Layer 1
 */
class SuperAdminController
{
    private Database $pdo;
    private Request $request;
    private Response $response;
    private AuditLogger $auditLogger;

    public function __construct(Database $pdo, Request $request, Response $response, AuditLogger $auditLogger)
    {
        $this->pdo = $pdo;
        $this->request = $request;
        $this->response = $response;
        $this->auditLogger = $auditLogger;

        // Enforce SuperAdmin role
        if (Session::get('role') !== 'superadmin') {
            http_response_code(403);
            exit('Access denied: SuperAdmin role required');
        }
    }

    // ========== Tenant Management ==========

    /**
     * List all organizations with subscription tier, feature flags summary, user count
     *
     * GET /superadmin/tenants
     */
    public function listTenants(): void
    {
        $stmt = $this->pdo->prepare('
            SELECT id, name, subscription_tier, feature_flags, created_at, archived_at,
                   (SELECT COUNT(*) FROM users WHERE org_id = organizations.id AND archived_at IS NULL) AS user_count
            FROM organizations
            ORDER BY created_at DESC
        ');
        $stmt->execute();
        $orgs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Render view: src/app/Views/superadmin/tenants/index.php
        extract(['orgs' => $orgs]);
        require_once __DIR__ . '/../Views/superadmin/tenants/index.php';
    }

    /**
     * Show tenant creation form
     *
     * GET /superadmin/tenants/create
     */
    public function createForm(): void
    {
        $subscriptionTiers = ['free', 'professional', 'enterprise'];
        $defaultFlags = json_encode([
            'enable_floor_plan' => false,
            'enable_inventory_erp' => false,
            'enable_deposits' => false,
            'enable_loyalty' => false,
        ]);

        require_once __DIR__ . '/../Views/superadmin/tenants/form.php';
    }

    /**
     * Store new organization
     *
     * POST /superadmin/tenants
     *
     * @return void
     * @throws \Exception If insertion fails
     */
    public function store(): void
    {
        // Validate CSRF token
        if ($_POST['_csrf'] !== Session::getCsrfToken()) {
            http_response_code(403);
            exit('CSRF token mismatch');
        }

        $name = trim($_POST['name'] ?? '');
        $subscriptionTier = trim($_POST['subscription_tier'] ?? 'free');

        // Validation
        if (empty($name)) {
            http_response_code(422);
            exit(json_encode(['error' => 'Organization name is required']));
        }

        $featureFlags = json_encode([
            'enable_floor_plan' => isset($_POST['enable_floor_plan']),
            'enable_inventory_erp' => isset($_POST['enable_inventory_erp']),
            'enable_deposits' => isset($_POST['enable_deposits']),
            'enable_loyalty' => isset($_POST['enable_loyalty']),
        ]);

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO organizations (name, subscription_tier, feature_flags, created_at)
                VALUES (:name, :tier, :flags, NOW())
            ');
            $stmt->execute([
                ':name' => $name,
                ':tier' => $subscriptionTier,
                ':flags' => $featureFlags,
            ]);

            $orgId = (int)$this->pdo->lastInsertId();

            // Audit log
            AuditLogger::log(
                org_id: 0, // SuperAdmin scope (org 0)
                actor_id: (int)Session::get('user_id'),
                action: 'CREATE',
                entity: 'organization',
                after: ['id' => $orgId, 'name' => $name, 'tier' => $subscriptionTier]
            );

            header("Location: /superadmin/tenants/$orgId/edit");
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            exit(json_encode(['error' => 'Failed to create organization']));
        }
    }

    /**
     * Show organization edit form
     *
     * GET /superadmin/tenants/:id/edit
     */
    public function editForm(int $orgId): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM organizations WHERE id = :id');
        $stmt->execute([':id' => $orgId]);
        $org = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$org) {
            http_response_code(404);
            exit('Organization not found');
        }

        $subscriptionTiers = ['free', 'professional', 'enterprise'];
        $flags = json_decode($org['feature_flags'] ?? '{}', true);

        require_once __DIR__ . '/../Views/superadmin/tenants/form.php';
    }

    /**
     * Update organization
     *
     * POST /superadmin/tenants/:id
     */
    public function update(int $orgId): void
    {
        if ($_POST['_csrf'] !== Session::getCsrfToken()) {
            http_response_code(403);
            exit('CSRF token mismatch');
        }

        $name = trim($_POST['name'] ?? '');
        $subscriptionTier = trim($_POST['subscription_tier'] ?? '');

        if (empty($name)) {
            http_response_code(422);
            exit(json_encode(['error' => 'Organization name is required']));
        }

        $featureFlags = json_encode([
            'enable_floor_plan' => isset($_POST['enable_floor_plan']),
            'enable_inventory_erp' => isset($_POST['enable_inventory_erp']),
            'enable_deposits' => isset($_POST['enable_deposits']),
            'enable_loyalty' => isset($_POST['enable_loyalty']),
        ]);

        try {
            $stmt = $this->pdo->prepare('
                UPDATE organizations
                SET name = :name, subscription_tier = :tier, feature_flags = :flags
                WHERE id = :id
            ');
            $stmt->execute([
                ':name' => $name,
                ':tier' => $subscriptionTier,
                ':flags' => $featureFlags,
                ':id' => $orgId,
            ]);

            AuditLogger::log(
                org_id: 0,
                actor_id: (int)Session::get('user_id'),
                action: 'UPDATE',
                entity: 'organization',
                after: ['id' => $orgId, 'name' => $name, 'flags' => $featureFlags]
            );

            header("Location: /superadmin/tenants");
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            exit(json_encode(['error' => 'Failed to update organization']));
        }
    }

    /**
     * Toggle a feature flag for organization
     *
     * POST /superadmin/tenants/:id/flags/:flag/toggle
     * Body: _csrf, enabled (1 or 0)
     */
    public function toggleFlag(int $orgId, string $flag): void
    {
        if ($_POST['_csrf'] !== Session::getCsrfToken()) {
            http_response_code(403);
            exit('CSRF token mismatch');
        }

        $enabled = isset($_POST['enabled']) ? (bool)$_POST['enabled'] : false;

        try {
            $stmt = $this->pdo->prepare('
                UPDATE organizations
                SET feature_flags = JSON_SET(feature_flags, :path, :value)
                WHERE id = :id
            ');
            $stmt->execute([
                ':path' => "$.enable_$flag",
                ':value' => $enabled ? 'true' : 'false',
                ':id' => $orgId,
            ]);

            AuditLogger::log(
                org_id: 0,
                actor_id: (int)Session::get('user_id'),
                action: 'UPDATE',
                entity: 'feature_flag',
                after: ['org_id' => $orgId, 'flag' => $flag, 'enabled' => $enabled]
            );

            http_response_code(200);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to toggle flag']);
        }
    }

    /**
     * Issue admin invite token for organization
     *
     * POST /superadmin/tenants/:id/invite
     * Body: _csrf, email
     */
    public function issueInvite(int $orgId): void
    {
        if ($_POST['_csrf'] !== Session::getCsrfToken()) {
            http_response_code(403);
            exit('CSRF token mismatch');
        }

        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            exit(json_encode(['error' => 'Invalid email address']));
        }

        // Generate hashed invite token
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO org_invites (org_id, email, token_hash, created_at, expires_at)
                VALUES (:org_id, :email, :token_hash, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))
            ');
            $stmt->execute([
                ':org_id' => $orgId,
                ':email' => $email,
                ':token_hash' => $tokenHash,
            ]);

            AuditLogger::log(
                org_id: 0,
                actor_id: (int)Session::get('user_id'),
                action: 'CREATE',
                entity: 'org_invite',
                after: ['org_id' => $orgId, 'email' => $email, 'token' => '***REDACTED***']
            );

            // Stub: email not sent, log only
            error_log("Invite token for $email: $token (org_id=$orgId)");

            http_response_code(201);
            echo json_encode(['success' => true, 'token_hash' => substr($tokenHash, 0, 8) . '...']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to issue invite']);
        }
    }

    // ========== Audit Logs ==========

    /**
     * View audit logs across all organizations
     *
     * GET /superadmin/audit?org_id=1&action=CREATE&entity=reservation
     */
    public function viewAuditLogs(): void
    {
        $orgId = (int)($_GET['org_id'] ?? 0);
        $action = trim($_GET['action'] ?? '');
        $entity = trim($_GET['entity'] ?? '');
        $limit = 50;
        $offset = (int)($_GET['page'] ?? 0) * $limit;

        $sql = 'SELECT * FROM audit_logs';
        $params = [];

        if ($orgId > 0) {
            $sql .= ' WHERE org_id = :org_id';
            $params[':org_id'] = $orgId;
        }
        if ($action) {
            $sql .= ($orgId > 0 ? ' AND' : ' WHERE') . ' action = :action';
            $params[':action'] = $action;
        }
        if ($entity) {
            $sql .= ($orgId > 0 || $action ? ' AND' : ' WHERE') . ' target_entity = :entity';
            $params[':entity'] = $entity;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Views/superadmin/audit_logs.php';
    }

    // ========== Sessions ==========

    /**
     * List active user sessions
     *
     * GET /superadmin/sessions
     */
    public function listSessions(): void
    {
        $stmt = $this->pdo->prepare('
            SELECT s.id, u.email, u.org_id, s.created_at, s.last_activity_at
            FROM sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.expires_at > NOW()
            ORDER BY s.last_activity_at DESC
        ');
        $stmt->execute();
        $sessions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Views/superadmin/sessions.php';
    }

    /**
     * Revoke user session (emergency access control)
     *
     * POST /superadmin/sessions/:id/revoke
     */
    public function revokeSession(string $sessionId): void
    {
        if ($_POST['_csrf'] !== Session::getCsrfToken()) {
            http_response_code(403);
            exit('CSRF token mismatch');
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE id = :id');
            $stmt->execute([':id' => $sessionId]);

            AuditLogger::log(
                org_id: 0,
                actor_id: (int)Session::get('user_id'),
                action: 'DELETE',
                entity: 'session',
                before: ['id' => $sessionId]
            );

            header("Location: /superadmin/sessions");
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            exit(json_encode(['error' => 'Failed to revoke session']));
        }
    }
}
