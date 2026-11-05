<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class AuthController
{
    public function showLogin(Request $request, Response $response): void
    {
        require_once __DIR__ . '/../Views/auth/login.php';
    }

    public function login(Request $request, Response $response): void
    {
        // Authenticate credentials against database
        $response->redirect('/dashboard');
    }

    public function logout(Request $request, Response $response): void
    {
        $response->redirect('/login');
    }
}
