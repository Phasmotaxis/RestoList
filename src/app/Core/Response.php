<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    public function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    public function json(array $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}
