<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

if (!csrfCheck($_POST['csrf'] ?? '')) {
    http_response_code(419);
    echo json_encode(['error' => 'Invalid session token, please refresh.']);
    exit;
}

$db = getDb();
$user = currentUser();

$booking = [
    'vehicle_id'         => (int) $_POST['vehicle_id'],
    'borrower_id'        => $user['id'],
    'driver_arrangement' => $_POST['driver_arrangement'], // owner|self|hired
    'assigned_driver_id' => !empty($_POST['driver_id']) ? (int) $_POST['driver_id'] : null,
    'pickup_date'        => $_POST['pickup_date'],
    'return_date'        => $_POST['return_date'],
    'pickup_location'    => $_POST['pickup_location'],
];

$check = resolveLicenseRequirement($booking, $db);

$status = $check['satisfied'] ? 'confirmed' : 'pending_verification';

$stmt = $db->prepare(
    'INSERT INTO bookings
     (vehicle_id, borrower_id, driver_arrangement, assigned_driver_id,
      pickup_date, return_date, pickup_location, total_price, status)
     VALUES (?,?,?,?,?,?,?,?,?)'
);

try {
    $stmt->execute([
        $booking['vehicle_id'], $booking['borrower_id'], $booking['driver_arrangement'],
        $booking['assigned_driver_id'], $booking['pickup_date'], $booking['return_date'],
        $booking['pickup_location'], calculatePrice($booking, $db), $status,
    ]);

    echo json_encode([
        'booking_id' => $db->lastInsertId(),
        'status'     => $status,
        'license_status' => $check['license_status'],
        'message'    => $status === 'confirmed'
            ? 'Booking confirmed.'
            : ($check['license_status'] === 'assignment_pending'
                ? 'Booking pending — waiting for an admin to assign a driver.'
                : 'Booking pending — the required driving license is not yet verified.'),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not create booking.']);
}
