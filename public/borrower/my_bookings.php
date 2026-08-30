<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('borrower');
$user = currentUser();
$db = getDb();

// Fetch bookings for this user
$stmt = $db->prepare('
    SELECT b.*, v.make, v.model, v.category 
    FROM bookings b 
    JOIN vehicles v ON b.vehicle_id = v.id 
    WHERE b.borrower_id = ? 
    ORDER BY b.created_at DESC
');
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

$extraCss = ['dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<div class="container grid grid-3" style="margin-top: var(--space-lg); margin-bottom: var(--space-lg);">
    <aside class="dashboard-sidebar" style="grid-column: 1 / 2;">
        <div class="card">
            <div class="card-body stack-sm">
                <h2 class="headline-md"><?= escapeHtml($user['full_name']) ?></h2>
                <p class="body-md">Borrower Dashboard</p>
                <hr style="border:0; border-top: 1px solid var(--color-outline); margin: var(--space-md) 0;">
                <ul class="stack-sm" style="list-style:none; padding:0;">
                    <li><a href="<?= baseUrl('/borrower/my_bookings.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start; font-weight: bold;">My Bookings</a></li>
                    <li><a href="<?= baseUrl('/fleet/search.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">Find a Vehicle</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="dashboard-content" style="grid-column: 2 / 4;">
        <h1 class="headline-lg" style="margin-bottom: var(--space-lg);">My Bookings</h1>
        
        <?php if (empty($bookings)): ?>
            <div class="card">
                <div class="card-body" style="text-align:center; padding: var(--space-lg);">
                    <p class="body-lg" style="color:var(--color-secondary);">You don't have any bookings yet.</p>
                    <a href="<?= baseUrl('/fleet/search.php') ?>" class="btn btn-primary" style="margin-top: var(--space-md);">Browse Fleet</a>
                </div>
            </div>
        <?php else: ?>
            <div class="stack-md">
                <?php foreach ($bookings as $b): ?>
                    <div class="card">
                        <div class="card-body">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <div>
                                    <h3 class="headline-md"><?= escapeHtml($b['make'] . ' ' . $b['model']) ?></h3>
                                    <p class="body-md" style="color:var(--color-secondary);">
                                        <?= escapeHtml(date('M d, Y', strtotime($b['pickup_date']))) ?> to <?= escapeHtml(date('M d, Y', strtotime($b['return_date']))) ?>
                                    </p>
                                </div>
                                <div style="text-align:right;">
                                    <h3 class="headline-md" style="color:var(--color-primary);">$<?= escapeHtml($b['total_price']) ?></h3>
                                    <?php
                                    $badgeClass = match($b['status']) {
                                        'confirmed', 'active', 'completed' => 'badge-status-verified',
                                        'pending_verification' => 'badge-status-pending',
                                        'rejected', 'cancelled' => 'badge-status-rejected',
                                        default => 'badge-status-pending'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= str_replace('_', ' ', escapeHtml($b['status'])) ?></span>
                                </div>
                            </div>
                            
                            <?php if ($b['status'] === 'pending_verification'): ?>
                                <div class="alert alert-error" style="margin-top: var(--space-md); margin-bottom: 0;">
                                    <?php if ($b['driver_arrangement'] === 'hired' && empty($b['assigned_driver_id'])): ?>
                                        <strong>Pending:</strong> Waiting for an admin to assign a driver to your booking.
                                    <?php else: ?>
                                        <strong>Action Required:</strong> Your booking is held until the required driving license is verified by an admin.
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($b['status'] === 'pending_payment'): ?>
                                <div class="alert alert-warning" style="margin-top: var(--space-md); margin-bottom: 0; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: var(--space-sm); border-radius: var(--radius-sm);">
                                    <strong>Payment Required:</strong> Your booking is awaiting payment. If you cancelled the payment, you may cancel this booking and try again.
                                </div>
                            <?php endif; ?>
                            
                            <?php 
                                $showReview = in_array($b['status'], ['completed', 'confirmed']);
                                $showCancel = !in_array($b['status'], ['completed', 'cancelled', 'rejected', 'reviewed']);
                            ?>
                            <?php if ($showReview || $showCancel): ?>
                                <div style="margin-top: var(--space-md); border-top: 1px solid var(--color-outline); padding-top: var(--space-sm);">
                                    <?php if ($showReview): ?>
                                        <button class="btn btn-ghost" onclick="leaveReview(<?= $b['id'] ?>)" style="padding: 4px 12px; font-size: 14px;">Leave Review</button>
                                        <button class="btn btn-ghost" onclick="reportDispute(<?= $b['id'] ?>)" style="padding: 4px 12px; font-size: 14px; color: var(--color-error);">Report Dispute</button>
                                    <?php endif; ?>
                                    <?php if ($showCancel): ?>
                                        <button class="btn btn-ghost" onclick="cancelBooking(<?= $b['id'] ?>)" style="padding: 4px 12px; font-size: 14px; color: var(--color-error);">Cancel Booking</button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
    const csrfToken = "<?= csrfToken() ?>";
    
    async function leaveReview(bookingId) {
        const rating = prompt("Enter a rating from 1 to 5:");
        if (!rating || isNaN(rating) || rating < 1 || rating > 5) return;
        
        const comment = prompt("Optional: Leave a comment for the owner:");
        
        const formData = new FormData();
        formData.append('csrf', csrfToken);
        formData.append('booking_id', bookingId);
        formData.append('rating', rating);
        formData.append('comment', comment || '');
        formData.append('target_user', 'owner');
        
        const res = await fetch('<?= baseUrl('/api/reviews/submit.php') ?>', { method: 'POST', body: formData });
        if (res.ok) {
            alert('Review submitted successfully!');
        } else {
            const data = await res.json();
            alert(data.error || 'Failed to submit review');
        }
    }
    
    async function reportDispute(bookingId) {
        const reason = prompt("What is the reason for this dispute? (e.g. Damage, No Show)");
        if (!reason) return;
        
        const desc = prompt("Please provide more details:");
        
        const formData = new FormData();
        formData.append('csrf', csrfToken);
        formData.append('booking_id', bookingId);
        formData.append('reason', reason);
        formData.append('description', desc || '');
        
        const res = await fetch('<?= baseUrl('/api/disputes/create.php') ?>', { method: 'POST', body: formData });
        if (res.ok) {
            alert('Dispute submitted successfully! An admin will review it.');
        } else {
            const data = await res.json();
            alert(data.error || 'Failed to submit dispute');
        }
    }
    
    async function cancelBooking(bookingId) {
        if (!confirm("Are you sure you want to cancel this booking?")) return;
        
        const formData = new FormData();
        formData.append('csrf', csrfToken);
        formData.append('booking_id', bookingId);
        
        try {
            const res = await fetch('<?= baseUrl('/api/bookings/cancel.php') ?>', { method: 'POST', body: formData });
            if (res.ok) {
                window.location.reload();
            } else {
                const data = await res.json();
                alert(data.error || 'Failed to cancel booking');
            }
        } catch (e) {
            alert('Network error');
        }
    }
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
