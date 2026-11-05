<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $callback): void
    {
        $this->routes['GET'][$path] = $callback;
    }

    public function post(string $path, callable|array $callback): void
    {
        $this->routes['POST'][$path] = $callback;
    }

    public function resolve(Request $request, Response $response): mixed
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $callback = $this->routes[$method][$path] ?? false;

        if ($callback === false) {
            $response->setStatusCode(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return null;
        }

        if (is_array($callback)) {
            [$class, $methodName] = $callback;
            $controller = new $class();
            return call_user_func([$controller, $methodName], $request, $response);
        }

        return call_user_func($callback, $request, $response);
    }
}
