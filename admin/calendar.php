<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$pdo = getDatabaseConnection();
$selectedDate = (string) ($_GET['date'] ?? date('Y-m-d'));
$dateObject = DateTime::createFromFormat('Y-m-d', $selectedDate);
if ($dateObject === false || $dateObject->format('Y-m-d') !== $selectedDate) {
    $selectedDate = date('Y-m-d');
    $dateObject = DateTime::createFromFormat('Y-m-d', $selectedDate);
}

$slotsStatement = $pdo->query(
    "SELECT setting_value FROM settings WHERE setting_key = 'booking_time_slots' LIMIT 1"
);
$slots = array_values(array_filter(array_map('trim', explode(',', (string) ($slotsStatement->fetchColumn() ?: '')))));

$bookingStatement = $pdo->prepare(
    "SELECT b.id, b.start_time, b.end_time, b.membership_type,
            u.full_name, u.email, b.created_at
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     WHERE b.booking_date = :booking_date AND b.status = 'confirmed'
     ORDER BY b.start_time"
);
$bookingStatement->execute(['booking_date' => $selectedDate]);
$bookings = [];
foreach ($bookingStatement as $booking) {
    $start = substr((string) $booking['start_time'], 0, 5);
    $end = substr((string) $booking['end_time'], 0, 5);
    $bookings[$start . '-' . $end] = $booking;
}

function adminDisplayTime(string $time): string
{
    $timeObject = DateTimeImmutable::createFromFormat('H:i', substr($time, 0, 5));
    return $timeObject instanceof DateTimeImmutable ? $timeObject->format('g:i A') : $time;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Booking Calendar</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="dashboard.php">Pickleball Booking System Admin</a>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="calendar.php">Booking Calendar</a>
        <a href="payments.php">Payments</a>
        <a href="users.php">Users</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>
<main class="page-container admin-page">
    <div class="page-heading">
        <h1>Booking Calendar</h1>
        <p>View the member and membership tier assigned to every confirmed reservation.</p>
    </div>
    <section class="admin-card admin-calendar-card">
        <form method="get" class="date-form">
            <label for="date">View date</label>
            <input id="date" name="date" type="date" value="<?= escape($selectedDate) ?>">
            <button class="button" type="submit">View bookings</button>
        </form>
        <h2><?= escape($dateObject->format('F j, Y')) ?></h2>
        <div class="admin-calendar-legend">
            <span class="legend-item legend-rally">Rally</span>
            <span class="legend-item legend-smash">Smash</span>
            <span class="legend-item legend-available">Available</span>
        </div>
        <?php if ($slots === []): ?>
            <p>No booking time slots have been configured.</p>
        <?php else: ?>
            <div class="slot-grid admin-slot-grid">
                <?php foreach ($slots as $slot): ?>
                    <?php [$start, $end] = array_pad(explode('-', $slot, 2), 2, ''); ?>
                    <?php $booking = $bookings[$start . '-' . $end] ?? null; ?>
                    <?php $tier = strtolower((string) ($booking['membership_type'] ?? '')); ?>
                    <article class="slot <?= $booking !== null ? 'slot-booked slot-' . escape($tier) : 'slot-available' ?>">
                        <strong><?= escape(adminDisplayTime($start)) ?>–<?= escape(adminDisplayTime($end)) ?></strong>
                        <?php if ($booking === null): ?>
                            <span>Available</span>
                        <?php else: ?>
                            <span>Booked by <?= escape($booking['full_name']) ?></span>
                            <small><?= escape($booking['email']) ?></small>
                            <small><?= escape(ucfirst($tier)) ?> member</small>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
