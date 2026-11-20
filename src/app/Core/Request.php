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
     * Retrieve sanitized request body (GET or POST parameters).
     *
     * Collects parameters from $_GET (if GET method) or $_POST (if POST method).
     * Sanitizes each value via FILTER_SANITIZE_SPECIAL_CHARS to encode HTML entities.
     * Prevents common XSS vectors in user-controlled form fields.
     *
     * @return array Associative array of sanitized parameters [key => sanitized_value, ...].
     */
    public function getBody(): array
    {
        $data = [];
        if ($this->getMethod() === 'GET') {
            foreach ($_GET as $key => $value) {
                $data[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        if ($this->getMethod() === 'POST') {
            foreach ($_POST as $key => $value) {
                $data[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        return $data;
    }
}
