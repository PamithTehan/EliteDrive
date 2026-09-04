<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

header('Content-Type: application/json');
requireLogin();
requireRole('borrower');

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
    'pickup_date'        => str_replace('T', ' ', $_POST['pickup_date']),
    'return_date'        => str_replace('T', ' ', $_POST['return_date']),
    'pickup_location'    => $_POST['pickup_location'],
];

$check = resolveLicenseRequirement($booking, $db);

if (!isVehicleAvailable($booking['vehicle_id'], $booking['pickup_date'], $booking['return_date'], $db)) {
    http_response_code(400);
    echo json_encode(['error' => 'This vehicle is already booked for the selected dates.']);
    exit;
}

if ($booking['driver_arrangement'] === 'hired' && $booking['assigned_driver_id']) {
    if (!isDriverAvailable($booking['assigned_driver_id'], $booking['pickup_date'], $booking['return_date'], $db)) {
        http_response_code(400);
        echo json_encode(['error' => 'The selected driver is already booked for the selected dates.']);
        exit;
    }
    if (!isDriverTransmissionCompatible($booking['assigned_driver_id'], $booking['vehicle_id'], $db)) {
        http_response_code(400);
        echo json_encode(['error' => 'The selected driver is not compatible with this vehicle\'s transmission.']);
        exit;
    }
}

// We always start with pending_payment now
$status = 'pending_payment';

$stmt = $db->prepare(
    'INSERT INTO bookings
     (vehicle_id, borrower_id, driver_arrangement, assigned_driver_id,
      pickup_date, return_date, pickup_location, total_price, status)
     VALUES (?,?,?,?,?,?,?,?,?)'
);

try {
    $totalPrice = calculatePrice($booking, $db);
    $stmt->execute([
        $booking['vehicle_id'], $booking['borrower_id'], $booking['driver_arrangement'],
        $booking['assigned_driver_id'], $booking['pickup_date'], $booking['return_date'],
        $booking['pickup_location'], $totalPrice, $status,
    ]);
    $bookingId = $db->lastInsertId();
    
    // Insert pending payment record
    $stmt = $db->prepare('INSERT INTO payments (booking_id, user_id, amount, status) VALUES (?, ?, ?, "pending")');
    $stmt->execute([$bookingId, $user['id'], $totalPrice]);
    
    // Create Stripe Checkout Session
    $config = require __DIR__ . '/../../../config/config.php';
    $stripeKey = $config['stripe_secret_key'] ?? '';
    
    $successUrl = baseUrl('/borrower/payment_success.php?booking_id=' . $bookingId);
    if (strpos($successUrl, 'http://') !== 0 && strpos($successUrl, 'https://') !== 0) {
        $successUrl = 'http://' . ltrim($successUrl, '/');
    }

    $cancelUrl = baseUrl('/borrower/payment_cancel.php?booking_id=' . $bookingId);
    if (strpos($cancelUrl, 'http://') !== 0 && strpos($cancelUrl, 'https://') !== 0) {
        $cancelUrl = 'http://' . ltrim($cancelUrl, '/');
    }
    
    $stripeData = http_build_query([
        'payment_method_types[0]' => 'card',
        'line_items[0][price_data][currency]' => 'usd',
        'line_items[0][price_data][product_data][name]' => 'Vehicle Rental Booking #' . $bookingId,
        'line_items[0][price_data][unit_amount]' => round($totalPrice * 100), // in cents
        'line_items[0][quantity]' => 1,
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'client_reference_id' => $bookingId,
    ]);
    
    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $stripeData);
    curl_setopt($ch, CURLOPT_USERPWD, $stripeKey . ':');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $db->exec("UPDATE bookings SET status = 'cancelled' WHERE id = " . (int)$bookingId);
        $db->exec("UPDATE payments SET status = 'failed' WHERE booking_id = " . (int)$bookingId);
        throw new Exception('Stripe API error: ' . $response);
    }
    
    $stripeRes = json_decode($response, true);
    if (empty($stripeRes['url'])) {
        throw new Exception('Stripe Checkout URL not returned.');
    }
    
    // Save stripe session id
    $db->prepare('UPDATE payments SET stripe_session_id = ? WHERE booking_id = ?')->execute([$stripeRes['id'], $bookingId]);

    echo json_encode([
        'ok' => true,
        'booking_id' => $bookingId,
        'stripe_url' => $stripeRes['url']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not create booking: ' . $e->getMessage()]);
}
