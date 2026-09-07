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
                        <a href="<?= baseUrl('/owner/income.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">payments</span> Income
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
                                <?php if ($v['status'] === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php elseif ($v['status'] === 'maintenance'): ?>
                                    <span class="badge badge-warning">Maintenance</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary"><?= escapeHtml(ucfirst($v['status'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer" style="padding: var(--space-md); border-top: 1px solid var(--color-outline); display:flex; justify-content:flex-end; gap:var(--space-sm);">
                            <a href="<?= baseUrl('/owner/vehicle_form.php?id=' . $v['id']) ?>" class="btn btn-outline" style="padding: var(--space-xs) var(--space-sm);">Edit</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
                        </div>
                </div>
            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
