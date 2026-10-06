<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request.');
}

$userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
$decision = (string) ($_POST['decision'] ?? '');
if ($userId === false || !in_array($decision, ['approve', 'reject'], true)) {
    http_response_code(400);
    exit('Invalid account review request.');
}

$statement = getDatabaseConnection()->prepare(
    "UPDATE users SET status = :status
     WHERE id = :id AND role <> 'admin'"
);
$statement->execute([
    'status' => $decision === 'approve' ? 'approved' : 'rejected',
    'id' => $userId,
]);

flash('success', $decision === 'approve' ? 'Account approved.' : 'Account rejected.');
redirect('users.php');
