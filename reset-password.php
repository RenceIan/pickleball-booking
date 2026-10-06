<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$error = null;
$success = null;

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'This password reset link is invalid.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'The form expired. Please try again.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $pdo = getDatabaseConnection();
        $statement = $pdo->prepare(
            'SELECT id, user_id FROM password_resets
             WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1'
        );
        $statement->execute(['token_hash' => hash('sha256', $token)]);
        $reset = $statement->fetch();
        if ($reset === false) {
            $error = 'This password reset link is invalid or expired.';
        } else {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE users SET password = :password WHERE id = :user_id')
                ->execute(['password' => password_hash($password, PASSWORD_DEFAULT), 'user_id' => $reset['user_id']]);
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id')
                ->execute(['id' => $reset['id']]);
            $pdo->commit();
            $success = 'Password updated successfully. You can now log in.';
        }
    }
}

$pageTitle = 'Reset Password';
require_once __DIR__ . '/includes/header.php';
?>
<section class="form-card">
    <h1>Reset password</h1>
    <?php if ($error !== null): ?><div class="message error"><?= escape($error) ?></div><?php endif; ?>
    <?php if ($success !== null): ?>
        <div class="message success"><?= escape($success) ?></div>
        <a class="button" href="login.php">Go to Login</a>
    <?php elseif ($error === null): ?>
        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
            <input type="hidden" name="token" value="<?= escape($token) ?>">
            <label for="password">New password</label>
            <div class="password-field"><input id="password" name="password" type="password" minlength="8" required><button class="password-toggle" type="button" data-password-toggle="password">Show</button></div>
            <label for="confirm_password">Confirm new password</label>
            <div class="password-field"><input id="confirm_password" name="confirm_password" type="password" minlength="8" required><button class="password-toggle" type="button" data-password-toggle="confirm_password">Show</button></div>
            <button class="button" type="submit">Update Password</button>
        </form>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
