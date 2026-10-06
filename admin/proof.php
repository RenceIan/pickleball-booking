<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';

$paymentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($paymentId === false) {
    http_response_code(404);
    exit('Proof not found.');
}
$statement = getDatabaseConnection()->prepare('SELECT proof_image FROM payments WHERE id = :id');
$statement->execute(['id' => $paymentId]);
$payment = $statement->fetch();
$proofDirectory = dirname(dirname(__DIR__)) . '/private_payment_proofs';
$proofPath = $proofDirectory . '/' . basename((string) ($payment['proof_image'] ?? ''));
if ($payment === false || !is_file($proofPath)) {
    http_response_code(404);
    exit('Proof not found.');
}
$mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($proofPath);
if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(404);
    exit('Proof not found.');
}
header('Content-Type: ' . $mimeType);
header('X-Content-Type-Options: nosniff');
readfile($proofPath);
