<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

requireRole('admin');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

$input = json_decode(file_get_contents('php://input'), true);

if (!csrfCheck($input['csrf'] ?? '')) {
    http_response_code(419);
    exit(json_encode(['error' => 'Invalid session token']));
}

$bookingId = (int) ($input['booking_id'] ?? 0);
$driverId = (int) ($input['driver_id'] ?? 0);

if (!$bookingId || !$driverId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid input']));
}

$db = getDb();

// Check if driver is valid and verified
$stmt = $db->prepare("
    SELECT u.id 
    FROM users u 
    JOIN driving_licenses dl ON dl.user_id = u.id 
    WHERE u.id = ? AND u.is_driver = 1 AND dl.status = 'verified' AND dl.expiry_date > NOW()
");
$stmt->execute([$driverId]);
if (!$stmt->fetchColumn()) {
    http_response_code(400);
    exit(json_encode(['error' => 'Selected driver is not valid or does not have a verified license.']));
}

// Update booking
$stmt = $db->prepare("
    UPDATE bookings 
    SET assigned_driver_id = ?, status = 'confirmed' 
    WHERE id = ? AND driver_arrangement = 'hired' AND status = 'pending_verification'
");
$stmt->execute([$driverId, $bookingId]);

if ($stmt->rowCount() === 0) {
    http_response_code(400);
    exit(json_encode(['error' => 'Booking not found or already assigned.']));
}

echo json_encode(['ok' => true]);
