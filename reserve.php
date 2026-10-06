<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireApprovedMember();

$date = (string) ($_POST['date'] ?? '');
$start = (string) ($_POST['start'] ?? '');
$end = (string) ($_POST['end'] ?? '');
$csrfToken = $_POST['csrf_token'] ?? null;
if (!verifyCsrfToken($csrfToken)) {
    flash('error', 'The reservation form expired. Please try again.');
    redirect('calendar.php');
}
$dateObject = DateTime::createFromFormat('Y-m-d', $date);
$timePattern = '/^\d{2}:\d{2}$/';
if ($dateObject === false || $dateObject->format('Y-m-d') !== $date
    || $date < date('Y-m-d')
    || !preg_match($timePattern, $start) || !preg_match($timePattern, $end)
    || $start >= $end) {
    http_response_code(400);
    exit('Invalid reservation details.');
}

$user = currentUser();
$pdo = getDatabaseConnection();
try {
    $pdo->beginTransaction();
    $membershipStatement = $pdo->prepare(
        "SELECT membership_type FROM memberships
         WHERE user_id = :user_id AND status = 'active'
           AND start_date <= CURDATE() AND expiration_date >= CURDATE()
         ORDER BY created_at DESC LIMIT 1 FOR UPDATE"
    );
    $membershipStatement->execute(['user_id' => $user['id']]);
    $membership = $membershipStatement->fetch();
    if ($membership === false) {
        throw new RuntimeException('You need an active membership before booking.');
    }

    $settings = $pdo->query(
        "SELECT setting_key, setting_value FROM settings
         WHERE setting_key IN ('booking_cost', 'booking_time_slots')"
    )->fetchAll();
    $settingValues = [];
    foreach ($settings as $setting) {
        $settingValues[$setting['setting_key']] = $setting['setting_value'];
    }
    $bookingCost = max(1, (int) ($settingValues['booking_cost'] ?? 1));
    $validSlots = array_map('trim', explode(',', (string) ($settingValues['booking_time_slots'] ?? '')));
    if (!in_array($start . '-' . $end, $validSlots, true)) {
        throw new RuntimeException('That time slot is not available.');
    }

    $bookingStatement = $pdo->prepare(
        "SELECT id FROM bookings
         WHERE booking_date = :booking_date AND start_time = :start_time
           AND end_time = :end_time AND status = 'confirmed' FOR UPDATE"
    );
    $bookingStatement->execute([
        'booking_date' => $date,
        'start_time' => $start,
        'end_time' => $end,
    ]);
    if ($bookingStatement->fetch() !== false) {
        throw new RuntimeException('This time slot is already booked.');
    }

    $creditStatement = $pdo->prepare(
        "SELECT id, remaining_amount FROM credit_batches
         WHERE user_id = :user_id AND status = 'active'
           AND remaining_amount >= :booking_cost AND expires_at > NOW()
         ORDER BY expires_at ASC LIMIT 1 FOR UPDATE"
    );
    $creditStatement->execute(['user_id' => $user['id'], 'booking_cost' => $bookingCost]);
    $batch = $creditStatement->fetch();
    if ($batch === false) {
        throw new RuntimeException('You do not have enough valid booking credits.');
    }

    $remaining = (int) $batch['remaining_amount'] - $bookingCost;
    $batchUpdate = $pdo->prepare(
        "UPDATE credit_batches SET remaining_amount = :remaining_amount,
         status = CASE WHEN :remaining_status = 0 THEN 'depleted' ELSE 'active' END
         WHERE id = :id"
    );
    $batchUpdate->execute([
        'remaining_amount' => $remaining,
        'remaining_status' => $remaining,
        'id' => $batch['id'],
    ]);

    $insertBooking = $pdo->prepare(
        'INSERT INTO bookings
         (user_id, booking_date, start_time, end_time, credit_used, credit_batch_id, membership_type)
         VALUES (:user_id, :booking_date, :start_time, :end_time, :credit_used, :credit_batch_id, :membership_type)'
    );
    $insertBooking->execute([
        'user_id' => $user['id'],
        'booking_date' => $date,
        'start_time' => $start,
        'end_time' => $end,
        'credit_used' => $bookingCost,
        'credit_batch_id' => $batch['id'],
        'membership_type' => $membership['membership_type'],
    ]);
    $transaction = $pdo->prepare(
        "INSERT INTO credit_transactions
         (user_id, credit_batch_id, amount, transaction_type, description)
         VALUES (:user_id, :credit_batch_id, :amount, 'used', :description)"
    );
    $transaction->execute([
        'user_id' => $user['id'],
        'credit_batch_id' => $batch['id'],
        'amount' => -$bookingCost,
        'description' => 'Credit used for court reservation.',
    ]);
    $bookingId = (int) $pdo->lastInsertId();
    $pdo->commit();
    flash('success', 'Reservation confirmed. Booking #' . $bookingId . ' used ' . $bookingCost . ' credit.');
    redirect('calendar.php?date=' . urlencode($date));
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
    redirect('calendar.php?date=' . urlencode($date));
}
