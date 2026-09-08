<?php
/**
 * File: review.php
 * Purpose: Multi-step review submission form
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();
$db = getDb();

$bookingId = (int)($_GET['booking_id'] ?? 0);
if (!$bookingId) {
    die('Invalid booking ID.');
}

// Fetch booking details
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
    die('Booking not found or not authorized.');
}

if ($booking['status'] !== 'completed' && $booking['status'] !== 'reviewed') {
    die('You can only review completed bookings.');
}

$hasDriver = !empty($booking['driver_user_id']) && $booking['driver_arrangement'] === 'hired';

// Fetch existing reviews for this booking
$stmtChk = $db->prepare('SELECT target_type FROM reviews WHERE booking_id = ? AND reviewer_id = ?');
$stmtChk->execute([$bookingId, $user['id']]);
$existingReviews = $stmtChk->fetchAll(PDO::FETCH_COLUMN);

$platformDone = in_array('platform', $existingReviews);
$vehicleDone = in_array('vehicle', $existingReviews);
$driverDone = in_array('driver', $existingReviews);

$allDone = $platformDone && $vehicleDone && (!$hasDriver || $driverDone);

if ($allDone && $booking['status'] !== 'reviewed') {
    $stmtUpdate = $db->prepare('UPDATE bookings SET status = "reviewed" WHERE id = ?');
    $stmtUpdate->execute([$bookingId]);
    $booking['status'] = 'reviewed';
}

$extraCss = ['dashboard', 'forms'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div class="container" style="max-width: 800px; margin-top: var(--space-xl); margin-bottom: var(--space-xl);">
    <h1 class="headline-lg" style="margin-bottom: var(--space-sm);">Leave a Review</h1>
    <p class="body-md" style="color:var(--color-secondary); margin-bottom: var(--space-xl);">
        Share your experience for booking <strong><?= escapeHtml($booking['make'] . ' ' . $booking['model']) ?></strong>.
    </p>

    <?php if ($allDone): ?>
        <div class="alert alert-success">
            You have successfully completed all reviews for this booking. Thank you for your feedback!
        </div>
        <div style="margin-top: var(--space-md);">
            <a href="<?= baseUrl('/borrower/my_bookings.php') ?>" class="btn btn-primary">Back to My Bookings</a>
        </div>
    <?php else: ?>

        <!-- 1. Platform Review -->
        <?php if (!$platformDone): ?>
        <div class="card" style="margin-bottom: var(--space-lg);">
            <div class="card-body stack-md">
                <h2 class="headline-md">Review EliteDrive</h2>
                <p class="body-sm" style="color:var(--color-secondary);">How was your overall experience using our platform?</p>
                <form class="single-review-form">
                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
                    <input type="hidden" name="target_type" value="platform">
                    
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <select name="rating" class="input" required>
                            <option value="">Select Rating...</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Good</option>
                            <option value="3">3 - Average</option>
                            <option value="2">2 - Poor</option>
                            <option value="1">1 - Terrible</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Comment (Optional)</label>
                        <textarea name="comment" class="input" rows="3" placeholder="Tell us what you liked or how we can improve..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Platform Review</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- 2. Vehicle Review -->
        <?php if (!$vehicleDone): ?>
        <div class="card" style="margin-bottom: var(--space-lg);">
            <div class="card-body stack-md">
                <h2 class="headline-md">Review Vehicle</h2>
                <p class="body-sm" style="color:var(--color-secondary);">How was the <?= escapeHtml($booking['make'] . ' ' . $booking['model']) ?>?</p>
                <form class="single-review-form">
                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
                    <input type="hidden" name="target_type" value="vehicle">
                    
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <select name="rating" class="input" required>
                            <option value="">Select Rating...</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Good</option>
                            <option value="3">3 - Average</option>
                            <option value="2">2 - Poor</option>
                            <option value="1">1 - Terrible</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Comment (Optional)</label>
                        <textarea name="comment" class="input" rows="3" placeholder="How was the condition and performance?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Vehicle Review</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- 3. Driver Review -->
        <?php if ($hasDriver && !$driverDone): ?>
        <div class="card" style="margin-bottom: var(--space-lg);">
            <div class="card-body stack-md">
                <h2 class="headline-md">Review Driver</h2>
                <p class="body-sm" style="color:var(--color-secondary);">How was your hired driver?</p>
                <form class="single-review-form">
                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
                    <input type="hidden" name="target_type" value="driver">
                    
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <select name="rating" class="input" required>
                            <option value="">Select Rating...</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Good</option>
                            <option value="3">3 - Average</option>
                            <option value="2">2 - Poor</option>
                            <option value="1">1 - Terrible</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Comment (Optional)</label>
                        <textarea name="comment" class="input" rows="3" placeholder="How was their driving and professionalism?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Driver Review</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('.single-review-form');
    forms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            
            try {
                btn.disabled = true;
                const res = await fetch('<?= baseUrl('/api/reviews/submit_single.php') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (res.ok) {
                    // Reload to update the view (hide completed forms and check if all done)
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to submit review');
                    btn.disabled = false;
                }
            } catch (err) {
                alert('An error occurred. Please try again.');
                btn.disabled = false;
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
