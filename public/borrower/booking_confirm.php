<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/functions.php';

requireRole('borrower');
$db = getDb();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /fleet/search.php');
    exit;
}

if (!csrfCheck($_POST['csrf'] ?? '')) {
    die('Invalid session token.');
}

$vehicleId = (int)$_POST['vehicle_id'];
$driverArrangement = $_POST['driver_arrangement'];
$pickupDate = $_POST['pickup_date'];
$returnDate = $_POST['return_date'];
$pickupLocation = $_POST['pickup_location'];
$driverId = $_POST['driver_id'] ?? null;

$stmt = $db->prepare('SELECT v.*, u.full_name as owner_name FROM vehicles v JOIN users u ON u.id = v.owner_id WHERE v.id = ?');
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    die('Vehicle not found.');
}

$price = calculatePrice([
    'vehicle_id' => $vehicleId,
    'pickup_date' => $pickupDate,
    'return_date' => $returnDate
], $db);

$driverName = 'Self (You)';
if ($driverArrangement === 'owner') {
    $driverName = $vehicle['owner_name'] . ' (Owner)';
} elseif ($driverArrangement === 'hired' && $driverId) {
    $stmt = $db->prepare('SELECT full_name FROM users WHERE id = ?');
    $stmt->execute([$driverId]);
    $driverName = $stmt->fetchColumn() . ' (Hired)';
}

$extraCss = ['dashboard'];
require __DIR__ . '/../../includes/partials/head.php';
?>

<div class="container grid" style="margin-top: var(--space-lg); margin-bottom: var(--space-lg); max-width: 800px;">
    <h1 class="headline-lg">Review and Confirm Booking</h1>
    
    <div class="card">
        <div class="card-body">
            <h2 class="headline-md" style="margin-bottom: var(--space-md);">Trip Summary</h2>
            
            <div class="grid grid-2" style="margin-bottom: var(--space-lg);">
                <div>
                    <h3 class="label-sm" style="color:var(--color-secondary);">Vehicle</h3>
                    <p class="body-md"><?= escapeHtml($vehicle['make'] . ' ' . $vehicle['model']) ?></p>
                </div>
                <div>
                    <h3 class="label-sm" style="color:var(--color-secondary);">Driver</h3>
                    <p class="body-md"><?= escapeHtml($driverName) ?></p>
                </div>
                <div>
                    <h3 class="label-sm" style="color:var(--color-secondary);">Pick-up</h3>
                    <p class="body-md"><?= escapeHtml($pickupDate) ?></p>
                    <p class="body-md" style="font-size:14px; color:var(--color-secondary);"><?= escapeHtml($pickupLocation) ?></p>
                </div>
                <div>
                    <h3 class="label-sm" style="color:var(--color-secondary);">Return</h3>
                    <p class="body-md"><?= escapeHtml($returnDate) ?></p>
                </div>
            </div>
            
            <hr style="border:0; border-top: 1px solid var(--color-outline); margin: var(--space-md) 0;">
            
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: var(--space-lg);">
                <h3 class="headline-md">Total Price</h3>
                <h3 class="headline-lg" style="color:var(--color-primary);">$<?= number_format($price, 2) ?></h3>
            </div>
            
            <div id="payment-status" class="alert alert-success" style="display:none;">Payment successful! Processing booking...</div>
            <div id="payment-error" class="alert alert-error" style="display:none;"></div>

            <button id="btn-pay" class="btn btn-primary" style="width: 100%;">Pay & Confirm</button>
        </div>
    </div>
</div>

<script>
    const csrfToken = "<?= csrfToken() ?>";
    
    document.getElementById('btn-pay').addEventListener('click', async (e) => {
        const btn = e.target;
        btn.disabled = true;
        btn.textContent = 'Processing Payment...';
        
        try {
            // 1. Process mock payment
            const payData = new FormData();
            payData.append('csrf', csrfToken);
            payData.append('amount', "<?= $price ?>");
            payData.append('payment_token', 'tok_mock_' + Math.random().toString(36).substring(7));
            
            const payRes = await fetch('/api/payments/process.php', {
                method: 'POST',
                body: payData
            });
            
            const payResult = await payRes.json();
            
            if (!payRes.ok) {
                throw new Error(payResult.error || 'Payment failed.');
            }
            
            // 2. Create Booking
            btn.textContent = 'Confirming Booking...';
            
            const formData = new FormData();
            formData.append('csrf', csrfToken);
            formData.append('vehicle_id', "<?= $vehicleId ?>");
            formData.append('driver_arrangement', "<?= $driverArrangement ?>");
            formData.append('driver_id', "<?= $driverId ?>");
            formData.append('pickup_date', "<?= $pickupDate ?>");
            formData.append('return_date', "<?= $returnDate ?>");
            formData.append('pickup_location', "<?= $pickupLocation ?>");
            formData.append('transaction_id', payResult.transaction_id);

            const res = await fetch('/api/bookings/create.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await res.json();
            
            if (res.ok) {
                document.getElementById('payment-status').textContent = 'Payment successful! Booking confirmed.';
                document.getElementById('payment-status').style.display = 'block';
                document.getElementById('payment-error').style.display = 'none';
                
                // Redirect to borrower dashboard
                setTimeout(() => {
                    window.location.href = '/borrower/my_bookings.php';
                }, 2000);
            } else {
                throw new Error(data.error || 'Failed to create booking.');
            }
        } catch (err) {
            document.getElementById('payment-error').textContent = err.message;
            document.getElementById('payment-error').style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Try Again';
        }
    });
</script>

<?php require __DIR__ . '/../../includes/partials/footer.php'; ?>
