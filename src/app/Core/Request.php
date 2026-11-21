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
     * Set dynamic route parameters.
     *
     * @param array<string, string> $params Associative array of matched route parameters.
     *
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Retrieve all matched route parameters.
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
     * @param string $key Parameter name.
     * @param mixed $default Fallback value if not found.
     *
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
        $path = $position === false ? $uri : substr($uri, 0, $position);
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return $path;
    }

    /**
     * Retrieve sanitized request body (GET or POST parameters, or parsed JSON).
     *
     * Collects parameters from $_GET (if GET method) or $_POST (if POST method).
     * Parses application/json payload if present.
     * Sanitizes string values via FILTER_SANITIZE_SPECIAL_CHARS to encode HTML entities.
     * Prevents common XSS vectors in user-controlled form fields.
     *
     * @return array Associative array of parameters.
     */
    public function getBody(): array
    {
        $data = $this->params;
        if ($this->getMethod() === 'GET') {
            foreach ($_GET as $key => $value) {
                $filtered = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                $data[$key] = $filtered !== null ? $filtered : (is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value);
            }
        }
        if ($this->getMethod() === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $raw = file_get_contents('php://input');
                if (!empty($raw)) {
                    $json = json_decode($raw, true);
                    if (is_array($json)) {
                        return array_merge($data, $json);
                    }
                }
            }
            foreach ($_POST as $key => $value) {
                $filtered = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                $data[$key] = $filtered !== null ? $filtered : (is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value);
            }
        }
        return $data;
    }
}
