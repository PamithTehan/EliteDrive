<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$user = currentUser();

$extraCss = ['dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';

$stmt = getDb()->query("SELECT * FROM inquiries ORDER BY created_at DESC");
$inquiries = $stmt->fetchAll();
?>

<div class="container grid grid-3" style="margin-top: var(--space-lg); margin-bottom: var(--space-lg);">
    <aside class="dashboard-sidebar" style="grid-column: 1 / 2;">
        <div class="card">
            <div class="card-body stack-sm">
                <h2 class="headline-md">Admin Console</h2>
                <p class="body-md">Manage platform</p>
                <hr style="border:0; border-top: 1px solid var(--color-outline); margin: var(--space-md) 0;">
                <ul class="stack-sm" style="list-style:none; padding:0;">
                    <li><a href="<?= baseUrl('/admin/vehicle_approvals.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">Vehicle Approvals</a></li>
                    <li><a href="<?= baseUrl('/admin/verification_queue.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">Verification Queue</a></li>
                    <li><a href="<?= baseUrl('/admin/driver_assignments.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">Driver Assignments</a></li>
                    <li><a href="<?= baseUrl('/admin/inquiries.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start; font-weight: bold;">Inquiries</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="dashboard-content" style="grid-column: 2 / 4;">
        <h1 class="headline-lg">Inquiries</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">Response sent successfully!</div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <table class="table">
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
            </div>
        </div>
    </main>
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
