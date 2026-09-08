<?php
/**
 * File: assign_driver.php
 * Purpose: Endpoint for drivers to accept trips
 */
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

// Fetch booking details including vehicle
$bStmt = $db->prepare('SELECT vehicle_id, pickup_date, return_date FROM bookings WHERE id = ?');
$bStmt->execute([$bookingId]);
$booking = $bStmt->fetch();

if (!$booking) {
    http_response_code(404);
    exit(json_encode(['error' => 'Booking not found.']));
}

// Check if driver is valid and has verified license
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

// Check transmission compatibility
if (!isDriverTransmissionCompatible($driverId, (int)$booking['vehicle_id'], $db)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Selected driver is not compatible with this vehicle\'s transmission.']));
}

// Check driver date availability
if (!isDriverAvailable($driverId, $booking['pickup_date'], $booking['return_date'], $db)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Selected driver is already booked for these dates.']));
}

// Calculate new total_price including driver fee
$calcBooking = $booking;
$calcBooking['driver_arrangement'] = 'hired';
$calcBooking['assigned_driver_id'] = $driverId;
$breakdown = calculateRentalBreakdown($calcBooking, $db);

// Check if booking is in pending_assignment
$bStatus = $db->prepare('SELECT status FROM bookings WHERE id = ?');
$bStatus->execute([$bookingId]);
$currentStatus = $bStatus->fetchColumn();

if ($currentStatus !== 'pending_assignment' && $currentStatus !== 'pending_verification') {
    http_response_code(400);
    exit(json_encode(['error' => 'Booking is not in a valid state for driver assignment.']));
}

$newStatus = ($currentStatus === 'pending_assignment') ? 'pending_payment' : 'confirmed';

// Update booking
$stmt = $db->prepare("
    UPDATE bookings 
    SET assigned_driver_id = ?, 
        status = ?,
        total_price = ?,
        commission_rate = ?,
        commission_amount = ?,
        owner_earnings = ?,
        driver_earnings = ?
    WHERE id = ? AND driver_arrangement = 'hired'
");
$stmt->execute([
    $driverId, $newStatus, 
    $breakdown['total_price'], $breakdown['commission_rate'], 
    $breakdown['commission_amount'], $breakdown['owner_earnings'], 
    $breakdown['driver_earnings'], $bookingId
]);

if ($stmt->rowCount() === 0) {
    http_response_code(400);
    exit(json_encode(['error' => 'Booking not found or already assigned.']));
}

// Update payment record if it exists
if ($newStatus === 'pending_payment') {
    $db->prepare('UPDATE payments SET amount = ? WHERE booking_id = ? AND status = "pending"')->execute([$breakdown['total_price'], $bookingId]);
}

echo json_encode(['ok' => true]);
