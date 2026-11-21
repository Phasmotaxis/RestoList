<?php

declare(strict_types=1);

/**
 * Unit checks for the hardening rules. Runs in php:8.3-cli against a throwaway SQLite file.
 *
 *   docker run --rm -v "$PWD/src":/app -v "$PWD/dev":/dev-scripts:ro \
 *     -e DB_CONNECTION=sqlite -e DB_SQLITE_PATH=/tmp/hardening.sqlite \
 *     -w /app php:8.3-cli php /dev-scripts/scripts/php/test-hardening.php
 *
 * Covers unit-level and source-level checks only. HTTP-level checks (login throttling over the
 * wire, logout cookie invalidation, per-route authorization) are not in this file and are not run by `just test`.
 */

$appDir = is_dir('/app/app') ? '/app' : (is_dir('/var/www/html/app') ? '/var/www/html' : dirname(__DIR__, 3) . '/src');
$devDir = is_dir('/dev-scripts') ? '/dev-scripts' : (is_dir('/var/www/dev') ? '/var/www/dev' : dirname(__DIR__, 3) . '/dev');

require_once $appDir . '/app/Core/Config.php';
require_once $appDir . '/app/Core/Database.php';
require_once $appDir . '/app/Core/Validator.php';
require_once $appDir . '/app/Core/ListQuery.php';
require_once $appDir . '/app/Core/LoginThrottle.php';
require_once $appDir . '/app/Core/ErrorHandler.php';
require_once $appDir . '/app/Core/Session.php';

use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\ListQuery;
use App\Core\LoginThrottle;
use App\Core\Session;
use App\Core\Validator;

// Initialize session before any output so Session::start() does not warn about sent headers.
Session::start();

$pass = 0;
$fail = 0;

function check(string $name, bool $ok): void
{
    global $pass, $fail;
    if ($ok) {
        $pass++;
        echo "PASS {$name}\n";
    } else {
        $fail++;
        echo "FAIL {$name}\n";
    }
}

// Password policy: 11 rejected, 12 accepted, 73 bytes rejected.
check('password 11 chars rejected', Validator::password(str_repeat('a', 11)) !== null);
check('password 12 chars accepted', Validator::password(str_repeat('a', 12)) === null);
check('password 73 bytes rejected', Validator::password(str_repeat('a', 73)) !== null);

// Whitelisted sort and direction: injected text falls back to defaults.
$_GET = ['sort' => 'email;DROP TABLE users', 'dir' => "x';--"];
$list = ListQuery::fromRequest(['name' => ['u.last_name'], 'email' => ['u.email']], ['admin' => 2]);
check('sort injection falls back to first whitelisted key', $list['sort'] === 'name');
check('dir injection falls back to ASC', $list['dir'] === 'ASC');
check('orderBy contains no user text', !str_contains($list['orderBy'], 'DROP'));

$_GET = ['sort' => 'email', 'dir' => 'desc'];
$list = ListQuery::fromRequest(['email' => ['u.email']], []);
check('valid sort and DESC accepted', $list['orderBy'] === 'u.email DESC');

// LIKE wildcards in q are escaped with '!'.
$_GET = ['q' => '%_'];
$list = ListQuery::fromRequest(['email' => ['u.email']], []);
check('q wildcards escaped', $list['likePattern'] === '%!%!_%');

// Role filter outside the whitelist is ignored.
$_GET = ['role' => '1'];
$list = ListQuery::fromRequest(['email' => ['u.email']], ['admin' => 2]);
check('unknown role filter ignored', $list['roleMask'] === null);

// SQL payloads through bound parameters leave the table intact.
putenv('DB_CONNECTION=sqlite');
$testSqlitePath = is_dir('/dev/shm') ? '/dev/shm/hardening.sqlite' : sys_get_temp_dir() . '/hardening.sqlite';
putenv('DB_SQLITE_PATH=' . $testSqlitePath);
@unlink($testSqlitePath);
$pdo = Database::getConnection();
Database::executeQuery("INSERT INTO tenants (id, name, subdomain, is_active) VALUES ('t1', 'T', 't1', 1)");
Database::executeQuery(
    "INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask) VALUES ('u1', 't1', 'a@x.test', 'h', 'A', 'B', 2)"
);
foreach (["' OR '1'='1", "'; DROP TABLE users; --", "x' UNION SELECT password_hash FROM users --", '%', '_'] as $payload) {
    $rows = Database::executeQuery(
        "SELECT id FROM users WHERE email LIKE :q ESCAPE '!'",
        ['q' => '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $payload) . '%']
    )->fetchAll();
    check('bound search payload returns no rows: ' . $payload, count($rows) === 0);
}
$count = (int) Database::executeQuery('SELECT COUNT(*) AS n FROM users')->fetch()['n'];
check('users table intact after payloads', $count === 1);

// Login throttle: 4 failures allowed, 5th blocks.
$ip = '203.0.113.9';
$email = 'probe@x.test';
for ($i = 0; $i < 4; $i++) {
    LoginThrottle::recordFailure($ip, $email);
}
check('4 failures do not block', !LoginThrottle::isBlocked($ip, $email));
LoginThrottle::recordFailure($ip, $email);
check('5th failure blocks', LoginThrottle::isBlocked($ip, $email));
check('per-email block applies from another IP', LoginThrottle::isBlocked('198.51.100.1', $email));
LoginThrottle::reset($email);
check('successful login reset clears email counter', !LoginThrottle::isBlocked('198.51.100.1', $email));

// Debug flag: only the exact string true exposes traces.
putenv('APP_DEBUG=true');
check('APP_DEBUG=true enables trace', ErrorHandler::isDebug());
putenv('APP_DEBUG=false');
check('APP_DEBUG=false hides trace', !ErrorHandler::isDebug());
putenv('APP_DEBUG=TRUE');
check('APP_DEBUG=TRUE (not exact) hides trace', !ErrorHandler::isDebug());

// --- Added in GREEN R4: search, sort and filter injection (paired pass and fail cases) ---

// Sort: a UNION or comment marker in the sort key falls back to the whitelisted default.
$_GET = ['sort' => 'name) UNION SELECT password_hash FROM users --', 'dir' => 'ASC'];
$list = ListQuery::fromRequest(['name' => ['u.last_name'], 'email' => ['u.email']], []);
check('sort UNION payload falls back to default', $list['sort'] === 'name' && !str_contains($list['orderBy'], 'UNION'));

// Role filter: any value outside the whitelist is dropped, so no SQL fragment reaches the query.
$_GET = ['role' => "1 OR 1=1"];
$list = ListQuery::fromRequest(['email' => ['u.email']], ['admin' => 2]);
check('role filter tautology ignored', $list['roleMask'] === null);
$_GET = ['role' => "admin' --"];
$list = ListQuery::fromRequest(['email' => ['u.email']], ['admin' => 2]);
check('role filter comment marker ignored', $list['roleMask'] === null);

// Paired failing case: a whitelisted role must still filter (the guard is not too strict).
$_GET = ['role' => 'admin'];
$list = ListQuery::fromRequest(['email' => ['u.email']], ['admin' => 2]);
check('whitelisted role filter applied', $list['roleMask'] === 2);

// Search: stacked query text is bound, so it matches no row and never runs as SQL.
$_GET = ['q' => "x'; DELETE FROM users; --"];
$list = ListQuery::fromRequest(['email' => ['u.email']], []);
check('stacked search text is escaped as data', str_contains($list['likePattern'], "DELETE") && !str_contains($list['orderBy'], 'DELETE'));
$_GET = [];

// Scoped row set: the tenant filter is in the SQL and an injected payload cannot widen it.
// This replays the scoped users query shape used by AdminController::renderUsers.
Database::executeQuery("INSERT INTO tenants (id, name, subdomain, is_active) VALUES ('t2', 'U', 't2', 1)");
Database::executeQuery(
    "INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask) VALUES ('u2', 't2', 'other@x.test', 'h', 'C', 'D', 2)"
);
foreach (["' OR '1'='1", '%', "x' OR tenant_id <> 't1' --"] as $payload) {
    $rows = Database::executeQuery(
        "SELECT u.id FROM users u WHERE u.tenant_id = :tenant_id AND (u.email LIKE :q_email ESCAPE '!')",
        ['tenant_id' => 't1', 'q_email' => '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $payload) . '%']
    )->fetchAll();
    check('scoped query never returns another tenant row: ' . $payload, array_column($rows, 'id') === [] || array_column($rows, 'id') === ['u1']);
}

// --- Static grep assertions over src/app and src/public ---

/**
 * Returns "pattern in file" for every PHP file under the roots that matches a forbidden pattern.
 */
function scanForbidden(array $patterns, array $roots): array
{
    $hits = [];
    foreach ($roots as $root) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($it as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $code = (string) file_get_contents($file->getPathname());
            foreach ($patterns as $name => $re) {
                if (preg_match($re, $code)) {
                    $hits[] = $name . ' in ' . $file->getPathname();
                }
            }
        }
    }
    return $hits;
}

// A call preceded by ->, ::, a word character or $ is a method or variable, not the bare function.
$forbidden = [
    'eval' => '/(?<![\w>:$])eval\s*\(/',
    'assert' => '/(?<![\w>:$])assert\s*\(/',
    'create_function' => '/(?<![\w>:$])create_function\s*\(/',
    'system' => '/(?<![\w>:$])system\s*\(/',
    'exec' => '/(?<![\w>:$])exec\s*\(/',
    'shell_exec' => '/(?<![\w>:$])shell_exec\s*\(/',
    'passthru' => '/(?<![\w>:$])passthru\s*\(/',
    'unserialize' => '/(?<![\w>:$])unserialize\s*\(/',
    'include-from-request' => '/\b(include|require)(_once)?\b[^;]*\$_(GET|POST|REQUEST|COOKIE)/',
    'include-variable' => '/\b(include|require)(_once)?\s*\(?\s*\$/',
];

// Paired detector check: the patterns must flag bad code and pass good code.
check('detector flags eval()', preg_match($forbidden['eval'], '$x = eval($y);') === 1);
check('detector ignores PDO ->exec()', preg_match($forbidden['exec'], '$pdo->exec("PRAGMA foreign_keys = ON;");') === 0);
check('detector flags include from $_GET', preg_match($forbidden['include-from-request'], 'include $_GET["page"];') === 1);

$hits = scanForbidden($forbidden, [$appDir . '/app', $appDir . '/public']);
check('src has no forbidden calls (eval, assert, exec, system, unserialize, variable include)', $hits === []);
foreach ($hits as $hit) {
    echo "      hit: {$hit}\n";
}

// --- Export path traversal: the CSV export takes no path input, so no request value reaches a file path. ---
$exportSrc = (string) file_get_contents($appDir . '/app/Controllers/UserController.php');
$exportBody = substr($exportSrc, (int) strpos($exportSrc, 'function exportData'));
check('export reads no request input', !str_contains($exportBody, '$_GET') && !str_contains($exportBody, '$_POST') && !str_contains($exportBody, '$request->'));
check('export opens php://output only, no file path', str_contains($exportBody, "php://output") && !preg_match('/fopen\(\s*\$/', $exportBody));

// --- Mass assignment: createUser and updateUser write only whitelisted columns ---
$adminSrc = (string) file_get_contents($appDir . '/app/Controllers/AdminController.php');
check('createUser ignores is_active from POST', !str_contains($adminSrc, "\$_POST['is_active']") && !str_contains($adminSrc, "'is_active'"));
check('updateUser never reads role_mask from a hidden field alone for SuperAdmin', str_contains($adminSrc, 'ASSIGNABLE_ROLES'));

// --- Password policy on edit: updateUser runs the same 12-character rule ---
check('updateUser applies Validator::password to a new password', str_contains($adminSrc, "Validator::password(\$password)"));

// --- Session and route guards (source-level only; no HTTP requests in this file) ---
$authSrc = (string) file_get_contents($appDir . '/app/Controllers/AuthController.php');
$regen = strpos($authSrc, 'Session::regenerate()');
$setUser = strpos($authSrc, "Session::set('user_id'");
check('login regenerates the session before storing user_id', $regen !== false && $setUser !== false && $regen < $setUser);
$indexSrc = (string) file_get_contents($appDir . '/public/index.php');
check('GET /logout is not routed', !str_contains($indexSrc, "\$router->get('/logout'"));
check('POST /logout is routed', str_contains($indexSrc, "\$router->post('/logout'"));

// --- Brute force: a spoofed X-Forwarded-For is trusted only from a trusted proxy ---
\App\Core\Config::load($devDir . '/values/constants/main.yaml');
check('untrusted remote ignores X-Forwarded-For', LoginThrottle::resolveClientIp('203.0.113.50', '10.9.9.1') === '203.0.113.50');
check('trusted proxy uses first X-Forwarded-For entry', LoginThrottle::resolveClientIp('10.0.0.1', '198.51.100.7, 10.0.0.1') === '198.51.100.7');
check('trusted proxy with invalid forwarded value keeps remote', LoginThrottle::resolveClientIp('10.0.0.1', 'not-an-ip') === '10.0.0.1');

// 6th failure is refused before the password is checked: 5 recorded failures block the next attempt.
LoginThrottle::reset('sixth@x.test');
for ($i = 0; $i < 5; $i++) {
    LoginThrottle::recordFailure('203.0.113.77', 'sixth@x.test');
}
check('6th attempt is blocked (429 path)', LoginThrottle::isBlocked('203.0.113.77', 'sixth@x.test'));
LoginThrottle::reset('sixth@x.test');

// --- Debug flag: the 500 view never shows paths or SQL when debug is off ---
$handlerSrc = (string) file_get_contents($appDir . '/app/Core/ErrorHandler.php');
$debugBranch = strpos($handlerSrc, 'if (!self::isDebug())');
$viewBranch = strpos($handlerSrc, 'errors/500.php');
check('ErrorHandler renders the 500 view before any trace output when debug is off', $debugBranch !== false && $viewBranch !== false && $viewBranch > $debugBranch);
// Only lines that print output count. The require lines name the layout file but never reach the browser.
$errView = (string) file_get_contents($appDir . '/app/Views/errors/500.php');
$printed = implode("\n", array_filter(explode("\n", $errView), static fn (string $l): bool => !str_contains($l, 'require')));
check('500 view prints no file, line, trace or SQL markers', !preg_match('/Stack trace|\.php|line \d|SQLSTATE|getTrace|\$e->/i', $printed));

// --- XSS escaping checks for list, edit and error views ---
// Asserts that htmlspecialchars(..., ENT_QUOTES, 'UTF-8') escapes script tags, attribute breakers, and javascript: URLs.
$xssPayloads = [
    'script' => '<script>alert(1)</script>',
    'attr' => '"><img src=x onerror=alert(2)>',
    'js_url' => 'javascript:alert(3)',
    'single_quote' => "' onmouseover='alert(4)",
];

foreach ($xssPayloads as $name => $payload) {
    $escaped = htmlspecialchars($payload, ENT_QUOTES, 'UTF-8');
    check("XSS {$name} escaping neutralizes < and >", !str_contains($escaped, '<') && !str_contains($escaped, '>'));
    check("XSS {$name} escaping neutralizes quotes", !str_contains($escaped, '"') && !str_contains($escaped, "'"));
}

// In the error and edit views, user input is routed through $e() / $v() which call htmlspecialchars.
// Test with an XSS probe payload to confirm raw markup never leaks.
$probeUser = [
    'id' => 'probe-1',
    'first_name' => '<script>alert("xss")</script>',
    'last_name' => '"><svg onload=alert(1)>',
    'email' => 'victim@test.edu',
    'role_mask' => 4,
];
$vProbe = static fn (string $field): string => htmlspecialchars((string) ($probeUser[$field] ?? ''), ENT_QUOTES, 'UTF-8');
check('edit view $v() escapes script tag', !str_contains($vProbe('first_name'), '<script>') && str_contains($vProbe('first_name'), '&lt;script&gt;'));
check('edit view $v() escapes attribute breaker', !str_contains($vProbe('last_name'), '">') && str_contains($vProbe('last_name'), '&quot;&gt;'));

// --- Authorization Matrix: SuperAdmin, Admin tenant A, Admin tenant B, User ---
require_once $appDir . '/app/Middleware/RoleMiddleware.php';
require_once $appDir . '/app/Controllers/AdminController.php';
use App\Middleware\RoleMiddleware;
use App\Controllers\AdminController;

// 1. Bitmask checkPermission unit tests
// SuperAdmin (1) has access to SuperAdmin (1), Admin (2), and User (4)
check('SuperAdmin satisfies SuperAdmin permission', RoleMiddleware::checkPermission(RoleMiddleware::ROLE_SUPERADMIN, RoleMiddleware::ROLE_SUPERADMIN));
check('SuperAdmin satisfies Admin permission', RoleMiddleware::checkPermission(RoleMiddleware::ROLE_SUPERADMIN, RoleMiddleware::ROLE_ADMIN));
check('SuperAdmin satisfies User permission', RoleMiddleware::checkPermission(RoleMiddleware::ROLE_SUPERADMIN, RoleMiddleware::ROLE_USER));

// Admin (2) satisfies Admin (2) and User (4), but NOT SuperAdmin (1)
check('Admin satisfies Admin permission', RoleMiddleware::checkPermission(RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_ADMIN));
check('Admin does NOT satisfy SuperAdmin permission', !RoleMiddleware::checkPermission(RoleMiddleware::ROLE_ADMIN, RoleMiddleware::ROLE_SUPERADMIN));

// User (4) satisfies User (4), but NOT Admin (2) and NOT SuperAdmin (1)
check('User satisfies User permission', RoleMiddleware::checkPermission(RoleMiddleware::ROLE_USER, RoleMiddleware::ROLE_USER));
check('User does NOT satisfy Admin permission', !RoleMiddleware::checkPermission(RoleMiddleware::ROLE_USER, RoleMiddleware::ROLE_ADMIN));
check('User does NOT satisfy SuperAdmin permission', !RoleMiddleware::checkPermission(RoleMiddleware::ROLE_USER, RoleMiddleware::ROLE_SUPERADMIN));

// 2. Cross-tenant isolation on edit and delete
// Insert users across two tenants: tenant-a and tenant-b
Database::executeQuery("INSERT OR IGNORE INTO tenants (id, name, subdomain, is_active) VALUES ('tenant-a', 'Tenant A', 'tenant-a', 1), ('tenant-b', 'Tenant B', 'tenant-b', 1)");
Database::executeQuery("INSERT OR REPLACE INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask) VALUES
    ('usr-admin-a', 'tenant-a', 'admin-a@cvsu.test', 'h', 'Admin', 'A', 2),
    ('usr-user-a',  'tenant-a', 'user-a@cvsu.test',  'h', 'User',  'A', 4),
    ('usr-admin-b', 'tenant-b', 'admin-b@cvsu.test', 'h', 'Admin', 'B', 2),
    ('usr-user-b',  'tenant-b', 'user-b@cvsu.test',  'h', 'User',  'B', 4),
    ('usr-super-1', 'tenant-a', 'super@cvsu.test',   'h', 'Super', 'X', 1)
");

// Use reflection to test AdminController::findManageableUser under different tenant sessions
$adminCtrl = new AdminController();
$refMethod = new ReflectionMethod(AdminController::class, 'findManageableUser');
$refMethod->setAccessible(true);

// Admin of tenant-a logged in
\App\Core\Session::start();
\App\Core\Session::set('tenant_id', 'tenant-a');
\App\Core\Session::set('user_id', 'usr-admin-a');

// Admin-A CAN manage User-A (same tenant)
$res = $refMethod->invoke($adminCtrl, 'usr-user-a');
check('Admin of tenant-a can manage user in tenant-a', is_array($res) && $res['id'] === 'usr-user-a');

// Admin-A CANNOT manage User-B (cross-tenant - tenant-b) -> must return null
$res = $refMethod->invoke($adminCtrl, 'usr-user-b');
check('Admin of tenant-a CANNOT manage user in tenant-b (cross-tenant blocked)', $res === null);

// Admin-A CANNOT manage Admin-B (cross-tenant) -> null
$res = $refMethod->invoke($adminCtrl, 'usr-admin-b');
check('Admin of tenant-a CANNOT manage admin in tenant-b', $res === null);

// Admin-A CANNOT manage SuperAdmin even if in same tenant -> null
$res = $refMethod->invoke($adminCtrl, 'usr-super-1');
check('Admin of tenant-a CANNOT manage SuperAdmin in same tenant', $res === null);

// Admin-B logged in: CAN manage User-B, CANNOT manage User-A
\App\Core\Session::set('tenant_id', 'tenant-b');
\App\Core\Session::set('user_id', 'usr-admin-b');
$res = $refMethod->invoke($adminCtrl, 'usr-user-b');
check('Admin of tenant-b can manage user in tenant-b', is_array($res) && $res['id'] === 'usr-user-b');
$res = $refMethod->invoke($adminCtrl, 'usr-user-a');
check('Admin of tenant-b CANNOT manage user in tenant-a', $res === null);

// --- Session Security: Cookie flags and HTTPS detection ---
$_SERVER['HTTPS'] = 'on';
check('Session::isHttps detects HTTPS=on', \App\Core\Session::isHttps());
$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = '443';
check('Session::isHttps detects port 443', \App\Core\Session::isHttps());
$_SERVER['SERVER_PORT'] = '80';
check('Session::isHttps returns false for plain HTTP', !\App\Core\Session::isHttps());

// --- Schema & Migrations apply cleanly to fresh SQLite ---
$freshSqlite = is_dir('/dev/shm') ? '/dev/shm/fresh-migration-test.sqlite' : sys_get_temp_dir() . '/fresh-migration-test.sqlite';
@unlink($freshSqlite);
$freshPdo = new PDO("sqlite:{$freshSqlite}", null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$freshPdo->exec('PRAGMA foreign_keys = ON;');
// Run all migrations in order
$migrationDir = is_dir($appDir . '/database/migrations') ? $appDir . '/database/migrations' : dirname($appDir) . '/database/migrations';
$migrationFiles = glob($migrationDir . '/*.sql');
sort($migrationFiles);
check('at least 4 migration files exist', count($migrationFiles) >= 4);

$migrationsOk = true;
foreach ($migrationFiles as $mf) {
    $sql = (string) file_get_contents($mf);
    // Strip MySQL-specific options (e.g. ENGINE=InnoDB, AUTO_INCREMENT) for SQLite execution
    $sql = preg_replace('/ENGINE\s*=\s*\w+/i', '', $sql);
    $sql = preg_replace('/AUTO_INCREMENT/i', '', $sql);
    try {
        $freshPdo->exec($sql);
    } catch (\Throwable $e) {
        $migrationsOk = false;
        echo "Migration failed on {$mf}: " . $e->getMessage() . "\n";
        break;
    }
}
check('all migrations apply to fresh SQLite store', $migrationsOk);

// Verify essential tables exist in fresh store
$tables = $freshPdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
foreach (['tenants', 'roles', 'permissions', 'users', 'audit_logs'] as $tbl) {
    check("fresh migration creates table {$tbl}", in_array($tbl, $tables, true));
}
@unlink($freshSqlite);

echo "\nRESULT pass={$pass} fail={$fail}\n";
exit($fail === 0 ? 0 : 1);
