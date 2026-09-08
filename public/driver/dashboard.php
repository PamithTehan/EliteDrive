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
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $countStmt = $db->prepare('SELECT COUNT(*) FROM bookings WHERE assigned_driver_id = ?');
    $countStmt->execute([$user['id']]);
    $totalAssignments = $countStmt->fetchColumn();
    $totalPages = ceil($totalAssignments / $limit);
    
    $stmt = $db->prepare('
        SELECT b.*, v.make, v.model, u.full_name as borrower_name 
        FROM bookings b 
        JOIN vehicles v ON b.vehicle_id = v.id 
        JOIN users u ON b.borrower_id = u.id
        WHERE b.assigned_driver_id = ? 
        ORDER BY b.pickup_date ASC
        LIMIT ? OFFSET ?
    ');
    $stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $assignments = $stmt->fetchAll();
} elseif ($tab === 'reviews') {
    // Fetch driver reviews
    $ratingFilter = isset($_GET['rating']) && $_GET['rating'] !== '' ? (int)$_GET['rating'] : null;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
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
} elseif ($tab === 'income') {
    $stmtIncome = $db->prepare('
        SELECT b.id, b.created_at, b.return_date, b.driver_earnings, b.status, v.make, v.model
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.assigned_driver_id = ? AND b.status IN ("completed", "reviewed")
        ORDER BY b.return_date DESC
    ');
    $stmtIncome->execute([$user['id']]);
    $earningsList = $stmtIncome->fetchAll();
    
    $totalEarned = 0;
    $thisMonthEarned = 0;
    $pendingPayouts = 0;
    
    $monthlyData = array_fill(1, 12, 0);
    $currentMonth = date('n');
    $currentYear = date('Y');

    foreach ($earningsList as $row) {
        $amount = (float)$row['driver_earnings'];
        $totalEarned += $amount;
        
        $ts = strtotime($row['return_date']);
        if (date('Y', $ts) == $currentYear) {
            $m = (int)date('n', $ts);
            $monthlyData[$m] += $amount;
            if ($m == $currentMonth) {
                $thisMonthEarned += $amount;
            }
        }
    }
}

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
            <h1>Driver Dashboard</h1>
            <p>Manage your driving assignments and read your reviews.</p>
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
                        <span class="role-badge driver">Driver</span>
                    </div>

                    <hr class="profile-stats-divider">
                    
                    <div class="profile-meta-list" style="margin-bottom: 24px;">
                        <a href="<?= baseUrl('/driver/dashboard.php?tab=assignments') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: <?= $tab === 'assignments' ? '#e0e7ff' : 'transparent' ?>; font-weight: <?= $tab === 'assignments' ? '600' : '400' ?>;">
                            <span class="material-symbols-outlined">work</span> Assignments
                        </a>
                        <a href="<?= baseUrl('/driver/dashboard.php?tab=reviews') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: <?= $tab === 'reviews' ? '#e0e7ff' : 'transparent' ?>; font-weight: <?= $tab === 'reviews' ? '600' : '400' ?>;">
                            <span class="material-symbols-outlined">star</span> My Reviews
                        </a>
                        <a href="<?= baseUrl('/driver/dashboard.php?tab=income') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: <?= $tab === 'income' ? '#e0e7ff' : 'transparent' ?>; font-weight: <?= $tab === 'income' ? '600' : '400' ?>;">
                            <span class="material-symbols-outlined">payments</span> Income
                        </a>
                    </div>
                </div>
            </aside>
            
            <div class="profile-content-area">
                
                <?php if ($tab === 'assignments'): ?>
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <span class="material-symbols-outlined">work_history</span>
                            <div>
                                <h2>My Assignments</h2>
                                <p>View upcoming and past driving assignments.</p>
                            </div>
                        </div>
                        <div class="settings-card-body" style="padding: 24px;">
            
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
                                            <strong>Return:</strong> <?= escapeHtml(date('M d, Y H:i', strtotime($a['return_date']))) ?> at <?= escapeHtml($a['return_location']) ?><br>
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
                
                <?php
                $baseUrl = '?tab=assignments&page=';
                $currentPage = $page;
                require __DIR__ . '/../../includes/partials/pagination.php';
                ?>
                
            <?php endif; ?>
                        </div> <!-- settings-card-body -->
                    </div> <!-- settings-card -->
                <?php endif; ?>

                <?php if ($tab === 'reviews'): ?>
                    <div class="settings-card">
                        <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap: 16px;">
                                <span class="material-symbols-outlined">star</span>
                                <div>
                                    <h2>My Reviews</h2>
                                    <p>Read what borrowers have said about your driving.</p>
                                </div>
                            </div>
                            <form method="GET" action="<?= baseUrl('/driver/dashboard.php') ?>" style="display:flex; align-items:center; gap:8px;">
                                <input type="hidden" name="tab" value="reviews">
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
                        <div class="settings-card-body" style="padding: 24px;">

            
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
                </div>
                
                <?php
                $baseUrl = '?tab=reviews' . ($ratingFilter !== null ? '&rating=' . $ratingFilter : '') . '&page=';
                $currentPage = $page;
                require __DIR__ . '/../../includes/partials/pagination.php';
                ?>
            <?php endif; ?>
                        </div> <!-- settings-card-body -->
                    </div> <!-- settings-card -->
                <?php endif; ?>

                <?php if ($tab === 'income'): ?>
                    <div class="settings-card">
                        <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap: 16px;">
                                <span class="material-symbols-outlined">payments</span>
                                <div>
                                    <h2>My Income</h2>
                                    <p>Track your earnings from completed driving assignments.</p>
                                </div>
                            </div>
                        </div>
                        <div class="settings-card-body" style="padding: 24px;">
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                                <div style="background: white; border: 1px solid var(--color-outline); border-radius: 8px; padding: 20px;">
                                    <div style="color: var(--color-secondary); font-size: 14px; margin-bottom: 8px;">Total Earnings</div>
                                    <div style="font-size: 28px; font-weight: 700; color: var(--color-primary);">LKR <?= number_format($totalEarned, 2) ?></div>
                                </div>
                                <div style="background: white; border: 1px solid var(--color-outline); border-radius: 8px; padding: 20px;">
                                    <div style="color: var(--color-secondary); font-size: 14px; margin-bottom: 8px;">Earnings This Month</div>
                                    <div style="font-size: 28px; font-weight: 700; color: #166534;">LKR <?= number_format($thisMonthEarned, 2) ?></div>
                                </div>
                            </div>

                            <div style="background: white; border: 1px solid var(--color-outline); border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                                <h3 style="margin-bottom: 16px; font-size: 16px;">Earnings Over Last 12 Months</h3>
                                <canvas id="incomeChart" height="100"></canvas>
                            </div>

                            <h3 style="margin-bottom: 16px; font-size: 18px;">Recent Payouts</h3>
                            <?php if (empty($earningsList)): ?>
                                <p style="color: var(--color-secondary);">No income records found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                                        <thead>
                                            <tr style="border-bottom: 2px solid var(--color-outline);">
                                                <th style="padding: 12px 8px; font-weight: 600; color: var(--color-secondary);">Date</th>
                                                <th style="padding: 12px 8px; font-weight: 600; color: var(--color-secondary);">Vehicle</th>
                                                <th style="padding: 12px 8px; font-weight: 600; color: var(--color-secondary);">Amount</th>
                                                <th style="padding: 12px 8px; font-weight: 600; color: var(--color-secondary);">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($earningsList as $c): ?>
                                                <tr style="border-bottom: 1px solid var(--color-outline);">
                                                    <td style="padding: 12px 8px;"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                                                    <td style="padding: 12px 8px;"><?= escapeHtml($c['make'] . ' ' . $c['model']) ?></td>
                                                    <td style="padding: 12px 8px; font-weight: 600;">LKR <?= number_format($c['driver_earnings'], 2) ?></td>
                                                    <td>
                                                        <?php if ($c['status'] === 'confirmed' || $c['status'] === 'active'): ?>
                                                            <span class="badge" style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">Pending</span>
                                                        <?php else: ?>
                                                            <span class="badge" style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">Paid</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                    
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                    const ctx = document.getElementById('incomeChart');
                    if (ctx) {
                        const monthlyData = <?= json_encode(array_values($monthlyData)) ?>;
                        const currentMonth = <?= $currentMonth ?>;
                        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: months,
                                datasets: [{
                                    label: 'Earnings (LKR)',
                                    data: monthlyData,
                                    backgroundColor: '#202a3fff',
                                    borderRadius: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { display: false }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return 'LKR ' + value.toLocaleString();
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                    </script>
                <?php endif; ?>

            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
