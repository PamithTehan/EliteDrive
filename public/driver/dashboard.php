<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/functions.php';

requireRole('driver');
$user = currentUser();
$db = getDb();

// Check license status
$stmt = $db->prepare('SELECT status FROM driving_licenses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
$stmt->execute([$user['id']]);
$licenseStatus = $stmt->fetchColumn();

$extraCss = ['dashboard'];
require __DIR__ . '/../../includes/partials/head.php';
?>

<div class="container grid grid-3" style="margin-top: var(--space-lg); margin-bottom: var(--space-lg);">
    <aside class="dashboard-sidebar" style="grid-column: 1 / 2;">
        <div class="card">
            <div class="card-body stack-sm">
                <h2 class="headline-md"><?= escapeHtml($user['full_name']) ?></h2>
                <p class="body-md">Driver Dashboard</p>
                <hr style="border:0; border-top: 1px solid var(--color-outline); margin: var(--space-md) 0;">
                <ul class="stack-sm" style="list-style:none; padding:0;">
                    <li><a href="<?= baseUrl('/driver/dashboard.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start; font-weight: bold;">Overview</a></li>
                    <li><a href="<?= baseUrl('/driver/assignments.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">Assignments</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="dashboard-content" style="grid-column: 2 / 4;">
        <h1 class="headline-lg" style="margin-bottom: var(--space-lg);">Driver Overview</h1>
        
        <?php if ($licenseStatus === 'verified'): ?>
            <div class="alert alert-success">
                <strong>You are ready to drive!</strong> Your license is verified and you can accept assignments.
            </div>
        <?php elseif ($licenseStatus === 'pending'): ?>
            <div class="alert alert-warning" style="background:#fef9c3; color:#713f12;">
                <strong>License under review.</strong> You cannot accept assignments until an admin verifies your license.
            </div>
        <?php elseif ($licenseStatus === 'rejected'): ?>
            <div class="alert alert-error">
                <strong>License rejected.</strong> Please upload a valid document.
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <h2 class="headline-md" style="margin-bottom: var(--space-sm);">Upload Driving License</h2>
                    <p class="body-md" style="margin-bottom: var(--space-md); color:var(--color-secondary);">You must upload a valid driving license to start accepting trips.</p>
                    <form method="POST" action="<?= baseUrl('/driver/dashboard.php') ?>" enctype="multipart/form-data">
                        <!-- Simplified mock upload for now -->
                        <div class="form-group">
                            <input type="file" name="license_doc" class="input" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload License</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require __DIR__ . '/../../includes/partials/footer.php'; ?>
