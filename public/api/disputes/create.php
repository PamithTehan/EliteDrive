<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/functions.php';

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

$user = currentUser();
$bookingId = (int) ($_POST['booking_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!$bookingId || !$reason || !$description) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing fields.']));
}

$db = getDb();

// Insert dispute
$stmt = $db->prepare('INSERT INTO disputes (booking_id, raised_by, reason, description) VALUES (?, ?, ?, ?)');
try {
    $stmt->execute([$bookingId, $user['id'], $reason, $description]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['error' => 'Failed to submit dispute.']));
}
