<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session Manager: Secure session lifecycle with CSRF and fixation defenses.
 *
 * Wraps PHP session functions with hardened security defaults:
 * - HttpOnly cookies to prevent XSS-based session theft.
 * - SameSite=Strict to block cross-site cookie riding.
 * - Secure flag on HTTPS connections.
 * - Session ID regeneration on authentication to prevent fixation attacks.
 * - Per-request CSRF token stored in $_SESSION and verified on form submission.
 */
class Session
{
    /**
     * Start session with strict security configuration.
     *
     * Initializes PHP session with hardened defaults if not already started:
     * - cookie_httponly=true: JavaScript cannot access session cookie (prevents XSS theft).
     * - cookie_samesite='Strict': browser only sends session cookie on same-site requests (prevents CSRF riding).
     * - cookie_secure=true on HTTPS connections: cookie sent only over encrypted channel.
     * - use_strict_mode=true: PHP rejects unknown session IDs.
     *
     * Idempotent: repeated calls are no-ops if session already active.
     *
     * @return void
     *
     * @see Session::isHttps() HTTPS detection for secure cookie flag.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict',
                'cookie_secure' => self::isHttps(),
                'use_strict_mode' => true,
            ]);
        }
    }

    /**
     * Detect HTTPS connection for secure session cookie flag.
     *
     * Checks HTTPS environment variable (standard for reverse proxies and web servers)
     * and SERVER_PORT = 443. Returns true if either indicates HTTPS.
     *
     * @return bool true if connection is HTTPS, false if HTTP or unknown.
     */
    public static function isHttps(): bool
    {
        return (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    /**
     * Store value in session (after starting session if needed).
     *
     * @param string $key Session variable name (e.g. 'user_id', 'auth_token').
     * @param mixed $value Value to store (scalar, array, or object).
     *
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieve value from session or return default if not set.
     *
     * @param string $key Session variable name.
     * @param mixed $default Value to return if key is not found (default null).
     *
     * @return mixed Session value or $default if not found.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Remove key from session.
     *
     * @param string $key Session variable name to unset.
     *
     * @return void
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Destroy session and all session data.
     *
     * Clears all session variables and destroys the session on server (and cookies in browser).
     * Used for logout or cleanup.
     *
     * @return void
     */
    public static function destroy(): void
    {
        self::start();
        session_destroy();
    }

    /**
     * Retrieve CSRF token for current session.
     *
     * Lazily creates a random 64-character hex token on first access (stores in $_SESSION['_csrf']).
     * Subsequent calls return the same token until session regeneration. Used to prevent
     * Cross-Site Request Forgery by validating token presence on POST/state-changing requests.
     *
     * @return string 64-character hex CSRF token.
     */
    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['_csrf'];
    }

    /**
     * Render hidden form input field containing CSRF token.
     *
     * Generates HTML hidden input with name="_csrf" and current CSRF token value.
     * Include this in all POST forms to be validated by verifyCsrf().
     *
     * @return string HTML hidden input tag with escaped token value.
     */
    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Verify CSRF token from POST request; reject with HTTP 403 if token missing or invalid.
     *
     * Retrieves expected token from $_SESSION['_csrf'] and submitted token from $_POST['_csrf'].
     * Uses constant-time comparison (hash_equals) to prevent timing attacks.
     * If tokens do not match or expected token is empty, renders 403 Forbidden view and exits.
     *
     * @return void Exits with HTTP 403 if validation fails; returns normally if token valid.
     *
     * @throws Exit On CSRF validation failure (HTTP 403).
     */
    public static function verifyCsrf(): void
    {
        $expected = (string) self::get('_csrf', '');
        $submitted = (string) ($_POST['_csrf'] ?? '');

        if ($expected === '' || !hash_equals($expected, $submitted)) {
            http_response_code(403);
            require __DIR__ . '/../Views/errors/403.php';
            exit;
        }
    }

    /**
     * Rotate session ID and clear CSRF token (session fixation defense, post-login).
     *
     * Calls session_regenerate_id(true) to change SID in both session storage and cookie,
     * invalidating pre-login session IDs and preventing fixation attacks. Clears _csrf token
     * so next request receives a fresh token after authentication state changes.
     *
     * Call this after successful login to invalidate any pre-auth session ID that an attacker
     * might have known.
     *
     * @return void
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
        unset($_SESSION['_csrf']);
    }
}
