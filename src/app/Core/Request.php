<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Request Envelope: Extract method, path, and sanitized body parameters.
 *
 * Wraps $_SERVER, $_GET, $_POST for clean access to incoming request data.
 * Sanitizes user input via FILTER_SANITIZE_SPECIAL_CHARS to prevent XSS in rendered contexts.
 */
class Request
{
    private array $params = [];

    /**
     * Store route parameters matched from dynamic route patterns.
     *
     * @param array<string, string> $params Key-value map of route parameters.
     * @return void
     */
    public function setRouteParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Set dynamic route parameters (alias for setRouteParams).
     *
     * @param array<string, string> $params Associative array of matched route parameters.
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Set request body parameters (testing or synthetic request injection).
     *
     * @param array $body Key-value map of body parameters.
     * @return void
     */
    public function setBody(array $body): void
    {
        $this->params = array_merge($this->params, $body);
    }

    /**
     * Retrieve all matched route parameters.
     *
     * @return array<string, string>
     */
    public function getRouteParams(): array
    {
        return $this->params;
    }

    /**
     * Retrieve all matched route parameters (alias for getRouteParams).
     *
     * @return array<string, string>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Retrieve single parameter by key from route params, body, or globals.
     *
     * Checks route parameters first, then body, then $_GET, then $_POST, returning $default if absent.
     *
     * @param string $key Parameter name.
     * @param mixed $default Fallback value if key is not found.
     * @return mixed Parameter value or fallback.
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }
        $body = $this->getBody();
        if (isset($body[$key])) {
            return $body[$key];
        }
        return $_GET[$key] ?? $_POST[$key] ?? $default;
    }

    /**
     * Retrieve HTTP request method (GET, POST, PUT, DELETE, etc.).
     *
     * @return string HTTP method in uppercase (e.g. 'GET', 'POST').
     */
    public function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Detect script base directory path (e.g. '/dict24-resto-erp/public' in XAMPP, or '' at root).
     *
     * @return string Subfolder base directory without trailing slash, or empty string if at root.
     */
    public static function getBasePath(): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $baseDir = dirname($scriptName);
        if ($baseDir === '/' || $baseDir === '\\' || $baseDir === '.') {
            return '';
        }
        return rtrim($baseDir, '/');
    }

    /**
     * Build base-path-aware URL for application routes and assets.
     *
     * Prepends detected subfolder prefix when deployed under XAMPP or sub-directory aliases.
     *
     * @param string $path Route or asset path (e.g. '/admin/dashboard', '/assets/css/base.css').
     * @return string Normalized URL with base path prepended when applicable.
     */
    public static function url(string $path = '/'): string
    {
        $base = self::getBasePath();
        if ($base === '') {
            return $path;
        }
        if (str_starts_with($path, $base)) {
            return $path;
        }
        return $base . '/' . ltrim($path, '/');
    }

    /**
     * Retrieve request URI path (without query string).
     *
     * Extracts path component from REQUEST_URI, removing query string (? and after).
     * Automatically strips subfolder base directory (e.g. in stock XAMPP htdocs).
     * Falls back to '/' if REQUEST_URI is absent.
     *
     * @return string Request path (e.g. '/admin/users', '/').
     */
    public function getPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($uri, '?');
        $path = $position === false ? $uri : substr($uri, 0, $position);

        $baseDir = self::getBasePath();
        if ($baseDir !== '' && str_starts_with($path, $baseDir)) {
            $path = substr($path, strlen($baseDir));
        } else {
            $parentBase = dirname($baseDir);
            if ($parentBase !== '/' && $parentBase !== '\\' && $parentBase !== '.' && str_starts_with($path, $parentBase)) {
                $path = substr($path, strlen($parentBase));
            }
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return $path === '' ? '/' : $path;
    }

    /**
     * Retrieve sanitized request body (GET, POST, or JSON payload).
     *
     * Collects parameters from $_GET (if GET method) or $_POST/JSON (if POST/PUT/PATCH/DELETE method).
     * Sanitizes string values via FILTER_SANITIZE_SPECIAL_CHARS to encode HTML entities.
     *
     * @return array Associative array of request parameters.
     */
    public function getBody(): array
    {
        $data = $this->params;
        $method = $this->getMethod();

        if ($method === 'GET') {
            foreach ($_GET as $key => $value) {
                $filtered = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                $data[$key] = $filtered !== null ? $filtered : (is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value);
            }
            return $data;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if (!empty($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return array_merge($data, $decoded);
                }
            }
        }

        foreach ($_POST as $key => $value) {
            $filtered = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            $data[$key] = $filtered !== null ? $filtered : (is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value);
        }

        return $data;
    }
}
