<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? 'Pickleball Booking System';
$user = currentUser();
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isAuthPage = in_array($currentPage, ['login.php', 'register.php'], true);
$isPendingMember = $user !== null
    && ($user['role'] ?? '') !== 'admin'
    && ($user['status'] ?? '') !== 'approved';
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
            <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a href="admin/dashboard.php">Admin Dashboard</a>
            <?php elseif ($isPendingMember): ?>
                <a href="club.php">Club Details</a>
            <?php else: ?>
                <a href="dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php elseif (!$isAuthPage): ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php else: ?>
            <a href="club.php">Club Details</a>
        <?php endif; ?>
    </nav>
</header>
<main class="page-container">
<?php if ($successMessage = flash('success')): ?>
    <div class="message success"><?= escape($successMessage) ?></div>
<?php endif; ?>
<?php if ($errorMessage = flash('error')): ?>
    <div class="message error"><?= escape($errorMessage) ?></div>
<?php endif; ?>
