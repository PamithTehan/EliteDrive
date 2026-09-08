<?php
/**
 * File: login.php
 * Purpose: Universal user authentication
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (currentUser()) {
    header('Location: ' . baseUrl('/index.php'));
    exit;
}

$error = '';
$activeTab = $_GET['tab'] ?? 'login'; // 'login' or 'register'
$registerStep = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $error = 'Invalid session token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'login') {
            $activeTab = 'login';
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (!$email || !$password) {
                $error = 'Please fill in all fields.';
            } else {
                $db = getDb();
                $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $userRow = $stmt->fetch();
                
                if ($userRow && password_verify($password, $userRow['password_hash'])) {
                    unset($userRow['password_hash']);
                    loginUser($userRow);
                    header('Location: ' . baseUrl('/index.php'));
                    exit;
                } else {
                    $error = 'Invalid email or password.';
                }
            }
        } elseif ($action === 'register') {
            $activeTab = 'register';
            
            // Step 1 Data
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $contactNumber = trim($_POST['contact_number'] ?? '');
            $password = $_POST['register_password'] ?? '';
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
                $registerStep = 2; // Jump to step 2 on error
            } elseif ($isDriver && ($dailyFee < 2500.00 || $dailyFee > 7000.00)) {
                $error = 'Driver daily fee must be between LKR 2500.00 and LKR 7000.00.';
                $registerStep = 2;
            } elseif ($isDriver && !in_array($transmissionPref, ['Manual', 'Auto', 'Both'], true)) {
                $error = 'Please select a valid transmission preference (Manual, Auto, or Both).';
                $registerStep = 2;
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
}

$extraCss = ['auth'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div class="container">
    <div class="auth-container" style="max-width: 600px; padding: 0;">
        <!-- Top Level Tabs (Login vs Register) -->
        <div style="display: flex; border-bottom: 1px solid var(--color-outline);">
            <button type="button" id="main-tab-login" class="btn btn-ghost" style="flex: 1; border-radius: 0; padding: 16px; font-size: 18px; border-bottom: 2px solid <?= $activeTab === 'login' ? 'var(--color-primary)' : 'transparent' ?>; color: <?= $activeTab === 'login' ? 'var(--color-primary)' : 'var(--color-secondary)' ?>; font-weight: <?= $activeTab === 'login' ? 'bold' : 'normal' ?>; border-top-left-radius: var(--radius-lg);">
                Log In
            </button>
            <button type="button" id="main-tab-register" class="btn btn-ghost" style="flex: 1; border-radius: 0; padding: 16px; font-size: 18px; border-bottom: 2px solid <?= $activeTab === 'register' ? 'var(--color-primary)' : 'transparent' ?>; color: <?= $activeTab === 'register' ? 'var(--color-primary)' : 'var(--color-secondary)' ?>; font-weight: <?= $activeTab === 'register' ? 'bold' : 'normal' ?>; border-top-right-radius: var(--radius-lg);">
                Sign Up
            </button>
        </div>

        <div style="padding: var(--space-xl);">
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: var(--space-lg);"><?= escapeHtml($error) ?></div>
            <?php endif; ?>

            <!-- LOGIN CONTENT -->
            <div id="content-login" style="display: <?= $activeTab === 'login' ? 'block' : 'none' ?>;">
                <h1 class="headline-lg auth-title" style="margin-bottom: var(--space-lg);">Welcome Back</h1>
                <form method="POST" action="">
                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="input" required value="<?= escapeHtml($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="input" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-md);">Log In</button>
                </form>
            </div>

            <!-- REGISTER CONTENT -->
            <div id="content-register" style="display: <?= $activeTab === 'register' ? 'block' : 'none' ?>;">
                <h1 class="headline-lg auth-title" style="margin-bottom: var(--space-lg);">Create an Account</h1>
                
                <form method="POST" action="" id="register-form">
                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="register">
                    
                    <!-- Registration Step Wizard -->
                    <div style="display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 2px solid var(--color-outline);">
                        <button type="button" id="reg-tab-1" class="btn btn-ghost" style="flex: 1; border-radius: 0; border-bottom: 2px solid <?= $registerStep === 1 ? 'var(--color-primary)' : 'transparent' ?>; color: <?= $registerStep === 1 ? 'var(--color-primary)' : 'var(--color-secondary)' ?>; font-weight: <?= $registerStep === 1 ? 'bold' : 'normal' ?>; font-size: 14px;">Step 1: Details</button>
                        <button type="button" id="reg-tab-2" class="btn btn-ghost" style="flex: 1; border-radius: 0; border-bottom: 2px solid <?= $registerStep === 2 ? 'var(--color-primary)' : 'transparent' ?>; color: <?= $registerStep === 2 ? 'var(--color-primary)' : 'var(--color-secondary)' ?>; font-weight: <?= $registerStep === 2 ? 'bold' : 'normal' ?>; font-size: 14px;">Step 2: Roles</button>
                    </div>

                    <!-- Step 1: Personal Info -->
                    <div id="reg-content-1" style="display: <?= $registerStep === 1 ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label class="form-label" for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="input" required value="<?= escapeHtml($_POST['full_name'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="reg_email">Email Address</label>
                            <input type="email" id="reg_email" name="email" class="input" required value="<?= escapeHtml($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="contact_number">Contact Number</label>
                            <input type="tel" id="contact_number" name="contact_number" class="input" value="<?= escapeHtml($_POST['contact_number'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="reg_password">Password</label>
                            <input type="password" id="reg_password" name="register_password" class="input" required value="<?= escapeHtml($_POST['register_password'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="input" required value="<?= escapeHtml($_POST['confirm_password'] ?? '') ?>">
                        </div>
                        
                        <button type="button" id="btn-next" class="btn btn-primary" style="width: 100%;">Next Step</button>
                    </div>

                    <!-- Step 2: Roles -->
                    <div id="reg-content-2" style="display: <?= $registerStep === 2 ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label class="form-label">How will you use EliteDrive? (Select all that apply)</label>
                            <div class="role-options">
                                <label class="role-option">
                                    <input type="checkbox" name="role_borrower" value="1" <?= isset($_POST['role_borrower']) || !$_POST ? 'checked' : '' ?>>
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
                                    <input type="number" id="daily_fee" name="daily_fee" class="input" min="2500" max="7000" step="50.00" value="<?= htmlspecialchars($_POST['daily_fee'] ?? '2500.00') ?>">
                                </div>
                                <small style="color: var(--color-secondary); display:block; margin-top: 4px;">Set the amount you charge per day (must be between LKR 2500 and LKR 7000).</small>
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
                                    <input type="radio" name="driving_preference" value="own_vehicles" <?= (isset($_POST['driving_preference']) && $_POST['driving_preference'] === 'own_vehicles') ? 'checked' : '' ?>> 
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
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Main Top Level Auth Tabs ---
        const mainTabLogin = document.getElementById('main-tab-login');
        const mainTabRegister = document.getElementById('main-tab-register');
        const contentLogin = document.getElementById('content-login');
        const contentRegister = document.getElementById('content-register');

        function switchMainTab(tab) {
            if (tab === 'login') {
                contentLogin.style.display = 'block';
                contentRegister.style.display = 'none';
                
                mainTabLogin.style.borderBottomColor = 'var(--color-primary)';
                mainTabLogin.style.color = 'var(--color-primary)';
                mainTabLogin.style.fontWeight = 'bold';
                
                mainTabRegister.style.borderBottomColor = 'transparent';
                mainTabRegister.style.color = 'var(--color-secondary)';
                mainTabRegister.style.fontWeight = 'normal';
                
                // Optional: Update URL without refreshing
                history.replaceState(null, '', '?tab=login');
            } else {
                contentLogin.style.display = 'none';
                contentRegister.style.display = 'block';
                
                mainTabRegister.style.borderBottomColor = 'var(--color-primary)';
                mainTabRegister.style.color = 'var(--color-primary)';
                mainTabRegister.style.fontWeight = 'bold';
                
                mainTabLogin.style.borderBottomColor = 'transparent';
                mainTabLogin.style.color = 'var(--color-secondary)';
                mainTabLogin.style.fontWeight = 'normal';
                
                history.replaceState(null, '', '?tab=register');
            }
        }

        mainTabLogin.addEventListener('click', () => switchMainTab('login'));
        mainTabRegister.addEventListener('click', () => switchMainTab('register'));

        // --- Registration Nested Tabs (Step 1 vs Step 2) ---
        const regTab1 = document.getElementById('reg-tab-1');
        const regTab2 = document.getElementById('reg-tab-2');
        const regContent1 = document.getElementById('reg-content-1');
        const regContent2 = document.getElementById('reg-content-2');
        const btnNext = document.getElementById('btn-next');
        const btnBack = document.getElementById('btn-back');
        
        // Input validation fields
        const fullName = document.getElementById('full_name');
        const regEmail = document.getElementById('reg_email');
        const regPassword = document.getElementById('reg_password');
        const confirmPassword = document.getElementById('confirm_password');

        function switchRegTab(step) {
            if (step === 1) {
                regContent1.style.display = 'block';
                regContent2.style.display = 'none';
                regTab1.style.borderBottomColor = 'var(--color-primary)';
                regTab1.style.color = 'var(--color-primary)';
                regTab1.style.fontWeight = 'bold';
                regTab2.style.borderBottomColor = 'transparent';
                regTab2.style.color = 'var(--color-secondary)';
                regTab2.style.fontWeight = 'normal';
            } else {
                regContent1.style.display = 'none';
                regContent2.style.display = 'block';
                regTab2.style.borderBottomColor = 'var(--color-primary)';
                regTab2.style.color = 'var(--color-primary)';
                regTab2.style.fontWeight = 'bold';
                regTab1.style.borderBottomColor = 'transparent';
                regTab1.style.color = 'var(--color-secondary)';
                regTab1.style.fontWeight = 'normal';
            }
        }

        regTab1.addEventListener('click', () => switchRegTab(1));
        regTab2.addEventListener('click', () => {
            if (validateStep1()) switchRegTab(2);
        });
        
        btnNext.addEventListener('click', () => {
            if (validateStep1()) switchRegTab(2);
        });
        
        btnBack.addEventListener('click', () => switchRegTab(1));

        function validateStep1() {
            if (!fullName.value.trim() || !regEmail.value.trim() || !regPassword.value || !confirmPassword.value) {
                alert("Please fill in all required fields in Step 1.");
                return false;
            }
            if (regPassword.value !== confirmPassword.value) {
                alert("Passwords do not match.");
                return false;
            }
            return true;
        }

        // --- Driver settings logic ---
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
