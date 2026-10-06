<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

$message = '';
$isSuccess = false;

try {
    $pdo = getDatabaseConnection();
    $pdo->query('SELECT 1');
    $message = 'Database connection successful.';
    $isSuccess = true;
} catch (PDOException $exception) {
    $message = 'Database connection failed. Check that MySQL is running and database.php matches your XAMPP settings.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        .success { color: #166534; }
        .error { color: #b91c1c; }
    </style>
</head>
<body>
    <h1>Pickleball Booking Database Test</h1>
    <p class="<?= $isSuccess ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </p>
</body>
</html>
