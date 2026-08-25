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
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$targetUser = $_POST['target_user'] ?? 'owner'; // 'owner' or 'driver' or 'borrower'

if (!$bookingId || $rating < 1 || $rating > 5) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid rating or booking ID.']));
}

$db = getDb();
// Check if booking belongs to the current user
$stmt = $db->prepare('SELECT * FROM bookings WHERE id = ? AND (borrower_id = ? OR assigned_driver_id = ?)');
$stmt->execute([$bookingId, $user['id'], $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(403);
    exit(json_encode(['error' => 'Not authorized to review this booking.']));
}

// Determine reviewee_id based on target_user
$revieweeId = null;
if ($targetUser === 'owner') {
    $stmt = $db->prepare('SELECT owner_id FROM vehicles WHERE id = ?');
    $stmt->execute([$booking['vehicle_id']]);
    $revieweeId = $stmt->fetchColumn();
} elseif ($targetUser === 'driver') {
    $revieweeId = $booking['assigned_driver_id'];
} elseif ($targetUser === 'borrower') {
    $revieweeId = $booking['borrower_id'];
}

if (!$revieweeId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid review target.']));
}

$stmt = $db->prepare('INSERT INTO reviews (booking_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
try {
    $stmt->execute([$bookingId, $user['id'], $revieweeId, $rating, $comment]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['error' => 'Failed to submit review.']));
}
