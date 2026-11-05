<?php

declare(strict_types=1);

/**
 * Enterprise Information Management System (EIMS)
 * Public Gateway Entrypoint
 */

require_once __DIR__ . '/../app/Core/ErrorHandler.php';

use App\Core\ErrorHandler;

// Initialize Live Panel Sabotage Defense Kernel
ErrorHandler::register();

// Route handling will be dispatched in subsequent phases
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EIMS - Enterprise Information Management System</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1>Enterprise Information Management System</h1>
            <p class="subtitle">Cavite State University - Department of Information Technology / Computer Science</p>
        </div>
    </header>
    <main class="container">
        <section class="card">
            <h2>System Status: Operational</h2>
            <p>Phase 1 Scaffolding and Docker Foundation initialized.</p>
        </section>
    </main>
    <footer class="site-footer container">
        <p>&copy; 2026 EIMS Team. Clean-Room Architecture.</p>
    </footer>
    <script src="/assets/js/app.js"></script>
</body>
</html>
