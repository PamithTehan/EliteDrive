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
                        <a href="<?= baseUrl('/admin/vehicle_approvals.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: #e0e7ff; font-weight: 600;">
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
                
                <div class="settings-card">
                    <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap: 16px;">
                            <span class="material-symbols-outlined">directions_car</span>
                            <div>
                                <h2>Vehicle Approvals</h2>
                                <p>Review and approve new vehicles added by owners.</p>
                            </div>
                        </div>
                    </div>
                    <div class="settings-card-body" style="padding: 24px;">
                <table class="table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Owner</th>
                            <th>Vehicle</th>
                            <th>Category</th>
                            <th>Rate</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="queue-body">
                        <!-- Loaded via JS -->
                        <tr><td colspan="5" style="text-align:center;">Loading...</td></tr>
                    </tbody>
                </table>
                <div id="pagination-container" style="display: flex; justify-content: center; margin-top: 24px; gap: 8px; align-items: center;"></div>
                    </div> <!-- settings-card-body -->
                </div> <!-- settings-card -->
            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<script>
    const csrfToken = "<?= csrfToken() ?>";
    
    const urlParams = new URLSearchParams(window.location.search);
    let currentPage = parseInt(urlParams.get('page')) || 1;

    async function loadQueue(page = currentPage) {
        try {
            const res = await fetch(`<?= baseUrl('/api/vehicles/pending.php') ?>?page=${page}`);
            const text = await res.text();
            let responseData;
            try {
                responseData = JSON.parse(text);
            } catch (err) {
                console.error("API Error Response:", text);
                document.querySelector('#queue-body').innerHTML = `<tr><td colspan="5" style="color:red;">Error loading data. Check console. Response: ${escapeHtml(text).substring(0, 100)}...</td></tr>`;
                return;
            }
            
            // Handle both old array format and new paginated object format just in case
            const rows = Array.isArray(responseData) ? responseData : (responseData.data || []);
            const pagination = responseData.pagination || { current_page: 1, total_pages: 1 };
            
            const tbody = document.querySelector('#queue-body');
            
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No vehicles pending review.</td></tr>';
                document.querySelector('#pagination-container').innerHTML = '';
                return;
            }
            
            tbody.innerHTML = rows.map(r => `
                <tr data-id="${r.id}">
                    <td>${escapeHtml(r.owner_name)}</td>
                    <td>${escapeHtml(r.make)} ${escapeHtml(r.model)}</td>
                    <td>${(r.category || '').split(',').map(c => c.trim()).filter(c => c).map(c => `<span class="badge badge-${c.toLowerCase()}" style="margin-right: 4px;">${escapeHtml(c)}</span>`).join('')}</td>
                    <td>LKR ${escapeHtml(r.daily_rate)}/day</td>
                    <td>
                        <button class="btn btn-primary btn-approve" data-id="${r.id}" style="padding: 4px 12px; font-size: 12px;">Approve</button>
                        <button class="btn btn-ghost btn-reject" data-id="${r.id}" style="padding: 4px 12px; font-size: 12px; color: var(--color-error);">Reject</button>
                    </td>
                </tr>`).join('');
                
            renderPagination(pagination.current_page, pagination.total_pages);
        } catch (e) {
            console.error('Failed to load queue', e);
        }
    }

    function renderPagination(current, total) {
        const container = document.querySelector('#pagination-container');
        if (total <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '';
        if (current > 1) {
            html += `<a href="?page=${current - 1}" class="btn btn-outline" style="padding: 6px 12px; font-size: 14px;">&laquo; Prev</a>`;
        } else {
            html += `<button class="btn btn-outline" style="padding: 6px 12px; font-size: 14px;" disabled>&laquo; Prev</button>`;
        }
        
        html += `<span style="font-size: 14px; font-weight: 500; color: var(--color-secondary);">Page ${current} of ${total}</span>`;
        
        if (current < total) {
            html += `<a href="?page=${current + 1}" class="btn btn-outline" style="padding: 6px 12px; font-size: 14px;">Next &raquo;</a>`;
        } else {
            html += `<button class="btn btn-outline" style="padding: 6px 12px; font-size: 14px;" disabled>Next &raquo;</button>`;
        }
        container.innerHTML = html;
    }

    document.addEventListener('click', async (e) => {
        if (e.target.matches('.btn-approve, .btn-reject')) {
            const btn = e.target;
            const decision = btn.matches('.btn-approve') ? 'approved' : 'rejected';
            btn.disabled = true;
            
            let reason = null;
            if (decision === 'rejected') {
                reason = prompt('Please provide a reason for rejection:');
                if (reason === null) {
                    btn.disabled = false;
                    return; // Cancelled
                }
            }
            
            try {
                const res = await fetch('<?= baseUrl('/api/vehicles/approve.php') ?>', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        vehicle_id: btn.dataset.id,
                        decision: decision,
                        reason: reason,
                        csrf: csrfToken
                    })
                });
                
                if (res.ok) {
                    loadQueue(); // refresh
                } else {
                    const data = await res.json();
                    alert(data.error || 'Failed to update status');
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Network error');
                btn.disabled = false;
            }
        }
    });

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    loadQueue();
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
