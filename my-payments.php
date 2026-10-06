<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireApprovedMember();

$user = currentUser();
$statement = getDatabaseConnection()->prepare(
    'SELECT id, amount, reference_number, payment_date, status, submitted_at, reviewed_at, admin_notes
     FROM payments WHERE user_id = :user_id ORDER BY submitted_at DESC'
);
$statement->execute(['user_id' => $user['id']]);
$payments = $statement->fetchAll();

$pageTitle = 'Payment History';
require_once __DIR__ . '/includes/header.php';
?>
<section class="dashboard dashboard-section">
    <h1>Payment History and Receipts</h1>
    <p>Keep this page as your payment record. Proof images remain private and are available only to administrators.</p>
    <?php if ($payments === []): ?>
        <p>No payment records yet. <a href="payment.php">Submit a membership payment</a>.</p>
    <?php else: ?>
        <?php foreach ($payments as $payment): ?>
            <article class="receipt-card">
                <div class="section-heading">
                    <h2>Payment #<?= (int) $payment['id'] ?></h2>
                    <span class="status-badge"><?= escape(ucfirst($payment['status'])) ?></span>
                </div>
                <p><strong>Amount:</strong> ₱<?= escape((string) $payment['amount']) ?></p>
                <p><strong>Reference:</strong> <?= escape($payment['reference_number']) ?></p>
                <p><strong>Payment date:</strong> <?= escape($payment['payment_date']) ?></p>
                <p><strong>Submitted:</strong> <?= escape($payment['submitted_at']) ?></p>
                <?php if ($payment['reviewed_at'] !== null): ?><p><strong>Reviewed:</strong> <?= escape($payment['reviewed_at']) ?></p><?php endif; ?>
                <?php if ($payment['admin_notes'] !== null && $payment['admin_notes'] !== ''): ?><p><strong>Admin note:</strong> <?= escape($payment['admin_notes']) ?></p><?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
