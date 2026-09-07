<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('owner');
$user = currentUser();
$db = getDb();

// Fetch bookings for vehicles owned by this user
$stmt = $db->prepare('
    SELECT b.*, v.make, v.model, u.full_name as borrower_name 
    FROM bookings b 
    JOIN vehicles v ON b.vehicle_id = v.id 
    JOIN users u ON b.borrower_id = u.id 
    WHERE v.owner_id = ? 
    ORDER BY b.created_at DESC
');
$stmt->execute([$user['id']]);
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
                        <a href="<?= baseUrl('/owner/dashboard.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">directions_car</span> My Vehicles
                        </a>
                        <a href="<?= baseUrl('/owner/bookings.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: #e0e7ff; font-weight: 600;">
                            <span class="material-symbols-outlined">calendar_month</span> Bookings
                        </a>
                    </div>
                </div>
            </aside>
            
            <div class="profile-content-area">
                
                <div class="settings-card">
                    <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap: 16px;">
                            <span class="material-symbols-outlined">calendar_month</span>
                            <div>
                                <h2>Bookings for My Vehicles</h2>
                                <p>Track all reservations made for your listed fleet.</p>
                            </div>
                        </div>
                    </div>
                    <div class="settings-card-body" style="padding: 24px;">
        
        <?php if (empty($bookings)): ?>
            <div class="card" style="text-align:center; padding:var(--space-xl) var(--space-md);">
                <span class="material-symbols-outlined" style="font-size:48px; color:var(--color-secondary); margin-bottom:var(--space-sm);">event_busy</span>
                <h3 class="headline-sm">No Bookings Yet</h3>
                <p class="body-md" style="color:var(--color-secondary); margin-bottom:var(--space-md);">You don't have any bookings for your vehicles yet.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <table class="table" style="width: 100%; text-align: left;">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Borrower</th>
                                <th>Dates</th>
                                <th>Total Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><?= escapeHtml($b['make'] . ' ' . $b['model']) ?></td>
                                    <td><?= escapeHtml($b['borrower_name']) ?></td>
                                    <td>
                                        <div style="font-size: 14px;"><?= date('M j, Y H:i', strtotime($b['pickup_date'])) ?></div>
                                        <div style="font-size: 14px; color: var(--color-secondary);">to <?= date('M j, Y H:i', strtotime($b['return_date'])) ?></div>
                                    </td>
                                    <td>LKR <?= number_format($b['total_price'], 2) ?></td>
                                    <td>
                                        <?php if ($b['status'] === 'confirmed' || $b['status'] === 'active'): ?>
                                            <span class="badge" style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">Active</span>
                                        <?php elseif ($b['status'] === 'completed' || $b['status'] === 'reviewed'): ?>
                                            <span class="badge" style="background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">Completed</span>
                                        <?php elseif ($b['status'] === 'cancelled' || $b['status'] === 'rejected' || $b['status'] === 'disputed'): ?>
                                            <span class="badge" style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;"><?= escapeHtml($b['status']) ?></span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;"><?= escapeHtml($b['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
                    </div> <!-- settings-card-body -->
                </div> <!-- settings-card -->
            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
