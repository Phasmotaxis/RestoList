<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Dual-Driver Database Connection Manager
 *
 * Implements primary MySQL connection with dynamic runtime failover
 * to SQLite3 for air-gapped or sabotaged environments.
 */
class Database
{
    private static ?PDO $pdo = null;
    private static bool $isFallback = false;

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
                ]);

                self::$isFallback = false;
                return self::$pdo;
            } catch (PDOException $e) {
                // Log and trigger fallback to SQLite
                error_log('[WARN] Primary MySQL connection failed: ' . $e->getMessage() . '. Falling back to SQLite3.');
                return self::initializeSqliteFallback();
            }
        }

        return self::initializeSqliteFallback();
    }

    public static function initializeSqliteFallback(): PDO
    {
        $sqlitePath = getenv('DB_SQLITE_PATH') ?: '/var/www/html/storage/fallback.sqlite';
        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        self::$pdo = new PDO("sqlite:{$sqlitePath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        self::$isFallback = true;
        return self::$pdo;
    }

    public static function isFallbackActive(): bool
    {
        return self::$isFallback;
    }

    public static function executeQuery(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
