<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method Not Allowed']));
}

requireRole('borrower');
$user = currentUser();

$bookingId = (int)($_POST['booking_id'] ?? 0);
if (!csrfCheck($_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid session token.']));
}

$db = getDb();

try {
    $stmt = $db->prepare('SELECT * FROM bookings WHERE id = ? AND borrower_id = ? AND status = "pending_payment"');
    $stmt->execute([$bookingId, $user['id']]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        throw new Exception('Invalid booking or booking is not awaiting payment.');
    }
    
    $totalPrice = (float)$booking['total_price'];
    
    // Check if there's a pending payment
    $stmt = $db->prepare('SELECT id FROM payments WHERE booking_id = ? AND status = "pending"');
    $stmt->execute([$bookingId]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        // If no pending payment exists (maybe it failed), create a new one
        $stmt = $db->prepare('INSERT INTO payments (booking_id, user_id, amount, status) VALUES (?, ?, ?, "pending")');
        $stmt->execute([$bookingId, $user['id'], $totalPrice]);
    }
    
    // Create Stripe Checkout Session
    $config = getConfig();
    $stripeKey = $config['stripe_secret_key'] ?? '';
    
    $successUrl = rtrim($config['base_url'], '/') . '/borrower/payment_success.php?booking_id=' . $bookingId;
    $cancelUrl  = rtrim($config['base_url'], '/') . '/borrower/payment_cancel.php?booking_id=' . $bookingId;
    
    $stripeData = http_build_query([
        'payment_method_types[0]' => 'card',
        'line_items[0][price_data][currency]' => 'lkr',
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass local XAMPP SSL certificate issues
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Stripe API error: ' . $response);
    }
    
    $stripeRes = json_decode($response, true);
    if (empty($stripeRes['url'])) {
        throw new Exception('Stripe Checkout URL not returned.');
    }
    
    // Save new stripe session id
    $db->prepare('UPDATE payments SET stripe_session_id = ? WHERE booking_id = ? AND status = "pending"')->execute([$stripeRes['id'], $bookingId]);

    echo json_encode([
        'ok' => true,
        'stripe_url' => $stripeRes['url']
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not process payment: ' . $e->getMessage()]);
}
