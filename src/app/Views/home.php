<?php
$title = 'Home - Enterprise Information Management System';
require_once __DIR__ . '/layouts/header.php';
?>
<section class="card">
    <h2>Enterprise Information Management System (EIMS)</h2>
    <p class="subtitle">Cavite State University &bull; DICT24 Information Management &bull; Clean-Room Architecture</p>
    <p>A multi-tenant institutional resource platform engineered for strict data isolation, role-based access control, dual-engine relational persistence, and live defense against runtime disruption.</p>
    <div class="action-group">
        <a href="<?= htmlspecialchars(\App\Core\Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">Discover Restaurants &rarr;</a>
        <a href="<?= htmlspecialchars(\App\Core\Request::url('/login'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Staff & Admin Login &rarr;</a>
        <a href="<?= htmlspecialchars(\App\Core\Request::url('/health'), ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary">Health Telemetry</a>
    </div>
</section>

<div class="grid-features">
    <div class="card card-stat">
        <h3>Service Status</h3>
        <p class="stat-value-success">Operational (200 OK)</p>
        <p class="subtitle">Primary Nginx 1.25 + PHP 8.3-FPM</p>
    </div>
    <div class="card card-stat">
        <h3>Data Persistence</h3>
        <p class="stat-value">Dual-Driver Engine</p>
        <p class="subtitle">MySQL 8.0 Primary with SQLite3 Fallback</p>
    </div>
    <div class="card card-stat">
        <h3>Security Kernel</h3>
        <p class="stat-value-accent">Bitmask RBAC</p>
        <p class="subtitle">SuperAdmin, Org Admin, Standard User</p>
    </div>
</div>

<section class="card">
    <h3>Diagnostic & Evaluation Verification</h3>
    <p>For panel examiners testing system defense resilience:</p>
    <p><a href="<?= htmlspecialchars(\App\Core\Request::url('/diagnostics/sabotage'), ENT_QUOTES, 'UTF-8') ?>" class="link-danger">Trigger Panel Sabotage Test &rarr;</a> <span class="subtitle">(Interception by ErrorHandler visual shield)</span></p>
</section>
<?php
require_once __DIR__ . '/layouts/footer.php';
?>
