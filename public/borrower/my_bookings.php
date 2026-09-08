<?php
/**
 * File: my_bookings.php
 * Purpose: Borrower's rental history
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('borrower');
$user = currentUser();
$db = getDb();

// Fetch bookings for this user
$statusFilter = $_GET['status'] ?? '';

$sql = '
    SELECT b.*, v.make, v.model, v.category 
    FROM bookings b 
    JOIN vehicles v ON b.vehicle_id = v.id 
    WHERE b.borrower_id = ? 
';
$params = [$user['id']];
if ($statusFilter) {
    $sql .= ' AND b.status = ? ';
    $params[] = $statusFilter;
}
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$countSql = 'SELECT COUNT(*) FROM bookings b WHERE b.borrower_id = ?';
$countParams = [$user['id']];
if ($statusFilter) {
    $countSql .= ' AND b.status = ?';
    $countParams[] = $statusFilter;
}
$countStmt = $db->prepare($countSql);
$countStmt->execute($countParams);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$sql .= ' ORDER BY b.created_at DESC LIMIT ? OFFSET ?';
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    // 1-indexed for bindValue
    $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key + 1, $val, $type);
}
$stmt->execute();
$bookings = $stmt->fetchAll();

// User initials for avatar circle
$initials = '';
$parts = explode(' ', trim($user['full_name']));
foreach ($parts as $p) {
    if (!empty($p)) {
        $initials .= strtoupper($p[0]);
    }
    if (strlen($initials) >= 2) break;
}
if (!$initials) $initials = 'U';

$extraCss = ['profile', 'dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<div style="background-color: #f8fafc; min-height: calc(100vh - 80px); padding-bottom: 40px;">
    <div class="profile-page-hero">
        <div class="container">
            <h1>Borrower Dashboard</h1>
            <p>Manage your vehicle reservations and rentals.</p>
        </div>
    </div>

    <div class="container">
        <div class="profile-layout">
            <aside>
                <div class="profile-user-card">
                    <div class="profile-avatar-circle">
                        <?= escapeHtml($initials) ?>
                    </div>
                    <div class="profile-user-name"><?= escapeHtml($user['full_name']) ?></div>
                    <div class="profile-user-email"><?= escapeHtml($user['email']) ?></div>
                    
                    <div class="profile-role-badges">
                        <span class="role-badge borrower">Borrower</span>
                    </div>

                    <hr class="profile-stats-divider">
                    
                    <div class="profile-meta-list" style="margin-bottom: 24px;">
                        <a href="<?= baseUrl('/borrower/my_bookings.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: #e0e7ff; font-weight: 600;">
                            <span class="material-symbols-outlined">book_online</span> My Bookings
                        </a>
                        <a href="<?= baseUrl('/fleet/search.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">search</span> Find a Vehicle
                        </a>
                    </div>
                </div>
            </aside>
            
            <div class="profile-content-area">
                
                <div class="settings-card">
                    <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap: 16px;">
                            <span class="material-symbols-outlined">book_online</span>
                            <div>
                                <h2>My Bookings</h2>
                                <p>Track your upcoming, active, and past rentals.</p>
                            </div>
                        </div>
            <form method="GET" action="<?= baseUrl('/borrower/my_bookings.php') ?>" style="display:flex; align-items:center; gap:8px;">
                <label for="status" class="body-sm" style="color:var(--color-secondary);">Filter:</label>
                <select name="status" id="status" class="input" style="padding: 6px 12px; width:auto; border-radius: var(--radius-sm);" onchange="this.form.submit()">
                    <option value="">All Bookings</option>
                    <option value="pending_payment" <?= $statusFilter === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
                    <option value="pending_verification" <?= $statusFilter === 'pending_verification' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="reviewed" <?= $statusFilter === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </form>
        </div>
        
        <?php if (empty($bookings)): ?>
            <div class="card">
                <div class="card-body" style="text-align:center; padding: var(--space-lg);">
                    <?php if ($statusFilter): ?>
                        <p class="body-lg" style="color:var(--color-secondary);">You don't have any bookings with the status "<?= escapeHtml(ucwords(str_replace('_', ' ', $statusFilter))) ?>".</p>
                    <?php else: ?>
                        <p class="body-lg" style="color:var(--color-secondary);">You don't have any bookings yet.</p>
                        <a href="<?= baseUrl('/fleet/search.php') ?>" class="btn btn-primary" style="margin-top: var(--space-md);">Browse Fleet</a>
                    <?php endif; ?>
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
                                    <h3 class="headline-md" style="color:var(--color-primary);">LKR <?= escapeHtml($b['total_price']) ?></h3>
                                    <?php
                                     $badgeClass = match($b['status']) {
                                         'confirmed', 'active', 'completed', 'resolved' => 'badge-status-verified',
                                         'pending_verification', 'pending_assignment' => 'badge-status-pending',
                                         'rejected', 'cancelled' => 'badge-status-rejected',
                                         default => 'badge-status-pending'
                                     };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= str_replace('_', ' ', escapeHtml($b['status'])) ?></span>
                                </div>
                            </div>
                            
                            <?php if ($b['status'] === 'pending_assignment'): ?>
                                <div class="alert alert-warning" style="margin-top: var(--space-md); margin-bottom: 0;">
                                    <strong>Pending:</strong> Waiting for an admin to assign a driver to your booking. We will notify you once assigned.
                                </div>
                            <?php elseif ($b['status'] === 'pending_verification'): ?>
                                <div class="alert alert-error" style="margin-top: var(--space-md); margin-bottom: 0;">
                                    <strong>Action Required:</strong> Your booking is held until the required driving license is verified by an admin.
                                </div>
                            <?php elseif ($b['status'] === 'pending_payment'): ?>
                                <div class="alert alert-warning" style="margin-top: var(--space-md); margin-bottom: 0; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: var(--space-sm); border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>Payment Required:</strong> Your booking is awaiting payment. <br>
                                        <small style="color: #666;">(Note: Email notification alerts will be implemented in a future update.)</small>
                                    </div>
                                    <button class="btn btn-primary btn-sm" onclick="payBooking(<?= $b['id'] ?>)">Pay Now</button>
                                </div>
                            <?php endif; ?>
                            
                            <?php 
                                $showReview = ($b['status'] === 'completed');
                                $showCancel = !in_array($b['status'], ['completed', 'cancelled', 'rejected', 'reviewed']);
                                $showInvoice = !in_array($b['status'], ['cancelled', 'rejected', 'pending_payment']);
                            ?>
                            <?php if ($showReview || $showCancel || $showInvoice): ?>
                                <div style="margin-top: var(--space-md); border-top: 1px solid var(--color-outline); padding-top: var(--space-sm);">
                                    <?php if ($showInvoice): ?>
                                        <a href="<?= baseUrl('/borrower/invoice.php?booking_id=' . $b['id'] . '&print=1') ?>" target="_blank" class="btn btn-ghost" style="padding: 4px 12px; font-size: 14px; color: var(--color-primary);">Download Invoice</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($showReview): ?>
                                        <a href="<?= baseUrl('/review.php?booking_id=' . $b['id']) ?>" class="btn btn-ghost" style="padding: 4px 12px; font-size: 14px;">Leave Review</a>
                                        <button class="btn btn-ghost" onclick="reportDispute(<?= $b['id'] ?>)" style="padding: 4px 12px; font-size: 14px; color: var(--color-error);">Report Dispute</button>
                                    <?php endif; ?>
                                    
                                    <?php if ($showCancel): ?>
                                        <button class="btn btn-ghost" onclick="cancelBooking(<?= $b['id'] ?>)" style="padding: 4px 12px; font-size: 14px; color:var(--color-error);">Cancel</button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php
            $baseUrl = '?';
            if ($statusFilter) {
                $baseUrl .= 'status=' . urlencode($statusFilter) . '&';
            }
            $baseUrl .= 'page=';
            require __DIR__ . '/../../includes/partials/pagination.php';
            ?>
            <?php endif; ?>
                    </div> <!-- settings-card-body -->
                </div> <!-- settings-card -->
            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<!-- Dispute Modal -->
<div id="dispute-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: var(--color-surface); margin: 10% auto; padding: var(--space-md); border-radius: var(--radius-md); width: 90%; max-width: 500px; box-shadow: var(--shadow-lg);">
        <h3 class="headline-sm" style="margin-top: 0;">Report a Dispute</h3>
        <p style="color: var(--color-on-surface-variant); margin-bottom: var(--space-md);">Please provide details about the issue with your booking.</p>
        
        <form id="dispute-form">
            <input type="hidden" id="dispute-booking-id" name="booking_id">
            
            <div class="form-group">
                <label for="dispute-reason" class="form-label">Reason</label>
                <input type="text" id="dispute-reason" name="reason" class="input" placeholder="e.g. Damage, Late Return, No Show" required>
            </div>
            
            <div class="form-group">
                <label for="dispute-details" class="form-label">Details</label>
                <textarea id="dispute-details" name="details" class="input" rows="4" placeholder="Please describe the issue..." required style="resize: vertical;"></textarea>
            </div>
            
            <div class="form-group">
                <label for="dispute-contact-method" class="form-label">Preferred Contact Method</label>
                <select id="dispute-contact-method" name="preferred_contact_method" class="input">
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="dispute-contact-info" class="form-label">Contact Information</label>
                <input type="text" id="dispute-contact-info" name="contact_info" class="input" placeholder="Your email or phone number" required>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('dispute-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Dispute</button>
            </div>
        </form>
    </div>
</div>

<script>
    const csrfToken = "<?= csrfToken() ?>";
    
    document.addEventListener('DOMContentLoaded', () => {
    });
    
    async function payBooking(bookingId) {
        const formData = new FormData();
        formData.append('csrf', csrfToken);
        formData.append('booking_id', bookingId);
        
        try {
            const res = await fetch('<?= baseUrl('/api/bookings/pay.php') ?>', { method: 'POST', body: formData });
            if (res.ok) {
                const data = await res.json();
                if (data.stripe_url) {
                    window.location.href = data.stripe_url;
                }
            } else {
                const data = await res.json();
                alert(data.error || 'Failed to initialize payment.');
            }
        } catch (e) {
            alert('Network error');
        }
    }
    
    function reportDispute(bookingId) {
        document.getElementById('dispute-booking-id').value = bookingId;
        document.getElementById('dispute-modal').style.display = 'block';
    }
    
    document.getElementById('dispute-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('csrf', csrfToken);
        
        try {
            const res = await fetch('<?= baseUrl('/api/bookings/dispute.php') ?>', { method: 'POST', body: formData });
            if (res.ok) {
                alert('Dispute submitted successfully! An admin will review it.');
                window.location.reload();
            } else {
                const data = await res.json();
                alert(data.error || 'Failed to submit dispute');
            }
        } catch(err) {
            alert('Network error');
        }
    });
    
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
