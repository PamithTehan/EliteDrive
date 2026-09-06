<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('borrower');
$db = getDb();
$user = currentUser();

$bookingId = (int)($_GET['booking_id'] ?? 0);
if (!$bookingId) {
    die('Invalid booking ID.');
}

// Get the payment from the DB regardless of status to debug
$stmt = $db->prepare('SELECT id, stripe_session_id, booking_id, status FROM payments WHERE booking_id = ?');
$stmt->execute([$bookingId]);
$payment = $stmt->fetch();

if (!$payment) {
    die("Payment for Booking ID {$bookingId} does not exist in the database.");
}

if ($payment['status'] !== 'pending') {
    die("Payment was found, but its status is '{$payment['status']}' instead of 'pending'. It might have already been processed or cancelled.");
}

if (empty($payment['stripe_session_id'])) {
    die("Payment is pending, but stripe_session_id is missing in the database!");
}

$sessionId = $payment['stripe_session_id'];

$config = getConfig();
$stripeKey = $config['stripe_secret_key'] ?? '';

// Verify session with Stripe
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $stripeKey . ':');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass local XAMPP SSL certificate issues

$response = curl_exec($ch);
if ($response === false) {
    die('cURL Error: ' . curl_error($ch));
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die('Failed to verify payment with Stripe.');
}

$session = json_decode($response, true);
if ($session['payment_status'] !== 'paid') {
    die('Payment not completed.');
}

// Update payment status
if ($payment) {
    $db->prepare('UPDATE payments SET status = "completed", stripe_payment_intent_id = ? WHERE id = ?')
       ->execute([$session['payment_intent'] ?? null, $payment['id']]);

    // Resolve booking status
    $stmt = $db->prepare('SELECT * FROM bookings WHERE id = ?');
    $stmt->execute([$payment['booking_id']]);
    $booking = $stmt->fetch();

    if ($booking) {
        $check = resolveLicenseRequirement($booking, $db);
        $newStatus = $check['satisfied'] ? 'confirmed' : 'pending_verification';
        
        $db->prepare('UPDATE bookings SET status = ? WHERE id = ?')
           ->execute([$newStatus, $booking['id']]);
    }
}

$extraCss = ['dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<div class="container" style="margin-top: var(--space-xl); margin-bottom: var(--space-xl); text-align: center; max-width: 600px;">
    <div class="card">
        <div class="card-body" style="padding: var(--space-xl);">
            <div style="font-size: 64px; color: var(--color-success); margin-bottom: var(--space-md);">
                ✓
            </div>
            <h1 class="headline-lg" style="margin-bottom: var(--space-sm);">Payment Successful!</h1>
            <p class="body-lg" style="color: var(--color-secondary); margin-bottom: var(--space-lg);">
                Your booking has been secured and payment was processed successfully.
            </p>
            <div style="display: flex; gap: var(--space-sm); justify-content: center; flex-wrap: wrap;">
                <a href="<?= baseUrl('/borrower/my_bookings.php') ?>" class="btn btn-ghost">View My Bookings</a>
                <a href="<?= baseUrl('/borrower/invoice.php?booking_id=' . $bookingId . '&print=1') ?>" target="_blank" class="btn btn-primary">Download Invoice PDF</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
