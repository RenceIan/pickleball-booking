<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo = getDatabaseConnection();
$user = currentUser();

$membershipStatement = $pdo->prepare(
    'SELECT status, expiration_date FROM memberships
     WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1'
);
$membershipStatement->execute(['user_id' => $user['id']]);
$membership = $membershipStatement->fetch() ?: null;

$creditStatement = $pdo->prepare(
    "SELECT COALESCE(SUM(remaining_amount), 0)
     FROM credit_batches
     WHERE user_id = :user_id AND status = 'active' AND remaining_amount > 0 AND expires_at > NOW()"
);
$creditStatement->execute(['user_id' => $user['id']]);
$validCredits = (int) $creditStatement->fetchColumn();

$nextExpirationStatement = $pdo->prepare(
    "SELECT MIN(expires_at) FROM credit_batches
     WHERE user_id = :user_id AND status = 'active' AND remaining_amount > 0 AND expires_at > NOW()"
);
$nextExpirationStatement->execute(['user_id' => $user['id']]);
$nextCreditExpiration = $nextExpirationStatement->fetchColumn();

$paymentStatement = $pdo->prepare(
    'SELECT id, amount, reference_number, payment_date, status, submitted_at, reviewed_at, admin_notes
     FROM payments WHERE user_id = :user_id ORDER BY submitted_at DESC LIMIT 5'
);
$paymentStatement->execute(['user_id' => $user['id']]);
$recentPayments = $paymentStatement->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<section class="dashboard">
    <h1>Welcome, <?= escape((string) $user['full_name']) ?></h1>
    <div class="member-summary">
        <div><span>Membership</span><strong><?= escape(ucfirst($membership['status'] ?? 'not started')) ?></strong></div>
        <div><span>Valid credits</span><strong><?= $validCredits ?></strong></div>
        <div><span>Membership expires</span><strong><?= escape($membership['expiration_date'] ?? '—') ?></strong></div>
        <div><span>Next credit expiration</span><strong><?= escape($nextCreditExpiration ?: '—') ?></strong></div>
    </div>
    <?php if (($membership['status'] ?? '') !== 'active'): ?>
        <div class="notice"><strong>Membership required:</strong> Submit a payment and wait for admin approval before booking.</div>
    <?php elseif ($validCredits === 0): ?>
        <div class="notice"><strong>No valid credits:</strong> Your credits are unavailable or expired.</div>
    <?php endif; ?>
    <p>
        <a class="button" href="calendar.php">View Reservation Calendar</a>
        <a class="button secondary" href="credits.php">View Credits</a>
        <a class="button secondary" href="my-payments.php">Payment History</a>
        <a class="button secondary" href="membership.php">Membership</a>
    </p>
</section>
<section class="dashboard dashboard-section">
    <div class="section-heading"><h2>Recent payments</h2><a href="my-payments.php">View all</a></div>
    <?php if ($recentPayments === []): ?>
        <p>No payments submitted yet.</p>
    <?php else: ?>
        <div class="table-wrapper"><table>
            <thead><tr><th>Date</th><th>Reference</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($recentPayments as $payment): ?><tr>
                <td><?= escape($payment['payment_date']) ?></td>
                <td><?= escape($payment['reference_number']) ?></td>
                <td>₱<?= escape((string) $payment['amount']) ?></td>
                <td><span class="status-badge"><?= escape(ucfirst($payment['status'])) ?></span></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
