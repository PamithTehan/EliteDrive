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

<!-- Document Modal -->
<div id="doc-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8);">
    <div style="background-color: var(--color-surface); margin: 5% auto; padding: var(--space-md); border-radius: var(--radius-md); width: 90%; max-width: 900px; height: 85vh; display: flex; flex-direction: column; box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-sm);">
            <h3 class="headline-sm" style="margin: 0;">Document Viewer</h3>
            <button id="close-modal" class="btn btn-ghost" style="padding: 4px 12px; font-size: 20px;">&times;</button>
        </div>
        <div id="doc-container" style="width: 100%; flex-grow: 1; border: 1px solid var(--color-outline); border-radius: var(--radius-sm); background: #eee; display: flex; align-items: center; justify-content: center; overflow: auto;">
            <!-- Content loaded dynamically -->
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    const csrfToken = "<?= csrfToken() ?>";
    
    async function loadQueue() {
        try {
            const res = await fetch('<?= baseUrl('/api/licenses/pending.php') ?>');
            const text = await res.text();
            let rows;
            try {
                rows = JSON.parse(text);
            } catch (err) {
                console.error("API Error Response:", text);
                document.querySelector('#queue-body').innerHTML = `<tr><td colspan="5" style="color:red;">Error loading data. Check console. Response: ${escapeHtml(text).substring(0, 100)}...</td></tr>`;
                return;
            }
            
            const tbody = document.querySelector('#queue-body');
            
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No licenses pending review.</td></tr>';
                return;
            }
            
            tbody.innerHTML = rows.map(r => {
                let docLinks = '';
                if (r.upload_format === 'pdf') {
                    docLinks = `<a href="<?= baseUrl('/document.php') ?>?type=license_pdf&id=${r.id}" class="doc-link" style="color:var(--color-accent); text-decoration:underline;">View PDF</a>`;
                } else {
                    docLinks = `<a href="<?= baseUrl('/document.php') ?>?type=license_front&id=${r.id}" class="doc-link" style="color:var(--color-accent); text-decoration:underline;">Front</a> | <a href="<?= baseUrl('/document.php') ?>?type=license_back&id=${r.id}" class="doc-link" style="color:var(--color-accent); text-decoration:underline;">Back</a>`;
                }

                return `
                <tr data-id="${r.id}">
                    <td>${escapeHtml(r.full_name)}</td>
                    <td>${escapeHtml(r.license_number)}</td>
                    <td>${escapeHtml(r.expiry_date)}</td>
                    <td>${docLinks}</td>
                    <td>
                        <button class="btn btn-primary btn-approve" data-id="${r.id}" style="padding: 4px 12px; font-size: 12px;">Approve</button>
                        <button class="btn btn-ghost btn-reject" data-id="${r.id}" style="padding: 4px 12px; font-size: 12px; color: var(--color-error);">Reject</button>
                    </td>
                </tr>`;
            }).join('');
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
        } else if (e.target.matches('.doc-link')) {
            e.preventDefault();
            const url = e.target.getAttribute('href');
            const container = document.getElementById('doc-container');
            
            if (url.includes('license_pdf')) {
                container.innerHTML = `<canvas id="pdf-canvas" style="max-width:100%; object-fit:contain;"></canvas>`;
                
                const loadingTask = pdfjsLib.getDocument(url);
                loadingTask.promise.then(pdf => {
                    return pdf.getPage(1); // Render first page
                }).then(page => {
                    const scale = 1.5;
                    const viewport = page.getViewport({ scale: scale });
                    const canvas = document.getElementById('pdf-canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    
                    const renderContext = {
                        canvasContext: context,
                        viewport: viewport
                    };
                    page.render(renderContext);
                }).catch(err => {
                    container.innerHTML = `<p style="color:red;">Error loading PDF: ${err.message}</p>`;
                });
            } else {
                container.innerHTML = `<img src="${url}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
            }
            
            document.getElementById('doc-modal').style.display = 'block';
        }
    });

    document.getElementById('close-modal').addEventListener('click', () => {
        document.getElementById('doc-modal').style.display = 'none';
        document.getElementById('doc-container').innerHTML = '';
    });

    // Close modal when clicking outside of it
    window.addEventListener('click', (e) => {
        const modal = document.getElementById('doc-modal');
        if (e.target === modal) {
            modal.style.display = 'none';
            document.getElementById('doc-container').innerHTML = '';
        }
    });

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    loadQueue();
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
