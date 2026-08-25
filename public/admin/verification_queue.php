<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$user = currentUser();

$extraCss = ['dashboard'];
require __DIR__ . '/../../includes/partials/head.php';
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
                    <li><a href="<?= baseUrl('/admin/verification_queue.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start; font-weight: bold;">Verification Queue</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="dashboard-content" style="grid-column: 2 / 4;">
        <h1 class="headline-lg">Verification Queue</h1>
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>License No.</th>
                            <th>Expiry</th>
                            <th>Document</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="queue-body">
                        <!-- Loaded via JS -->
                        <tr><td colspan="5" style="text-align:center;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    const csrfToken = "<?= csrfToken() ?>";
    
    async function loadQueue() {
        try {
            const res = await fetch('<?= baseUrl('/api/licenses/pending.php') ?>');
            const rows = await res.json();
            const tbody = document.querySelector('#queue-body');
            
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No licenses pending review.</td></tr>';
                return;
            }
            
            tbody.innerHTML = rows.map(r => `
                <tr data-id="${r.id}">
                    <td>${escapeHtml(r.full_name)}</td>
                    <td>${escapeHtml(r.license_number)}</td>
                    <td>${escapeHtml(r.expiry_date)}</td>
                    <td><a href="<?= baseUrl('/document.php') ?>?type=license&id=${r.id}" target="_blank" style="color:var(--color-accent); text-decoration:underline;">View Doc</a></td>
                    <td>
                        <button class="btn btn-primary btn-approve" data-id="${r.id}" style="padding: 4px 12px; font-size: 12px;">Approve</button>
                        <button class="btn btn-ghost btn-reject" data-id="${r.id}" style="padding: 4px 12px; font-size: 12px; color: var(--color-error);">Reject</button>
                    </td>
                </tr>`).join('');
        } catch (e) {
            console.error('Failed to load queue', e);
        }
    }

    document.addEventListener('click', async (e) => {
        if (e.target.matches('.btn-approve, .btn-reject')) {
            const btn = e.target;
            const decision = btn.matches('.btn-approve') ? 'verified' : 'rejected';
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
                const res = await fetch('<?= baseUrl('/api/licenses/verify.php') ?>', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        license_id: btn.dataset.id,
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

<?php require __DIR__ . '/../../includes/partials/footer.php'; ?>
