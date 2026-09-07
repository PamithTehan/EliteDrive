<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (currentUser()) {
    header('Location: ' . baseUrl('/index.php'));
    exit;
}

// Redirect to phase 1 if session is missing
if (empty($_SESSION['register_phase1'])) {
    header('Location: ' . baseUrl('/register.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $error = 'Invalid session token. Please try again.';
    } else {
        $isOwner = isset($_POST['role_owner']) ? 1 : 0;
        $isDriver = isset($_POST['role_driver']) ? 1 : 0;
        $isBorrower = isset($_POST['role_borrower']) ? 1 : 0;
        $drivingPreference = $_POST['driving_preference'] ?? 'any_vehicle';
        $dailyFee = isset($_POST['daily_fee']) ? (float)$_POST['daily_fee'] : 25.00;
        $transmissionPref = $_POST['transmission_preference'] ?? 'Both';

        if (!$isOwner && !$isDriver && !$isBorrower) {
            $error = 'Please select at least one role.';
        } elseif ($isDriver && ($dailyFee < 2500.00 || $dailyFee > 7000.00)) {
            $error = 'Driver daily fee must be between LKR 2500.00 and LKR 7000.00.';
        } elseif ($isDriver && !in_array($transmissionPref, ['Manual', 'Auto', 'Both'], true)) {
            $error = 'Please select a valid transmission preference (Manual, Auto, or Both).';
        } else {
            $phase1 = $_SESSION['register_phase1'];
            $hash = password_hash($phase1['password'], PASSWORD_BCRYPT);
            
            $db = getDb();
            try {
                $db->beginTransaction();

                // Insert into main users table with role flags
                $stmt = $db->prepare('INSERT INTO users (full_name, email, password_hash, contact_number, is_owner, is_borrower, is_driver) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$phase1['full_name'], $phase1['email'], $hash, $phase1['contact_number'], $isOwner, $isBorrower, $isDriver]);
                $userId = $db->lastInsertId();

                // If user registered as a driver, insert into drivers table for preferences and rate
                if ($isDriver) {
                    $pref = ($isOwner && $drivingPreference === 'own_vehicles') ? 'own_vehicles' : 'any_vehicle';
                    $stmt = $db->prepare('INSERT INTO drivers (user_id, daily_fee, transmission_preference, driving_preference) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$userId, $dailyFee, $transmissionPref, $pref]);
                }

                $db->commit();
                
                // Fetch user data for login
                $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                $userRow = $stmt->fetch();
                unset($userRow['password_hash']);
                
                // Clear session and login
                unset($_SESSION['register_phase1']);
                loginUser($userRow);
                
                header('Location: ' . baseUrl('/index.php'));
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'An error occurred during registration. Please try again.';
            }
        }
    }
}

$extraCss = ['auth'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div class="container">
    <div class="auth-container">
        <h1 class="headline-lg auth-title">Choose Your Roles - Step 2</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= escapeHtml($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="role-form">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            
            <div class="form-group">
                <label class="form-label">How will you use EliteDrive? (Select all that apply)</label>
                <div class="role-options">
                    <label class="role-option">
                        <input type="checkbox" name="role_borrower" value="1" <?= isset($_POST['role_borrower']) ? 'checked' : '' ?>>
                        <span>I want to rent vehicles</span>
                    </label>
                    <label class="role-option">
                        <input type="checkbox" id="role_owner" name="role_owner" value="1" <?= isset($_POST['role_owner']) ? 'checked' : '' ?>>
                        <span>I want to list my vehicles</span>
                    </label>
                    <label class="role-option">
                        <input type="checkbox" id="role_driver" name="role_driver" value="1" <?= isset($_POST['role_driver']) ? 'checked' : '' ?>>
                        <span>I want to be a hired driver</span>
                    </label>
                </div>
            </div>

            <!-- Driver Specific Settings -->
            <div id="driver-settings-group" style="display: none; background: var(--color-surface); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--color-outline); margin-top: 15px; margin-bottom: 15px;">
                <h3 class="headline-sm" style="margin-bottom: 12px; color: var(--color-primary);">Driver Profile & Rates</h3>
                
                <div class="form-group">
                    <label class="form-label" for="daily_fee">Your Daily Fee (LKR 2500.00 – LKR 7000.00)</label>
                    <div style="position: relative;">
                        <input type="number" id="daily_fee" name="daily_fee" class="input" min="2500" max="7000" step="50.00" value="<?= htmlspecialchars($_POST['daily_fee'] ?? '2500.00') ?>" required>
                    </div>
                    <small style="color: var(--color-secondary); display:block; margin-top: 4px;">Set the amount you charge per day (must be between LKR 2500 and LKR 7000).</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Preferred Transmission</label>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="transmission_preference" value="Both" <?= (!isset($_POST['transmission_preference']) || $_POST['transmission_preference'] === 'Both') ? 'checked' : '' ?>> 
                            <span>Both (Automatic & Manual)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="transmission_preference" value="Auto" <?= (isset($_POST['transmission_preference']) && $_POST['transmission_preference'] === 'Auto') ? 'checked' : '' ?>> 
                            <span>Automatic Only</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="transmission_preference" value="Manual" <?= (isset($_POST['transmission_preference']) && $_POST['transmission_preference'] === 'Manual') ? 'checked' : '' ?>> 
                            <span>Manual Only</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" id="driver-owner-pref" style="display: none; border-top: 1px solid var(--color-outline); padding-top: 12px; margin-top: 12px;">
                    <label class="form-label" style="margin-bottom: 8px;">As a vehicle owner and driver, what is your driving preference?</label>
                    <label style="display: block; margin-bottom: 8px;">
                        <input type="radio" name="driving_preference" value="any_vehicle" checked> 
                        I am willing to drive any vehicle.
                    </label>
                    <label style="display: block;">
                        <input type="radio" name="driving_preference" value="own_vehicles"> 
                        I only want to drive my own vehicles.
                    </label>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <a href="<?= baseUrl('/register.php') ?>" class="btn btn-secondary" style="flex: 1; text-align: center; text-decoration: none;">Back</a>
                <button type="submit" class="btn btn-primary" style="flex: 2;">Complete Registration</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ownerCheckbox = document.getElementById('role_owner');
        const driverCheckbox = document.getElementById('role_driver');
        const driverSettings = document.getElementById('driver-settings-group');
        const driverOwnerPref = document.getElementById('driver-owner-pref');
        const dailyFeeInput = document.getElementById('daily_fee');

        function toggleDriverFields() {
            if (driverCheckbox.checked) {
                driverSettings.style.display = 'block';
                dailyFeeInput.required = true;
                if (ownerCheckbox.checked) {
                    driverOwnerPref.style.display = 'block';
                } else {
                    driverOwnerPref.style.display = 'none';
                }
            } else {
                driverSettings.style.display = 'none';
                dailyFeeInput.required = false;
            }
        }

        ownerCheckbox.addEventListener('change', toggleDriverFields);
        driverCheckbox.addEventListener('change', toggleDriverFields);
        toggleDriverFields();
    });
</script>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
