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
                        <a href="<?= baseUrl('/admin/vehicle_approvals.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">directions_car</span> Vehicle Approvals
                        </a>
                        <a href="<?= baseUrl('/admin/verification_queue.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">verified</span> Verification Queue
                        </a>
                        <a href="<?= baseUrl('/admin/driver_assignments.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: #e0e7ff; font-weight: 600;">
                            <span class="material-symbols-outlined">work</span> Driver Assignments
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
                            <span class="material-symbols-outlined">work</span>
                            <div>
                                <h2>Driver Assignments</h2>
                                <p>Assign available drivers to pending bookings.</p>
                            </div>
                        </div>
                    </div>
                    <div class="settings-card-body" style="padding: 24px;">
                <table class="table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Renter</th>
                            <th>Vehicle</th>
                            <th>Date / Location</th>
                            <th>Assign Driver</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="queue-body">
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
    let driversList = [];
    
    async function loadDrivers() {
        try {
            const res = await fetch('<?= baseUrl('/api/drivers/available.php') ?>');
            driversList = await res.json();
        } catch(e) {
            console.error('Failed to load drivers');
        }
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    let currentPage = parseInt(urlParams.get('page')) || 1;

    async function loadQueue(page = currentPage) {
        try {
            const res = await fetch(`<?= baseUrl('/api/bookings/unassigned.php') ?>?page=${page}`);
            const responseData = await res.json();
            
            const rows = Array.isArray(responseData) ? responseData : (responseData.data || []);
            const pagination = responseData.pagination || { current_page: 1, total_pages: 1 };
            
            const tbody = document.querySelector('#queue-body');
            
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No bookings need driver assignment.</td></tr>';
                document.querySelector('#pagination-container').innerHTML = '';
                return;
            }
            
            tbody.innerHTML = rows.map(r => {
                const eligibleDrivers = driversList.filter(d => 
                    !d.transmission_preference || d.transmission_preference === 'Both' || d.transmission_preference.toLowerCase() === (r.transmission || '').toLowerCase()
                );
                
                let options = '<option value="">Select a driver...</option>';
                if (eligibleDrivers.length === 0) {
                    options = '<option value="">No compatible drivers available</option>';
                } else {
                    options += eligibleDrivers.map(d => {
                        const fee = d.daily_fee ? `LKR ${parseFloat(d.daily_fee).toFixed(2)}/day` : '';
                        const trans = d.transmission_preference ? ` (${d.transmission_preference})` : '';
                        return `<option value="${d.id}">${escapeHtml(d.full_name)}${fee ? ' - ' + fee : ''}${trans}</option>`;
                    }).join('');
                }

                return `
                <tr data-id="${r.id}">
                    <td>${escapeHtml(r.renter_name)}</td>
                    <td>
                        <strong>${escapeHtml(r.make)} ${escapeHtml(r.model)}</strong>
                        <div style="margin-top: 2px;">
                            <span class="badge" style="font-size:11px; background:var(--color-surface); border:1px solid var(--color-outline);">${escapeHtml(r.transmission || 'Auto')}</span>
                        </div>
                    </td>
                    <td>
                        <div><strong>Pick-up:</strong> ${escapeHtml(r.pickup_date)}<br><small style="color:var(--color-secondary);">${escapeHtml(r.pickup_location)}</small></div>
                        <div style="margin-top: 4px;"><strong>Return:</strong> ${escapeHtml(r.return_date)}<br><small style="color:var(--color-secondary);">${escapeHtml(r.return_location)}</small></div>
                    </td>
                    <td>
                        <select class="input driver-select" data-id="${r.id}" style="padding: 4px; font-size: 14px;">
                            ${options}
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-primary btn-assign" data-id="${r.id}" style="padding: 4px 12px; font-size: 12px;">Assign</button>
                    </td>
                </tr>`;
            }).join('');
            
            renderPagination(pagination.current_page, pagination.total_pages);
        } catch(e) {
            console.error('Failed to load queue', e);
            document.querySelector('#queue-body').innerHTML = `<tr><td colspan="5" style="color:red;">Error loading data.</td></tr>`;
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
        if (e.target.matches('.btn-assign')) {
            const btn = e.target;
            const bookingId = btn.dataset.id;
            
            // Find the select element in the same row
            const select = document.querySelector(`select.driver-select[data-id="${bookingId}"]`);
            const driverId = select.value;
            
            if (!driverId) {
                alert('Please select a driver first.');
                return;
            }
            
            btn.disabled = true;
            
            try {
                const res = await fetch('<?= baseUrl('/api/bookings/assign_driver.php') ?>', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        booking_id: bookingId,
                        driver_id: driverId,
                        csrf: csrfToken
                    })
                });
                
                if (res.ok) {
                    loadQueue();
                } else {
                    const data = await res.json();
                    alert(data.error || 'Failed to assign driver');
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

    // Load drivers then load queue
    loadDrivers().then(() => loadQueue());
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
