<?php

declare(strict_types=1);

use App\Core\Session;

$title = 'Tenant Administration - EIMS';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <h2>Tenant Administration: <?= htmlspecialchars($tenant['name'] ?? 'Organization', ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="subtitle">Subdomain: <code><?= htmlspecialchars($tenant['subdomain'] ?? 'default', ENT_QUOTES, 'UTF-8') ?></code></p>
</section>

<div class="specimen-grid-cards">
    <div class="card specimen-card-tight">
        <h3>Scoped Users</h3>
        <p class="specimen-stat-primary"><?= (int) ($userCount ?? 0) ?></p>
        <p class="subtitle">Users within this organization</p>
    </div>
    <div class="card specimen-card-tight">
        <h3>Organization Status</h3>
        <p class="specimen-status-active"><?= !empty($tenant['is_active']) ? 'ACTIVE' : 'SUSPENDED' ?></p>
        <p class="subtitle">Tenant operational status</p>
    </div>
</div>

<section class="card">
    <h3>Restaurant ERP Operations & Modules</h3>
    <ul class="specimen-action-list">
        <li><a href="<?= htmlspecialchars(\App\Core\Request::url('/admin/tables'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-primary">Dining Tables & Floor Plan &rarr;</a></li>
        <li><a href="<?= htmlspecialchars(\App\Core\Request::url('/admin/zones'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-primary">Dining Zones Management &rarr;</a></li>
        <li><a href="<?= htmlspecialchars(\App\Core\Request::url('/admin/reservations'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-primary">Reservation Bookings & Covers &rarr;</a></li>
        <li><a href="<?= htmlspecialchars(\App\Core\Request::url('/admin/waitlist'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-primary">Live Walk-in Waitlist Queue &rarr;</a></li>
        <li><a href="<?= htmlspecialchars(\App\Core\Request::url('/admin/menu'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-primary">Menu Catalog & 86-List Toggle &rarr;</a></li>
        <li><a href="<?= htmlspecialchars(\App\Core\Request::url('/admin/staff'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-primary">Staff Access & RBAC Assignments &rarr;</a></li>
        <li><a href="<?= htmlspecialchars(\App\Core\Request::url('/admin/analytics'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-primary">Business Analytics & Revenue Reports &rarr;</a></li>
        <li><a href="<?= htmlspecialchars(\App\Core\Request::url('/admin/reviews'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-primary">Customer Review Moderation &rarr;</a></li>
        <li><a href="<?= htmlspecialchars(\App\Core\Request::url('/admin/users'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-primary">Manage Tenant Users & Accounts &rarr;</a></li>
        <li><a href="<?= htmlspecialchars(\App\Core\Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-primary">Public Restaurant Marketplace &rarr;</a></li>
        <li><form method="POST" action="<?= htmlspecialchars(\App\Core\Request::url('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-inline-form"><?= Session::csrfField() ?><button type="submit" class="specimen-button-danger">Logout &rarr;</button></form></li>
    </ul>
</section>

<section class="card">
    <h3>Recent Tenant Audit Activity</h3>
    <?php if (empty($recentLogs)): ?>
        <p class="subtitle">No recent audit logs for this organization.</p>
    <?php else: ?>
        <div class="specimen-table-scroll">
            <table class="specimen-table-spaced">
                <thead>
                    <tr class="specimen-row-header">
                        <th class="specimen-cell">Action</th>
                        <th class="specimen-cell">Entity</th>
                        <th class="specimen-cell">Details</th>
                        <th class="specimen-cell">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr class="specimen-row">
                            <td class="specimen-cell"><code><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td class="specimen-cell"><?= htmlspecialchars($log['entity'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="specimen-cell"><?= htmlspecialchars($log['details'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="specimen-cell"><?= htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
