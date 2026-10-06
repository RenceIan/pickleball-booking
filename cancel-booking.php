<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireApprovedMember();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid cancellation request.');
}

$bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
if ($bookingId === false) {
    http_response_code(400);
    exit('Invalid booking.');
}

$user = currentUser();
$pdo = getDatabaseConnection();
$redirectDate = date('Y-m-d');
try {
    $pdo->beginTransaction();
    $bookingStatement = $pdo->prepare(
        "SELECT id, booking_date, start_time, credit_used, credit_batch_id, status
         FROM bookings WHERE id = :id AND user_id = :user_id FOR UPDATE"
    );
    $bookingStatement->execute(['id' => $bookingId, 'user_id' => $user['id']]);
    $booking = $bookingStatement->fetch();
    if ($booking === false || $booking['status'] !== 'confirmed') {
        throw new RuntimeException('This booking is no longer available for cancellation.');
    }
    $redirectDate = (string) $booking['booking_date'];

    $settingsStatement = $pdo->query(
        "SELECT setting_value FROM settings
         WHERE setting_key = 'cancellation_window_minutes' LIMIT 1"
    );
    $cancellationMinutes = max(0, (int) ($settingsStatement->fetchColumn() ?: 30));
    $bookingStart = DateTimeImmutable::createFromFormat(
        'Y-m-d H:i:s',
        $booking['booking_date'] . ' ' . $booking['start_time']
    );
    if ($bookingStart === false || $bookingStart <= (new DateTimeImmutable())->modify('+' . $cancellationMinutes . ' minutes')) {
        throw new RuntimeException('This booking can no longer be cancelled because it starts within 30 minutes.');
    }

    $batchStatement = $pdo->prepare(
        'SELECT id, remaining_amount, expires_at FROM credit_batches
         WHERE id = :id AND user_id = :user_id FOR UPDATE'
    );
    $batchStatement->execute(['id' => $booking['credit_batch_id'], 'user_id' => $user['id']]);
    $batch = $batchStatement->fetch();
    if ($batch === false) {
        throw new RuntimeException('The original credit batch could not be found.');
    }

    $newRemaining = (int) $batch['remaining_amount'] + (int) $booking['credit_used'];
    $batchUpdate = $pdo->prepare(
        "UPDATE credit_batches SET remaining_amount = :remaining_amount,
         status = CASE WHEN expires_at > NOW() THEN 'active' ELSE 'expired' END
         WHERE id = :id"
    );
    $batchUpdate->execute(['remaining_amount' => $newRemaining, 'id' => $batch['id']]);
    $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id")
        ->execute(['id' => $bookingId]);

    $transaction = $pdo->prepare(
        "INSERT INTO credit_transactions
         (user_id, credit_batch_id, amount, transaction_type, description)
         VALUES (:user_id, :credit_batch_id, :amount, 'refunded', :description)"
    );
    $transaction->execute([
        'user_id' => $user['id'],
        'credit_batch_id' => $batch['id'],
        'amount' => (int) $booking['credit_used'],
        'description' => 'Credit refunded after reservation cancellation.',
    ]);
    $pdo->commit();
    flash('success', 'Reservation cancelled and credit refunded.');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('calendar.php?date=' . urlencode($redirectDate));
