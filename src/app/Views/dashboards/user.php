<?php

declare(strict_types=1);

use App\Core\Session;

$title = 'User Workspace - EIMS';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <h2>Welcome, <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="subtitle">Organization: <strong><?= htmlspecialchars($user['tenant_name'] ?? 'Assigned Tenant', ENT_QUOTES, 'UTF-8') ?></strong> &bull; Email: <?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
</section>

<section class="card">
    <h3>Account & Privacy Operations (RA 10173)</h3>
    <p>Under the Philippine Data Privacy Act of 2012 (RA 10173), you have the right to data portability and visibility over your personal information records.</p>
    <div class="specimen-actions">
        <a href="<?= htmlspecialchars(\App\Core\Request::url('/discover'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-button">Browse Restaurants & Table Booking &rarr;</a>
        <a href="<?= htmlspecialchars(\App\Core\Request::url('/user/export'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-link-button">Export My Personal Data (CSV) &darr;</a>
        <form method="POST" action="<?= htmlspecialchars(\App\Core\Request::url('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="specimen-inline-form">
            <?= Session::csrfField() ?>
            <button type="submit" class="specimen-button-outline-danger">Sign Out</button>
        </form>
    </div>
</section>

<section class="card">
    <h3>Your Recent Activity Log</h3>
    <?php if (empty($activities)): ?>
        <p class="subtitle">No recent user activity recorded.</p>
    <?php else: ?>
        <div class="specimen-table-scroll">
            <table class="specimen-table-spaced">
                <thead>
                    <tr class="specimen-row-header">
                        <th class="specimen-cell">Action</th>
                        <th class="specimen-cell">Details</th>
                        <th class="specimen-cell">IP Address</th>
                        <th class="specimen-cell">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $act): ?>
                        <tr class="specimen-row">
                            <td class="specimen-cell"><code><?= htmlspecialchars($act['action'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td class="specimen-cell"><?= htmlspecialchars($act['details'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="specimen-cell"><?= htmlspecialchars($act['ip_address'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="specimen-cell"><?= htmlspecialchars($act['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
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
