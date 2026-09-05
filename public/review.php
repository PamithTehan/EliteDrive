<?php
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

if (!in_array($booking['status'], ['completed', 'confirmed'])) {
    die('You can only review completed or confirmed bookings.');
}

// Check if already reviewed to prevent duplicate submissions
$stmtChk = $db->prepare('SELECT COUNT(*) FROM reviews WHERE booking_id = ? AND reviewer_id = ?');
$stmtChk->execute([$bookingId, $user['id']]);
if ($stmtChk->fetchColumn() > 0) {
    $alreadyReviewed = true;
} else {
    $alreadyReviewed = false;
}

$hasDriver = !empty($booking['driver_user_id']) && $booking['driver_arrangement'] === 'hired';

$extraCss = ['dashboard', 'forms'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div class="container" style="max-width: 800px; margin-top: var(--space-xl); margin-bottom: var(--space-xl);">
    <h1 class="headline-lg" style="margin-bottom: var(--space-sm);">Leave a Review</h1>
    <p class="body-md" style="color:var(--color-secondary); margin-bottom: var(--space-xl);">
        Share your experience for booking <strong><?= escapeHtml($booking['make'] . ' ' . $booking['model']) ?></strong>.
    </p>

    <?php if ($alreadyReviewed): ?>
        <div class="alert alert-success">
            You have already submitted a review for this booking. Thank you for your feedback!
        </div>
        <div style="margin-top: var(--space-md);">
            <a href="<?= baseUrl('/borrower/my_bookings.php') ?>" class="btn btn-primary">Back to My Bookings</a>
        </div>
    <?php else: ?>
        <form id="multi-review-form" method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="booking_id" value="<?= $bookingId ?>">

            <!-- 1. Platform Review -->
            <div class="card" style="margin-bottom: var(--space-lg);">
                <div class="card-body stack-md">
                    <h2 class="headline-md">Review EliteDrive</h2>
                    <p class="body-sm" style="color:var(--color-secondary);">How was your overall experience using our platform?</p>
                    
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <select name="platform_rating" class="input" required>
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
                        <textarea name="platform_comment" class="input" rows="3" placeholder="Tell us what you liked or how we can improve..."></textarea>
                    </div>
                </div>
            </div>

            <!-- 2. Vehicle Review -->
            <div class="card" style="margin-bottom: var(--space-lg);">
                <div class="card-body stack-md">
                    <h2 class="headline-md">Review Vehicle</h2>
                    <p class="body-sm" style="color:var(--color-secondary);">How was the <?= escapeHtml($booking['make'] . ' ' . $booking['model']) ?>?</p>
                    
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <select name="vehicle_rating" class="input" required>
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
                        <textarea name="vehicle_comment" class="input" rows="3" placeholder="How was the condition and performance?"></textarea>
                    </div>
                </div>
            </div>

            <!-- 3. Driver Review -->
            <?php if ($hasDriver): ?>
            <div class="card" style="margin-bottom: var(--space-lg);">
                <div class="card-body stack-md">
                    <h2 class="headline-md">Review Driver</h2>
                    <p class="body-sm" style="color:var(--color-secondary);">How was your hired driver?</p>
                    
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <select name="driver_rating" class="input" required>
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
                        <textarea name="driver_comment" class="input" rows="3" placeholder="How was their driving and professionalism?"></textarea>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 16px; padding: 12px;">Submit All Reviews</button>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('multi-review-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            
            try {
                const res = await fetch('<?= baseUrl('/api/reviews/submit_multi.php') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (res.ok) {
                    alert('Reviews submitted successfully! Thank you for your feedback.');
                    window.location.href = '<?= baseUrl('/borrower/my_bookings.php') ?>';
                } else {
                    alert(data.error || 'Failed to submit reviews');
                }
            } catch (err) {
                alert('An error occurred. Please try again.');
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
