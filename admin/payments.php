<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pdo = getDatabaseConnection();
$statement = $pdo->query(
    'SELECT p.id, p.amount, p.reference_number, p.payment_date, p.proof_image, p.status,
            p.submitted_at, p.admin_notes, u.full_name, u.email
     FROM payments p JOIN users u ON u.id = p.user_id
     ORDER BY p.submitted_at DESC'
);
$payments = $statement->fetchAll();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="../dashboard.php">Pickleball Booking System</a>
    <nav><a href="dashboard.php">Admin Dashboard</a><a href="../logout.php">Logout</a></nav>
</header>
<main class="page-container">
    <section class="form-card">
        <h1>Payment Management</h1>
        <?php if ($message = flash('success')): ?><div class="message success"><?= escape($message) ?></div><?php endif; ?>
        <?php if ($payments === []): ?>
            <p>No payment submissions yet.</p>
        <?php else: ?>
            <?php foreach ($payments as $payment): ?>
                <article class="notice">
                    <strong><?= escape($payment['full_name']) ?></strong> (<?= escape($payment['email']) ?>)<br>
                    Amount: ₱<?= escape((string) $payment['amount']) ?><br>
                    Reference: <?= escape($payment['reference_number']) ?><br>
                    Payment date: <?= escape($payment['payment_date']) ?><br>
                    Status: <strong><?= escape(ucfirst($payment['status'])) ?></strong><br>
                    <a href="proof.php?id=<?= (int) $payment['id'] ?>">View proof</a>
                    <?php if ($payment['status'] === 'pending'): ?>
                        <form method="post" action="review-payment.php" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                            <input type="hidden" name="payment_id" value="<?= (int) $payment['id'] ?>">
                            <input name="admin_notes" placeholder="Optional note">
                            <button class="button" name="decision" value="approve" type="submit">Approve</button>
                            <button class="button secondary" name="decision" value="reject" type="submit">Reject</button>
                        </form>
                    <?php elseif ($payment['admin_notes'] !== null): ?>
                        Note: <?= escape($payment['admin_notes']) ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
