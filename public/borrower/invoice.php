<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('borrower');
$db = getDb();
$user = currentUser();

$bookingId = (int)($_GET['booking_id'] ?? 0);
if (!$bookingId) {
    die('Invalid booking ID.');
}

// Fetch booking details
$stmt = $db->prepare('
    SELECT b.*, 
           v.make, v.model, v.daily_rate as vehicle_daily_rate,
           u.full_name as owner_name, u.email as owner_email,
           d_user.full_name as driver_name,
           d.daily_fee as driver_daily_fee,
           p.amount as paid_amount, p.currency, p.status as payment_status
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users u ON v.owner_id = u.id
    LEFT JOIN drivers d ON b.assigned_driver_id = d.user_id
    LEFT JOIN users d_user ON d.user_id = d_user.id
    LEFT JOIN payments p ON p.booking_id = b.id AND p.status IN ("completed", "pending")
    WHERE b.id = ? AND b.borrower_id = ?
');
$stmt->execute([$bookingId, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    die('Booking not found or access denied.');
}

// Calculations
$breakdown = calculateRentalBreakdown([
    'vehicle_id' => $booking['vehicle_id'],
    'driver_arrangement' => $booking['driver_arrangement'],
    'assigned_driver_id' => $booking['assigned_driver_id'],
    'pickup_date' => $booking['pickup_date'],
    'return_date' => $booking['return_date']
], $db);

$vehicleDailyRate = $breakdown['vehicle_daily_rate'];
$driverDailyFee = $breakdown['driver_daily_fee'];
$effectiveDailyRate = $breakdown['effective_daily_rate'];
$chargeForDayQuarter = $breakdown['block_rate'];
$totalDays = $breakdown['total_days'];
$remainingBlocks = $breakdown['remaining_blocks'];
$gracePeriodApplied = $breakdown['grace_period_applied'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Booking #<?= $booking['id'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #2563eb;
            --color-text: #1f2937;
            --color-text-light: #6b7280;
            --color-border: #e5e7eb;
            --color-bg: #f9fafb;
        }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--color-text);
            background-color: var(--color-bg);
            margin: 0;
            padding: 40px;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--color-border);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--color-border);
        }
        .brand h1 {
            margin: 0;
            color: var(--color-primary);
            font-size: 28px;
            font-weight: 700;
        }
        .brand p {
            margin: 5px 0 0 0;
            color: var(--color-text-light);
            font-size: 14px;
        }
        .invoice-details {
            text-align: right;
        }
        .invoice-details h2 {
            margin: 0;
            font-size: 24px;
            color: var(--color-text);
        }
        .invoice-details p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: var(--color-text-light);
        }
        .addresses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .address-block {
            width: 48%;
        }
        .address-block h3 {
            font-size: 14px;
            color: var(--color-text-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        .address-block p {
            margin: 0 0 4px 0;
            font-size: 15px;
            font-weight: 500;
        }
        .address-block p.light {
            font-weight: 400;
            color: var(--color-text-light);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--color-border);
        }
        th {
            background-color: var(--color-bg);
            font-weight: 600;
            font-size: 14px;
            color: var(--color-text-light);
            text-transform: uppercase;
        }
        td {
            font-size: 15px;
        }
        .text-right {
            text-align: right;
        }
        .total-row td {
            font-size: 18px;
            font-weight: 700;
            border-bottom: none;
            padding-top: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-pending {
            background-color: #fef08a;
            color: #854d0e;
        }
        .print-btn {
            display: block;
            width: max-content;
            margin: 0 auto 40px auto;
            background-color: var(--color-primary);
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
            transition: background-color 0.2s;
        }
        .print-btn:hover {
            background-color: #1d4ed8;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .invoice-box {
                box-shadow: none;
                border: none;
                padding: 0;
            }
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-btn">Print / Save as PDF</button>

    <div class="invoice-box">
        <div class="header">
            <div class="brand">
                <h1>EliteDrive</h1>
                <p>Premium Vehicle Rental System</p>
                <p>contact@elitedrive.com</p>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <p><strong>Booking #:</strong> <?= str_pad($booking['id'], 6, '0', STR_PAD_LEFT) ?></p>
                <p><strong>Date Issued:</strong> <?= date('M d, Y') ?></p>
                <p>
                    <strong>Payment Status:</strong> 
                    <?php if (($booking['payment_status'] ?? '') === 'completed'): ?>
                        <span class="status-badge status-paid">PAID</span>
                    <?php else: ?>
                        <span class="status-badge status-pending">PENDING</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="addresses">
            <div class="address-block">
                <h3>Billed To</h3>
                <p><?= escapeHtml($user['full_name']) ?></p>
                <p class="light"><?= escapeHtml($user['email']) ?></p>
            </div>
            <div class="address-block" style="text-align:right;">
                <h3>Vehicle Owner</h3>
                <p><?= escapeHtml($booking['owner_name']) ?></p>
                <p class="light"><?= escapeHtml($booking['owner_email']) ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Duration</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Vehicle Rental</strong><br>
                        <span style="font-size:13px; color:var(--color-text-light);"><?= escapeHtml($booking['make'] . ' ' . $booking['model']) ?></span>
                    </td>
                    <td class="text-right">$<?= number_format($vehicleDailyRate, 2) ?>/day</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                </tr>
                
                <?php if ($booking['driver_arrangement'] === 'hired'): ?>
                <tr>
                    <td>
                        <strong>Hired Driver</strong><br>
                        <span style="font-size:13px; color:var(--color-text-light);"><?= escapeHtml($booking['driver_name'] ?? 'Driver Assigned Later') ?></span>
                    </td>
                    <td class="text-right">$<?= number_format($driverDailyFee, 2) ?>/day</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                </tr>
                <?php endif; ?>
                
                <?php if ($totalDays > 0): ?>
                <tr>
                    <td>
                        <strong>Full Days Rental</strong><br>
                        <span style="font-size:13px; color:var(--color-text-light);">
                            Daily Rate applied for <?= $totalDays ?> days
                        </span>
                    </td>
                    <td class="text-right">$<?= number_format($effectiveDailyRate, 2) ?> / day</td>
                    <td class="text-right"><?= $totalDays ?> days</td>
                    <td class="text-right">$<?= number_format($totalDays * $effectiveDailyRate, 2) ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if ($remainingBlocks > 0 || $totalDays === 0): ?>
                <tr>
                    <td>
                        <strong>Partial Rental Charge</strong><br>
                        <span style="font-size:13px; color:var(--color-text-light);">
                            Billed in 6-hour blocks
                            <?php if ($gracePeriodApplied): ?>
                            <br><span style="color:#166534; font-weight:500;">(1-Hour Grace Period Applied)</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="text-right">$<?= number_format($chargeForDayQuarter, 2) ?> / block</td>
                    <td class="text-right"><?= $remainingBlocks ?> blocks</td>
                    <td class="text-right">$<?= number_format($remainingBlocks * $chargeForDayQuarter, 2) ?></td>
                </tr>
                <?php endif; ?>
                
                <tr class="total-row">
                    <td colspan="3" class="text-right">Total:</td>
                    <td class="text-right">$<?= number_format($booking['total_price'], 2) ?></td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top: 40px; border-top: 1px solid var(--color-border); padding-top: 20px; font-size: 14px; color: var(--color-text-light); text-align: center;">
            <p>Thank you for choosing EliteDrive. For any inquiries about this invoice, please contact support.</p>
        </div>
    </div>
    
    <script>
        // Automatically open print dialog if requested via query parameter (optional)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1') {
            window.onload = function() {
                window.print();
            }
        }
    </script>
</body>
</html>
