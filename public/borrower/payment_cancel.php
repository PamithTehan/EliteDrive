<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('borrower');
$db = getDb();
$user = currentUser();

$bookingId = (int)($_GET['booking_id'] ?? 0);
if (!$bookingId) {
    header('Location: ' . baseUrl('/borrower/my_bookings.php'));
    exit;
}

// Cancel the pending payment booking
$stmt = $db->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ? AND borrower_id = ? AND status = "pending_payment"');
$stmt->execute([$bookingId, $user['id']]);

if ($stmt->rowCount() > 0) {
    $db->prepare('UPDATE payments SET status = "failed" WHERE booking_id = ? AND status = "pending"')->execute([$bookingId]);
}

$extraCss = ['dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<div class="container" style="margin-top: var(--space-xl); margin-bottom: var(--space-xl); text-align: center; max-width: 600px;">
    <div class="card">
        <div class="card-body" style="padding: var(--space-xl);">
            <div style="font-size: 64px; color: var(--color-error); margin-bottom: var(--space-md);">
                ✕
            </div>
            <h1 class="headline-lg" style="margin-bottom: var(--space-sm);">Payment Cancelled</h1>
            <p class="body-lg" style="color: var(--color-secondary); margin-bottom: var(--space-lg);">
                You have cancelled the payment process. Your booking has not been finalized.
            </p>
            <a href="<?= baseUrl('/fleet/search.php') ?>" class="btn btn-primary">Find Another Vehicle</a>
            <a href="<?= baseUrl('/borrower/my_bookings.php') ?>" class="btn btn-ghost" style="margin-top: var(--space-sm);">Go to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
