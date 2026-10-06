<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
$pdo = getDatabaseConnection();
$batchStatement = $pdo->prepare(
    'SELECT id, amount, remaining_amount, issued_at, expires_at, status
     FROM credit_batches WHERE user_id = :user_id ORDER BY expires_at ASC'
);
$batchStatement->execute(['user_id' => $user['id']]);
$batches = $batchStatement->fetchAll();

$transactionStatement = $pdo->prepare(
    'SELECT ct.amount, ct.transaction_type, ct.description, ct.created_at
     FROM credit_transactions ct WHERE ct.user_id = :user_id ORDER BY ct.created_at DESC'
);
$transactionStatement->execute(['user_id' => $user['id']]);
$transactions = $transactionStatement->fetchAll();

$validStatement = $pdo->prepare(
    "SELECT COALESCE(SUM(remaining_amount), 0) FROM credit_batches
     WHERE user_id = :user_id AND status = 'active' AND remaining_amount > 0 AND expires_at > NOW()"
);
$validStatement->execute(['user_id' => $user['id']]);
$validCredits = (int) $validStatement->fetchColumn();

$pageTitle = 'Credits';
require_once __DIR__ . '/includes/header.php';
?>
<section class="dashboard dashboard-section">
    <h1>My Credits</h1>
    <div class="credit-total">Valid credits: <strong><?= $validCredits ?></strong></div>
    <h2>Credit batches</h2>
    <?php if ($batches === []): ?><p>No credit batches yet.</p><?php else: ?>
        <div class="table-wrapper"><table>
            <thead><tr><th>Issued</th><th>Total</th><th>Remaining</th><th>Expires</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($batches as $batch): ?><tr>
                <td><?= escape($batch['issued_at']) ?></td><td><?= (int) $batch['amount'] ?></td>
                <td><?= (int) $batch['remaining_amount'] ?></td><td><?= escape($batch['expires_at']) ?></td>
                <td><?= escape(ucfirst($batch['status'])) ?></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
    <h2>Credit transaction history</h2>
    <?php if ($transactions === []): ?><p>No credit transactions yet.</p><?php else: ?>
        <div class="table-wrapper"><table>
            <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th></tr></thead>
            <tbody><?php foreach ($transactions as $transaction): ?><tr>
                <td><?= escape($transaction['created_at']) ?></td>
                <td><?= escape(ucfirst($transaction['transaction_type'])) ?></td>
                <td><?= ((int) $transaction['amount'] > 0 ? '+' : '') . (int) $transaction['amount'] ?></td>
                <td><?= escape((string) ($transaction['description'] ?? '')) ?></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
