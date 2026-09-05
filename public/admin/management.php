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

// Pagination Setup
$perPage = 10;
$pageBorrowers = isset($_GET['page_borrowers']) ? max(1, (int)$_GET['page_borrowers']) : 1;
$pageOwners = isset($_GET['page_owners']) ? max(1, (int)$_GET['page_owners']) : 1;
$pageDrivers = isset($_GET['page_drivers']) ? max(1, (int)$_GET['page_drivers']) : 1;
$pageVehicles = isset($_GET['page_vehicles']) ? max(1, (int)$_GET['page_vehicles']) : 1;

$offsetBorrowers = ($pageBorrowers - 1) * $perPage;
$offsetOwners = ($pageOwners - 1) * $perPage;
$offsetDrivers = ($pageDrivers - 1) * $perPage;
$offsetVehicles = ($pageVehicles - 1) * $perPage;

$db = getDb();

// Fetch Borrowers
$totalBorrowers = $db->query("SELECT COUNT(*) FROM users WHERE is_borrower = 1")->fetchColumn();
$stmtBorrowers = $db->prepare("SELECT id, full_name, email, contact_number, created_at FROM users WHERE is_borrower = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmtBorrowers->bindValue(1, $perPage, PDO::PARAM_INT);
$stmtBorrowers->bindValue(2, $offsetBorrowers, PDO::PARAM_INT);
$stmtBorrowers->execute();
$borrowers = $stmtBorrowers->fetchAll();
$totalPagesBorrowers = ceil($totalBorrowers / $perPage);

// Fetch Owners
$totalOwners = $db->query("SELECT COUNT(*) FROM users WHERE is_owner = 1")->fetchColumn();
$stmtOwners = $db->prepare("SELECT id, full_name, email, contact_number, created_at FROM users WHERE is_owner = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmtOwners->bindValue(1, $perPage, PDO::PARAM_INT);
$stmtOwners->bindValue(2, $offsetOwners, PDO::PARAM_INT);
$stmtOwners->execute();
$owners = $stmtOwners->fetchAll();
$totalPagesOwners = ceil($totalOwners / $perPage);

// Fetch Drivers
$totalDrivers = $db->query("SELECT COUNT(*) FROM users WHERE is_driver = 1")->fetchColumn();
$stmtDrivers = $db->prepare("
    SELECT u.id, u.full_name, u.email, u.contact_number, u.created_at, d.daily_fee, d.transmission_preference 
    FROM users u 
    JOIN drivers d ON u.id = d.user_id 
    WHERE u.is_driver = 1 
    ORDER BY u.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmtDrivers->bindValue(1, $perPage, PDO::PARAM_INT);
$stmtDrivers->bindValue(2, $offsetDrivers, PDO::PARAM_INT);
$stmtDrivers->execute();
$drivers = $stmtDrivers->fetchAll();
$totalPagesDrivers = ceil($totalDrivers / $perPage);

// Fetch Vehicles
$totalVehicles = $db->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$stmtVehicles = $db->prepare("
    SELECT v.id, v.make, v.model, v.yom, v.category, v.status, u.full_name as owner_name 
    FROM vehicles v 
    JOIN users u ON v.owner_id = u.id 
    ORDER BY v.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmtVehicles->bindValue(1, $perPage, PDO::PARAM_INT);
$stmtVehicles->bindValue(2, $offsetVehicles, PDO::PARAM_INT);
$stmtVehicles->execute();
$vehicles = $stmtVehicles->fetchAll();
$totalPagesVehicles = ceil($totalVehicles / $perPage);


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
                    </div>

                    <div class="settings-card-body" style="padding: 24px;">
                        
                        <!-- Borrowers Tab -->
                        <div id="tab-borrowers" class="mgmt-tab-content" style="display: <?= $activeTab === 'borrowers' ? 'block' : 'none' ?>;">
                            <table class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($borrowers as $b): ?>
                                        <tr>
                                            <td><?= escapeHtml($b['full_name']) ?></td>
                                            <td><?= escapeHtml($b['email']) ?></td>
                                            <td><?= date('M j, Y', strtotime($b['created_at'])) ?></td>
                                            <td>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove this borrower?');" style="display:inline;">
                                                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="tab" value="borrowers">
                                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger); border-color: var(--color-danger);">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($borrowers)): ?>
                                        <tr><td colspan="4" style="text-align:center;">No borrowers found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php if ($totalPagesBorrowers > 1): ?>
                            <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
                                <?php for ($i = 1; $i <= $totalPagesBorrowers; $i++): ?>
                                    <a href="?tab=borrowers&page_borrowers=<?= $i ?>" class="btn <?= $i === $pageBorrowers ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Owners Tab -->
                        <div id="tab-owners" class="mgmt-tab-content" style="display: <?= $activeTab === 'owners' ? 'block' : 'none' ?>;">
                            <table class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($owners as $o): ?>
                                        <tr>
                                            <td><?= escapeHtml($o['full_name']) ?></td>
                                            <td><?= escapeHtml($o['email']) ?></td>
                                            <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                                            <td>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove this owner? Their vehicles will also be removed if constraints allow.');" style="display:inline;">
                                                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="tab" value="owners">
                                                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger); border-color: var(--color-danger);">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($owners)): ?>
                                        <tr><td colspan="4" style="text-align:center;">No owners found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php if ($totalPagesOwners > 1): ?>
                            <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
                                <?php for ($i = 1; $i <= $totalPagesOwners; $i++): ?>
                                    <a href="?tab=owners&page_owners=<?= $i ?>" class="btn <?= $i === $pageOwners ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Drivers Tab -->
                        <div id="tab-drivers" class="mgmt-tab-content" style="display: <?= $activeTab === 'drivers' ? 'block' : 'none' ?>;">
                            <table class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Daily Fee</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drivers as $d): ?>
                                        <tr>
                                            <td>
                                                <div><?= escapeHtml($d['full_name']) ?></div>
                                                <div style="font-size:12px; color:var(--color-secondary);"><?= escapeHtml($d['transmission_preference']) ?></div>
                                            </td>
                                            <td><?= escapeHtml($d['email']) ?></td>
                                            <td>$<?= number_format($d['daily_fee'], 2) ?></td>
                                            <td>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove this driver?');" style="display:inline;">
                                                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="tab" value="drivers">
                                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger); border-color: var(--color-danger);">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($drivers)): ?>
                                        <tr><td colspan="4" style="text-align:center;">No drivers found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php if ($totalPagesDrivers > 1): ?>
                            <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
                                <?php for ($i = 1; $i <= $totalPagesDrivers; $i++): ?>
                                    <a href="?tab=drivers&page_drivers=<?= $i ?>" class="btn <?= $i === $pageDrivers ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Vehicles Tab -->
                        <div id="tab-vehicles" class="mgmt-tab-content" style="display: <?= $activeTab === 'vehicles' ? 'block' : 'none' ?>;">
                            <table class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Owner</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehicles as $v): ?>
                                        <tr>
                                            <td>
                                                <div><?= escapeHtml($v['make'] . ' ' . $v['model'] . ' (' . $v['yom'] . ')') ?></div>
                                                <div style="font-size:12px; color:var(--color-secondary);"><?= escapeHtml($v['category']) ?></div>
                                            </td>
                                            <td><?= escapeHtml($v['owner_name']) ?></td>
                                            <td>
                                                <span class="badge" style="background: var(--color-surface-variant); color: var(--color-primary); border-radius: 4px; padding: 4px 8px; font-size: 11px;">
                                                    <?= escapeHtml(ucfirst(str_replace('_', ' ', $v['status']))) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove this vehicle?');" style="display:inline;">
                                                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                                    <input type="hidden" name="action" value="delete_vehicle">
                                                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger); border-color: var(--color-danger);">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($vehicles)): ?>
                                        <tr><td colspan="4" style="text-align:center;">No vehicles found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php if ($totalPagesVehicles > 1): ?>
                            <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
                                <?php for ($i = 1; $i <= $totalPagesVehicles; $i++): ?>
                                    <a href="?tab=vehicles&page_vehicles=<?= $i ?>" class="btn <?= $i === $pageVehicles ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                    </div> <!-- settings-card-body -->
                </div> <!-- settings-card -->
            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabBtns = document.querySelectorAll('.mgmt-tab-btn');
        const tabContents = document.querySelectorAll('.mgmt-tab-content');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active styling from all buttons
                tabBtns.forEach(b => {
                    b.style.borderBottomColor = 'transparent';
                    b.style.color = 'var(--color-secondary)';
                    b.style.fontWeight = 'normal';
                });
                // Hide all contents
                tabContents.forEach(c => c.style.display = 'none');

                // Apply active styling
                btn.style.borderBottomColor = 'var(--color-primary)';
                btn.style.color = 'var(--color-primary)';
                btn.style.fontWeight = 'bold';

                // Show selected content
                const tabId = btn.getAttribute('data-tab');
                document.getElementById('tab-' + tabId).style.display = 'block';
                
                // Update URL without refreshing (optional but nice)
                history.replaceState(null, '', '?tab=' + tabId);
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
