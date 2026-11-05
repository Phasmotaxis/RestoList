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
<?php
$pageTitle = 'EIMS - Enterprise Information Management System';
require_once __DIR__ . '/../app/Views/partials/header.php';
?>
<body>
    <header class="site-header">
        <div class="container">
            <h1><?= htmlspecialchars($sys['name'] ?? 'Enterprise Information Management System', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="subtitle"><?= htmlspecialchars(($sys['institution'] ?? 'Cavite State University') . ' - ' . ($sys['academic_term'] ?? 'DICT24 Information Management'), ENT_QUOTES, 'UTF-8') ?></p>
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
</body>
</html>
