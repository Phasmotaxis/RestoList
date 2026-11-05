<?php
$title = 'Home - Enterprise Information Management System';
require_once __DIR__ . '/layouts/header.php';
?>
<section class="card">
    <h2>Enterprise Information Management System (EIMS)</h2>
    <p class="subtitle">Cavite State University &bull; DICT24 Information Management &bull; Clean-Room Architecture</p>
    <p>A multi-tenant institutional resource platform engineered for strict data isolation, role-based access control, dual-engine relational persistence, and live defense against runtime disruption.</p>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem;">
        <a href="/login" style="background: var(--color-primary); color: white; padding: 0.75rem 1.5rem; border-radius: var(--radius-sm); text-decoration: none; font-weight: 600;">Access System Portal &rarr;</a>
        <a href="/health" style="background: transparent; border: 1px solid var(--color-border); color: var(--color-text); padding: 0.75rem 1.5rem; border-radius: var(--radius-sm); text-decoration: none; font-weight: 500;">Health Telemetry Probe</a>
    </div>
</section>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="margin-bottom: 0;">
        <h3>Service Status</h3>
        <p style="font-size: 1.25rem; font-weight: bold; color: #16a34a;">Operational (200 OK)</p>
        <p class="subtitle">Primary Nginx 1.25 + PHP 8.3-FPM</p>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <h3>Data Persistence</h3>
        <p style="font-size: 1.25rem; font-weight: bold; color: var(--color-primary);">Dual-Driver Engine</p>
        <p class="subtitle">MySQL 8.0 Primary with SQLite3 Fallback</p>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <h3>Security Kernel</h3>
        <p style="font-size: 1.25rem; font-weight: bold; color: var(--color-accent);">Bitmask RBAC</p>
        <p class="subtitle">SuperAdmin, Org Admin, Standard User</p>
    </div>
</div>

<section class="card">
    <h3>Diagnostic & Evaluation Verification</h3>
    <p>For panel examiners testing system defense resilience:</p>
    <p><a href="/diagnostics/sabotage" style="color: var(--color-danger, #dc2626); font-weight: 500;">Trigger Panel Sabotage Test &rarr;</a> <span class="subtitle">(Interception by ErrorHandler visual shield)</span></p>
</section>
<?php
require_once __DIR__ . '/layouts/footer.php';
?>
