<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect((currentUser()['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
}

$errors = [];
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'The form expired. Please try again.';
    }
    if ($fullName === '' || strlen($fullName) > 150) {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if ($errors === []) {
        try {
            $pdo = getDatabaseConnection();
            $statement = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $statement->execute(['email' => $email]);

            if ($statement->fetch() !== false) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO users (full_name, email, password, status)
                     VALUES (:full_name, :email, :password, "pending")'
                );
                $statement->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                flash('success', 'Registration successful. You can now log in.');
                redirect('login.php');
            }
        } catch (PDOException $exception) {
            $errors[] = 'Registration could not be completed. Please try again.';
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>
<section class="form-card">
    <h1>Create an account</h1>
    <?php if ($errors !== []): ?>
        <div class="message error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= escape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <label for="full_name">Full name</label>
        <input id="full_name" name="full_name" type="text" maxlength="150" required value="<?= escape($fullName) ?>">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?= escape($email) ?>">
        <label for="password">Password</label>
        <div class="password-field">
            <input id="password" name="password" type="password" minlength="8" required>
            <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">Show</button>
        </div>
        <label for="confirm_password">Confirm password</label>
        <div class="password-field">
            <input id="confirm_password" name="confirm_password" type="password" minlength="8" required>
            <button class="password-toggle" type="button" data-password-toggle="confirm_password" aria-label="Show password">Show</button>
        </div>
        <button class="button" type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Log in</a>.</p>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
