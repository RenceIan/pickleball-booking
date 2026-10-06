<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo = getDatabaseConnection();
$selectedDate = (string) ($_GET['date'] ?? date('Y-m-d'));
$dateObject = DateTime::createFromFormat('Y-m-d', $selectedDate);
if ($dateObject === false || $dateObject->format('Y-m-d') !== $selectedDate) {
    $selectedDate = date('Y-m-d');
}

$settingsStatement = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'booking_time_slots' LIMIT 1");
$slotsValue = (string) ($settingsStatement->fetchColumn() ?: '');
$slots = array_values(array_filter(array_map('trim', explode(',', $slotsValue))));

$bookingStatement = $pdo->prepare(
    "SELECT start_time, end_time FROM bookings
     WHERE booking_date = :booking_date AND status = 'confirmed' ORDER BY start_time"
);
$bookingStatement->execute(['booking_date' => $selectedDate]);
$bookedSlots = [];
foreach ($bookingStatement as $booking) {
    $bookedSlots[$booking['start_time'] . '-' . $booking['end_time']] = true;
}

$pageTitle = 'Reservation Calendar';
require_once __DIR__ . '/includes/header.php';
?>
<section class="dashboard dashboard-section">
    <h1>Reservation Calendar</h1>
    <p>Booked slots are unavailable. Select another date to check availability.</p>
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
                <?php $isBooked = isset($bookedSlots[$start . '-' . $end]); ?>
                <div class="slot <?= $isBooked ? 'slot-booked' : 'slot-available' ?>">
                    <strong><?= escape($start) ?>–<?= escape($end) ?></strong>
                    <span><?= $isBooked ? 'Booked' : 'Available' ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
