<?php

declare(strict_types=1);

/**
 * Enterprise Information Management System (EIMS)
 * Public Gateway Entrypoint & Canonical Request Router
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

// Initialize Live Panel Sabotage Defense Kernel
ErrorHandler::register();

// No inline script or style: every page loads only same-origin scripts and stylesheets.
// Air-gapped: zero external CDN or third-party font dependencies.
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

// 4-7. Super Admin Cross-Tenant Subsystem
$router->get('/superadmin/dashboard', [SuperAdminController::class, 'dashboard']);
$router->get('/superadmin/organizations', [SuperAdminController::class, 'organizations']);
$router->post('/superadmin/organizations', [SuperAdminController::class, 'createOrganization']);
$router->get('/superadmin/organizations/edit', [SuperAdminController::class, 'editOrganization']);
$router->post('/superadmin/organizations/update', [SuperAdminController::class, 'updateOrganization']);
$router->get('/superadmin/users', [SuperAdminController::class, 'users']);
$router->get('/superadmin/audit-logs', [SuperAdminController::class, 'auditLogs']);

// 8-9. Organization Admin Tenant Subsystem
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/users', [AdminController::class, 'createUser']);
$router->get('/admin/users/edit', [AdminController::class, 'editUser']);
$router->post('/admin/users/update', [AdminController::class, 'updateUser']);
$router->post('/admin/users/delete', [AdminController::class, 'deleteUser']);

// 10-11. Standard User Subsystem & Data Portability
$router->get('/user/dashboard', [UserController::class, 'dashboard']);
$router->get('/user/export', [UserController::class, 'exportData']);

// 12. Healthcheck Telemetry Probe (Public JSON)
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

// 13. Live Panel Sabotage Fault Trigger (SuperAdmin / Debug Defense Kernel Test)
$router->get('/diagnostics/sabotage', function (Request $req, Response $res): void {
    throw new \RuntimeException('Diagnostic Sabotage Test: Deliberate fault triggered to verify EIMS Defense Kernel error shield.');
});

// Resolve and dispatch request
$router->resolve($request, $response);
