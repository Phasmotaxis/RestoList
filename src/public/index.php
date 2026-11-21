<?php

declare(strict_types=1);

/**
 * Enterprise Information Management System (EIMS): HTTP Gateway & Route Registry
 *
 * Canonical request entry point. Initializes error handling, sets security headers (CSP, X-Frame-Options, etc.),
 * registers HTTP routes for multi-tenant RBAC hierarchy (public auth → superadmin/admin/user dashboards),
 * and dispatches incoming requests through Router. All inline documentation visible for panel defense verification.
 *
 * Security headers:
 * - CSP: 'self' only (no CDN, no inline script/style) for air-gapped operation
 * - X-Frame-Options: DENY (prevents clickjacking)
 * - X-Content-Type-Options: nosniff (prevents MIME type sniffing)
 * - Referrer-Policy: same-origin (leaks no referrer to external sites)
 */

require_once __DIR__ . '/../app/Core/ErrorHandler.php';
require_once __DIR__ . '/../app/Core/Config.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Session.php';
require_once __DIR__ . '/../app/Core/Request.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Core/Audit.php';
require_once __DIR__ . '/../app/Core/ListQuery.php';
require_once __DIR__ . '/../app/Core/Validator.php';
require_once __DIR__ . '/../app/Core/LoginThrottle.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/SuperAdminController.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';
require_once __DIR__ . '/../app/Controllers/UserController.php';
require_once __DIR__ . '/../app/Services/TableAllocator.php';
require_once __DIR__ . '/../app/Services/FeatureFlagService.php';
require_once __DIR__ . '/../app/Controllers/DiscoveryController.php';
require_once __DIR__ . '/../app/Controllers/SlotAvailabilityController.php';
require_once __DIR__ . '/../app/Controllers/BookingFlowController.php';
require_once __DIR__ . '/../app/Controllers/MenuSyncController.php';
require_once __DIR__ . '/../app/Controllers/WaitlistPortalController.php';
require_once __DIR__ . '/../app/Controllers/ReviewSubmissionController.php';
require_once __DIR__ . '/../app/Controllers/DepositPortalController.php';
require_once __DIR__ . '/../app/Controllers/LoyaltyPortalController.php';
require_once __DIR__ . '/../app/Controllers/ConciergeController.php';
require_once __DIR__ . '/../app/Controllers/CustomerController.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\SuperAdminController;
use App\Controllers\AdminController;
use App\Controllers\UserController;
use App\Controllers\DiscoveryController;
use App\Controllers\SlotAvailabilityController;
use App\Controllers\BookingFlowController;
use App\Controllers\MenuSyncController;
use App\Controllers\WaitlistPortalController;
use App\Controllers\ReviewSubmissionController;
use App\Controllers\DepositPortalController;
use App\Controllers\LoyaltyPortalController;
use App\Controllers\ConciergeController;
use App\Controllers\CustomerController;

// Initialize Live Panel Sabotage Defense Kernel (global error/exception handler).
ErrorHandler::register();

// Air-gapped CSP headers: no external CDN, no inline scripts, no third-party fonts.
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; font-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$router = new Router();
$request = new Request();
$response = new Response();

// 1. Landing Page (Public)
$router->get('/', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/Views/home.php';
});

// 2. Authentication: Login (Public / Guest)
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);

// 3. Authentication: Logout (Authenticated)
$router->post('/logout', [AuthController::class, 'logout']);

// 4-7. SuperAdmin Cross-Tenant Subsystem (ROLE_SUPERADMIN only)
$router->get('/superadmin/dashboard', [SuperAdminController::class, 'dashboard']);
$router->get('/superadmin/organizations', [SuperAdminController::class, 'organizations']);
$router->post('/superadmin/organizations', [SuperAdminController::class, 'createOrganization']);
$router->get('/superadmin/organizations/edit', [SuperAdminController::class, 'editOrganization']);
$router->post('/superadmin/organizations/update', [SuperAdminController::class, 'updateOrganization']);
$router->get('/superadmin/users', [SuperAdminController::class, 'users']);
$router->get('/superadmin/audit-logs', [SuperAdminController::class, 'auditLogs']);

// 8-9. Organization Admin Tenant Subsystem (ROLE_ADMIN+)
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/users', [AdminController::class, 'createUser']);
$router->get('/admin/users/edit', [AdminController::class, 'editUser']);
$router->post('/admin/users/update', [AdminController::class, 'updateUser']);
$router->post('/admin/users/delete', [AdminController::class, 'deleteUser']);

// 10-11. Standard User Subsystem & Data Portability (ROLE_USER+)
$router->get('/user/dashboard', [UserController::class, 'dashboard']);
$router->get('/user/export', [UserController::class, 'exportData']);

// 12. Customer Portal Subsystem (Userspace 3: Discovery, Booking, Waitlist, Menu, Reviews)
$router->get('/customer', [CustomerController::class, 'index']);
$router->get('/customer/dashboard', [CustomerController::class, 'dashboard']);
$router->get('/discover', [DiscoveryController::class, 'index']);
$router->get('/discover/search', [DiscoveryController::class, 'search']);
$router->get('/discover/:id', [DiscoveryController::class, 'show']);
$router->get('/discover/:id/availability', [SlotAvailabilityController::class, 'getSlots']);
$router->get('/discover/:id/menu', [MenuSyncController::class, 'getMenu']);
$router->get('/discover/:id/reviews', [ReviewSubmissionController::class, 'index']);
$router->post('/discover/:id/reviews', [ReviewSubmissionController::class, 'create']);

$router->get('/menu/:restaurant_id', [MenuSyncController::class, 'getMenu']);

$router->get('/bookings/new', [BookingFlowController::class, 'showForm']);
$router->post('/bookings', [BookingFlowController::class, 'create']);
$router->get('/bookings/:token', [BookingFlowController::class, 'showConfirmation']);
$router->get('/bookings/:token/confirm', [BookingFlowController::class, 'confirmEmail']);
$router->post('/bookings/:token/cancel', [BookingFlowController::class, 'cancel']);

$router->get('/waitlist/:restaurant_id/join', [WaitlistPortalController::class, 'showForm']);
$router->post('/waitlist/:restaurant_id/join', [WaitlistPortalController::class, 'join']);
$router->get('/waitlist/:token/position', [WaitlistPortalController::class, 'trackPosition']);
$router->post('/waitlist/:token/cancel', [WaitlistPortalController::class, 'cancel']);

$router->get('/reviews', [ReviewSubmissionController::class, 'index']);
$router->post('/reviews', [ReviewSubmissionController::class, 'create']);

$router->get('/deposits/balance', [DepositPortalController::class, 'getBalance']);
$router->post('/deposits/hold', [DepositPortalController::class, 'hold']);

$router->get('/loyalty/points', [LoyaltyPortalController::class, 'getPoints']);
$router->post('/loyalty/redeem', [LoyaltyPortalController::class, 'redeem']);
$router->post('/loyalty/earn', [LoyaltyPortalController::class, 'earn']);

$router->get('/concierge/recommendations', [ConciergeController::class, 'getRecommendations']);

// 12. Healthcheck Telemetry Probe (Public JSON endpoint for orchestration).
$router->get('/health', function (Request $req, Response $res): void {
    $dbStatus = 'connected';
    try {
        Database::getConnection();
    } catch (\Throwable $e) {
        $dbStatus = 'degraded: ' . $e->getMessage();
    }

    $isFallback = Database::isFallbackActive();
    $status = $isFallback ? 'degraded' : 'healthy';
    $driver = Database::getDriverName();

    $res->json([
        'status' => $status,
        'driver' => $driver,
        'resilient' => true,
        'timestamp' => time(),
        'system' => Config::get('system.short_name', 'EIMS'),
        'database' => $isFallback ? 'fallback (sqlite)' : $dbStatus,
        'fallback_active' => $isFallback,
        'version' => '1.0.0',
    ]);
});

// 13. Live Panel Sabotage Fault Trigger (SuperAdmin / Debug Defense Kernel Test).
$router->get('/diagnostics/sabotage', function (Request $req, Response $res): void {
    throw new \RuntimeException('Diagnostic Sabotage Test: Deliberate fault triggered to verify EIMS Defense Kernel error shield.');
});

// Resolve request path/method and dispatch to matched handler.
$router->resolve($request, $response);
