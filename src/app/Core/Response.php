<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Response Builder: Status codes, redirects, and JSON serialization.
 *
 * Wraps PHP headers and output for clean HTTP response control.
 * Supports HTTP status codes, Location redirects, and JSON serialization with safety defaults.
 */
class Response
{
    /**
     * Set HTTP response status code (200, 404, 500, etc.).
     *
     * @param int $code HTTP status code (e.g. 200, 302, 404, 500).
     *
     * @return void
     */
    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    /**
     * Send Location header and exit (HTTP 302 redirect).
     *
     * Sets Location header and terminates execution. Browser follows redirect to $url.
     * Use for POST-redirect-GET pattern (prevent form re-submission on refresh).
     *
     * @param string $url Redirect target URL (absolute or relative).
     *
     * @return void Exits after sending header.
     */
    public function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Serialize array as JSON and send with Content-Type header.
     *
     * Encodes $data to JSON with JSON_UNESCAPED_SLASHES (URLs readable) and JSON_PRETTY_PRINT
     * (human-friendly formatting). Sets HTTP status code and Content-Type application/json.
     * Exits after output.
     *
     * @param array $data Associative array or list to serialize as JSON.
     * @param int $statusCode HTTP status code (default 200).
     *
     * @return void Exits after encoding and sending.
     */
    public function json(array $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}
