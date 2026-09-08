<?php
/**
 * File: cancel.php
 * Purpose: Endpoint to cancel a booking
 */
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

requireRole('borrower');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (!csrfCheck($input['csrf'] ?? '')) {
    http_response_code(419);
    exit(json_encode(['error' => 'Invalid session token']));
}

$bookingId = (int) ($input['booking_id'] ?? 0);

if (!$bookingId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid booking ID']));
}

$db = getDb();
$user = currentUser();

// Ensure the booking belongs to the current user and is not already completed/cancelled
$stmt = $db->prepare('SELECT status FROM bookings WHERE id = ? AND borrower_id = ?');
$stmt->execute([$bookingId, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(404);
    exit(json_encode(['error' => 'Booking not found']));
}

if (in_array($booking['status'], ['completed', 'cancelled', 'rejected', 'reviewed'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Cannot cancel a booking in this state']));
}

$stmt = $db->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ? AND borrower_id = ?');
$stmt->execute([$bookingId, $user['id']]);

echo json_encode(['ok' => true]);
