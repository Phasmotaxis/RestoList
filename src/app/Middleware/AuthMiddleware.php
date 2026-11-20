<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

/**
 * Authentication Middleware: Guard against unauthenticated access.
 *
 * Starts session and checks for authenticated user_id. If not present (not logged in),
 * redirects to /login with HTTP 302 and exits. Called before protected route handlers
 * to ensure only authenticated users proceed.
 */
class AuthMiddleware
{
    /**
     * Enforce authentication: redirect to login if user_id not in session.
     *
     * Starts session, checks Session::get('user_id'), and redirects to /login if null or empty.
     * Exit terminates request after redirect header. No return value (caller never reached on auth failure).
     *
     * @return void Exits with redirect if unauthenticated; returns normally if user_id present.
     */
    public static function handle(): void
    {
        Session::start();
        if (Session::get('user_id') === null) {
            header('Location: /login');
            exit;
        }
    }
}
