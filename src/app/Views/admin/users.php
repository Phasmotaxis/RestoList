<?php
$title = 'Tenant Users - Admin';
require_once __DIR__ . '/../layouts/header.php';
?>
<section class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2>Tenant User Management</h2>
            <p class="subtitle">Scoped user provisioning and roster for your organization</p>
        </div>
        <a href="/admin/dashboard" style="color: var(--color-primary); font-weight: 500;">&larr; Back to Dashboard</a>
    </div>
</section>

<section class="card">
    <h3>Provision User Within Tenant</h3>
    <form method="POST" action="/admin/users" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end; margin-top: 1rem;">
        <div>
            <label for="first_name" style="display: block; margin-bottom: 0.25rem;">First Name</label>
            <input type="text" id="first_name" name="first_name" required placeholder="Juan" style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
        </div>
        <div>
            <label for="last_name" style="display: block; margin-bottom: 0.25rem;">Last Name</label>
            <input type="text" id="last_name" name="last_name" required placeholder="Dela Cruz" style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
        </div>
        <div>
            <label for="email" style="display: block; margin-bottom: 0.25rem;">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="user@cvsu.edu.ph" style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
        </div>
        <div>
            <label for="password" style="display: block; margin-bottom: 0.25rem;">Initial Password</label>
            <input type="password" id="password" name="password" required value="Password123!" style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
        </div>
        <div>
            <button type="submit" style="background: var(--color-primary); color: white; border: none; padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); cursor: pointer; font-weight: 500;">Provision User</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Tenant User Roster</h3>
    <div style="overflow-x: auto; margin-top: 0.5rem;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--color-border);">
                    <th style="padding: 0.5rem;">User ID</th>
                    <th style="padding: 0.5rem;">Full Name</th>
                    <th style="padding: 0.5rem;">Email</th>
                    <th style="padding: 0.5rem;">Role Mask</th>
                    <th style="padding: 0.5rem;">Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($users ?? []) as $u): ?>
                    <?php
                    $mask = (int) ($u['role_mask'] ?? 4);
                    $roleLabel = ($mask & 2) ? 'Admin' : 'User';
                    ?>
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <td style="padding: 0.5rem;"><code><?= htmlspecialchars($u['id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td style="padding: 0.5rem; font-weight: 500;"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 0.5rem;"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 0.5rem;"><span style="background: rgba(37, 99, 235, 0.1); color: var(--color-primary); padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600;"><?= $roleLabel ?> (<?= $mask ?>)</span></td>
                        <td style="padding: 0.5rem;"><?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
