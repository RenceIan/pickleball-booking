<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? 'Pickleball Booking System';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($pageTitle) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php">Pickleball Booking System</a>
    <nav aria-label="Main navigation">
        <?php if ($user !== null): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>
<main class="page-container">
<?php if ($successMessage = flash('success')): ?>
    <div class="message success"><?= escape($successMessage) ?></div>
<?php endif; ?>
