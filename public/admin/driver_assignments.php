<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$user = currentUser();

$extraCss = ['dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';
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
                    <li><a href="<?= baseUrl('/admin/driver_assignments.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start; font-weight: bold;">Driver Assignments</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="dashboard-content" style="grid-column: 2 / 4;">
        <h1 class="headline-lg">Driver Assignments</h1>
        <div class="card">
            <div class="card-body">
                <table class="table">
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
            </div>
        </div>
    </main>
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
    
    async function loadQueue() {
        try {
            const res = await fetch('<?= baseUrl('/api/bookings/unassigned.php') ?>');
            const rows = await res.json();
            
            const tbody = document.querySelector('#queue-body');
            
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No bookings need driver assignment.</td></tr>';
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
                        const fee = d.daily_fee ? `$${parseFloat(d.daily_fee).toFixed(2)}/day` : '';
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
                    <td>${escapeHtml(r.pickup_date)}<br><small style="color:var(--color-secondary);">${escapeHtml(r.pickup_location)}</small></td>
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
        } catch (e) {
            console.error('Failed to load queue', e);
            document.querySelector('#queue-body').innerHTML = `<tr><td colspan="5" style="color:red;">Error loading data.</td></tr>`;
        }
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
