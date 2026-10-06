<?php
declare(strict_types=1);

$pageTitle = 'Pickleball Booking System';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <h1>Pickleball Booking System</h1>
    <p>Book your pickleball session, manage your membership, and check court availability in one place.</p>
    <?php if ($user === null): ?>
        <a class="button" href="login.php">Login</a>
        <a class="button secondary" href="register.php">Register</a>
    <?php else: ?>
        <a class="button" href="dashboard.php">Go to Dashboard</a>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
