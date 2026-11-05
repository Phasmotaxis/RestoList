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

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

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

    private static function isFatalType(int $type): bool
    {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
    }

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

    private static function renderDiagnosticScreen(
        string $errorType,
        string $message,
        string $file,
        int $line,
        string $trace
    ): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

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
    <style>
        :root {
            --bg-color: #0b0f19;
            --surface-color: #161e2e;
            --text-color: #e5e7eb;
            --text-muted: #9ca3af;
            --border-color: #2e384d;
            --danger-color: #ef4444;
            --danger-bg: #450a0a;
            --highlight-bg: rgba(239, 68, 68, 0.25);
            --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: var(--font-mono);
            margin: 0;
            padding: 2rem;
            line-height: 1.5;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .header {
            background: var(--danger-bg);
            border: 1px solid var(--danger-color);
            border-radius: 6px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .header h1 {
            color: var(--danger-color);
            font-size: 1.25rem;
            margin: 0 0 0.5rem 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .header .meta {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card h2 {
            font-size: 1rem;
            margin-top: 0;
            margin-bottom: 1rem;
            color: #38bdf8;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.5rem;
        }
        .code-block {
            background: #000000;
            border-radius: 4px;
            padding: 1rem;
            overflow-x: auto;
            font-size: 0.875rem;
        }
        .code-line {
            display: flex;
            padding: 0.125rem 0.5rem;
        }
        .code-line.target {
            background-color: var(--highlight-bg);
            border-left: 4px solid var(--danger-color);
            font-weight: bold;
        }
        .line-num {
            width: 3.5rem;
            color: var(--text-muted);
            user-select: none;
            flex-shrink: 0;
        }
        .line-text {
            white-space: pre-wrap;
            word-break: break-all;
        }
        pre.trace {
            background: #000000;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 0.8125rem;
            color: #d1d5db;
        }
        .suggestion {
            background: rgba(14, 165, 233, 0.1);
            border-left: 4px solid #0ea5e9;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #e0f2fe;
        }
    </style>
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
