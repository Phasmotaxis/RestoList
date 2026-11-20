<?php

declare(strict_types=1);

/**
 * SuperAdmin Provisioning CLI Script (One-Time Initialization)
 *
 * Command-line only. Creates SuperAdmin user account on first deployment with operator-supplied password.
 * Validates email/password strength before insert. Idempotent: existing account keeps original password
 * (re-running never resets password). Exit codes: 0 (success), 2 (validation/env error), 3 (missing tenant).
 *
 * Usage:
 *   SUPERADMIN_EMAIL=admin@example.edu SUPERADMIN_PASSWORD='at least 12 chars' \
 *     php src/database/bootstrap_superadmin.php
 *
 * Security: bcrypt hashing (PASSWORD_BCRYPT), validates email and password strength via Validator.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/Core/Config.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Validator.php';

use App\Core\Database;
use App\Core\Validator;

$email = trim((string) getenv('SUPERADMIN_EMAIL'));
$password = (string) getenv('SUPERADMIN_PASSWORD');

// Exit 2: Missing or empty environment variables.
if ($email === '' || $password === '') {
    fwrite(STDERR, "Set SUPERADMIN_EMAIL and SUPERADMIN_PASSWORD.\n");
    exit(2);
}

// Exit 2: Email or password validation failed (email format, password strength).
$error = Validator::email($email) ?? Validator::password($password);
if ($error !== null) {
    fwrite(STDERR, $error . "\n");
    exit(2);
}

// Exit 3: Bootstrap tenant (cvsu-main-001) not found (schema/seed not run).
$tenantId = 'tenant-cvsu-main-001';
$tenant = Database::executeQuery('SELECT id FROM tenants WHERE id = :id', ['id' => $tenantId])->fetch();
if ($tenant === false) {
    fwrite(STDERR, "Tenant {$tenantId} is missing. Run the schema and seed first.\n");
    exit(3);
}

// Exit 0: Email already registered (idempotent, password unchanged).
$existing = Database::executeQuery('SELECT id FROM users WHERE email = :email', ['email' => $email])->fetch();
if ($existing !== false) {
    fwrite(STDOUT, "A user with that email already exists. Password unchanged.\n");
    exit(0);
}

// Insert SuperAdmin user with role_mask=1 (ROLE_SUPERADMIN).
Database::executeQuery(
    'INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask) VALUES (:id, :tenant_id, :email, :password_hash, :first_name, :last_name, :role_mask)',
    [
        'id' => 'usr-superadmin-' . bin2hex(random_bytes(4)),
        'tenant_id' => $tenantId,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        'first_name' => 'System',
        'last_name' => 'SuperAdmin',
        'role_mask' => 1,
    ]
);

// Exit 0: Success.
fwrite(STDOUT, "SuperAdmin created.\n");
