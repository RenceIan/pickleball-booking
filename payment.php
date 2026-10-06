<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireApprovedMember();

$user = currentUser();
$errors = [];
$referenceNumber = '';
$paymentDate = date('Y-m-d');
$amount = '';
$membershipType = 'rally';
$uploadDirectory = dirname(__DIR__) . '/private_payment_proofs';

$settingsStatement = getDatabaseConnection()->query(
    "SELECT setting_key, setting_value FROM settings
     WHERE setting_key IN ('membership_price', 'credits_included')"
);
$settings = [];
foreach ($settingsStatement as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}
$membershipPrice = $settings['membership_price'] ?? '500';
$creditsIncluded = $settings['credits_included'] ?? '10';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $referenceNumber = trim((string) ($_POST['reference_number'] ?? ''));
    $paymentDate = (string) ($_POST['payment_date'] ?? '');
    $amount = trim((string) ($_POST['amount'] ?? ''));
    $membershipType = strtolower(trim((string) ($_POST['membership_type'] ?? '')));
    $proof = $_FILES['proof_image'] ?? null;

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'The form expired. Please try again.';
    }
    if ($referenceNumber === '' || strlen($referenceNumber) > 100) {
        $errors[] = 'Enter a valid payment reference number.';
    }
    $dateObject = DateTime::createFromFormat('Y-m-d', $paymentDate);
    if ($dateObject === false || $dateObject->format('Y-m-d') !== $paymentDate) {
        $errors[] = 'Enter a valid payment date.';
    }
    if (!is_numeric($amount) || (float) $amount <= 0) {
        $errors[] = 'Enter a valid payment amount.';
    }
    if (!in_array($membershipType, ['rally', 'smash'], true)) {
        $errors[] = 'Select a valid membership type.';
    }
    if (!is_array($proof) || ($proof['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload a payment proof image.';
    } elseif (($proof['size'] ?? 0) > 5 * 1024 * 1024) {
        $errors[] = 'Payment proof must not exceed 5 MB.';
    }

    if ($errors === [] && is_array($proof)) {
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($proof['tmp_name']);
        if (!isset($allowedMimeTypes[$mimeType])) {
            $errors[] = 'Payment proof must be a JPG, PNG, or WEBP image.';
        }
    }

    if ($errors === [] && is_array($proof)) {
        try {
            $pdo = getDatabaseConnection();
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
                throw new RuntimeException('Could not create the payment proof directory.');
            }

            $extension = $allowedMimeTypes[$mimeType];
            $storedFilename = bin2hex(random_bytes(16)) . '.' . $extension;
            $storedPath = $uploadDirectory . DIRECTORY_SEPARATOR . $storedFilename;
            if (!move_uploaded_file($proof['tmp_name'], $storedPath)) {
                throw new RuntimeException('Could not store the payment proof.');
            }

            $pdo->beginTransaction();
            $membershipStatement = $pdo->prepare(
                "INSERT INTO memberships (user_id, membership_type, amount_paid, status)
                 VALUES (:user_id, :membership_type, :amount_paid, 'pending')"
            );
            $membershipStatement->execute([
                'user_id' => $user['id'],
                'membership_type' => $membershipType,
                'amount_paid' => number_format((float) $amount, 2, '.', ''),
            ]);
            $membershipId = (int) $pdo->lastInsertId();

            $paymentStatement = $pdo->prepare(
                'INSERT INTO payments
                 (user_id, membership_id, amount, reference_number, payment_date, proof_image)
                 VALUES (:user_id, :membership_id, :amount, :reference_number, :payment_date, :proof_image)'
            );
            $paymentStatement->execute([
                'user_id' => $user['id'],
                'membership_id' => $membershipId,
                'amount' => number_format((float) $amount, 2, '.', ''),
                'reference_number' => $referenceNumber,
                'payment_date' => $paymentDate,
                'proof_image' => $storedFilename,
            ]);
            $paymentId = (int) $pdo->lastInsertId();

            $pdo->prepare('UPDATE memberships SET payment_id = :payment_id WHERE id = :membership_id')
                ->execute(['payment_id' => $paymentId, 'membership_id' => $membershipId]);
            $pdo->commit();

            flash('success', 'Payment submitted. Please wait for admin approval.');
            redirect('membership.php');
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (isset($storedPath) && is_file($storedPath)) {
                unlink($storedPath);
            }
            $errors[] = 'Payment could not be submitted. Please try again.';
        }
    }
}

$pageTitle = 'Submit Payment';
require_once __DIR__ . '/includes/header.php';
?>
<section class="form-card">
    <h1>Submit Membership Payment</h1>
    <p>Send ₱<?= escape($membershipPrice) ?> using the facility's payment QR code, then submit the details below.</p>
    <div class="notice">
        <strong>QR payment instructions:</strong> Ask the facility administrator for the current payment QR code.
        Scanning the code does not automatically approve your payment.
    </div>
    <?php if ($errors !== []): ?>
        <div class="message error"><ul><?php foreach ($errors as $error): ?><li><?= escape($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <label for="membership_type">Membership type</label>
        <select id="membership_type" name="membership_type" required>
            <option value="rally" <?= $membershipType === 'rally' ? 'selected' : '' ?>>Rally</option>
            <option value="smash" <?= $membershipType === 'smash' ? 'selected' : '' ?>>Smash</option>
        </select>
        <label for="reference_number">Payment reference number</label>
        <input id="reference_number" name="reference_number" required maxlength="100" value="<?= escape($referenceNumber) ?>">
        <label for="payment_date">Payment date</label>
        <input id="payment_date" name="payment_date" type="date" required value="<?= escape($paymentDate) ?>">
        <label for="amount">Amount paid</label>
        <input id="amount" name="amount" type="number" min="0.01" step="0.01" required value="<?= escape($amount) ?>">
        <label for="proof_image">Payment proof (JPG, PNG, or WEBP; max 5 MB)</label>
        <input id="proof_image" name="proof_image" type="file" accept="image/jpeg,image/png,image/webp" required>
        <button class="button" type="submit">Submit for Approval</button>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
