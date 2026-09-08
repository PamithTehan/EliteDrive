<?php
/**
 * File: dispute.php
 * Purpose: Endpoint to escalate a booking dispute
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

$bookingId = (int)($_POST['booking_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$details = trim($_POST['details'] ?? '');
$contactMethod = $_POST['preferred_contact_method'] ?? 'email';
$contactInfo = trim($_POST['contact_info'] ?? '');

if (!$bookingId || !$reason || !$details || !$contactInfo) {
    http_response_code(400);
    exit(json_encode(['error' => 'All fields are required.']));
}

if (!in_array($contactMethod, ['email', 'phone'])) {
    $contactMethod = 'email';
}

$db = getDb();
$user = currentUser();

// Verify booking belongs to user and is in a state that can be disputed
$stmt = $db->prepare('SELECT id, status FROM bookings WHERE id = ? AND borrower_id = ?');
$stmt->execute([$bookingId, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(404);
    exit(json_encode(['error' => 'Booking not found or access denied.']));
}

// Can only dispute if it's completed, active, or reviewed (not cancelled/rejected)
$allowedStatuses = ['completed', 'active', 'reviewed', 'disputed'];
if (!in_array($booking['status'], $allowedStatuses)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Booking status does not allow disputes.']));
}

// Check if dispute already exists for this booking
$dStmt = $db->prepare('SELECT id FROM disputes WHERE booking_id = ? AND user_id = ? AND status = "open"');
$dStmt->execute([$bookingId, $user['id']]);
if ($dStmt->fetch()) {
    http_response_code(400);
    exit(json_encode(['error' => 'An open dispute already exists for this booking.']));
}

$db->beginTransaction();
try {
    // Insert dispute
    $ins = $db->prepare('INSERT INTO disputes (booking_id, user_id, reason, details, preferred_contact_method, contact_info) VALUES (?, ?, ?, ?, ?, ?)');
    $ins->execute([$bookingId, $user['id'], $reason, $details, $contactMethod, $contactInfo]);

    // Update booking status
    if ($booking['status'] !== 'disputed') {
        $upd = $db->prepare('UPDATE bookings SET status = "disputed" WHERE id = ?');
        $upd->execute([$bookingId]);
    }

    $db->commit();
    echo json_encode(['ok' => true, 'message' => 'Dispute submitted successfully.']);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit dispute.']);
}
