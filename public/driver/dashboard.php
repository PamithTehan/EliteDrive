<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('driver');
$user = currentUser();
$db = getDb();

$tab = $_GET['tab'] ?? 'assignments';

// Check license status
$stmt = $db->prepare('SELECT id, status, rejection_reason FROM driving_licenses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
$stmt->execute([$user['id']]);
$licenseRow = $stmt->fetch();
$licenseStatus = $licenseRow ? $licenseRow['status'] : null;
$rejectionReason = ($licenseStatus === 'rejected') ? ($licenseRow['rejection_reason'] ?: 'No reason provided.') : '';

if ($tab === 'assignments') {
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
} elseif ($tab === 'reviews') {
    // Fetch driver reviews
    $ratingFilter = isset($_GET['rating']) && $_GET['rating'] !== '' ? (int)$_GET['rating'] : null;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 5;
    $offset = ($page - 1) * $limit;

    $revSql = '
        SELECT r.rating, r.comment, r.created_at, u.full_name as reviewer_name
        FROM reviews r
        JOIN users u ON r.reviewer_id = u.id
        WHERE r.target_type = "driver" AND r.target_id = ?
    ';
    $revParams = [$user['id']];

    if ($ratingFilter !== null) {
        $revSql .= ' AND r.rating = ?';
        $revParams[] = $ratingFilter;
    }

    $countSql = str_replace('r.rating, r.comment, r.created_at, u.full_name as reviewer_name', 'COUNT(*)', $revSql);
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute($revParams);
    $totalReviews = $stmtCount->fetchColumn();
    $totalPages = ceil($totalReviews / $limit);

    $revSql .= ' ORDER BY r.created_at DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
    $stmtRev = $db->prepare($revSql);
    $stmtRev->execute($revParams);
    $reviews = $stmtRev->fetchAll();
}

$extraCss = ['dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<div class="container grid grid-3" style="margin-top: var(--space-lg); margin-bottom: var(--space-lg);">
    <aside class="dashboard-sidebar" style="grid-column: 1 / 2;">
        <div class="card">
            <div class="card-body stack-sm">
                <h2 class="headline-md"><?= escapeHtml($user['full_name']) ?></h2>
                <p class="body-md">Driver Dashboard</p>
                <hr style="border:0; border-top: 1px solid var(--color-outline); margin: var(--space-md) 0;">
                <ul class="stack-sm" style="list-style:none; padding:0;">
                    <li><a href="<?= baseUrl('/driver/dashboard.php?tab=assignments') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start; font-weight: <?= $tab === 'assignments' ? 'bold' : 'normal' ?>;">Assignments</a></li>
                    <li><a href="<?= baseUrl('/driver/dashboard.php?tab=reviews') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start; font-weight: <?= $tab === 'reviews' ? 'bold' : 'normal' ?>;">My Reviews</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="dashboard-content" style="grid-column: 2 / 4;">
        
        <?php if ($tab === 'assignments'): ?>
            <h1 class="headline-lg" style="margin-bottom: var(--space-lg);">My Assignments</h1>
            
            <?php if ($licenseStatus === 'pending'): ?>
                <div class="alert alert-warning" style="background:#fef9c3; color:#713f12; margin-bottom: var(--space-md);">
                    <strong>License under review.</strong> You cannot accept assignments until an admin verifies your license.
                </div>
            <?php elseif ($licenseStatus === 'rejected'): ?>
                <div class="alert alert-error" style="margin-bottom: var(--space-md);">
                    <strong>License rejected.</strong> Please update your license in your Account Settings.<br>
                    <span style="display:inline-block; margin-top:5px; font-size:0.9em; opacity:0.9;">Reason: <?= escapeHtml($rejectionReason) ?></span>
                </div>
            <?php elseif ($licenseStatus !== 'verified'): ?>
                <div class="alert alert-warning" style="background:#fef9c3; color:#713f12; margin-bottom: var(--space-md);">
                    <strong>License required.</strong> Please upload your driving license in your Account Settings to receive assignments.
                </div>
            <?php endif; ?>

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
                                    <div>
                                        <span class="badge" style="background:#e0e7ff; color:#3730a3;"><?= escapeHtml($a['status']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($tab === 'reviews'): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: var(--space-md);">
                <h1 class="headline-lg">My Reviews</h1>
                <form method="GET" action="<?= baseUrl('/driver/dashboard.php') ?>" style="display:flex; align-items:center; gap:8px;">
                    <input type="hidden" name="tab" value="reviews">
                    <label for="rating" class="body-sm" style="color:var(--color-secondary);">Filter:</label>
                    <select name="rating" id="rating" class="input" style="padding: 6px 12px; width:auto; border-radius: var(--radius-sm);" onchange="this.form.submit()">
                        <option value="">All Ratings</option>
                        <option value="5" <?= $ratingFilter === 5 ? 'selected' : '' ?>>5 Stars</option>
                        <option value="4" <?= $ratingFilter === 4 ? 'selected' : '' ?>>4 Stars</option>
                        <option value="3" <?= $ratingFilter === 3 ? 'selected' : '' ?>>3 Stars</option>
                        <option value="2" <?= $ratingFilter === 2 ? 'selected' : '' ?>>2 Stars</option>
                        <option value="1" <?= $ratingFilter === 1 ? 'selected' : '' ?>>1 Star</option>
                    </select>
                </form>
            </div>
            
            <?php if (empty($reviews)): ?>
                <div class="card">
                    <div class="card-body" style="text-align:center; padding: var(--space-lg);">
                        <p class="body-lg" style="color:var(--color-secondary);">You have no reviews matching this filter.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="stack-md">
                    <?php foreach ($reviews as $rev): ?>
                        <div class="card" style="padding: var(--space-md);">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                                <strong class="body-md"><?= escapeHtml($rev['reviewer_name']) ?></strong>
                                <span class="body-sm" style="color:var(--color-secondary);"><?= escapeHtml(date('M d, Y', strtotime($rev['created_at']))) ?></span>
                            </div>
                            <div style="margin-bottom: 8px; color:#f59e0b; font-size: 18px;">
                                <?= str_repeat('★', $rev['rating']) ?><?= str_repeat('☆', 5 - $rev['rating']) ?>
                            </div>
                            <?php if ($rev['comment']): ?>
                                <p class="body-md"><?= nl2br(escapeHtml($rev['comment'])) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <div style="display:flex; justify-content:center; gap: 8px; margin-top: var(--space-md);">
                    <?php if ($page > 1): ?>
                        <a href="<?= baseUrl('/driver/dashboard.php?tab=reviews&rating=' . ($ratingFilter ?? '') . '&page=' . ($page - 1)) ?>" class="btn btn-ghost" style="padding: 6px 12px;">Previous</a>
                    <?php endif; ?>
                    
                    <span style="display:flex; align-items:center; padding: 0 12px; color:var(--color-secondary);" class="body-sm">
                        Page <?= $page ?> of <?= $totalPages ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= baseUrl('/driver/dashboard.php?tab=reviews&rating=' . ($ratingFilter ?? '') . '&page=' . ($page + 1)) ?>" class="btn btn-ghost" style="padding: 6px 12px;">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
