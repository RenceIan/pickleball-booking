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
        <p>View account roles and the latest membership status for every user.</p>
    </div>
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
                            <td><span class="status-badge"><?= escape(ucfirst(str_replace('_', ' ', $account['membership_status']))) ?></span></td>
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
