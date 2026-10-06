<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireApprovedMember();

$pdo = getDatabaseConnection();
$currentUser = currentUser();
$canReserve = $currentUser !== null && ($currentUser['role'] ?? '') !== 'admin';
$selectedDate = (string) ($_GET['date'] ?? date('Y-m-d'));
$dateObject = DateTime::createFromFormat('Y-m-d', $selectedDate);
if ($dateObject === false || $dateObject->format('Y-m-d') !== $selectedDate) {
    $selectedDate = date('Y-m-d');
}

$settingsStatement = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'booking_time_slots' LIMIT 1");
$slotsValue = (string) ($settingsStatement->fetchColumn() ?: '');
$slots = array_values(array_filter(array_map('trim', explode(',', $slotsValue))));
$cancellationStatement = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'cancellation_window_minutes' LIMIT 1");
$cancellationMinutes = max(0, (int) ($cancellationStatement->fetchColumn() ?: 30));

$bookingStatement = $pdo->prepare(
    "SELECT id, user_id, start_time, end_time, membership_type FROM bookings
     WHERE booking_date = :booking_date AND status = 'confirmed' ORDER BY start_time"
);
$bookingStatement->execute(['booking_date' => $selectedDate]);
$bookedSlots = [];
foreach ($bookingStatement as $booking) {
    $startTime = substr((string) $booking['start_time'], 0, 5);
    $endTime = substr((string) $booking['end_time'], 0, 5);
    $bookedSlots[$startTime . '-' . $endTime] = [
        'id' => (int) $booking['id'],
        'user_id' => (int) $booking['user_id'],
        'membership_type' => strtolower((string) $booking['membership_type']),
    ];
}

$pageTitle = 'Reservation Calendar';
require_once __DIR__ . '/includes/header.php';
?>
<section class="dashboard dashboard-section">
    <h1>Reservation Calendar</h1>
    <p>Booked slots are unavailable. Select another date to check availability.</p>
    <?php if (!$canReserve): ?>
        <div class="notice">Administrators can view the reservation calendar, but reservations require an approved member account with an active membership and credits.</div>
    <?php endif; ?>
    <form method="get" class="date-form">
        <label for="date">Select date</label>
        <input id="date" name="date" type="date" min="<?= escape(date('Y-m-d')) ?>" value="<?= escape($selectedDate) ?>">
        <button class="button" type="submit">Check Availability</button>
    </form>
    <h2><?= escape($selectedDate) ?></h2>
    <?php if ($slots === []): ?><p>No booking time slots have been configured.</p><?php else: ?>
        <div class="slot-grid">
            <?php foreach ($slots as $slot): ?>
                <?php [$start, $end] = array_pad(explode('-', $slot, 2), 2, ''); ?>
                <?php $booking = $bookedSlots[$start . '-' . $end] ?? null; ?>
                <?php $isBooked = $booking !== null; ?>
                <?php $bookedMembershipType = $booking['membership_type'] ?? null; ?>
                <?php $bookingStart = DateTimeImmutable::createFromFormat('Y-m-d H:i', $selectedDate . ' ' . $start); ?>
                <?php $displayStartObject = DateTimeImmutable::createFromFormat('H:i', $start); ?>
                <?php $displayEndObject = DateTimeImmutable::createFromFormat('H:i', $end); ?>
                <?php $displayStart = $displayStartObject instanceof DateTimeImmutable ? $displayStartObject->format('g:i A') : $start; ?>
                <?php $displayEnd = $displayEndObject instanceof DateTimeImmutable ? $displayEndObject->format('g:i A') : $end; ?>
                <?php $canCancel = $booking !== null && $booking['user_id'] === (int) $currentUser['id']
                    && $bookingStart !== false
                    && $bookingStart > (new DateTimeImmutable())->modify('+' . $cancellationMinutes . ' minutes'); ?>
                <div class="slot <?= $isBooked ? 'slot-booked slot-' . escape((string) $bookedMembershipType) : 'slot-available' ?>">
                    <strong><?= escape($displayStart) ?>–<?= escape($displayEnd) ?></strong>
                    <span><?= $isBooked ? 'Booked (' . escape(ucfirst($bookedMembershipType)) . ')' : 'Available' ?></span>
                    <?php if ($canCancel): ?>
                        <form method="post" action="cancel-booking.php">
                            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                            <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                            <button class="button secondary" type="submit">Cancel</button>
                        </form>
                    <?php elseif ($isBooked && $booking !== null && $booking['user_id'] === (int) $currentUser['id']): ?>
                        <small>Cancellation closed 30 minutes before start.</small>
                    <?php endif; ?>
                    <?php if (!$isBooked && $canReserve): ?>
                        <form method="post" action="reserve.php">
                            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                            <input type="hidden" name="date" value="<?= escape($selectedDate) ?>">
                            <input type="hidden" name="start" value="<?= escape($start) ?>">
                            <input type="hidden" name="end" value="<?= escape($end) ?>">
                            <button class="button" type="submit">Reserve</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
