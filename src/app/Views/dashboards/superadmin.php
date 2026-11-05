<?php
$title = 'SuperAdmin Console - EIMS';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <h2>Global Deployment Control Plane</h2>
    <p class="subtitle">Complete visibility across all tenant organizations, organization admins, and active users.</p>
</section>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="margin-bottom: 0;">
        <h3>Tenant Organizations</h3>
        <p style="font-size: 2rem; font-weight: bold; color: var(--color-primary);"><?= (int) ($tenantsCount ?? 0) ?></p>
        <p class="subtitle">Active enterprise tenants</p>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <h3>Organization Admins</h3>
        <p style="font-size: 2rem; font-weight: bold; color: var(--color-accent);"><?= (int) ($adminsCount ?? 0) ?></p>
        <p class="subtitle">Scoped tenant administrators</p>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <h3>Total Users</h3>
        <p style="font-size: 2rem; font-weight: bold; color: var(--color-primary);"><?= (int) ($usersCount ?? 0) ?></p>
        <p class="subtitle">Global user accounts across tenants</p>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <h3>Audit Records</h3>
        <p style="font-size: 2rem; font-weight: bold; color: var(--color-text-muted);"><?= (int) ($logsCount ?? 0) ?></p>
        <p class="subtitle">Total captured system actions</p>
    </div>
</div>

<section class="card">
    <h3>SuperAdmin Operations</h3>
    <ul style="list-style-type: none; padding-left: 0; display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
        <li><a href="/superadmin/organizations" style="color: var(--color-primary); font-weight: 500;">Manage Organizations &rarr;</a></li>
        <li><a href="/superadmin/users" style="color: var(--color-primary); font-weight: 500;">All Users Directory &rarr;</a></li>
        <li><a href="/superadmin/audit-logs" style="color: var(--color-primary); font-weight: 500;">Global Security Logs &rarr;</a></li>
        <li><form method="POST" action="/logout" style="display: inline;"><button type="submit" style="background: none; border: none; color: var(--color-danger, #dc2626); cursor: pointer; font-weight: 500; font-size: 1rem; padding: 0;">Logout &rarr;</button></form></li>
    </ul>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
