<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'The form expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Invalid email or password.';
    } else {
        try {
            $pdo = getDatabaseConnection();
            $statement = $pdo->prepare(
                'SELECT id, full_name, email, password, role FROM users WHERE email = :email LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if ($user === false || !password_verify($password, $user['password'])) {
                $error = 'Invalid email or password.';
            } else {
                session_regenerate_id(true);
                unset($user['password']);
                $_SESSION['user'] = $user;
                redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
            }
        } catch (PDOException $exception) {
            $error = 'Login could not be completed. Please try again.';
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>
<section class="form-card">
    <h1>Log in</h1>
    <?php if ($error !== null): ?>
        <div class="message error"><?= escape($error) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?= escape($email) ?>">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
        <button class="button" type="submit">Login</button>
    </form>
    <p>Do not have an account? <a href="register.php">Register</a>.</p>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
