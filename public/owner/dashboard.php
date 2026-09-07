<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('owner');
$user = currentUser();
$db = getDb();

// Fetch vehicles owned by this user
$stmt = $db->prepare('
    SELECT v.*, p.photo_path 
    FROM vehicles v 
    LEFT JOIN vehicle_photos p ON v.id = p.vehicle_id AND p.is_primary = 1 
    WHERE v.owner_id = ? 
    ORDER BY v.created_at DESC
');
$stmt->execute([$user['id']]);
$vehicles = $stmt->fetchAll();

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
            <h1>Owner Dashboard</h1>
            <p>Manage your listed vehicles and review bookings.</p>
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
                        <span class="role-badge owner">Owner</span>
                    </div>

                    <hr class="profile-stats-divider">
                    
                    <div class="profile-meta-list" style="margin-bottom: 24px;">
                        <a href="<?= baseUrl('/owner/dashboard.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: #e0e7ff; font-weight: 600;">
                            <span class="material-symbols-outlined">directions_car</span> My Vehicles
                        </a>
                        <a href="<?= baseUrl('/owner/bookings.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">calendar_month</span> Bookings
                        </a>
                    </div>
                </div>
            </aside>
            
            <div class="profile-content-area">
                
                <div class="settings-card">
                    <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap: 16px;">
                            <span class="material-symbols-outlined">directions_car</span>
                            <div>
                                <h2>My Vehicles</h2>
                                <p>Manage your fleet and add new vehicles.</p>
                            </div>
                        </div>
                        <a href="<?= baseUrl('/owner/vehicle_form.php') ?>" class="btn btn-primary">Add Vehicle</a>
                    </div>
                    <div class="settings-card-body" style="padding: 24px;">
        
        <?php if (empty($vehicles)): ?>
            <div class="card">
                <div class="card-body" style="text-align:center; padding: var(--space-lg);">
                    <p class="body-lg" style="color:var(--color-secondary);">You haven't listed any vehicles yet.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($vehicles as $v): ?>
                    <div class="card">
                        <?php if (!empty($v['photo_path'])): ?>
                            <?php $imgUrl = strpos($v['photo_path'], 'http') === 0 ? $v['photo_path'] : baseUrl($v['photo_path']); ?>
                            <img src="<?= escapeHtml($imgUrl) ?>" alt="Vehicle Photo" style="width: 100%; height: 200px; object-fit: cover; border-top-left-radius: var(--radius-md); border-top-right-radius: var(--radius-md);">
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="headline-md"><?= escapeHtml($v['make'] . ' ' . $v['model']) ?></h3>
                            <div class="card-specs">
                                <span><?= escapeHtml(str_replace(',', ', ', $v['category'])) ?></span>
                                <span>LKR <?= escapeHtml($v['daily_rate']) ?>/day</span>
                            </div>
                            <div style="margin-top: var(--space-sm);">
                                <?php
                                $badgeClass = match($v['status']) {
                                    'approved' => 'badge-status-verified',
                                    'pending_review' => 'badge-status-pending',
                                    'rejected' => 'badge-status-rejected',
                                    default => 'badge-status-pending'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= escapeHtml($v['status']) ?></span>
                                <a href="<?= baseUrl('/owner/vehicle_form.php?id=' . $v['id']) ?>" class="btn btn-ghost" style="padding: 4px 12px; font-size: 13px; margin-left: 8px;">Edit</a>
                            </div>
                            <?php if ($v['status'] === 'rejected' && !empty($v['rejection_reason'])): ?>
                                <div class="alert alert-error" style="margin-top: var(--space-sm); font-size: 13px; padding: 8px 12px;">
                                    <strong>Rejection Reason:</strong> <?= escapeHtml($v['rejection_reason']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
                    </div> <!-- settings-card-body -->
                </div> <!-- settings-card -->
            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
