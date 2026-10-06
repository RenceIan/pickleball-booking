<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';

require_once dirname(__DIR__) . '/config/database.php';

$pdo = getDatabaseConnection();
$countStatement = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM memberships WHERE status = 'active' AND expiration_date >= CURDATE()) AS active_members,
        (SELECT COUNT(*) FROM payments WHERE status = 'pending') AS pending_payments,
        (SELECT COUNT(*) FROM payments WHERE status = 'approved') AS approved_payments,
        (SELECT COUNT(*) FROM payments WHERE status = 'rejected') AS rejected_payments,
        (SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE() AND status = 'confirmed') AS todays_bookings,
        (SELECT COUNT(*) FROM bookings WHERE booking_date >= CURDATE() AND status = 'confirmed') AS upcoming_bookings,
        (SELECT COALESCE(SUM(amount), 0) FROM credit_transactions WHERE transaction_type = 'issued') AS credits_issued,
        (SELECT COALESCE(SUM(ABS(amount)), 0) FROM credit_transactions WHERE transaction_type = 'used') AS credits_used"
);
$counts = $countStatement->fetch();

$todayStatement = $pdo->query(
    "SELECT b.id, b.booking_date, b.start_time, b.end_time, u.full_name
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     WHERE b.booking_date = CURDATE() AND b.status = 'confirmed'
     ORDER BY b.start_time
     LIMIT 10"
);
$todaysBookings = $todayStatement->fetchAll();

$pendingStatement = $pdo->query(
    "SELECT p.id, p.amount, p.reference_number, p.submitted_at, u.full_name
     FROM payments p
     JOIN users u ON u.id = p.user_id
     WHERE p.status = 'pending'
     ORDER BY p.submitted_at
     LIMIT 5"
);
$pendingPayments = $pendingStatement->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Admin Dashboard</title><link rel="stylesheet" href="../style.css"></head>
<body>
<header class="site-header">
    <a class="brand" href="dashboard.php">Pickleball Booking System Admin</a>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="payments.php">Payments</a>
        <a href="../dashboard.php">User Dashboard</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>
<main class="page-container admin-page">
    <div class="page-heading">
        <h1>Admin Dashboard</h1>
        <p>Monitor users, memberships, payments, bookings, and credits.</p>
    </div>

    <section class="stats-grid" aria-label="System summary">
        <a class="stat-card stat-link" href="users.php"><span>Total users</span><strong><?= (int) $counts['total_users'] ?></strong><small>View user accounts</small></a>
        <article class="stat-card"><span>Active members</span><strong><?= (int) $counts['active_members'] ?></strong></article>
        <article class="stat-card highlight"><span>Pending payments</span><strong><?= (int) $counts['pending_payments'] ?></strong><a href="payments.php">Review</a></article>
        <article class="stat-card"><span>Approved payments</span><strong><?= (int) $counts['approved_payments'] ?></strong></article>
        <article class="stat-card"><span>Rejected payments</span><strong><?= (int) $counts['rejected_payments'] ?></strong></article>
        <article class="stat-card"><span>Today's bookings</span><strong><?= (int) $counts['todays_bookings'] ?></strong></article>
        <article class="stat-card"><span>Upcoming bookings</span><strong><?= (int) $counts['upcoming_bookings'] ?></strong></article>
        <article class="stat-card"><span>Credits issued</span><strong><?= (int) $counts['credits_issued'] ?></strong></article>
        <article class="stat-card"><span>Credits used</span><strong><?= (int) $counts['credits_used'] ?></strong></article>
    </section>

    <section class="admin-columns">
        <article class="admin-card">
            <div class="section-heading">
                <h2>Pending payments</h2>
                <a href="payments.php">View all</a>
            </div>
            <?php if ($pendingPayments === []): ?>
                <p>No pending payments.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>User</th><th>Amount</th><th>Reference</th><th>Submitted</th></tr></thead>
                        <tbody>
                        <?php foreach ($pendingPayments as $payment): ?>
                            <tr>
                                <td><?= escape($payment['full_name']) ?></td>
                                <td>₱<?= escape((string) $payment['amount']) ?></td>
                                <td><?= escape($payment['reference_number']) ?></td>
                                <td><?= escape($payment['submitted_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>

        <article class="admin-card">
            <div class="section-heading"><h2>Today's bookings</h2></div>
            <?php if ($todaysBookings === []): ?>
                <p>No confirmed bookings today.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>User</th><th>Time</th></tr></thead>
                        <tbody>
                        <?php foreach ($todaysBookings as $booking): ?>
                            <tr>
                                <td><?= escape($booking['full_name']) ?></td>
                                <td><?= escape(substr($booking['start_time'], 0, 5)) ?>–<?= escape(substr($booking['end_time'], 0, 5)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </section>
</main>
</body>
</html>
