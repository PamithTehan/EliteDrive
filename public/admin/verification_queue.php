<?php
/**
 * File: verification_queue.php
 * Purpose: Validate pending user driving licenses
 */
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
            <?php 
            $activeAdminNav = 'verification_queue';
            require __DIR__ . '/../../includes/partials/admin/sidebar.php'; 
            ?>
            
            <div class="profile-content-area">
                
                <div class="settings-card">
                    <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap: 16px;">
                            <span class="material-symbols-outlined">verified</span>
                            <div>
                                <h2>Verification Queue</h2>
                                <p>Review driving licenses submitted by drivers.</p>
                            </div>
                        </div>
                    </div>
                    <div class="settings-card-body" style="padding: 24px;">
                <table class="table" style="width: 100%;">
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
                <div id="pagination-container" style="display: flex; justify-content: center; margin-top: 24px; gap: 8px; align-items: center;"></div>
                    </div> <!-- settings-card-body -->
                </div> <!-- settings-card -->
            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<!-- Document Modal -->
<div id="doc-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8);">
    <div style="background-color: var(--color-surface); margin: 5% auto; padding: var(--space-md); border-radius: var(--radius-md); width: 90%; max-width: 900px; height: 85vh; display: flex; flex-direction: column; box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-sm);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <h3 class="headline-sm" style="margin: 0;">Document Viewer</h3>
                <div style="display: flex; align-items: center; gap: 4px; background: var(--color-background); padding: 4px; border-radius: var(--radius-sm); border: 1px solid var(--color-outline);">
                    <button id="zoom-out" class="btn btn-ghost" style="padding: 2px 8px; font-weight: bold;" title="Zoom Out">-</button>
                    <button id="zoom-reset" class="btn btn-ghost" style="padding: 2px 8px; font-size: 12px;" title="Reset Zoom">Reset</button>
                    <button id="zoom-in" class="btn btn-ghost" style="padding: 2px 8px; font-weight: bold;" title="Zoom In">+</button>
                </div>
            </div>
            <button id="close-modal" class="btn btn-ghost" style="padding: 4px 12px; font-size: 20px;">&times;</button>
        </div>
        <div id="doc-container" style="position: relative; width: 100%; flex-grow: 1; min-height: 0; border: 1px solid var(--color-outline); border-radius: var(--radius-sm); background: #eee; overflow: hidden;">
            <!-- Content loaded dynamically -->
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    const csrfToken = "<?= csrfToken() ?>";
    
    const urlParams = new URLSearchParams(window.location.search);
    let currentPage = parseInt(urlParams.get('page')) || 1;

    async function loadQueue(page = currentPage) {
        try {
            const res = await fetch(`<?= baseUrl('/api/licenses/pending.php') ?>?page=${page}`);
            const text = await res.text();
            let responseData;
            try {
                responseData = JSON.parse(text);
            } catch (err) {
                console.error("API Error Response:", text);
                document.querySelector('#queue-body').innerHTML = `<tr><td colspan="5" style="color:red;">Error loading data. Check console. Response: ${escapeHtml(text).substring(0, 100)}...</td></tr>`;
                return;
            }
            
            const rows = Array.isArray(responseData) ? responseData : (responseData.data || []);
            const pagination = responseData.pagination || { current_page: 1, total_pages: 1 };
            
            const tbody = document.querySelector('#queue-body');
            
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No licenses pending review.</td></tr>';
                document.querySelector('#pagination-container').innerHTML = '';
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
                container.innerHTML = `<canvas id="pdf-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain;"></canvas>`;
                
                const b64Url = url.replace('type=license_pdf', 'type=license_pdf_b64');
                
                fetch(b64Url).then(res => res.json()).then(data => {
                    if (!data.pdf_base64) throw new Error("Invalid PDF response");
                    
                    const binary = atob(data.pdf_base64);
                    const uint8Array = new Uint8Array(binary.length);
                    for (let i = 0; i < binary.length; i++) {
                        uint8Array[i] = binary.charCodeAt(i);
                    }
                    
                    const loadingTask = pdfjsLib.getDocument({ data: uint8Array });
                    return loadingTask.promise;
                }).then(pdf => {
                    currentPdf = pdf;
                    return pdf.getPage(1);
                }).then(page => {
                    const scale = 2.0; // Higher base scale for crisp zooming
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
                    currentZoom = 0.5;
                    applyZoom();
                }).catch(err => {
                    container.innerHTML = `<p style="color:red;">Error loading PDF: ${err.message}</p>`;
                });
            } else {
                currentZoom = 1.0;
                applyZoom();
                container.innerHTML = `<img src="${url}" id="pdf-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain;">`;
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

    let currentZoom = 1.0;
    
    function applyZoom() {
        const el = document.getElementById('pdf-canvas');
        if (!el) return;
        
        const container = document.getElementById('doc-container');
        if (currentZoom <= 1.0) {
            el.style.position = 'absolute';
            el.style.width = '100%';
            el.style.height = '100%';
            container.style.overflow = 'hidden';
        } else {
            el.style.position = 'relative';
            el.style.width = `${currentZoom * 100}%`;
            el.style.height = `${currentZoom * 100}%`;
            container.style.overflow = 'auto';
        }
    }
    
    document.getElementById('zoom-in').addEventListener('click', () => {
        currentZoom += 0.5;
        applyZoom();
    });
    
    document.getElementById('zoom-out').addEventListener('click', () => {
        currentZoom = Math.max(0.5, currentZoom - 0.5);
        applyZoom();
    });
    
    document.getElementById('zoom-reset').addEventListener('click', () => {
        currentZoom = 1.0;
        applyZoom();
    });

    loadQueue();
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
