<?php

declare(strict_types=1);

use App\Core\Session;

$title = 'SuperAdmin Console - EIMS';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <h2>Global Deployment Control Plane</h2>
    <p class="subtitle">Complete visibility across all tenant organizations, organization admins, and active users.</p>
</section>

<div class="specimen-grid-cards">
    <div class="card specimen-card-tight">
        <h3>Tenant Organizations</h3>
        <p class="specimen-stat-primary"><?= (int) ($tenantsCount ?? 0) ?></p>
        <p class="subtitle">Active enterprise tenants</p>
    </div>
    <div class="card specimen-card-tight">
        <h3>Organization Admins</h3>
        <p class="specimen-stat-accent"><?= (int) ($adminsCount ?? 0) ?></p>
        <p class="subtitle">Scoped tenant administrators</p>
    </div>
    <div class="card specimen-card-tight">
        <h3>Total Users</h3>
        <p class="specimen-stat-primary"><?= (int) ($usersCount ?? 0) ?></p>
        <p class="subtitle">Global user accounts across tenants</p>
    </div>
    <div class="card specimen-card-tight">
        <h3>Audit Records</h3>
        <p class="specimen-stat-muted"><?= (int) ($logsCount ?? 0) ?></p>
        <p class="subtitle">Total captured system actions</p>
    </div>
</div>

<section class="card">
    <h3>SuperAdmin Operations</h3>
    <ul class="specimen-action-list">
        <li><a href="/superadmin/organizations" class="specimen-link-primary">Manage Organizations &rarr;</a></li>
        <li><a href="/superadmin/users" class="specimen-link-primary">All Users Directory &rarr;</a></li>
        <li><a href="/superadmin/audit-logs" class="specimen-link-primary">Global Security Logs &rarr;</a></li>
        <li><form method="POST" action="/logout" class="specimen-inline-form"><?= Session::csrfField() ?><button type="submit" class="specimen-button-danger">Logout &rarr;</button></form></li>
    </ul>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
