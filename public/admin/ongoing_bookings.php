<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$user = currentUser();
$db = getDb();

// Handle Confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm' && isset($_POST['booking_id'])) {
    if (csrfCheck($_POST['csrf'] ?? '')) {
        $bookingId = (int)$_POST['booking_id'];
        $db->exec("UPDATE bookings SET status = 'confirmed' WHERE id = $bookingId AND status = 'pending_payment'");
        $success = "Booking #$bookingId confirmed successfully.";
    } else {
        $error = "Invalid session token.";
    }
}

// Handle Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject' && isset($_POST['booking_id'])) {
    if (csrfCheck($_POST['csrf'] ?? '')) {
        $bookingId = (int)$_POST['booking_id'];
        $reason = trim($_POST['reason'] ?? 'Rejected by Admin');
        
        $db->beginTransaction();
        try {
            $db->exec("UPDATE bookings SET status = 'rejected' WHERE id = $bookingId");
            $stmt = $db->prepare("INSERT INTO rejection_logs (entity_type, entity_id, reason, rejected_by) VALUES ('booking', ?, ?, ?)");
            $stmt->execute([$bookingId, $reason, $user['id']]);
            $db->commit();
            $success = "Booking #$bookingId rejected successfully.";
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Failed to reject booking: " . $e->getMessage();
        }
    } else {
        $error = "Invalid session token.";
    }
}

// Pagination setup
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$totalOngoingStmt = $db->query('SELECT COUNT(*) FROM bookings WHERE status NOT IN ("completed", "cancelled", "rejected")');
$totalOngoing = $totalOngoingStmt->fetchColumn();

$stmt = $db->prepare('
    SELECT b.id, b.created_at, b.pickup_date, b.return_date, b.total_price, b.status,
           u.full_name as borrower_name, v.make, v.model,
           o.full_name as owner_name
    FROM bookings b
    JOIN users u ON b.borrower_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users o ON v.owner_id = o.id
    WHERE b.status NOT IN ("completed", "cancelled", "rejected")
    ORDER BY b.created_at DESC 
    LIMIT ? OFFSET ?
');
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll();
$totalPages = ceil($totalOngoing / $perPage);

$extraCss = ['profile', 'dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<div style="background-color: #f8fafc; min-height: calc(100vh - 80px); padding-bottom: 40px;">
    <div class="profile-page-hero">
        <div class="container">
            <h1>Ongoing Bookings</h1>
            <p>Manage active and pending bookings on the platform.</p>
        </div>
    </div>

    <div class="container">
        <div class="profile-layout">
            <aside>
                <div class="profile-user-card" style="padding: 24px 16px;">
                    <div class="profile-meta-list">
                        <a href="<?= baseUrl('/admin/vehicle_approvals.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">directions_car</span> Vehicle Approvals
                        </a>
                        <a href="<?= baseUrl('/admin/verification_queue.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">verified</span> Verification Queue
                        </a>
                        <a href="<?= baseUrl('/admin/driver_assignments.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">work</span> Driver Assignments
                        </a>
                        <a href="<?= baseUrl('/admin/ongoing_bookings.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: #e0e7ff; font-weight: 600;">
                            <span class="material-symbols-outlined">event</span> Ongoing Bookings
                        </a>
                        <a href="<?= baseUrl('/admin/inquiries.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">contact_support</span> Inquiries
                        </a>
                        <a href="<?= baseUrl('/admin/income.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">payments</span> Income
                        </a>
                        <a href="<?= baseUrl('/admin/management.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">manage_accounts</span> System Management
                        </a>
                    </div>
                </div>
            </aside>
            
            <div class="profile-content-area">
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;"><?= escapeHtml($success) ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;"><?= escapeHtml($error) ?></div>
                <?php endif; ?>

                <div class="settings-card">
                    <div class="settings-card-header">
                        <div style="display:flex; align-items:center; gap: 16px;">
                            <span class="material-symbols-outlined">event</span>
                            <div>
                                <h2>Ongoing Bookings</h2>
                                <p>Review, confirm, or reject active rentals.</p>
                            </div>
                        </div>
                    </div>
                    <div class="settings-card-body" style="padding: 24px;">
                        <div class="table-responsive">
                            <table class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Vehicle & Owner</th>
                                        <th>Borrower</th>
                                        <th>Dates</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $b): ?>
                                        <tr>
                                            <td>#<?= $b['id'] ?></td>
                                            <td>
                                                <div style="font-weight: 500;"><?= escapeHtml($b['make'] . ' ' . $b['model']) ?></div>
                                                <div style="font-size:12px; color:var(--color-secondary);">Owner: <?= escapeHtml($b['owner_name']) ?></div>
                                            </td>
                                            <td><?= escapeHtml($b['borrower_name']) ?></td>
                                            <td>
                                                <div style="font-size: 13px;"><?= date('M j', strtotime($b['pickup_date'])) ?> &rarr; <?= date('M j', strtotime($b['return_date'])) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: var(--color-surface-variant); color: var(--color-primary); border-radius: 4px; padding: 4px 8px; font-size: 11px;">
                                                    <?= escapeHtml(ucfirst(str_replace('_', ' ', $b['status']))) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display:flex; gap:8px;">
                                                    <?php if ($b['status'] === 'pending_payment'): ?>
                                                        <form method="POST" action="" style="display:inline;">
                                                            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                                            <input type="hidden" name="action" value="confirm">
                                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Confirm payment received for this booking?');">Confirm</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" class="btn btn-outline btn-sm reject-btn" data-id="<?= $b['id'] ?>" style="color: var(--color-danger); border-color: var(--color-danger);">Reject</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($bookings)): ?>
                                        <tr><td colspan="6" style="text-align:center;">No ongoing bookings found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php
                        $baseUrl = '?page=';
                        $currentPage = $page;
                        require __DIR__ . '/../../includes/partials/pagination.php';
                        ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="reject-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; padding:24px; border-radius:8px; width:100%; max-width:400px;">
        <h3 style="margin-bottom:16px;">Reject Booking</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="booking_id" id="reject_booking_id" value="">
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Reason for Rejection</label>
                <textarea name="reason" class="input" rows="3" required placeholder="E.g., Vehicle unavailable, driver mismatch..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('reject-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:var(--color-danger); border-color:var(--color-danger);">Reject Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.reject-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        document.getElementById('reject_booking_id').value = e.target.getAttribute('data-id');
        document.getElementById('reject-modal').style.display = 'flex';
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
