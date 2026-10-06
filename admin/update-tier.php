<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request.');
}

$userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
$membershipType = strtolower(trim((string) ($_POST['membership_type'] ?? '')));
if ($userId === false || !in_array($membershipType, ['rally', 'smash'], true)) {
    http_response_code(400);
    exit('Invalid membership tier update.');
}

$statement = getDatabaseConnection()->prepare(
    "UPDATE memberships m
     JOIN users u ON u.id = m.user_id
     SET m.membership_type = :membership_type
     WHERE m.user_id = :user_id AND u.role <> 'admin'
       AND m.id = (
           SELECT latest.id FROM (
               SELECT id FROM memberships
               WHERE user_id = :latest_user_id
               ORDER BY created_at DESC LIMIT 1
           ) AS latest
       )"
);
$statement->execute([
    'membership_type' => $membershipType,
    'user_id' => $userId,
    'latest_user_id' => $userId,
]);

flash('success', $statement->rowCount() > 0 ? 'Membership tier updated.' : 'No membership was found for that account.');
redirect('users.php');
