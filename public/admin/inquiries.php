<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$user = currentUser();

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

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$countStmt = getDb()->query("SELECT COUNT(*) FROM inquiries");
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$stmt = getDb()->prepare("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$inquiries = $stmt->fetchAll();
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
                        <a href="<?= baseUrl('/admin/ongoing_bookings.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">event</span> Ongoing Bookings
                        </a>
                        <a href="<?= baseUrl('/admin/inquiries.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: #e0e7ff; font-weight: 600;">
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
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;">Response sent successfully!</div>
                <?php endif; ?>
                
                <div class="settings-card">
                    <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap: 16px;">
                            <span class="material-symbols-outlined">contact_support</span>
                            <div>
                                <h2>Inquiries</h2>
                                <p>Review and respond to messages from users.</p>
                            </div>
                        </div>
                    </div>
                    <div class="settings-card-body" style="padding: 24px;">
                <table class="table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inquiry): ?>
                            <tr>
                                <td>
                                    <div><?= escapeHtml($inquiry['full_name']) ?></div>
                                    <div style="font-size: 12px; color: var(--color-secondary);"><?= escapeHtml($inquiry['email']) ?></div>
                                </td>
                                <td><?= escapeHtml(ucfirst($inquiry['subject'])) ?></td>
                                <td><?= date('M j, Y', strtotime($inquiry['created_at'])) ?></td>
                                <td>
                                    <?php if ($inquiry['status'] === 'pending'): ?>
                                        <span class="badge" style="background: #fef3c7; color: #92400e; border-radius: 4px; padding: 4px 8px; font-size: 11px;">Pending</span>
                                    <?php elseif ($inquiry['status'] === 'responded'): ?>
                                        <span class="badge" style="background: #e0f2fe; color: #0369a1; border-radius: 4px; padding: 4px 8px; font-size: 11px;">Responded</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #d1d5db; color: #374151; border-radius: 4px; padding: 4px 8px; font-size: 11px;">Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-outline btn-sm" onclick='openModal(<?= json_encode($inquiry, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($inquiries)): ?>
                            <tr><td colspan="5" style="text-align:center;">No inquiries found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php
                $baseUrl = '?page=';
                require __DIR__ . '/../../includes/partials/pagination.php';
                ?>
                
                    </div> <!-- settings-card-body -->
                </div> <!-- settings-card -->
            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<!-- Modal -->
<div id="inquiryModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; padding: 24px; border-radius: var(--radius-lg); width: 500px; max-width: 90%;">
        <div style="display:flex; justify-content:space-between; margin-bottom: 16px;">
            <h3 class="headline-sm">Inquiry Details</h3>
            <button onclick="closeModal()" style="border:none; background:transparent; cursor:pointer;"><span class="material-symbols-outlined">close</span></button>
        </div>
        
        <div style="margin-bottom: 16px;">
            <strong>From:</strong> <span id="modalName"></span> (<span id="modalEmail"></span>)
        </div>
        <div style="margin-bottom: 16px;">
            <strong>Subject:</strong> <span id="modalSubject"></span>
        </div>
        <div style="margin-bottom: 16px;">
            <strong>Message:</strong>
            <p id="modalMessage" style="background: #f8fafc; padding: 12px; border-radius: var(--radius-sm); margin-top: 8px;"></p>
        </div>
        
        <div id="responseSection" style="display:none; margin-bottom: 16px;">
            <strong>Admin Response:</strong>
            <p id="modalResponse" style="background: #e0f2fe; padding: 12px; border-radius: var(--radius-sm); margin-top: 8px;"></p>
        </div>

        <form id="replyForm" action="<?= baseUrl('/api/admin/respond_inquiry.php') ?>" method="POST" style="margin-top: 24px;">
            <input type="hidden" name="inquiry_id" id="modalInquiryId">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label class="label-sm">Reply (via Email)</label>
                <textarea name="response" rows="4" style="width:100%; padding: 8px; border: 1px solid var(--color-outline); border-radius: var(--radius-sm);" required></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top: 16px;">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Reply</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(inquiry) {
    document.getElementById('modalName').textContent = inquiry.full_name;
    document.getElementById('modalEmail').textContent = inquiry.email;
    document.getElementById('modalSubject').textContent = inquiry.subject;
    document.getElementById('modalMessage').textContent = inquiry.message;
    document.getElementById('modalInquiryId').value = inquiry.id;
    
    if (inquiry.admin_response) {
        document.getElementById('responseSection').style.display = 'block';
        document.getElementById('modalResponse').textContent = inquiry.admin_response;
        document.getElementById('replyForm').style.display = 'none';
    } else {
        document.getElementById('responseSection').style.display = 'none';
        document.getElementById('replyForm').style.display = 'block';
    }
    
    document.getElementById('inquiryModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('inquiryModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
