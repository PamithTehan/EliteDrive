<?php
/**
 * File: sidebar.php
 * Purpose: Reusable sidebar navigation for admin pages
 */

// Ensure $activeAdminNav is set
$activeNav = $activeAdminNav ?? '';

// Calculate initials for the avatar if $user is available
$initials = 'U';
if (isset($user['full_name'])) {
    $initials = '';
    $parts = explode(' ', trim($user['full_name']));
    foreach ($parts as $p) {
        if (!empty($p)) {
            $initials .= strtoupper($p[0]);
        }
        if (strlen($initials) >= 2) break;
    }
    if (!$initials) $initials = 'U';
}

// Helper to determine link styling
function adminNavStyle($navId, $currentNav) {
    $base = "display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; ";
    if ($navId === $currentNav) {
        return $base . "background-color: #e0e7ff; font-weight: 600;";
    }
    return $base . "background-color: transparent; font-weight: 400;";
}
?>

<aside>
    <div class="profile-user-card" style="padding: 24px 16px;">
        <div class="profile-avatar-circle" style="width: 80px; height: 80px; border-radius: 50%; background-color: var(--color-primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; margin: 0 auto 16px auto;">
            <?= escapeHtml($initials) ?>
        </div>
        <h2 style="text-align: center; font-size: 20px; font-weight: 700; margin-bottom: 4px;"><?= escapeHtml($user['full_name'] ?? 'Admin User') ?></h2>
        <p style="text-align: center; color: var(--color-secondary); font-size: 14px; margin-bottom: 12px;"><?= escapeHtml($user['email'] ?? 'admin@elitedrive.com') ?></p>
        <div style="text-align: center; margin-bottom: 24px;">
            <span class="badge" style="background:#fce7f3; color:#9d174d; padding: 4px 12px; font-size: 12px;">ADMIN</span>
        </div>
        <hr style="border:0; border-top:1px solid #e2e8f0; margin-bottom:24px; width:100%;">

        <div class="profile-meta-list">
            <a href="<?= baseUrl('/admin/vehicle_approvals.php') ?>" style="<?= adminNavStyle('vehicle_approvals', $activeNav) ?>">
                <span class="material-symbols-outlined">directions_car</span> Vehicle Approvals
            </a>
            <a href="<?= baseUrl('/admin/verification_queue.php') ?>" style="<?= adminNavStyle('verification_queue', $activeNav) ?>">
                <span class="material-symbols-outlined">verified</span> Verification Queue
            </a>
            <a href="<?= baseUrl('/admin/driver_assignments.php') ?>" style="<?= adminNavStyle('driver_assignments', $activeNav) ?>">
                <span class="material-symbols-outlined">work</span> Driver Assignments
            </a>
            <a href="<?= baseUrl('/admin/ongoing_bookings.php') ?>" style="<?= adminNavStyle('ongoing_bookings', $activeNav) ?>">
                <span class="material-symbols-outlined">event</span> Ongoing Bookings
            </a>
            <a href="<?= baseUrl('/admin/inquiries.php') ?>" style="<?= adminNavStyle('inquiries', $activeNav) ?>">
                <span class="material-symbols-outlined">contact_support</span> Inquiries
            </a>
            <a href="<?= baseUrl('/admin/disputes.php') ?>" style="<?= adminNavStyle('disputes', $activeNav) ?>">
                <span class="material-symbols-outlined">gavel</span> Disputes
            </a>
            <a href="<?= baseUrl('/admin/income.php') ?>" style="<?= adminNavStyle('income', $activeNav) ?>">
                <span class="material-symbols-outlined">payments</span> Income
            </a>
            <a href="<?= baseUrl('/admin/management.php') ?>" style="<?= adminNavStyle('management', $activeNav) ?>">
                <span class="material-symbols-outlined">manage_accounts</span> System Management
            </a>
        </div>
    </div>
</aside>
