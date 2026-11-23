<?php
declare(strict_types=1);

require_once __DIR__ . '/../../Core/Session.php';
require_once __DIR__ . '/../../Core/Request.php';
require_once __DIR__ . '/../../Middleware/RoleMiddleware.php';

use App\Core\Session;
use App\Core\Request;
use App\Middleware\RoleMiddleware;

Session::start();
$userMask = (int) (Session::get('role_mask') ?? 0);
$isLoggedIn = Session::get('user_id') !== null;

$isSuperAdmin = ($userMask & RoleMiddleware::ROLE_SUPERADMIN) === RoleMiddleware::ROLE_SUPERADMIN;
$isAdmin = ($userMask & RoleMiddleware::ROLE_ADMIN) === RoleMiddleware::ROLE_ADMIN;
$isFoh = ($userMask & RoleMiddleware::ROLE_FRONTOFHOUSE) === RoleMiddleware::ROLE_FRONTOFHOUSE;
$isKitchen = ($userMask & RoleMiddleware::ROLE_KITCHEN) === RoleMiddleware::ROLE_KITCHEN;
$isCustomer = ($userMask & RoleMiddleware::ROLE_CUSTOMER) === RoleMiddleware::ROLE_CUSTOMER;
$isStandardUser = ($userMask & RoleMiddleware::ROLE_USER) === RoleMiddleware::ROLE_USER;

// Dynamic Tenant Theme Resolution
$sessionTenantId = (string) (Session::get('tenant_id') ?? Session::get('org_id') ?? '');
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// Check cookie override first
$cookieTheme = $_COOKIE['theme'] ?? $_COOKIE['eims_theme'] ?? null;
if (in_array($cookieTheme, ['lyra', 'hiraya', 'katipunan', 'default'], true)) {
    $activeTheme = $cookieTheme;
} else {
    // Dynamic tenant resolution
    // Hiraya Bistro: tenant-hiraya-001 or URL contains 'hiraya'
    if (
        $sessionTenantId === 'tenant-hiraya-001' ||
        str_contains($requestUri, 'hiraya') ||
        (isset($restaurantData) && (($restaurantData['id'] ?? '') === 'tenant-hiraya-001' || ($restaurantData['subdomain'] ?? '') === 'hiraya-bistro'))
    ) {
        $activeTheme = 'hiraya';
    } elseif (
        $sessionTenantId === 'tenant-katipunan-002' ||
        str_contains($requestUri, 'katipunan') ||
        (isset($restaurantData) && (($restaurantData['id'] ?? '') === 'tenant-katipunan-002' || ($restaurantData['subdomain'] ?? '') === 'katipunan-grill'))
    ) {
        $activeTheme = 'katipunan';
    } else {
        // SuperAdmin/Admin and general portal default to lyra
        $activeTheme = 'lyra';
    }
}

$pageTitle = $title ?? null;
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($activeTheme, ENT_QUOTES, 'UTF-8') ?>">
<?php
require __DIR__ . '/../partials/header.php';
?>
<body>
    <header class="site-header">
        <div class="container site-header-content">
            <div>
                <h1><a href="<?= htmlspecialchars(Request::url('/'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sys['name'] ?? 'Enterprise Information Management System', ENT_QUOTES, 'UTF-8') ?></a></h1>
                <p class="subtitle"><?= htmlspecialchars(($sys['institution'] ?? 'Cavite State University') . ' - ' . ($sys['academic_term'] ?? 'DICT24 Information Management'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="theme-switcher-container">
                <label for="theme-select" class="theme-switcher-label">Theme:</label>
                <select id="theme-select" class="theme-select" aria-label="Select Color Theme">
                    <option value="lyra"<?= $activeTheme === 'lyra' ? ' selected' : '' ?>>Lyra (Platform Neon)</option>
                    <option value="hiraya"<?= $activeTheme === 'hiraya' ? ' selected' : '' ?>>Hiraya Bistro (Filipiniana)</option>
                    <option value="katipunan"<?= $activeTheme === 'katipunan' ? ' selected' : '' ?>>Katipunan Grill (Rustic Smokehouse)</option>
                    <option value="default"<?= $activeTheme === 'default' ? ' selected' : '' ?>>Default (Neutral)</option>
                </select>
            </div>
        </div>
        <div class="container">
            <nav class="site-nav" aria-label="Main Application Navigation">
                <?php if (!$isLoggedIn): ?>
                    <a href="<?= htmlspecialchars(Request::url('/'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Home</a>
                    <a href="<?= htmlspecialchars(Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Discover Restaurants</a>
                    <a href="<?= htmlspecialchars(Request::url('/health'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Health Telemetry</a>
                    <a href="<?= htmlspecialchars(Request::url('/login'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item nav-item-primary">Sign In &rarr;</a>
                <?php elseif ($isSuperAdmin): ?>
                    <a href="<?= htmlspecialchars(Request::url('/superadmin/dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Control Plane</a>
                    <a href="<?= htmlspecialchars(Request::url('/superadmin/organizations'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Organizations</a>
                    <a href="<?= htmlspecialchars(Request::url('/superadmin/users'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">User Directory</a>
                    <a href="<?= htmlspecialchars(Request::url('/superadmin/audit-logs'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Audit Logs</a>
                    <a href="<?= htmlspecialchars(Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Marketplace</a>
                    <form method="POST" action="<?= htmlspecialchars(Request::url('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="nav-logout-form">
                        <?= Session::csrfField() ?>
                        <button type="submit" class="nav-logout-btn">Logout</button>
                    </form>
                <?php elseif ($isAdmin): ?>
                    <a href="<?= htmlspecialchars(Request::url('/admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Dashboard</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/tables'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Tables & Floor</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/zones'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Zones</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/reservations'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Reservations</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/waitlist'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Waitlist</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/menu'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Menu & 86</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/staff'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Staff RBAC</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/analytics'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Analytics</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/reviews'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Reviews</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/users'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Users</a>
                    <a href="<?= htmlspecialchars(Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Marketplace</a>
                    <form method="POST" action="<?= htmlspecialchars(Request::url('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="nav-logout-form">
                        <?= Session::csrfField() ?>
                        <button type="submit" class="nav-logout-btn">Logout</button>
                    </form>
                <?php elseif ($isFoh): ?>
                    <a href="<?= htmlspecialchars(Request::url('/admin/tables'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Floor Plan & Tables</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/zones'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Dining Zones</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/reservations'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Reservations</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/waitlist'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Waitlist Queue</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/menu'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Menu Availability</a>
                    <a href="<?= htmlspecialchars(Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Marketplace</a>
                    <form method="POST" action="<?= htmlspecialchars(Request::url('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="nav-logout-form">
                        <?= Session::csrfField() ?>
                        <button type="submit" class="nav-logout-btn">Logout</button>
                    </form>
                <?php elseif ($isKitchen): ?>
                    <a href="<?= htmlspecialchars(Request::url('/admin/menu/86'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">86-List Toggle</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/menu'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Menu Catalog</a>
                    <a href="<?= htmlspecialchars(Request::url('/admin/reservations'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Upcoming Covers</a>
                    <a href="<?= htmlspecialchars(Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Marketplace</a>
                    <form method="POST" action="<?= htmlspecialchars(Request::url('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="nav-logout-form">
                        <?= Session::csrfField() ?>
                        <button type="submit" class="nav-logout-btn">Logout</button>
                    </form>
                <?php elseif ($isCustomer): ?>
                    <a href="<?= htmlspecialchars(Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Discover Restaurants</a>
                    <a href="<?= htmlspecialchars(Request::url('/user/dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">My Account</a>
                    <a href="<?= htmlspecialchars(Request::url('/user/export'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Export Data</a>
                    <form method="POST" action="<?= htmlspecialchars(Request::url('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="nav-logout-form">
                        <?= Session::csrfField() ?>
                        <button type="submit" class="nav-logout-btn">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="<?= htmlspecialchars(Request::url('/user/dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Workspace</a>
                    <a href="<?= htmlspecialchars(Request::url('/user/export'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Data Export</a>
                    <a href="<?= htmlspecialchars(Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">Marketplace</a>
                    <form method="POST" action="<?= htmlspecialchars(Request::url('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="nav-logout-form">
                        <?= Session::csrfField() ?>
                        <button type="submit" class="nav-logout-btn">Logout</button>
                    </form>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">
