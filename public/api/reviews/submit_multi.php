<?php
/**
 * File: submit_multi.php
 * Purpose: Endpoint for batched reviews
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

if (!$bookingId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid booking ID.']));
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

// Ensure it's completed or confirmed
if (!in_array($booking['status'], ['completed', 'confirmed'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Booking not eligible for review.']));
}

$platformRating = (int)($_POST['platform_rating'] ?? 0);
$platformComment = trim($_POST['platform_comment'] ?? '');

$vehicleRating = (int)($_POST['vehicle_rating'] ?? 0);
$vehicleComment = trim($_POST['vehicle_comment'] ?? '');

$driverRating = (int)($_POST['driver_rating'] ?? 0);
$driverComment = trim($_POST['driver_comment'] ?? '');
$hasDriver = !empty($booking['driver_user_id']) && $booking['driver_arrangement'] === 'hired';

if ($platformRating < 1 || $platformRating > 5 || $vehicleRating < 1 || $vehicleRating > 5) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid rating values.']));
}
if ($hasDriver && ($driverRating < 1 || $driverRating > 5)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid driver rating.']));
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare('INSERT INTO reviews (booking_id, reviewer_id, target_id, target_type, rating, comment) VALUES (?, ?, ?, ?, ?, ?)');

    // 1. Platform (target_id = 1 as dummy id)
    $stmt->execute([$bookingId, $user['id'], 1, 'platform', $platformRating, $platformComment]);

    // 2. Vehicle
    $stmt->execute([$bookingId, $user['id'], $booking['vehicle_id'], 'vehicle', $vehicleRating, $vehicleComment]);

    // 3. Driver
    if ($hasDriver) {
        $stmt->execute([$bookingId, $user['id'], $booking['driver_user_id'], 'driver', $driverRating, $driverComment]);
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    // 1062 is duplicate entry (due to UNIQUE constraint)
    if ($e->getCode() == 23000 || $e->getCode() == 1062) {
        http_response_code(400);
        exit(json_encode(['error' => 'You have already submitted a review for this booking.']));
    }
    http_response_code(500);
    exit(json_encode(['error' => 'Failed to submit reviews.']));
}
