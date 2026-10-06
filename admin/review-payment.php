<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request.');
}

$paymentId = filter_var($_POST['payment_id'] ?? null, FILTER_VALIDATE_INT);
$decision = (string) ($_POST['decision'] ?? '');
$membershipType = strtolower(trim((string) ($_POST['membership_type'] ?? '')));
$adminNotes = trim((string) ($_POST['admin_notes'] ?? ''));
if ($paymentId === false || !in_array($decision, ['approve', 'reject'], true)
    || ($decision === 'approve' && !in_array($membershipType, ['rally', 'smash'], true))) {
    http_response_code(400);
    exit('Invalid payment review request.');
}

$pdo = getDatabaseConnection();
try {
    $pdo->beginTransaction();
    $paymentStatement = $pdo->prepare(
        'SELECT p.*, m.user_id AS membership_user_id
         FROM payments p JOIN memberships m ON m.id = p.membership_id
         WHERE p.id = :id FOR UPDATE'
    );
    $paymentStatement->execute(['id' => $paymentId]);
    $payment = $paymentStatement->fetch();
    if ($payment === false || $payment['status'] !== 'pending') {
        throw new RuntimeException('This payment has already been reviewed or does not exist.');
    }

    $reviewStatement = $pdo->prepare(
        'UPDATE payments
         SET status = :status, reviewed_at = NOW(), reviewed_by = :reviewed_by, admin_notes = :admin_notes
         WHERE id = :id'
    );
    $reviewStatement->execute([
        'status' => $decision === 'approve' ? 'approved' : 'rejected',
        'reviewed_by' => currentUser()['id'],
        'admin_notes' => $adminNotes !== '' ? $adminNotes : null,
        'id' => $paymentId,
    ]);

    if ($decision === 'approve') {
        $pdo->prepare('UPDATE memberships SET membership_type = :membership_type WHERE id = :id')
            ->execute(['membership_type' => $membershipType, 'id' => $payment['membership_id']]);
        $settingsStatement = $pdo->query(
            "SELECT setting_key, setting_value FROM settings
             WHERE setting_key IN ('membership_duration_months', 'credit_duration_months', 'credits_included')"
        );
        $settings = [];
        foreach ($settingsStatement as $setting) {
            $settings[$setting['setting_key']] = $setting['setting_value'];
        }
        $membershipMonths = max(1, (int) ($settings['membership_duration_months'] ?? 6));
        $creditMonths = max(1, (int) ($settings['credit_duration_months'] ?? 6));
        $creditsIncluded = max(1, (int) ($settings['credits_included'] ?? 10));
        $today = new DateTimeImmutable('today');
        $membershipExpiration = $today->modify('+' . $membershipMonths . ' months')->format('Y-m-d');
        $creditExpiration = (new DateTimeImmutable())->modify('+' . $creditMonths . ' months')->format('Y-m-d H:i:s');

        $membershipStatement = $pdo->prepare(
            "UPDATE memberships
             SET status = 'active', start_date = :start_date, expiration_date = :expiration_date
             WHERE id = :id"
        );
        $membershipStatement->execute([
            'start_date' => $today->format('Y-m-d'),
            'expiration_date' => $membershipExpiration,
            'id' => $payment['membership_id'],
        ]);

        $batchStatement = $pdo->prepare(
            'INSERT INTO credit_batches
             (user_id, amount, remaining_amount, issued_at, expires_at)
             VALUES (:user_id, :amount_total, :amount_remaining, NOW(), :expires_at)'
        );
        $batchStatement->execute([
            'user_id' => $payment['membership_user_id'],
            'amount_total' => $creditsIncluded,
            'amount_remaining' => $creditsIncluded,
            'expires_at' => $creditExpiration,
        ]);
        $batchId = $pdo->lastInsertId();

        $transactionStatement = $pdo->prepare(
            "INSERT INTO credit_transactions
             (user_id, credit_batch_id, amount, transaction_type, description)
             VALUES (:user_id, :batch_id, :amount, 'issued', :description)"
        );
        $transactionStatement->execute([
            'user_id' => $payment['membership_user_id'],
            'batch_id' => $batchId,
            'amount' => $creditsIncluded,
            'description' => 'Membership payment approved.',
        ]);
    } else {
        $pdo->prepare("UPDATE memberships SET status = 'rejected' WHERE id = :id")
            ->execute(['id' => $payment['membership_id']]);
    }

    $pdo->commit();
    flash('success', $decision === 'approve' ? 'Payment approved and membership activated.' : 'Payment rejected.');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Payment review failed for payment ' . $paymentId . ': ' . $exception->getMessage());
    flash('success', 'Payment review failed. No changes were saved.');
}

redirect('payments.php');
