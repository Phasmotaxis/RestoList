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
    private array $routeParams = [];

    /**
     * Store route parameters matched from dynamic route patterns.
     *
     * @param array $params Key-value map of route parameters.
     * @return void
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Retrieve all matched route parameters.
     *
     * @return array
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Retrieve specific route or request parameter by key.
     *
     * Checks route parameters first, then $_GET, then $_POST, returning $default if absent.
     *
     * @param string $key Parameter name.
     * @param mixed $default Fallback value if key is not found.
     * @return mixed
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $_GET[$key] ?? $_POST[$key] ?? $default;
    }

    /**
     * Retrieve HTTP request method (GET, POST, etc.).
     *
     * @return string HTTP method in uppercase (e.g. 'GET', 'POST').
     */
    public function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Retrieve request URI path (without query string).
     *
     * Extracts path component from REQUEST_URI, removing query string (? and after).
     * Falls back to '/' if REQUEST_URI is absent.
     *
     * @return string Request path (e.g. '/admin/users', '/').
     */
    public function getPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($uri, '?');
        if ($position === false) {
            return $uri;
        }
        return substr($uri, 0, $position);
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
        $data = [];
        $method = $this->getMethod();

        if ($method === 'GET') {
            foreach ($_GET as $key => $value) {
                $data[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
            return $data;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        foreach ($_POST as $key => $value) {
            if (is_string($value)) {
                $data[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
