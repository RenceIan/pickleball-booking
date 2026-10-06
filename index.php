<?php
declare(strict_types=1);

$pageTitle = 'Pickleball Booking System';
require_once __DIR__ . '/includes/header.php';
?>
<section class="landing-hero">
    <div class="landing-hero-content">
        <p class="eyebrow">PRIVATE CLUB · MEMBERS ONLY</p>
        <h1>Club 1018</h1>
        <p class="landing-kicker">Membership, reservations, and court time — beautifully simple.</p>
        <p class="landing-copy">Manage your membership, book your pickleball session, and keep track of your credits in one place.</p>
        <a class="button landing-button" href="club.php">Explore the Club</a>
    <?php if ($user === null): ?>
        <a class="button" href="login.php">Login</a>
        <a class="button secondary" href="register.php">Register</a>
    <?php else: ?>
        <a class="button landing-button" href="<?= ($user['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'dashboard.php' ?>">Go to Dashboard</a>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
