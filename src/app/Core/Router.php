<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request Router: HTTP verb-to-handler dispatcher.
 *
 * Maps GET and POST request paths to callable handlers (closures or [ClassName, 'methodName'] arrays).
 * Resolves incoming Request to matched callback, instantiates controller if needed, and invokes handler.
 * Renders 404 view if path is not registered; routes through Response for HTTP status/headers.
 */
class Router
{
    private array $routes = [];

    /**
     * Register GET route handler.
     *
     * @param string $path URI path (e.g. '/admin/users', '/').
     * @param callable|array $callback Closure or [ControllerClass::class, 'methodName'] tuple.
     *
     * @return void
     */
    public function get(string $path, callable|array $callback): void
    {
        $this->routes['GET'][$path] = $callback;
    }

    /**
     * Register POST route handler.
     *
     * @param string $path URI path (e.g. '/admin/users/create', '/login').
     * @param callable|array $callback Closure or [ControllerClass::class, 'methodName'] tuple.
     *
     * @return void
     */
    public function post(string $path, callable|array $callback): void
    {
        $this->routes['POST'][$path] = $callback;
    }

    /**
     * Dispatch Request to registered handler; render 404 if no match.
     *
     * Extracts request path and HTTP method, looks up route in $routes registry.
     * If matched, invokes callback (closure or controller method); if no match, sets HTTP 404
     * and requires 404.php view. Returns handler result (typically void for view-based handlers).
     *
     * @param Request $request Incoming HTTP request with path and method.
     * @param Response $response HTTP response object for status/header management.
     *
     * @return mixed Handler return value (typically null for view renderers).
     */
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

        // Invoke controller method if callback is [ClassName, 'methodName']; otherwise invoke closure.
        if (is_array($callback)) {
            [$class, $methodName] = $callback;
            $controller = new $class();
            return call_user_func([$controller, $methodName], $request, $response);
        }

        return call_user_func($callback, $request, $response);
    }
}
