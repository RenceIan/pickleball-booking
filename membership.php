<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireApprovedMember();

$pdo = getDatabaseConnection();
$user = currentUser();
$statement = $pdo->prepare(
    'SELECT id, amount_paid, start_date, expiration_date, status, created_at
     FROM memberships WHERE user_id = :user_id ORDER BY created_at DESC'
);
$statement->execute(['user_id' => $user['id']]);
$memberships = $statement->fetchAll();

$settingsStatement = $pdo->query(
    "SELECT setting_key, setting_value FROM settings
     WHERE setting_key IN ('membership_price', 'credits_included', 'membership_duration_months')"
);
$settings = [];
foreach ($settingsStatement as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

$pageTitle = 'Membership';
require_once __DIR__ . '/includes/header.php';
?>
<section class="form-card">
    <h1>Membership</h1>
    <p>Membership price: <strong>₱<?= escape($settings['membership_price'] ?? '500') ?></strong></p>
    <p>Credits included: <strong><?= escape($settings['credits_included'] ?? '10') ?></strong></p>
    <p>Duration: <strong><?= escape($settings['membership_duration_months'] ?? '6') ?> months</strong></p>
    <a class="button" href="payment.php">Become a Member / Renew Membership</a>

    <?php if ($memberships !== []): ?>
        <h2>Your membership history</h2>
        <?php foreach ($memberships as $membership): ?>
            <div class="notice">
                Status: <strong><?= escape(ucfirst($membership['status'])) ?></strong><br>
                <?php if ($membership['expiration_date'] !== null): ?>
                    Expires: <?= escape($membership['expiration_date']) ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
