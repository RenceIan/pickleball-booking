<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pdo = getDatabaseConnection();
$statement = $pdo->query(
    "SELECT
        u.id,
        u.full_name,
        u.email,
        u.role,
        u.status,
        u.created_at,
        COALESCE(
            (
                SELECT m.status
                FROM memberships m
                WHERE m.user_id = u.id
                ORDER BY m.created_at DESC
                LIMIT 1
            ),
            'not_started'
        ) AS membership_status,
        (
            SELECT m.membership_type
            FROM memberships m
            WHERE m.user_id = u.id
            ORDER BY m.created_at DESC
            LIMIT 1
        ) AS membership_type,
        (
            SELECT m.expiration_date
            FROM memberships m
            WHERE m.user_id = u.id
            ORDER BY m.created_at DESC
            LIMIT 1
        ) AS membership_expiration
     FROM users u
     ORDER BY u.created_at DESC"
);
$users = $statement->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="dashboard.php">Pickleball Booking System Admin</a>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="payments.php">Payments</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>
<main class="page-container admin-page">
    <div class="page-heading">
        <h1>User Management</h1>
        <p>Approve or reject new account applications and view membership status.</p>
    </div>
    <?php if ($message = flash('success')): ?><div class="message success"><?= escape($message) ?></div><?php endif; ?>
    <section class="admin-card">
        <?php if ($users === []): ?>
            <p>No user accounts have been registered yet.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Membership status</th>
                        <th>Tier</th>
                        <th>Account status</th>
                        <th>Membership expiration</th>
                        <th>Registered</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $account): ?>
                        <tr>
                            <td><?= escape($account['full_name']) ?></td>
                            <td><?= escape($account['email']) ?></td>
                            <td><span class="status-badge"><?= escape(ucfirst($account['role'])) ?></span></td>
                            <td>
                                <span class="status-badge"><?= escape(ucfirst(str_replace('_', ' ', $account['membership_status']))) ?></span>
                            </td>
                            <td>
                                <?php if ($account['membership_type'] !== null && $account['role'] !== 'admin'): ?>
                                    <form method="post" action="update-tier.php" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                                        <input type="hidden" name="user_id" value="<?= (int) $account['id'] ?>">
                                        <select name="membership_type" aria-label="Membership tier for <?= escape($account['full_name']) ?>">
                                            <option value="rally" <?= strtolower((string) $account['membership_type']) === 'rally' ? 'selected' : '' ?>>Rally</option>
                                            <option value="smash" <?= strtolower((string) $account['membership_type']) === 'smash' ? 'selected' : '' ?>>Smash</option>
                                        </select>
                                        <button class="button" type="submit">Save tier</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge"><?= escape(ucfirst($account['status'])) ?></span>
                                <?php if ($account['role'] !== 'admin' && $account['status'] === 'pending'): ?>
                                    <form method="post" action="review-user.php" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                                        <input type="hidden" name="user_id" value="<?= (int) $account['id'] ?>">
                                        <button class="button" name="decision" value="approve" type="submit">Approve</button>
                                        <button class="button secondary" name="decision" value="reject" type="submit">Reject</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td><?= $account['membership_expiration'] !== null ? escape($account['membership_expiration']) : '—' ?></td>
                            <td><?= escape($account['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
