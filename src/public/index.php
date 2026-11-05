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
$router->get('/logout', [AuthController::class, 'logout']);

// 4-7. Super Admin Cross-Tenant Subsystem
$router->get('/superadmin/dashboard', [SuperAdminController::class, 'dashboard']);
$router->get('/superadmin/organizations', [SuperAdminController::class, 'organizations']);
$router->post('/superadmin/organizations', [SuperAdminController::class, 'createOrganization']);
$router->get('/superadmin/users', [SuperAdminController::class, 'users']);
$router->get('/superadmin/audit-logs', [SuperAdminController::class, 'auditLogs']);

// 8-9. Organization Admin Tenant Subsystem
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/users', [AdminController::class, 'createUser']);

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

    $res->json([
        'status' => 'healthy',
        'timestamp' => time(),
        'system' => Config::get('system.short_name', 'EIMS'),
        'database' => $dbStatus,
        'fallback_active' => Database::isFallbackActive(),
        'version' => '1.0.0',
    ]);
});

// 13. Live Panel Sabotage Fault Trigger (SuperAdmin / Debug Defense Kernel Test)
$router->get('/diagnostics/sabotage', function (Request $req, Response $res): void {
    throw new \RuntimeException('Diagnostic Sabotage Test: Deliberate fault triggered to verify EIMS Defense Kernel error shield.');
});

// Resolve and dispatch request
$router->resolve($request, $response);
