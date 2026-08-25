<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/functions.php';

requireRole('driver');
$user = currentUser();
$db = getDb();

// Fetch assignments where this user is the assigned driver
$stmt = $db->prepare('
    SELECT b.*, v.make, v.model, u.full_name as borrower_name 
    FROM bookings b 
    JOIN vehicles v ON b.vehicle_id = v.id 
    JOIN users u ON b.borrower_id = u.id
    WHERE b.assigned_driver_id = ? 
    ORDER BY b.pickup_date ASC
');
$stmt->execute([$user['id']]);
$assignments = $stmt->fetchAll();

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
                    <li><a href="<?= baseUrl('/driver/dashboard.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">Overview</a></li>
                    <li><a href="<?= baseUrl('/driver/assignments.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start; font-weight: bold;">Assignments</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="dashboard-content" style="grid-column: 2 / 4;">
        <h1 class="headline-lg" style="margin-bottom: var(--space-lg);">My Assignments</h1>
        
        <?php if (empty($assignments)): ?>
            <div class="card">
                <div class="card-body" style="text-align:center; padding: var(--space-lg);">
                    <p class="body-lg" style="color:var(--color-secondary);">You have no driving assignments.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="stack-md">
                <?php foreach ($assignments as $a): ?>
                    <div class="card">
                        <div class="card-body">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <div>
                                    <h3 class="headline-md"><?= escapeHtml($a['make'] . ' ' . $a['model']) ?></h3>
                                    <p class="body-md" style="color:var(--color-secondary);">
                                        <strong>Pick-up:</strong> <?= escapeHtml(date('M d, Y H:i', strtotime($a['pickup_date']))) ?> at <?= escapeHtml($a['pickup_location']) ?><br>
                                        <strong>Return:</strong> <?= escapeHtml(date('M d, Y H:i', strtotime($a['return_date']))) ?><br>
                                        <strong>Borrower:</strong> <?= escapeHtml($a['borrower_name']) ?>
                                    </p>
                                </div>
                                <div style="text-align:right;">
                                    <span class="badge badge-status-verified"><?= escapeHtml($a['status']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require __DIR__ . '/../../includes/partials/footer.php'; ?>
