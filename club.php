<?php
declare(strict_types=1);

$pageTitle = 'About the Club';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero club-page">
    <p class="eyebrow">PRIVATE CLUB 1018</p>
    <h1>Exclusive private pickleball club</h1>
    <p>Club access is reserved for active members and registered guests. Explore the membership benefits, rules, and booking terms before joining.</p>
    <div class="membership-overview club-membership-overview">
        <div>
            <p class="eyebrow">TWO WAYS TO PLAY</p>
            <h2>Rally or Smash?</h2>
            <p>Rally is designed for relaxed, regular play. Smash is for members who want more court time and more playing company.</p>
            <div class="tier-grid">
                <article class="tier-card tier-rally"><span class="tier-label">RALLY</span><h3>After-work rallies</h3><ul><li>1 free playing guest per booking</li><li>20% off beyond included free hours</li><li>Additional guests: ₱50 each</li></ul></article>
                <article class="tier-card tier-smash"><span class="tier-label">SMASH</span><h3>More court, more company</h3><ul><li>Up to 3 free playing guests per booking</li><li>30% off beyond included free hours</li><li>Additional guests: ₱50 each</li></ul></article>
            </div>
        </div>
    </div>
    <div class="club-grid">
        <article class="club-card"><h2>Membership benefits</h2><ul><li>Standard paddle rental</li><li>Water refills during play</li><li>One cold towel per session</li><li>Free hours cover court booking fees only</li></ul></article>
        <article class="club-card"><h2>Access and guests</h2><ul><li>Only active members and registered guests may enter</li><li>Guests must be accompanied by their host member</li><li>Guest privileges apply while the member is playing</li><li>Additional guest fees may apply</li></ul></article>
        <article class="club-card"><h2>Rules and etiquette</h2><ul><li>Wear proper court or non-marking shoes</li><li>Respect members, guests, and staff</li><li>No aggressive behavior, equipment abuse, or profanity</li><li>Sealed water bottles and sports drinks are allowed court-side</li></ul></article>
        <article class="club-card"><h2>Booking terms</h2><ul><li>Bookings use 60-minute time slots</li><li>Members may book up to 2 consecutive hours per day</li><li>Cancel at least 30 minutes before the scheduled time</li><li>Unused monthly court credits do not roll over</li></ul></article>
    </div>
    <a class="button" href="register.php">Apply for Membership</a>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
