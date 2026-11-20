<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Login Attempt Throttling: Brute-force defense with dual-scope rate limiting.
 *
 * - Per-IP: max 10 failures per 15 minutes (prevents lockout of unrelated accounts on shared IP).
 * - Per-email: max 5 failures per 15 minutes (prevents brute force on a specific account).
 *
 * Both scopes are tracked separately. Only per-email failures are cleared on successful login.
 * HTTP responses are identical for both block reasons (anti-enumeration). Records login attempts
 * in login_attempts table (bootstrapped on first access) with scope/subject/timestamp for query.
 */
class LoginThrottle
{
    public const MAX_FAILURES_PER_EMAIL = 5;
    public const MAX_FAILURES_PER_IP = 10;
    public const WINDOW_SECONDS = 900;

    private static bool $tableReady = false;

    /**
     * Resolve client IP: direct REMOTE_ADDR, or X-Forwarded-For if from trusted proxy.
     *
     * Handles reverse proxy scenarios (Nginx, load balancer) by trusting X-Forwarded-For only
     * when direct connection is from a proxy in security.trusted_proxies config. Anti-spoofing:
     * returns REMOTE_ADDR if forwarded header is present but proxy is untrusted.
     *
     * @param ?string $remoteAddr Direct connection IP (default $_SERVER['REMOTE_ADDR']).
     * @param ?string $forwardedFor X-Forwarded-For header value (default $_SERVER['HTTP_X_FORWARDED_FOR']).
     *
     * @return string Resolved client IP address.
     *
     * @example resolveClientIp('10.0.0.1', '203.0.113.5, 192.0.2.1') with trusted_proxies=['10.0.0.1'] returns '203.0.113.5'
     * @example resolveClientIp('203.0.113.1', '1.2.3.4, 5.6.7.8') with empty trusted_proxies returns '203.0.113.1' (untrusted)
     */
    public static function resolveClientIp(?string $remoteAddr = null, ?string $forwardedFor = null): string
    {
        $remote = $remoteAddr ?? (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $forwarded = $forwardedFor ?? (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');

        if ($forwarded === '') {
            return $remote;
        }

        $rawTrusted = Config::get('security.trusted_proxies', getenv('TRUSTED_PROXIES') ?: []);
        $trustedProxies = is_array($rawTrusted)
            ? $rawTrusted
            : array_filter(array_map('trim', explode(',', (string) $rawTrusted)));

        if (in_array($remote, $trustedProxies, true)) {
            $ips = array_filter(array_map('trim', explode(',', $forwarded)));
            if (!empty($ips)) {
                $clientIp = reset($ips);
                if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                    return $clientIp;
                }
            }
        }

        return $remote;
    }

    /**
     * Check if login attempt is blocked by either email or IP rate limit.
     *
     * Queries login_attempts table for failures in past WINDOW_SECONDS (900 = 15 min).
     * Returns true if email OR IP exceeds threshold (MAX_FAILURES_PER_EMAIL or MAX_FAILURES_PER_IP).
     * Anti-enumeration: single block response for both conditions.
     *
     * @param string $ip Client IP address (from resolveClientIp).
     * @param string $email User email address (case-normalized to lowercase for consistency).
     *
     * @return bool true if blocked; false if attempt permitted.
     *
     * @see LoginThrottle::recordFailure() Record failure after rejected login attempt.
     */
    public static function isBlocked(string $ip, string $email): bool
    {
        self::ensureTable();
        $since = time() - self::WINDOW_SECONDS;

        $emailFailures = self::countSince('email', strtolower($email), $since);
        $ipFailures = self::countSince('ip', $ip, $since);

        return $emailFailures >= self::MAX_FAILURES_PER_EMAIL
            || $ipFailures >= self::MAX_FAILURES_PER_IP;
    }

    /**
     * Record failed login attempt for both email and IP rate-limit scopes.
     *
     * Inserts two rows into login_attempts: one for email scope, one for IP scope.
     * Allows independent per-email and per-IP failure counters. Recorded at current Unix timestamp.
     *
     * @param string $ip Client IP address.
     * @param string $email User email (normalized to lowercase).
     *
     * @return void
     *
     * @see LoginThrottle::isBlocked() Check if rate limits exceeded.
     */
    public static function recordFailure(string $ip, string $email): void
    {
        self::ensureTable();
        $now = time();
        foreach ([['ip', $ip], ['email', strtolower($email)]] as [$scope, $subject]) {
            Database::executeQuery(
                'INSERT INTO login_attempts (scope, subject, attempted_at) VALUES (:scope, :subject, :at)',
                ['scope' => $scope, 'subject' => $subject, 'at' => $now]
            );
        }
    }

    /**
     * Clear login failures for email address (post-successful login).
     *
     * Deletes all 'email' scope rows for this email from login_attempts.
     * Keeps 'ip' scope rows (IP may have other accounts under throttle).
     *
     * @param string $email User email address (normalized to lowercase).
     *
     * @return void
     */
    public static function reset(string $email): void
    {
        self::ensureTable();
        Database::executeQuery(
            'DELETE FROM login_attempts WHERE scope = :scope AND subject = :subject',
            ['scope' => 'email', 'subject' => strtolower($email)]
        );
    }

    /**
     * Count failed attempts in scope within time window.
     *
     * Private helper: queries login_attempts for rows matching scope, subject, and
     * attempted_at >= $since timestamp. Returns count for threshold comparison.
     *
     * @param string $scope 'email' or 'ip' lookup column value.
     * @param string $subject Email address or IP address to query.
     * @param int $since Unix timestamp (lower bound inclusive).
     *
     * @return int Number of matching attempts in window.
     */
    private static function countSince(string $scope, string $subject, int $since): int
    {
        $row = Database::executeQuery(
            'SELECT COUNT(*) AS n FROM login_attempts WHERE scope = :scope AND subject = :subject AND attempted_at >= :since',
            ['scope' => $scope, 'subject' => $subject, 'since' => $since]
        )->fetch();

        return (int) ($row['n'] ?? 0);
    }

    /**
     * Bootstrap login_attempts table (idempotent, dual MySQL/SQLite3 support).
     *
     * Creates login_attempts table with columns: id (auto-increment), scope (email or ip),
     * subject (value to rate-limit), attempted_at (Unix timestamp). Includes optimized
     * index for rate-limit queries on MySQL. SQLite3 uses simpler auto-increment.
     *
     * Idempotent: subsequent calls are no-ops (guarded by $tableReady flag).
     *
     * @return void
     */
    private static function ensureTable(): void
    {
        if (self::$tableReady) {
            return;
        }
        $pdo = Database::getConnection();
        if (Database::getDriverName() === 'sqlite') {
            $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                scope VARCHAR(8) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                attempted_at INTEGER NOT NULL
            );');
        } else {
            $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                scope VARCHAR(8) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                attempted_at INT NOT NULL,
                INDEX idx_login_attempts_lookup (scope, subject, attempted_at)
            );');
        }
        self::$tableReady = true;
    }
}
