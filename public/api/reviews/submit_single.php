<?php
/**
 * File: submit_single.php
 * Purpose: Endpoint for single entity review
 */
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

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
$bookingId = (int)($_POST['booking_id'] ?? 0);
$targetType = $_POST['target_type'] ?? '';
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if (!$bookingId || !in_array($targetType, ['platform', 'vehicle', 'driver']) || $rating < 1 || $rating > 5) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid input parameters.']));
}

$db = getDb();
// Check if booking belongs to the current user
$stmt = $db->prepare('
    SELECT b.*, v.make, v.model, v.owner_id, d.user_id AS driver_user_id 
    FROM bookings b 
    JOIN vehicles v ON b.vehicle_id = v.id 
    LEFT JOIN drivers d ON b.assigned_driver_id = d.user_id
    WHERE b.id = ? AND b.borrower_id = ?
');
$stmt->execute([$bookingId, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(403);
    exit(json_encode(['error' => 'Not authorized to review this booking.']));
}

// Ensure it's completed
if ($booking['status'] !== 'completed') {
    http_response_code(403);
    exit(json_encode(['error' => 'Only completed bookings can be reviewed.']));
}

$hasDriver = !empty($booking['driver_user_id']) && $booking['driver_arrangement'] === 'hired';
if ($targetType === 'driver' && !$hasDriver) {
    http_response_code(400);
    exit(json_encode(['error' => 'No hired driver for this booking.']));
}

// Determine target_id
$targetId = 0;
if ($targetType === 'platform') {
    $targetId = 1;
} elseif ($targetType === 'vehicle') {
    $targetId = $booking['vehicle_id'];
} elseif ($targetType === 'driver') {
    $targetId = $booking['driver_user_id'];
}

try {
    $stmt = $db->prepare('INSERT INTO reviews (booking_id, reviewer_id, target_id, target_type, rating, comment) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$bookingId, $user['id'], $targetId, $targetType, $rating, $comment]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // 1062 is duplicate entry (due to UNIQUE constraint)
    if ($e->getCode() == 23000 || $e->getCode() == 1062) {
        http_response_code(400);
        exit(json_encode(['error' => 'You have already submitted a review for this.']));
    }
    http_response_code(500);
    exit(json_encode(['error' => 'Failed to submit review.']));
}
