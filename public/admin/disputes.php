<?php
/**
 * File: disputes.php
 * Purpose: Review and resolve user disputes
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDb();
$user = currentUser();

// Handle resolution logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_id'])) {
    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $errorMsg = 'Invalid session token. Please refresh.';
    } else {
        $resolveId = (int)$_POST['resolve_id'];
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT booking_id FROM disputes WHERE id = ? AND status = "open"');
            $stmt->execute([$resolveId]);
            $dispute = $stmt->fetch();
            
            if ($dispute) {
                // Update dispute status
                $updDispute = $db->prepare('UPDATE disputes SET status = "resolved" WHERE id = ?');
                $updDispute->execute([$resolveId]);
                
                // Update booking status to resolved
                $updBooking = $db->prepare('UPDATE bookings SET status = "resolved" WHERE id = ? AND status = "disputed"');
                $updBooking->execute([$dispute['booking_id']]);
                
                $db->commit();
                $successMsg = 'Dispute resolved successfully.';
            } else {
                $db->rollBack();
                $errorMsg = 'Dispute not found or already resolved.';
            }
        } catch (Exception $e) {
            $db->rollBack();
            $errorMsg = 'Failed to resolve dispute.';
        }
    }
}

$statusFilter = $_GET['status'] ?? 'open';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch disputes
$countStmt = $db->prepare('SELECT COUNT(*) FROM disputes WHERE status = ?');
$countStmt->execute([$statusFilter]);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$stmt = $db->prepare('
    SELECT d.*, b.vehicle_id, u.full_name as user_name
    FROM disputes d
    JOIN bookings b ON d.booking_id = b.id
    JOIN users u ON d.user_id = u.id
    WHERE d.status = ?
    ORDER BY d.created_at DESC
    LIMIT ? OFFSET ?
');
$stmt->bindValue(1, $statusFilter);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$disputes = $stmt->fetchAll();

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
        <?php if (isset($successMsg)): ?>
            <div class="alert-profile success">
                <span class="material-symbols-outlined" style="font-size:20px;">check_circle</span>
                <span><?= escapeHtml($successMsg) ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($errorMsg)): ?>
            <div class="alert-profile error">
                <span class="material-symbols-outlined" style="font-size:20px;">error</span>
                <span><?= escapeHtml($errorMsg) ?></span>
            </div>
        <?php endif; ?>
        
        <div class="profile-layout">
            <?php 
            $activeAdminNav = 'disputes';
            require __DIR__ . '/../../includes/partials/admin/sidebar.php'; 
            ?>
            
            <div class="profile-content-area">
                
                <div class="settings-card">
                    <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap: 16px;">
                            <span class="material-symbols-outlined">gavel</span>
                            <div>
                                <h2>Disputes</h2>
                                <p>Manage and resolve booking disputes reported by users.</p>
                            </div>
                        </div>
                    </div>
                    <div class="settings-card-body" style="padding: 24px;">
                        
                        <div style="margin-bottom: 24px; display:flex; gap: 8px;">
                            <a href="?status=open" class="btn <?= $statusFilter === 'open' ? 'btn-primary' : 'btn-ghost' ?>">Open</a>
                            <a href="?status=resolved" class="btn <?= $statusFilter === 'resolved' ? 'btn-primary' : 'btn-ghost' ?>">Resolved</a>
                        </div>
                        
                        <?php if (count($disputes) === 0): ?>
                            <p style="text-align:center; padding: 40px; color: var(--color-on-surface-variant);">No disputes found in this category.</p>
                        <?php else: ?>
                            <div style="display:flex; flex-direction: column; gap: 16px;">
                                <?php foreach ($disputes as $d): ?>
                                    <div style="border: 1px solid var(--color-outline); border-radius: var(--radius-md); padding: var(--space-md);">
                                        <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom: 12px;">
                                            <div>
                                                <h3 style="margin:0 0 4px 0; font-size:18px;"><?= escapeHtml($d['reason']) ?></h3>
                                                <div style="font-size:14px; color:var(--color-on-surface-variant);">
                                                    Reported by: <strong><?= escapeHtml($d['user_name']) ?></strong> | 
                                                    Contact: <?= escapeHtml($d['preferred_contact_method']) ?> (<?= escapeHtml($d['contact_info']) ?>)
                                                </div>
                                            </div>
                                            <div style="text-align:right;">
                                                <div style="font-size:12px; color:var(--color-on-surface-variant); margin-bottom:4px;">Booking ID: #<?= $d['booking_id'] ?></div>
                                                <span class="badge <?= $d['status'] === 'open' ? 'badge-status-rejected' : 'badge-status-verified' ?>"><?= escapeHtml(ucfirst($d['status'])) ?></span>
                                            </div>
                                        </div>
                                        
                                        <div style="background-color: var(--color-background); padding: 12px; border-radius: var(--radius-sm); font-size: 14px; margin-bottom: 16px;">
                                            <?= nl2br(escapeHtml($d['details'])) ?>
                                        </div>
                                        
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <div style="font-size: 12px; color: var(--color-on-surface-variant);">
                                                Reported at: <?= date('M d, Y h:i A', strtotime($d['created_at'])) ?>
                                            </div>
                                            <?php if ($d['status'] === 'open'): ?>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to resolve this dispute? This will update the booking status to Resolved.');">
                                                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                                    <input type="hidden" name="resolve_id" value="<?= $d['id'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm">Mark as Resolved</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php
                            $baseUrl = '?status=' . urlencode($statusFilter) . '&page=';
                            require __DIR__ . '/../../includes/partials/pagination.php';
                            ?>
                        <?php endif; ?>
                    </div> <!-- settings-card-body -->
                </div> <!-- settings-card -->
            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
