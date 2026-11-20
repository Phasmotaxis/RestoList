<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Live Panel Sabotage Defense Kernel
 *
 * Traps runtime errors, syntax parse errors, type errors, and unhandled
 * exceptions, rendering a high-contrast visual diagnostic console.
 */
class ErrorHandler
{
    private static bool $registered = false;

    /**
     * Register global error and exception handlers.
     *
     * Activates unified exception kernel: converts runtime PHP errors (E_ALL) to ErrorException,
     * routes unhandled exceptions through handleException(), and registers shutdown handler
     * for fatal errors (E_ERROR, E_PARSE, E_COMPILE_ERROR). Disables display_errors to stderr;
     * all output routed to renderDiagnosticScreen() with debug flag control.
     *
     * Idempotent: repeated calls are no-ops (guarded by $registered flag).
     *
     * @return void
     *
     * @see ErrorHandler::handleError() PHP error handler.
     * @see ErrorHandler::handleException() Unhandled exception handler.
     * @see ErrorHandler::handleShutdown() Fatal error shutdown handler.
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', '0');

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$registered = true;
    }

    /**
     * Convert PHP error to ErrorException for unified handling.
     *
     * Called by set_error_handler(). Returns false to stop normal PHP error handling,
     * allowing exception machinery to catch and render the error through handleException().
     * Respects error_reporting() mask: returns false if error type is suppressed.
     *
     * @param int $severity PHP error constant (E_WARNING, E_NOTICE, E_USER_ERROR, etc.).
     * @param string $message Human-readable error message.
     * @param string $file Absolute path to file where error occurred.
     * @param int $line Line number in file.
     *
     * @return bool false to prevent normal PHP error handling; true to continue.
     *
     * @throws ErrorException Always throws for non-suppressed errors, triggering handleException().
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Render diagnostic screen for unhandled exception (interactive defense mode).
     *
     * Called by set_exception_handler(). Captures exception type, message, file, line, and trace.
     * If APP_DEBUG=true, renders full trace and source snippet to browser; otherwise safe generic error.
     * Always logs to error_log() with request ID for audit trail. Exits with code 1.
     *
     * @param Throwable $throwable Exception or Error thrown.
     *
     * @return void Exits with code 1 after rendering.
     *
     * @see ErrorHandler::renderDiagnosticScreen() Screen rendering logic.
     */
    public static function handleException(Throwable $throwable): void
    {
        self::renderDiagnosticScreen(
            get_class($throwable),
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString()
        );
        exit(1);
    }

    /**
     * Intercept fatal PHP errors at shutdown (E_ERROR, E_PARSE, E_COMPILE_ERROR).
     *
     * Called by register_shutdown_function(). Checks error_get_last() for fatal error.
     * Renders diagnostic screen with fatal error type string and halted-execution context.
     * Returns normally (no exit); PHP runtime terminates after all shutdown functions.
     *
     * @return void
     *
     * @see ErrorHandler::isFatalType() Fatal error type check.
     */
    public static function handleShutdown(): void
    {
        $lastError = error_get_last();
        if ($lastError !== null && self::isFatalType($lastError['type'])) {
            self::renderDiagnosticScreen(
                'Fatal Error [' . self::errorTypeToString($lastError['type']) . ']',
                $lastError['message'],
                $lastError['file'],
                $lastError['line'],
                'Fatal shutdown frame (execution halted by PHP runtime)'
            );
        }
    }

    /**
     * Check if error type is fatal (E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR).
     *
     * @param int $type PHP error constant.
     *
     * @return bool true if fatal, false otherwise.
     */
    private static function isFatalType(int $type): bool
    {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
    }

    /**
     * Map PHP error constant to human-readable string (E_ERROR, E_PARSE, etc.).
     *
     * @param int $type PHP error constant.
     *
     * @return string Error type string or 'UNKNOWN_FATAL' if not recognized.
     */
    private static function errorTypeToString(int $type): string
    {
        return match ($type) {
            E_ERROR => 'E_ERROR',
            E_PARSE => 'E_PARSE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_USER_ERROR => 'E_USER_ERROR',
            default => 'UNKNOWN_FATAL'
        };
    }

    /**
     * Extract source code snippet around error line for visual context.
     *
     * Reads $file and returns an array of line objects with content around $targetLine.
     * Each object: ['line' => int, 'content' => string, 'is_target' => bool].
     * Returns empty array if file is unreadable or not found.
     *
     * @param string $file Absolute path to PHP source file.
     * @param int $targetLine Line number to highlight.
     * @param int $context Number of lines before/after to include (default 5).
     *
     * @return array Associative array [line_num => ['line' => int, 'content' => string, 'is_target' => bool], ...].
     */
    private static function getSourceSnippet(string $file, int $targetLine, int $context = 5): array
    {
        if (!is_file($file) || !is_readable($file)) {
            return [];
        }

        $lines = @file($file);
        if ($lines === false) {
            return [];
        }

        $start = max(1, $targetLine - $context);
        $end = min(count($lines), $targetLine + $context);

        $snippet = [];
        for ($i = $start; $i <= $end; $i++) {
            $snippet[$i] = [
                'line' => $i,
                'content' => $lines[$i - 1],
                'is_target' => ($i === $targetLine)
            ];
        }

        return $snippet;
    }

    /**
     * Query debug mode status: APP_DEBUG=true enables full trace/source exposure.
     *
     * Debug mode allows full stack trace and source code snippet in browser response.
     * Production deployments must set APP_DEBUG to any value except 'true' (false, 0, empty string).
     *
     * @return bool true if APP_DEBUG environment variable === 'true' (exact string match).
     */
    public static function isDebug(): bool
    {
        return getenv('APP_DEBUG') === 'true';
    }

    /**
     * Render interactive diagnostic screen or safe error page.
     *
     * In debug mode (APP_DEBUG=true): displays full exception type, message, file, line, source snippet,
     * and stack trace to aide panel defense debugging. In production mode: displays generic error page
     * with safe request ID for support correlation. Always logs full details to error_log().
     *
     * @param string $errorType Exception class name (e.g. 'RuntimeException', 'Fatal Error [E_PARSE]').
     * @param string $message Human-readable error message.
     * @param string $file Absolute path to file where error occurred.
     * @param int $line Line number.
     * @param string $trace Stack trace string from getTraceAsString().
     *
     * @return void Outputs HTML and returns (does not exit; caller exits with code 1).
     */
    private static function renderDiagnosticScreen(
        string $errorType,
        string $message,
        string $file,
        int $line,
        string $trace
    ): void {
        // Clear any partial output buffers to prevent corruption.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set HTTP response headers if not already sent.
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        $requestId = bin2hex(random_bytes(6));
        // Always log full error details to error log (file, syslog, etc.) for backend audit.
        error_log(sprintf(
            '[ERROR] request_id=%s type=%s message=%s location=%s:%d trace=%s',
            $requestId,
            $errorType,
            $message,
            $file,
            $line,
            $trace
        ));

        if (!self::isDebug()) {
            // Production mode: generic error page with safe request ID for support tickets.
            $title = 'Internal Server Error';
            $safeRequestId = htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8');
            require __DIR__ . '/../Views/errors/500.php';
            return;
        }

        // Debug mode: full diagnostic console with source snippet and stack trace.
        $snippet = self::getSourceSnippet($file, $line);
        $escapedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $escapedFile = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
        $escapedType = htmlspecialchars($errorType, ENT_QUOTES, 'UTF-8');
        $escapedTrace = htmlspecialchars($trace, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Alert - EIMS Defense Kernel</title>
    <link rel="stylesheet" href="/assets/css/diagnostic.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>[Panel Defense Interception] {$escapedType}</h1>
            <p><strong>Message:</strong> {$escapedMessage}</p>
            <p class="meta"><strong>Location:</strong> {$escapedFile}:{$line}</p>
        </div>

        <div class="card">
            <h2>Source Context</h2>
HTML;

        if (!empty($snippet)) {
            echo '<div class="code-block">';
            foreach ($snippet as $item) {
                $targetClass = $item['is_target'] ? ' target' : '';
                $lineEscaped = htmlspecialchars($item['content'], ENT_QUOTES, 'UTF-8');
                echo "<div class=\"code-line{$targetClass}\"><span class=\"line-num\">{$item['line']}</span><span class=\"line-text\">{$lineEscaped}</span></div>";
            }
            echo '</div>';
        } else {
            echo '<p class="meta">Source code preview unavailable for this location.</p>';
        }

        echo <<<HTML
        </div>

        <div class="card">
            <h2>Call Stack Trace</h2>
            <pre class="trace">{$escapedTrace}</pre>
        </div>

        <div class="card">
            <h2>Live Defense Remediation Hint</h2>
            <div class="suggestion">
                Inspect file <code>{$escapedFile}</code> at line <strong>{$line}</strong>.
                If syntax was modified by panel examiners, verify semicolon termination, bracket parity, or variable declaration state.
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
