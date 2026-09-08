<?php
/**
 * File: income.php
 * Purpose: Revenue tracking and owner payouts
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
$user = currentUser();
$db = getDb();

// 1. Get Platform Totals
$totalRevenueStmt = $db->query('SELECT SUM(commission_amount) FROM bookings WHERE commission_amount > 0 AND status IN ("completed", "reviewed")');
$totalEarned = (float)$totalRevenueStmt->fetchColumn();

// 2. Get This Month's Revenue
$currentMonthStr = date('Y-m');
$thisMonthStmt = $db->prepare('
    SELECT SUM(commission_amount) 
    FROM bookings 
    WHERE commission_amount > 0 
      AND status IN ("completed", "reviewed")
      AND DATE_FORMAT(return_date, "%Y-%m") = ?
');
$thisMonthStmt->execute([$currentMonthStr]);
$thisMonthEarned = (float)$thisMonthStmt->fetchColumn();

// 3. Get Monthly Data for Chart (Current Year)
$currentYear = date('Y');
$monthlyDataStmt = $db->prepare('
    SELECT MONTH(return_date) as month, SUM(commission_amount) as total
    FROM bookings
    WHERE commission_amount > 0 
      AND status IN ("completed", "reviewed")
      AND YEAR(return_date) = ?
    GROUP BY MONTH(return_date)
');
$monthlyDataStmt->execute([$currentYear]);
$rawMonthly = $monthlyDataStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Initialize all 12 months with 0
$monthlyData = [];
for ($i = 1; $i <= 12; $i++) {
    $monthlyData[$i] = isset($rawMonthly[$i]) ? (float)$rawMonthly[$i] : 0.0;
}
$currentMonthNum = (int)date('n');

$extraCss = ['profile', 'dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<div style="background-color: #f8fafc; min-height: calc(100vh - 80px); padding-bottom: 40px;">
    <div class="profile-page-hero">
        <div class="container">
            <h1>Platform Income</h1>
            <p>Financial overview and site revenue.</p>
        </div>
    </div>

    <div class="container">
        <div class="profile-layout">
            <?php 
            $activeAdminNav = 'income';
            require __DIR__ . '/../../includes/partials/admin/sidebar.php'; 
            ?>
            
            <div class="profile-content-area">
                <div class="settings-card">
                    <div class="settings-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap: 16px;">
                            <span class="material-symbols-outlined">payments</span>
                            <div>
                                <h2>Income Overview</h2>
                                <p>Platform revenue from commissions.</p>
                            </div>
                        </div>
                    </div>
                    <div class="settings-card-body" style="padding: 24px;">
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                            <div style="background: white; border: 1px solid var(--color-outline); border-radius: 8px; padding: 20px;">
                                <div style="color: var(--color-secondary); font-size: 14px; margin-bottom: 8px;">Total Platform Revenue</div>
                                <div style="font-size: 28px; font-weight: 700; color: var(--color-primary);">LKR <?= number_format($totalEarned, 2) ?></div>
                            </div>
                            <div style="background: white; border: 1px solid var(--color-outline); border-radius: 8px; padding: 20px;">
                                <div style="color: var(--color-secondary); font-size: 14px; margin-bottom: 8px;">Revenue This Month</div>
                                <div style="font-size: 28px; font-weight: 700; color: #166534;">LKR <?= number_format($thisMonthEarned, 2) ?></div>
                            </div>
                        </div>

                        <div style="background: white; border: 1px solid var(--color-outline); border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                            <h3 style="margin-bottom: 16px; font-size: 16px;">Revenue Over Last 12 Months</h3>
                            <canvas id="incomeChart" height="100"></canvas>
                        </div>

                    </div>
                </div>
                
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                const ctx = document.getElementById('incomeChart');
                if (ctx) {
                    const monthlyData = <?= json_encode(array_values($monthlyData)) ?>;
                    const currentMonth = <?= $currentMonthNum ?>;
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: months,
                            datasets: [{
                                label: 'Revenue (LKR)',
                                data: monthlyData,
                                borderColor: '#16a34a',
                                backgroundColor: 'rgba(22, 163, 74, 0.1)',
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
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
