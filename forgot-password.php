<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$message = null;
$error = null;
$email = '';
$resetUrl = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'The form expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $pdo = getDatabaseConnection();
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
        $message = 'If an account exists for that email, a password reset link has been created.';

        if ($user !== false) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id OR expires_at <= NOW()')
                ->execute(['user_id' => $user['id']]);
            $pdo->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expires_at)
                 VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
            )->execute(['user_id' => $user['id'], 'token_hash' => $tokenHash]);
            $resetUrl = 'reset-password.php?token=' . urlencode($token);
        }
    }
}

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';
?>
<section class="form-card">
    <h1>Forgot password</h1>
    <p>Enter your account email to create a password reset link.</p>
    <?php if ($error !== null): ?><div class="message error"><?= escape($error) ?></div><?php endif; ?>
    <?php if ($message !== null): ?>
        <div class="message success"><?= escape($message) ?></div>
        <?php if ($resetUrl !== null): ?>
            <div class="notice">
                <strong>Local testing link:</strong><br>
                <a href="<?= escape($resetUrl) ?>"><?= escape($resetUrl) ?></a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?= escape($email) ?>">
        <button class="button" type="submit">Create Reset Link</button>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
