<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

class AuthMiddleware
{
    public static function handle(): void
    {
        Session::start();
        if (Session::get('user_id') === null) {
            header('Location: /login');
            exit;
        }
    }
}
