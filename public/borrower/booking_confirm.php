<?php
/**
 * File: booking_confirm.php
 * Purpose: Success page after payment
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

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
$pickupDate = str_replace('T', ' ', $_POST['pickup_date']);
$returnDate = str_replace('T', ' ', $_POST['return_date']);
$pickupLocation = $_POST['pickup_location'];
$returnLocation = $_POST['return_location'] ?? 'Headquarters (Colombo)';
$driverId = $_POST['driver_id'] ?? null;

$stmt = $db->prepare('SELECT v.*, u.full_name as owner_name FROM vehicles v JOIN users u ON u.id = v.owner_id WHERE v.id = ?');
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    die('Vehicle not found.');
}

if ($driverArrangement === 'owner') {
    $driverId = $vehicle['owner_id'];
}

$errorMessage = '';
if (!isVehicleAvailable($vehicleId, $pickupDate, $returnDate, $db)) {
    $errorMessage = 'This vehicle is already booked for the selected dates. Please go back and choose different dates.';
} elseif ($driverArrangement === 'hired' && $driverId) {
    if (!isDriverAvailable((int)$driverId, $pickupDate, $returnDate, $db)) {
        $errorMessage = 'The selected driver is already booked for the selected dates. Please go back and choose another driver.';
    } elseif (!isDriverTransmissionCompatible((int)$driverId, $vehicleId, $db)) {
        $errorMessage = 'The selected driver is not compatible with this vehicle\'s ' . htmlspecialchars($vehicle['transmission']) . ' transmission.';
    }
}

if ($errorMessage) {
    require_once __DIR__ . '/../../includes/partials/head.php';
    echo '<div class="container" style="margin-top: 40px; margin-bottom: 40px;"><div class="alert alert-error">'.escapeHtml($errorMessage).'</div><br><a href="javascript:history.back()" class="btn btn-primary">Go Back</a></div>';
    require_once __DIR__ . '/../../includes/partials/footer.php';
    exit;
}

$price = calculatePrice([
    'vehicle_id' => $vehicleId,
    'driver_arrangement' => $driverArrangement,
    'assigned_driver_id' => $driverId ? (int)$driverId : null,
    'pickup_date' => $pickupDate,
    'return_date' => $returnDate
], $db);

$driverName = 'Self (You)';
if ($driverArrangement === 'owner') {
    $driverName = $vehicle['owner_name'] . ' (Owner)';
} elseif ($driverArrangement === 'hired') {
    if ($driverId) {
        $stmt = $db->prepare('SELECT u.full_name, d.daily_fee, d.transmission_preference FROM users u JOIN drivers d ON d.user_id = u.id WHERE u.id = ?');
        $stmt->execute([$driverId]);
        $driverData = $stmt->fetch();
        if ($driverData) {
            $driverName = $driverData['full_name'] . ' (Hired - $' . number_format($driverData['daily_fee'], 2) . '/day, ' . $driverData['transmission_preference'] . ')';
        } else {
            $driverName = 'Hired Driver';
        }
    } else {
        $driverName = 'Hired Driver (Admin will assign)';
    }
}

$extraCss = ['dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';
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
                    <p class="body-md" style="font-size:14px; color:var(--color-secondary);"><?= escapeHtml($returnLocation) ?></p>
                </div>
            </div>
            
            <?php
            // Calculate breakdown
            $breakdown = calculateRentalBreakdown([
                'vehicle_id' => $vehicleId,
                'driver_arrangement' => $driverArrangement,
                'assigned_driver_id' => $driverId ? (int)$driverId : null,
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate
            ], $db);
            ?>
            
            <div style="background-color: #f8fafc; padding: var(--space-lg); border-radius: var(--radius-md); margin-bottom: var(--space-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-md);">
                    <h3 class="label-md" style="margin: 0; color: var(--color-text); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">Invoice Breakdown</h3>
                    <?php if ($breakdown['grace_period_applied']): ?>
                        <span style="background-color: #dcfce7; color: #166534; font-size: 11px; padding: 2px 6px; border-radius: 4px; font-weight: 600;">1-Hour Grace Period Applied</span>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span class="body-md" style="color: var(--color-secondary);">Vehicle Daily Rate</span>
                    <span class="body-md">LKR <?= number_format($breakdown['vehicle_daily_rate'], 2) ?></span>
                </div>
                
                <?php if ($breakdown['driver_daily_fee'] > 0): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span class="body-md" style="color: var(--color-secondary);">Driver Daily Fee</span>
                    <span class="body-md">LKR <?= number_format($breakdown['driver_daily_fee'], 2) ?></span>
                </div>
                <?php endif; ?>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                    <span class="body-md" style="color: var(--color-text); font-weight: 600;">Effective Daily Rate</span>
                    <span class="body-md" style="font-weight: 600;">LKR <?= number_format($breakdown['effective_daily_rate'], 2) ?></span>
                </div>
                
                <hr style="border: 0; border-top: 1px dashed var(--color-outline); margin: 16px 0;">
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span class="body-md" style="color: var(--color-secondary);">Rental Duration</span>
                    <?php 
                        $pickup = new DateTime($pickupDate);
                        $return = new DateTime($returnDate);
                        $diffSeconds = $return->getTimestamp() - $pickup->getTimestamp();
                        $borrowPeriodInHours = (int) ceil($diffSeconds / 3600);
                    ?>
                    <span class="body-md"><?= $borrowPeriodInHours ?> hours</span>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <span class="body-md" style="color: var(--color-secondary);">Chargeable Blocks (6-hour periods)</span>
                    <span class="body-md"><?= $breakdown['total_blocks'] ?> &times; LKR <?= number_format($breakdown['block_rate'], 2) ?></span>
                </div>
                
                <hr style="border: 0; border-top: 1px dashed var(--color-outline); margin: 16px 0;">
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span class="body-md" style="color: var(--color-secondary);">Base Rental Price</span>
                    <span class="body-md">LKR <?= number_format($breakdown['base_price'], 2) ?></span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <span class="body-md" style="color: var(--color-secondary);">Platform Commission (<?= $breakdown['commission_rate'] ?>%)</span>
                    <span class="body-md" style="color: var(--color-primary); font-weight: 600;">+ LKR <?= number_format($breakdown['commission_amount'], 2) ?></span>
                </div>
            </div>
            
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: var(--space-lg); border-top: 2px solid var(--color-outline); padding-top: var(--space-md);">
                <h3 class="headline-md">Total Price</h3>
                <h3 class="headline-lg" style="color:var(--color-primary);">LKR <?= number_format($price, 2) ?></h3>
            </div>
            
            <div id="payment-status" class="alert alert-success" style="display:none;">Payment successful! Processing booking...</div>
            <div id="payment-error" class="alert alert-error" style="display:none;"></div>

            <div style="margin-bottom: var(--space-lg);">
                <label style="display:block; font-weight:600; margin-bottom: 12px; color: var(--color-on-surface);">Select Payment Method</label>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <label class="payment-card" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px 16px; border: 2px solid var(--color-primary); border-radius: 8px; background-color: rgba(15, 23, 42, 0.02); transition: all 0.2s ease;">
                        <input type="radio" name="payment_method" value="card" checked style="display:none;" onchange="updatePaymentCards()">
                        <span class="material-symbols-outlined payment-icon" style="font-size: 32px; color: var(--color-primary); margin-bottom: 8px;">credit_card</span>
                        <span style="font-weight: 600; color: var(--color-on-surface);">Online Payment</span>
                        <span style="font-size: 12px; color: var(--color-secondary);">Pay securely via Stripe</span>
                    </label>
                    
                    <label class="payment-card" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px 16px; border: 2px solid var(--color-outline); border-radius: 8px; background-color: var(--color-surface); transition: all 0.2s ease;">
                        <input type="radio" name="payment_method" value="headquarters" style="display:none;" onchange="updatePaymentCards()">
                        <span class="material-symbols-outlined payment-icon" style="font-size: 32px; color: var(--color-secondary); margin-bottom: 8px;">storefront</span>
                        <span style="font-weight: 600; color: var(--color-on-surface);">Pay at Headquarters</span>
                        <span style="font-size: 12px; color: var(--color-secondary);">Pay in person</span>
                    </label>
                </div>
            </div>

            <script>
                function updatePaymentCards() {
                    const cards = document.querySelectorAll('.payment-card');
                    cards.forEach(card => {
                        const radio = card.querySelector('input[type="radio"]');
                        const icon = card.querySelector('.payment-icon');
                        if (radio.checked) {
                            card.style.borderColor = 'var(--color-primary)';
                            card.style.backgroundColor = 'rgba(15, 23, 42, 0.02)';
                            icon.style.color = 'var(--color-primary)';
                        } else {
                            card.style.borderColor = 'var(--color-outline)';
                            card.style.backgroundColor = 'var(--color-surface)';
                            icon.style.color = 'var(--color-secondary)';
                        }
                    });
                }
            </script>

            <button id="btn-pay" class="btn btn-primary" style="width: 100%;">Confirm Booking</button>
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
            btn.textContent = 'Redirecting to Payment...';
            
            const formData = new FormData();
            formData.append('csrf', csrfToken);
            formData.append('vehicle_id', "<?= $vehicleId ?>");
            formData.append('driver_arrangement', "<?= $driverArrangement ?>");
            formData.append('driver_id', "<?= $driverId ?>");
            formData.append('pickup_date', "<?= $pickupDate ?>");
            formData.append('return_date', "<?= $returnDate ?>");
            formData.append('pickup_location', "<?= $pickupLocation ?>");
            formData.append('return_location', "<?= $returnLocation ?>");
            
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            formData.append('payment_method', paymentMethod);

            const res = await fetch('<?= baseUrl('/api/bookings/create.php') ?>', {
                method: 'POST',
                body: formData
            });
            
            const data = await res.json();
            
            if (res.ok) {
                if (paymentMethod === 'card' && data.stripe_url) {
                    // Redirect directly to Stripe Checkout
                    window.location.href = data.stripe_url;
                } else if (paymentMethod === 'headquarters' && data.ok) {
                    window.location.href = "<?= baseUrl('/borrower/my_bookings.php') ?>";
                } else {
                    throw new Error(data.error || 'Failed to initialize payment.');
                }
            } else {
                throw new Error(data.error || 'Failed to initialize payment.');
            }
        } catch (err) {
            document.getElementById('payment-error').textContent = err.message;
            document.getElementById('payment-error').style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Try Again';
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
