<?php
use App\Core\Session;

$title = $title ?? 'Staff & Role-Based Access Control - Admin';
require_once __DIR__ . '/../layouts/header.php';
$staff = $staff ?? [];
$pendingInvites = $pendingInvites ?? [];
$orgId = $orgId ?? (string) Session::get('tenant_id');
?>
<section class="card">
    <div class="header-actions">
        <div>
            <h2>Staff Roster & Permissions (RBAC)</h2>
            <p class="subtitle">Assign operational roles: Manager, Front of House, and Kitchen</p>
        </div>
        <a href="/admin/dashboard" class="link-nav">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Invite New Staff Member</h3>
    <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/staff/invite" class="form-grid-admin" novalidate>
        <?= Session::csrfField() ?>
        <div>
            <label for="email" class="form-label">Staff Email Address</label>
            <input type="email" id="email" name="email" class="form-input" required placeholder="colleague@restaurant.com">
        </div>
        <div>
            <label for="role_mask" class="form-label">Staff Role Assignment</label>
            <select id="role_mask" name="role_mask" class="form-input" required>
                <option value="8">Front of House (Host / Server)</option>
                <option value="16">Kitchen Staff</option>
                <option value="2">Manager (Admin)</option>
                <option value="4">General Staff (User)</option>
            </select>
        </div>
        <div class="form-full">
            <button type="submit" class="btn-primary">Dispatch Staff Invite</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Active Staff Members</h3>
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Assigned Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($staff)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No staff members enrolled yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($staff as $member): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars(trim((string)$member['first_name'] . ' ' . (string)$member['last_name']), ENT_QUOTES, 'UTF-8') ?: 'Unnamed' ?></strong></td>
                            <td><?= htmlspecialchars((string)$member['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge"><?= htmlspecialchars((string)($member['role_label'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td>
                                <form method="POST" action="/admin/restaurants/<?= htmlspecialchars($orgId, ENT_QUOTES, 'UTF-8') ?>/staff/<?= htmlspecialchars((string)$member['id'], ENT_QUOTES, 'UTF-8') ?>/revoke" class="inline-form">
                                    <?= Session::csrfField() ?>
                                    <button type="submit" class="btn-danger-sm" onclick="return confirm('Revoke access for this staff member?');">Revoke</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
