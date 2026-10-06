<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
if (($user['role'] ?? '') === 'admin') {
    redirect('admin/dashboard.php');
}
if ($user !== null && ($user['status'] ?? '') === 'approved') {
    redirect('dashboard.php');
}

$pageTitle = 'Account Approval';
require_once __DIR__ . '/includes/header.php';
?>
<section class="form-card">
    <h1>Account awaiting approval</h1>
    <p>Thank you for registering, <?= escape((string) ($user['full_name'] ?? 'member')) ?>.</p>
    <p>An administrator must approve your account before you can access the member dashboard or submit a membership payment.</p>
    <p>You can review the club information while waiting.</p>
    <a class="button" href="club.php">View Club Details</a>
    <a class="button secondary" href="logout.php">Log out</a>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
