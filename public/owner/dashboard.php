<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('owner');
$user = currentUser();
$db = getDb();

// Fetch vehicles owned by this user
$stmt = $db->prepare('SELECT * FROM vehicles WHERE owner_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$vehicles = $stmt->fetchAll();

$extraCss = ['dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<div class="container grid grid-3" style="margin-top: var(--space-lg); margin-bottom: var(--space-lg);">
    <aside class="dashboard-sidebar" style="grid-column: 1 / 2;">
        <div class="card">
            <div class="card-body stack-sm">
                <h2 class="headline-md"><?= escapeHtml($user['full_name']) ?></h2>
                <p class="body-md">Owner Dashboard</p>
                <hr style="border:0; border-top: 1px solid var(--color-outline); margin: var(--space-md) 0;">
                <ul class="stack-sm" style="list-style:none; padding:0;">
                    <li><a href="<?= baseUrl('/owner/dashboard.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">My Vehicles</a></li>
                    <li><a href="<?= baseUrl('/owner/bookings.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">Bookings</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="dashboard-content" style="grid-column: 2 / 4;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-md);">
            <h1 class="headline-lg">My Vehicles</h1>
            <a href="<?= baseUrl('/owner/vehicle_form.php') ?>" class="btn btn-primary">Add Vehicle</a>
        </div>
        
        <?php if (empty($vehicles)): ?>
            <div class="card">
                <div class="card-body" style="text-align:center; padding: var(--space-lg);">
                    <p class="body-lg" style="color:var(--color-secondary);">You haven't listed any vehicles yet.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($vehicles as $v): ?>
                    <div class="card">
                        <div class="card-body">
                            <h3 class="headline-md"><?= escapeHtml($v['make'] . ' ' . $v['model']) ?></h3>
                            <div class="card-specs">
                                <span><?= escapeHtml($v['category']) ?></span>
                                <span>$<?= escapeHtml($v['daily_rate']) ?>/day</span>
                            </div>
                            <div style="margin-top: var(--space-sm);">
                                <?php
                                $badgeClass = match($v['status']) {
                                    'approved' => 'badge-status-verified',
                                    'pending_review' => 'badge-status-pending',
                                    'rejected' => 'badge-status-rejected',
                                    default => 'badge-status-pending'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= escapeHtml($v['status']) ?></span>
                            </div>
                            <?php if ($v['status'] === 'rejected' && !empty($v['rejection_reason'])): ?>
                                <div class="alert alert-error" style="margin-top: var(--space-sm); font-size: 13px; padding: 8px 12px;">
                                    <strong>Rejection Reason:</strong> <?= escapeHtml($v['rejection_reason']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
