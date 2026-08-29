<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

if (!csrfCheck($_POST['csrf'] ?? '')) {
    http_response_code(419);
    exit(json_encode(['error' => 'Invalid session token']));
}

$amount = (float) ($_POST['amount'] ?? 0);
$token = $_POST['payment_token'] ?? '';

if ($amount <= 0 || empty($token)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid payment details']));
}

// Simulate payment gateway processing (e.g. Stripe, PayPal)
// We randomly fail ~10% of the time to simulate realistic gateway issues
if (rand(1, 100) <= 10) {
    http_response_code(402);
    exit(json_encode(['error' => 'Payment declined by bank.']));
}

// Generate a fake transaction ID
$transactionId = 'txn_' . bin2hex(random_bytes(12));

echo json_encode([
    'ok' => true,
    'transaction_id' => $transactionId,
    'amount_processed' => $amount,
    'message' => 'Payment successful'
]);
