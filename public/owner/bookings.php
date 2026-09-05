<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('owner');
$user = currentUser();
$db = getDb();

// Fetch bookings for vehicles owned by this user
$stmt = $db->prepare('
    SELECT b.*, v.make, v.model, u.full_name as borrower_name 
    FROM bookings b 
    JOIN vehicles v ON b.vehicle_id = v.id 
    JOIN users u ON b.borrower_id = u.id 
    WHERE v.owner_id = ? 
    ORDER BY b.created_at DESC
');
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

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
                    <li><a href="<?= baseUrl('/owner/bookings.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start; font-weight: bold;">Bookings</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="dashboard-content" style="grid-column: 2 / 4;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-md);">
            <h1 class="headline-lg">Bookings for My Vehicles</h1>
        </div>
        
        <?php if (empty($bookings)): ?>
            <div class="card" style="text-align:center; padding:var(--space-xl) var(--space-md);">
                <span class="material-symbols-outlined" style="font-size:48px; color:var(--color-secondary); margin-bottom:var(--space-sm);">event_busy</span>
                <h3 class="headline-sm">No Bookings Yet</h3>
                <p class="body-md" style="color:var(--color-secondary); margin-bottom:var(--space-md);">You don't have any bookings for your vehicles yet.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <table class="table" style="width: 100%; text-align: left;">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Borrower</th>
                                <th>Dates</th>
                                <th>Total Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><?= escapeHtml($b['make'] . ' ' . $b['model']) ?></td>
                                    <td><?= escapeHtml($b['borrower_name']) ?></td>
                                    <td>
                                        <div style="font-size: 14px;"><?= date('M j, Y H:i', strtotime($b['pickup_date'])) ?></div>
                                        <div style="font-size: 14px; color: var(--color-secondary);">to <?= date('M j, Y H:i', strtotime($b['return_date'])) ?></div>
                                    </td>
                                    <td>$<?= number_format($b['total_price'], 2) ?></td>
                                    <td>
                                        <?php if ($b['status'] === 'confirmed' || $b['status'] === 'active'): ?>
                                            <span class="badge" style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">Active</span>
                                        <?php elseif ($b['status'] === 'completed' || $b['status'] === 'reviewed'): ?>
                                            <span class="badge" style="background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">Completed</span>
                                        <?php elseif ($b['status'] === 'cancelled' || $b['status'] === 'rejected' || $b['status'] === 'disputed'): ?>
                                            <span class="badge" style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;"><?= escapeHtml($b['status']) ?></span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;"><?= escapeHtml($b['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
