<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('owner');
$user = currentUser();
$db = getDb();

// Fetch earnings for this owner
$stmtIncome = $db->prepare('
    SELECT b.id, b.created_at, b.owner_earnings, b.status, v.make, v.model, u.full_name as borrower_name
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users u ON b.borrower_id = u.id
    WHERE v.owner_id = ? AND b.status IN ("confirmed", "active", "completed", "reviewed")
    ORDER BY b.created_at DESC
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
    $amount = (float)$row['owner_earnings'];
    if (in_array($row['status'], ['confirmed', 'active'])) {
        $pendingPayouts += $amount;
    } else {
        $totalEarned += $amount;
        
        $ts = strtotime($row['created_at']);
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
            <h1>Owner Dashboard</h1>
            <p>Manage your listed vehicles and review bookings.</p>
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
                        <span class="role-badge owner">Owner</span>
                    </div>

                    <hr class="profile-stats-divider">
                    
                    <div class="profile-meta-list" style="margin-bottom: 24px;">
                        <a href="<?= baseUrl('/owner/dashboard.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">directions_car</span> My Vehicles
                        </a>
                        <a href="<?= baseUrl('/owner/bookings.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: transparent; font-weight: 400;">
                            <span class="material-symbols-outlined">calendar_month</span> Bookings
                        </a>
                        <a href="<?= baseUrl('/owner/income.php') ?>" style="display:flex; align-items:center; gap:8px; color: var(--color-primary); text-decoration: none; padding: 8px 12px; border-radius: 6px; background-color: #e0e7ff; font-weight: 600;">
                            <span class="material-symbols-outlined">payments</span> Income
                        </a>
                    </div>
                </div>
            </aside>
            
            <div class="profile-content-area">
                
                <div class="settings-card">
                    <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap: 16px;">
                            <span class="material-symbols-outlined">payments</span>
                            <div>
                                <h2>Fleet Revenue</h2>
                                <p>Track your earnings from vehicle rentals.</p>
                            </div>
                        </div>
                    </div>
                    <div class="settings-card-body" style="padding: 24px;">
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                            <div style="background: white; border: 1px solid var(--color-outline); border-radius: 8px; padding: 20px;">
                                <div style="color: var(--color-secondary); font-size: 14px; margin-bottom: 8px;">Total Fleet Revenue</div>
                                <div style="font-size: 24px; font-weight: 700; color: var(--color-primary);">LKR <?= number_format($totalEarned, 2) ?></div>
                            </div>
                            <div style="background: white; border: 1px solid var(--color-outline); border-radius: 8px; padding: 20px;">
                                <div style="color: var(--color-secondary); font-size: 14px; margin-bottom: 8px;">Revenue This Month</div>
                                <div style="font-size: 24px; font-weight: 700; color: #166534;">LKR <?= number_format($thisMonthEarned, 2) ?></div>
                            </div>
                            <div style="background: white; border: 1px solid var(--color-outline); border-radius: 8px; padding: 20px;">
                                <div style="color: var(--color-secondary); font-size: 14px; margin-bottom: 8px;">Pending Revenue</div>
                                <div style="font-size: 24px; font-weight: 700; color: #b45309;">LKR <?= number_format($pendingPayouts, 2) ?></div>
                            </div>
                        </div>

                        <div style="background: white; border: 1px solid var(--color-outline); border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                            <h3 style="margin-bottom: 16px; font-size: 16px;">Revenue Over Last 12 Months</h3>
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
                                            <th style="padding: 12px 8px; font-weight: 600; color: var(--color-secondary);">Borrower</th>
                                            <th style="padding: 12px 8px; font-weight: 600; color: var(--color-secondary);">Amount</th>
                                            <th style="padding: 12px 8px; font-weight: 600; color: var(--color-secondary);">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($earningsList as $c): ?>
                                            <tr style="border-bottom: 1px solid var(--color-outline);">
                                                <td style="padding: 12px 8px;"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                                                <td style="padding: 12px 8px;"><?= escapeHtml($c['make'] . ' ' . $c['model']) ?></td>
                                                <td style="padding: 12px 8px;"><?= escapeHtml($c['borrower_name']) ?></td>
                                                <td style="padding: 12px 8px; font-weight: 600;">LKR <?= number_format($c['owner_earnings'], 2) ?></td>
                                                <td style="padding: 12px 8px;">
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
                        type: 'line',
                        data: {
                            labels: months,
                            datasets: [{
                                label: 'Revenue (LKR)',
                                data: monthlyData,
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                fill: true,
                                tension: 0.4
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

            </div> <!-- profile-content-area -->
        </div> <!-- profile-layout -->
    </div> <!-- container -->
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
