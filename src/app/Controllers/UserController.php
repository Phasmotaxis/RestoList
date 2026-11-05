<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\RoleMiddleware;

class UserController
{
    public function dashboard(Request $request, Response $response): void
    {
        RoleMiddleware::handle(RoleMiddleware::ROLE_USER);
        require_once __DIR__ . '/../Views/dashboards/user.php';
    }
}
