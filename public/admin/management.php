<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$user = currentUser();

$error = '';
$success = '';
$activeTab = $_GET['tab'] ?? 'borrowers';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $error = 'Invalid session token.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        
        $db = getDb();
        
        try {
            if ($action === 'delete_user' && $id > 0) {
                // Determine which tab to stay on
                if (isset($_POST['tab'])) {
                    $activeTab = $_POST['tab'];
                }
                
                // Prevent self-deletion
                if ($id === $user['id']) {
                    $error = "You cannot delete your own admin account.";
                } else {
                    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->execute([$id]);
                    $success = "User successfully removed.";
                }
            } elseif ($action === 'delete_vehicle' && $id > 0) {
                $activeTab = 'vehicles';
                $stmt = $db->prepare('DELETE FROM vehicles WHERE id = ?');
                $stmt->execute([$id]);
                $success = "Vehicle successfully removed.";
            }
        } catch (PDOException $e) {
            // Error 1451 means foreign key constraint violation
            if ($e->getCode() == 23000 || strpos($e->getMessage(), '1451') !== false) {
                $error = "Cannot remove this record because it has active associations (like bookings, payments, or reviews).";
            } else {
                $error = "An error occurred while trying to delete the record.";
            }
        }
    }
}

require_once __DIR__ . '/../../includes/admin/management_queries.php';


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

$extraCss = ['profile', 'dashboard', 'management'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<div style="background-color: #f8fafc; min-height: calc(100vh - 80px); padding-bottom: 40px;">
    <div class="profile-page-hero">
        <div class="container">
            <h1>Admin Console</h1>
            <p>Manage the platform, verify users, and approve vehicles.</p>
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
                        <span class="role-badge admin">Admin</span>
                    </div>

                    <hr class="profile-stats-divider">
                    
                    <div class="profile-meta-list" style="margin-bottom: 24px;">
                        <a href="<?= baseUrl('/admin/vehicle_approvals.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">directions_car</span> Vehicle Approvals
                        </a>
                        <a href="<?= baseUrl('/admin/verification_queue.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">verified</span> Verification Queue
                        </a>
                        <a href="<?= baseUrl('/admin/driver_assignments.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">work</span> Driver Assignments
                        </a>
                        <a href="<?= baseUrl('/admin/inquiries.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">contact_support</span> Inquiries
                        </a>
                        <a href="<?= baseUrl('/admin/management.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: #e0e7ff; font-weight: 600;">
                            <span class="material-symbols-outlined">manage_accounts</span> System Management
                        </a>
                    </div>
                </div>
            </aside>
            
            <div class="profile-content-area">
                
                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;"><?= escapeHtml($success) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;"><?= escapeHtml($error) ?></div>
                <?php endif; ?>
                
                <div class="settings-card">
                    <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap: 16px;">
                            <span class="material-symbols-outlined" style="font-size: 32px; color: var(--color-primary);">manage_accounts</span>
                            <div>
                                <h2 style="margin: 0; font-size: 20px;">System Management</h2>
                                <p style="margin: 4px 0 0 0; color: var(--color-secondary); font-size: 14px;">Manage all system records and users.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabs -->
                    <div style="display: flex; border-bottom: 1px solid var(--color-outline); padding: 0 24px;">
                        <button type="button" class="mgmt-tab-btn" data-tab="borrowers" style="padding: 16px; background: none; border: none; font-size: 16px; border-bottom: 2px solid <?= $activeTab === 'borrowers' ? 'var(--color-primary)' : 'transparent' ?>; color: <?= $activeTab === 'borrowers' ? 'var(--color-primary)' : 'var(--color-secondary)' ?>; font-weight: <?= $activeTab === 'borrowers' ? 'bold' : 'normal' ?>; cursor: pointer;">
                            Borrowers
                        </button>
                        <button type="button" class="mgmt-tab-btn" data-tab="owners" style="padding: 16px; background: none; border: none; font-size: 16px; border-bottom: 2px solid <?= $activeTab === 'owners' ? 'var(--color-primary)' : 'transparent' ?>; color: <?= $activeTab === 'owners' ? 'var(--color-primary)' : 'var(--color-secondary)' ?>; font-weight: <?= $activeTab === 'owners' ? 'bold' : 'normal' ?>; cursor: pointer;">
                            Owners
                        </button>
                        <button type="button" class="mgmt-tab-btn" data-tab="drivers" style="padding: 16px; background: none; border: none; font-size: 16px; border-bottom: 2px solid <?= $activeTab === 'drivers' ? 'var(--color-primary)' : 'transparent' ?>; color: <?= $activeTab === 'drivers' ? 'var(--color-primary)' : 'var(--color-secondary)' ?>; font-weight: <?= $activeTab === 'drivers' ? 'bold' : 'normal' ?>; cursor: pointer;">
                            Drivers
                        </button>
                        <button type="button" class="mgmt-tab-btn" data-tab="vehicles" style="padding: 16px; background: none; border: none; font-size: 16px; border-bottom: 2px solid <?= $activeTab === 'vehicles' ? 'var(--color-primary)' : 'transparent' ?>; color: <?= $activeTab === 'vehicles' ? 'var(--color-primary)' : 'var(--color-secondary)' ?>; font-weight: <?= $activeTab === 'vehicles' ? 'bold' : 'normal' ?>; cursor: pointer;">
                            Vehicles
                        </button>
                        <button type="button" class="mgmt-tab-btn" data-tab="rejections" style="padding: 16px; background: none; border: none; font-size: 16px; border-bottom: 2px solid <?= $activeTab === 'rejections' ? 'var(--color-primary)' : 'transparent' ?>; color: <?= $activeTab === 'rejections' ? 'var(--color-primary)' : 'var(--color-secondary)' ?>; font-weight: <?= $activeTab === 'rejections' ? 'bold' : 'normal' ?>; cursor: pointer;">
                            Rejection Logs
                        </button>
                        <button type="button" class="mgmt-tab-btn" data-tab="bookings" style="padding: 16px; background: none; border: none; font-size: 16px; border-bottom: 2px solid <?= $activeTab === 'bookings' ? 'var(--color-primary)' : 'transparent' ?>; color: <?= $activeTab === 'bookings' ? 'var(--color-primary)' : 'var(--color-secondary)' ?>; font-weight: <?= $activeTab === 'bookings' ? 'bold' : 'normal' ?>; cursor: pointer;">
                            Bookings
                        </button>
                        <button type="button" class="mgmt-tab-btn" data-tab="commissions" style="padding: 16px; background: none; border: none; font-size: 16px; border-bottom: 2px solid <?= $activeTab === 'commissions' ? 'var(--color-primary)' : 'transparent' ?>; color: <?= $activeTab === 'commissions' ? 'var(--color-primary)' : 'var(--color-secondary)' ?>; font-weight: <?= $activeTab === 'commissions' ? 'bold' : 'normal' ?>; cursor: pointer;">
                            Commissions
                        </button>
                    </div>

                    <div style="padding: 16px 24px 0 24px; display: flex; justify-content: flex-end; align-items: center; gap: 16px;">
                        <div id="booking-filter-container" style="display: <?= $activeTab === 'bookings' ? 'block' : 'none' ?>;">
                            <form method="GET" action="">
                                <input type="hidden" name="tab" value="bookings">
                                <select name="booking_status" onchange="this.form.submit()" style="padding: 6px; border-radius: 4px; border: 1px solid var(--color-outline);">
                                    <option value="">All Statuses</option>
                                    <option value="pending_payment" <?= $bookingStatus === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
                                    <option value="pending_verification" <?= $bookingStatus === 'pending_verification' ? 'selected' : '' ?>>Pending Verification</option>
                                    <option value="confirmed" <?= $bookingStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="active" <?= $bookingStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="completed" <?= $bookingStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $bookingStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="rejected" <?= $bookingStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </form>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">print</span> Print Current Tab
                        </button>
                    </div>

                    <div class="settings-card-body" style="padding: 24px;">
                        
                        <!-- Tabs Content -->
                        <?php if ($activeTab === 'borrowers'): ?>
                            <?php require_once __DIR__ . '/../../includes/partials/admin/tab_borrowers.php'; ?>
                        <?php elseif ($activeTab === 'owners'): ?>
                            <?php require_once __DIR__ . '/../../includes/partials/admin/tab_owners.php'; ?>
                        <?php elseif ($activeTab === 'drivers'): ?>
                            <?php require_once __DIR__ . '/../../includes/partials/admin/tab_drivers.php'; ?>
                        <?php elseif ($activeTab === 'vehicles'): ?>
                            <?php require_once __DIR__ . '/../../includes/partials/admin/tab_vehicles.php'; ?>
                        <?php elseif ($activeTab === 'rejections'): ?>
                            <?php require_once __DIR__ . '/../../includes/partials/admin/tab_rejections.php'; ?>
                        <?php elseif ($activeTab === 'bookings'): ?>
                            <?php require_once __DIR__ . '/../../includes/partials/admin/tab_bookings.php'; ?>
                        <?php elseif ($activeTab === 'commissions'): ?>
                            <?php require_once __DIR__ . '/../../includes/partials/admin/tab_commissions.php'; ?>
                        <?php endif; ?>

                    </div> <!-- settings-card-body -->
                </div> <!-- settings-card -->
            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabBtns = document.querySelectorAll('.mgmt-tab-btn');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');
                window.location.href = '?tab=' + tabId;
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
