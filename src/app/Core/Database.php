<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Dual-Driver Database Connection Manager
 *
 * Implements primary MySQL connection with dynamic runtime failover
 * to SQLite3 for air-gapped, offline, or container-chaos environments.
 */
class Database
{
    private static ?PDO $pdo = null;
    private static bool $isFallback = false;

    /**
     * Retrieve primary MySQL connection or initialize SQLite3 fallback.
     *
     * Establishes persistent PDO connection to primary MySQL database using environment configuration.
     * On MySQL failure (network timeout, auth error, unavailable host), automatically initializes
     * SQLite3 fallback and sets DB_FALLBACK_ACTIVE=true for runtime awareness.
     *
     * @return PDO Configured PDO instance (MySQL primary or SQLite3 fallback) with ERRMODE_EXCEPTION,
     *         FETCH_ASSOC, and EMULATE_PREPARES disabled for security and consistency.
     *
     * @throws PDOException If both MySQL and SQLite3 connections fail catastrophically.
     *
     * @see Database::initializeSqliteFallback() SQLite3 initialization path.
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $connection = getenv('DB_CONNECTION') ?: 'mysql';

        if ($connection === 'mysql') {
            try {
                $host = getenv('DB_HOST') ?: '127.0.0.1';
                $port = getenv('DB_PORT') ?: '3306';
                $dbname = getenv('DB_NAME') ?: 'eims_db';
                $user = getenv('DB_USER') ?: 'root';
                $pass = getenv('DB_PASS') ?: '';

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 2,
                ]);

                self::$isFallback = false;
                putenv('DB_FALLBACK_ACTIVE=false');
                $_ENV['DB_FALLBACK_ACTIVE'] = 'false';
                return self::$pdo;
            } catch (PDOException $e) {
                error_log('[ALERT] Primary MySQL connection failed: ' . $e->getMessage() . '. Initializing resilient SQLite3 fallback.');
                return self::initializeSqliteFallback();
            }
        }

        return self::initializeSqliteFallback();
    }

    /**
     * Initialize SQLite3 fallback database for air-gapped or chaos-tested environments.
     *
     * Creates and configures SQLite3 database at DB_SQLITE_PATH (default: /var/www/html/storage/fallback.sqlite),
     * with automatic fallback to system temp directory if primary path is unavailable.
     * Bootstraps schema with tenants, roles, permissions, users, and audit_logs tables.
     * Sets DB_FALLBACK_ACTIVE=true for runtime awareness of degraded mode.
     *
     * @return PDO Configured SQLite3 PDO instance with ERRMODE_EXCEPTION and FETCH_ASSOC.
     *
     * @throws PDOException If both primary and temp path SQLite3 connections fail.
     *
     * @see Database::bootstrapSqliteSchema() Schema initialization and seeding.
     */
    public static function initializeSqliteFallback(): PDO
    {
        $sqlitePath = getenv('DB_SQLITE_PATH') ?: '/var/www/html/storage/fallback.sqlite';
        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        try {
            self::$pdo = new PDO("sqlite:{$sqlitePath}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            $tempPath = sys_get_temp_dir() . '/fallback.sqlite';
            self::$pdo = new PDO("sqlite:{$tempPath}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        self::$isFallback = true;
        putenv('DB_FALLBACK_ACTIVE=true');
        $_ENV['DB_FALLBACK_ACTIVE'] = 'true';

        self::bootstrapSqliteSchema(self::$pdo);

        return self::$pdo;
    }

    /**
     * Bootstrap SQLite3 schema with idempotent table creation and seed data.
     *
     * Creates PRAGMA foreign_keys constraint tables: tenants (multi-tenant scope), roles (RBAC),
     * permissions (bitwise access control), users (authentication), and audit_logs (append-only
     * activity trail). Seeds baseline roles and permissions; tenants are bootstrapped if not present.
     * Subsequent invocations are safe no-ops (idempotent via IF NOT EXISTS and INSERT OR IGNORE).
     *
     * @param PDO $pdo Initialized SQLite3 PDO connection.
     *
     * @return void
     *
     * @throws PDOException If table creation or constraint violations occur.
     */
    private static function bootstrapSqliteSchema(PDO $pdo): void
    {
        // Idempotent: skip if tenants table already exists from prior invocation.
        $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tenants'")->fetch();
        if ($check) {
            return;
        }

        // Enable foreign key constraint enforcement (SQLite3 default is OFF).
        $pdo->exec('PRAGMA foreign_keys = ON;');

        // Tenants: Multi-tenant isolation boundary. Each restaurant, cafeteria, or commissary is a tenant.
        $pdo->exec('CREATE TABLE IF NOT EXISTS tenants (
            id VARCHAR(36) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            subdomain VARCHAR(64) NOT NULL UNIQUE,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );');

        // Roles: RBAC role registry (SuperAdmin, Admin, User).
        $pdo->exec('CREATE TABLE IF NOT EXISTS roles (
            id INT PRIMARY KEY,
            name VARCHAR(64) NOT NULL UNIQUE,
            mask INT NOT NULL UNIQUE,
            description VARCHAR(255)
        );');

        // Permissions: Bitwise permission masks (TENANT_MANAGE, USER_MANAGE, DATA_READ, DATA_WRITE).
        $pdo->exec('CREATE TABLE IF NOT EXISTS permissions (
            id INT PRIMARY KEY,
            name VARCHAR(64) NOT NULL UNIQUE,
            mask INT NOT NULL UNIQUE,
            description VARCHAR(255)
        );');

        // Users: Tenant user accounts with role_mask for permission evaluation.
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(128) NOT NULL,
            last_name VARCHAR(128) NOT NULL,
            role_mask INT NOT NULL DEFAULT 4,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        );');

        // Audit logs: Append-only activity trail for compliance and debugging.
        $pdo->exec('CREATE TABLE IF NOT EXISTS audit_logs (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36),
            action VARCHAR(128) NOT NULL,
            entity VARCHAR(64) NOT NULL,
            entity_id VARCHAR(36),
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        );');

        // Index for audit log queries filtered by timestamp (recent activity, pagination).
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs(created_at);');

        // Seed baseline RBAC roles: SuperAdmin (cross-tenant), Admin (tenant-scoped), User (standard).
        $pdo->exec("INSERT OR IGNORE INTO roles (id, name, mask, description) VALUES
            (1, 'SuperAdmin', 1, 'Full cross-tenant system control'),
            (2, 'Admin', 2, 'Tenant administrator'),
            (3, 'User', 4, 'Standard tenant user');");

        // Seed baseline permissions for CRUD operations on tenant data and configuration.
        $pdo->exec("INSERT OR IGNORE INTO permissions (id, name, mask, description) VALUES
            (1, 'TENANT_MANAGE', 1, 'Manage tenant configuration'),
            (2, 'USER_MANAGE', 2, 'Provision and edit tenant users'),
            (3, 'DATA_READ', 4, 'Read operational tenant data'),
            (4, 'DATA_WRITE', 8, 'Create and update operational tenant data');");

        // Seed baseline tenants: CvSU main campus and College of Education branches.
        $pdo->exec("INSERT OR IGNORE INTO tenants (id, name, subdomain, is_active) VALUES
            ('tenant-cvsu-main-001', 'Cavite State University Main Campus', 'cvsu-main', 1),
            ('tenant-cvsu-educ-002', 'CvSU College of Education', 'cvsu-educ', 1);");

        // SuperAdmin user creation is deferred to src/database/bootstrap_superadmin.php
        // to allow operator-supplied password at deployment time.
    }

    /**
     * Query fallback status: true if SQLite3 is active, false if MySQL primary is active.
     *
     * @return bool true if fallback is active, false if primary is in use.
     */
    public static function isFallbackActive(): bool
    {
        return self::$isFallback;
    }

    /**
     * Retrieve active database driver name for runtime diagnostics and UI indication.
     *
     * @return string 'sqlite' (SQLite3 fallback), 'mysql' (MySQL primary), or 'disconnected' (no connection established).
     */
    public static function getDriverName(): string
    {
        if (self::$isFallback) {
            return 'sqlite';
        }
        if (self::$pdo === null) {
            return 'disconnected';
        }
        return self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) ?: 'mysql';
    }

    /**
     * Reset connection state (test isolation and chaos recovery).
     *
     * Clears PDO singleton and fallback flag, resets DB_FALLBACK_ACTIVE=false in $_ENV and putenv().
     * Used for test teardown and manual failover recovery scenarios.
     *
     * @return void
     */
    public static function resetConnection(): void
    {
        self::$pdo = null;
        self::$isFallback = false;
        putenv('DB_FALLBACK_ACTIVE=false');
        $_ENV['DB_FALLBACK_ACTIVE'] = 'false';
    }

    /**
     * Execute parameterized SQL query with automatic parameter binding.
     *
     * Prepares SQL statement and executes with provided parameters using bound placeholders
     * (prevents SQL injection). Returns PDOStatement for result iteration.
     *
     * @param string $sql Parameterized SQL query with :named or ? placeholders.
     * @param array $params Named parameter map (e.g. ['id' => '123', 'status' => 'active']) or indexed array for ? placeholders.
     *
     * @return \PDOStatement Executed statement ready for fetchAll(), fetch(), rowCount(), etc.
     *
     * @throws PDOException If query preparation or execution fails.
     */
    public static function executeQuery(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
