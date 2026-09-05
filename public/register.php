<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (currentUser()) {
    header('Location: ' . baseUrl('/index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $error = 'Invalid session token. Please try again.';
    } else {
        // Step 1 Data
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Step 2 Data
        $isOwner = isset($_POST['role_owner']) ? 1 : 0;
        $isDriver = isset($_POST['role_driver']) ? 1 : 0;
        $isBorrower = isset($_POST['role_borrower']) ? 1 : 0;
        $drivingPreference = $_POST['driving_preference'] ?? 'any_vehicle';
        $dailyFee = isset($_POST['daily_fee']) ? (float)$_POST['daily_fee'] : 25.00;
        $transmissionPref = $_POST['transmission_preference'] ?? 'Both';

        if (!$fullName || !$email || !$password || !$confirmPassword) {
            $error = 'Please fill in all required personal details.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (!$isOwner && !$isDriver && !$isBorrower) {
            $error = 'Please select at least one role.';
        } elseif ($isDriver && ($dailyFee < 20.00 || $dailyFee > 35.00)) {
            $error = 'Driver daily fee must be between $20.00 and $35.00.';
        } elseif ($isDriver && !in_array($transmissionPref, ['Manual', 'Auto', 'Both'], true)) {
            $error = 'Please select a valid transmission preference (Manual, Auto, or Both).';
        } else {
            // Check if email already exists
            $db = getDb();
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                
                try {
                    $db->beginTransaction();

                    // Insert into main users table with role flags
                    $stmt = $db->prepare('INSERT INTO users (full_name, email, password_hash, contact_number, is_owner, is_borrower, is_driver) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$fullName, $email, $hash, $contactNumber, $isOwner, $isBorrower, $isDriver]);
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
                    
                    // Login
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
}

$extraCss = ['auth'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div class="container">
    <div class="auth-container" style="max-width: 600px;">
        <h1 class="headline-lg auth-title">Create an Account</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= escapeHtml($error) ?></div>
        <?php endif; ?>

        <!-- Tabs Navigation -->
        <div style="display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 2px solid var(--color-outline);">
            <button type="button" id="tab-btn-1" class="btn btn-ghost" style="flex: 1; border-radius: 0; border-bottom: 2px solid var(--color-primary); color: var(--color-primary); font-weight: bold;">Step 1: Personal Details</button>
            <button type="button" id="tab-btn-2" class="btn btn-ghost" style="flex: 1; border-radius: 0; border-bottom: 2px solid transparent; color: var(--color-secondary);">Step 2: Role Selection</button>
        </div>

        <form method="POST" action="" id="register-form">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            
            <!-- TAB 1: Personal Info -->
            <div id="tab-content-1">
                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="input" required value="<?= escapeHtml($_POST['full_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="input" required value="<?= escapeHtml($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="contact_number">Contact Number</label>
                    <input type="tel" id="contact_number" name="contact_number" class="input" value="<?= escapeHtml($_POST['contact_number'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="input" required value="<?= escapeHtml($_POST['password'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="input" required value="<?= escapeHtml($_POST['confirm_password'] ?? '') ?>">
                </div>
                
                <button type="button" id="btn-next" class="btn btn-primary" style="width: 100%;">Next Step</button>
                
                <div class="auth-footer">
                    Already have an account? <a href="<?= baseUrl('/login.php') ?>" style="color: var(--color-accent);">Log In</a>
                </div>
            </div>

            <!-- TAB 2: Roles -->
            <div id="tab-content-2" style="display: none;">
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
                        <label class="form-label" for="daily_fee">Your Daily Fee ($20.00 – $35.00)</label>
                        <div style="position: relative;">
                            <input type="number" id="daily_fee" name="daily_fee" class="input" min="20" max="35" step="0.50" value="<?= htmlspecialchars($_POST['daily_fee'] ?? '25.00') ?>">
                        </div>
                        <small style="color: var(--color-secondary); display:block; margin-top: 4px;">Set the amount you charge per day (must be between $20 and $35).</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Preferred Transmission</label>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio" name="transmission_preference" value="Both" <?= (!isset($_POST['transmission_preference']) || $_POST['transmission_preference'] === 'Both') ? 'checked' : '' ?>> 
                                <span>Both</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio" name="transmission_preference" value="Auto" <?= (isset($_POST['transmission_preference']) && $_POST['transmission_preference'] === 'Auto') ? 'checked' : '' ?>> 
                                <span>Auto Only</span>
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
                    <button type="button" id="btn-back" class="btn btn-secondary" style="flex: 1; text-align: center;">Back</button>
                    <button type="submit" class="btn btn-primary" style="flex: 2;">Complete Registration</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Tab switching logic
        const tabBtn1 = document.getElementById('tab-btn-1');
        const tabBtn2 = document.getElementById('tab-btn-2');
        const tabContent1 = document.getElementById('tab-content-1');
        const tabContent2 = document.getElementById('tab-content-2');
        const btnNext = document.getElementById('btn-next');
        const btnBack = document.getElementById('btn-back');
        
        // Input validation fields
        const fullName = document.getElementById('full_name');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');

        function switchTab(tabIndex) {
            if (tabIndex === 1) {
                tabContent1.style.display = 'block';
                tabContent2.style.display = 'none';
                tabBtn1.style.borderBottomColor = 'var(--color-primary)';
                tabBtn1.style.color = 'var(--color-primary)';
                tabBtn1.style.fontWeight = 'bold';
                tabBtn2.style.borderBottomColor = 'transparent';
                tabBtn2.style.color = 'var(--color-secondary)';
                tabBtn2.style.fontWeight = 'normal';
            } else {
                tabContent1.style.display = 'none';
                tabContent2.style.display = 'block';
                tabBtn2.style.borderBottomColor = 'var(--color-primary)';
                tabBtn2.style.color = 'var(--color-primary)';
                tabBtn2.style.fontWeight = 'bold';
                tabBtn1.style.borderBottomColor = 'transparent';
                tabBtn1.style.color = 'var(--color-secondary)';
                tabBtn1.style.fontWeight = 'normal';
            }
        }

        tabBtn1.addEventListener('click', () => switchTab(1));
        tabBtn2.addEventListener('click', () => {
            if (validateStep1()) switchTab(2);
        });
        
        btnNext.addEventListener('click', () => {
            if (validateStep1()) switchTab(2);
        });
        
        btnBack.addEventListener('click', () => switchTab(1));

        function validateStep1() {
            if (!fullName.value.trim() || !email.value.trim() || !password.value || !confirmPassword.value) {
                alert("Please fill in all required fields in Step 1.");
                return false;
            }
            if (password.value !== confirmPassword.value) {
                alert("Passwords do not match.");
                return false;
            }
            return true;
        }

        // Driver settings logic
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
        
        // Show Step 2 if there's a specific role error after submission
        <?php if ($error && (strpos($error, 'role') !== false || strpos($error, 'fee') !== false || strpos($error, 'transmission') !== false)): ?>
            switchTab(2);
        <?php endif; ?>
    });
</script>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
