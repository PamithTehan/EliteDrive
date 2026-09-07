<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = getDb();
$sessionUser = currentUser();
$userId = (int)$sessionUser['id'];

// Fetch latest user record
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    logoutUser();
}

$driver = null;
$license = null;
if (!empty($user['is_driver'])) {
    $stmtDriver = $db->prepare("SELECT * FROM drivers WHERE user_id = ?");
    $stmtDriver->execute([$userId]);
    $driver = $stmtDriver->fetch();

    $stmtLicense = $db->prepare("SELECT * FROM driving_licenses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmtLicense->execute([$userId]);
    $license = $stmtLicense->fetch();
}

$successMsg = '';
$errorMsg = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');

        if (empty($fullName)) {
            $errorMsg = 'Full name is required.';
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Please provide a valid email address.';
        } else {
            // Check if email changed and if it is already taken
            if (strtolower($email) !== strtolower($user['email'])) {
                $chk = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $chk->execute([$email, $userId]);
                if ($chk->fetch()) {
                    $errorMsg = 'This email address is already associated with another account.';
                }
            }

            if (!$errorMsg) {
                $upd = $db->prepare("UPDATE users SET full_name = ?, email = ?, contact_number = ? WHERE id = ?");
                $upd->execute([$fullName, $email, $contactNumber, $userId]);

                // Refresh session and user data
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                loginUser($user);

                $successMsg = 'Your personal profile details have been successfully updated.';
            }
        }
    } elseif ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) {
            $errorMsg = 'Please enter your current password.';
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $errorMsg = 'The current password you entered is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $errorMsg = 'New password must be at least 8 characters in length.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = 'New password and confirmation do not match.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $upd->execute([$newHash, $userId]);

            $successMsg = 'Your password has been changed successfully.';
        }
    } elseif ($action === 'update_roles') {
        $isBorrower = isset($_POST['role_borrower']) ? 1 : 0;
        $isOwner = isset($_POST['role_owner']) ? 1 : 0;
        $isDriver = isset($_POST['role_driver']) ? 1 : 0;

        // Ensure they have at least one role
        if (!$isBorrower && !$isOwner && !$isDriver && empty($user['is_admin'])) {
            $errorMsg = 'You must select at least one role to continue using EliteDrive.';
        } else {
            // Validate driver settings if driver is selected
            if ($isDriver) {
                $dailyFee = floatval($_POST['daily_fee'] ?? 25.00);
                $transmissionPref = $_POST['transmission_preference'] ?? 'Both';
                $drivingPref = $_POST['driving_preference'] ?? 'any_vehicle';

                if ($dailyFee < 2500.00 || $dailyFee > 7000.00) {
                    $errorMsg = 'Driver daily fee must be between LKR 2500.00 and LKR 7000.00 per day.';
                } elseif (!in_array($transmissionPref, ['Manual', 'Auto', 'Both'], true)) {
                    $errorMsg = 'Invalid transmission preference selected.';
                } elseif (!in_array($drivingPref, ['any_vehicle', 'own_vehicles'], true)) {
                    $errorMsg = 'Invalid driving preference selected.';
                }
            }

            if (!$errorMsg) {
                // Update roles
                $upd = $db->prepare("UPDATE users SET is_borrower = ?, is_owner = ?, is_driver = ? WHERE id = ?");
                $upd->execute([$isBorrower, $isOwner, $isDriver, $userId]);

                if ($isDriver) {
                    $stmtUpdDriver = $db->prepare("
                        INSERT INTO drivers (user_id, daily_fee, transmission_preference, driving_preference)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            daily_fee = VALUES(daily_fee),
                            transmission_preference = VALUES(transmission_preference),
                            driving_preference = VALUES(driving_preference)
                    ");
                    $stmtUpdDriver->execute([$userId, $dailyFee, $transmissionPref, $drivingPref]);
                }

                // Refresh session and user data
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                loginUser($user);

                if ($isDriver) {
                    $stmtDriver = $db->prepare("SELECT * FROM drivers WHERE user_id = ?");
                    $stmtDriver->execute([$userId]);
                    $driver = $stmtDriver->fetch();
                } else {
                    $driver = null;
                }

                $successMsg = 'Account roles and preferences updated successfully.';
            }
        }
    } elseif ($action === 'upload_license') {
        $licenseNumber = trim($_POST['license_number'] ?? '');
        $expiryDate = $_POST['expiry_date'] ?? '';
        $format = $_POST['upload_format'] ?? 'pdf';
        
        if (!$licenseNumber || !$expiryDate) {
            $errorMsg = 'License number and expiry date are required.';
        } else {
            $db->beginTransaction();
            try {
                $stmt = $db->prepare('INSERT INTO driving_licenses (user_id, license_number, expiry_date, upload_format, status) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $licenseNumber, $expiryDate, $format, 'pending']);
                $licenseId = $db->lastInsertId();
                
                $uploadDir = __DIR__ . '/../storage/licenses/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                if ($format === 'pdf') {
                    if (empty($_FILES['license_pdf']['name'])) {
                        throw new Exception("Please upload a PDF document.");
                    }
                    $filename = time() . '_' . basename($_FILES['license_pdf']['name']);
                    move_uploaded_file($_FILES['license_pdf']['tmp_name'], $uploadDir . $filename);
                    $stmt = $db->prepare('INSERT INTO driving_license_pdfs (license_id, file_path) VALUES (?, ?)');
                    $stmt->execute([$licenseId, 'licenses/' . $filename]);
                } else {
                    if (empty($_FILES['license_front']['name']) || empty($_FILES['license_back']['name'])) {
                        throw new Exception("Please upload both front and back images.");
                    }
                    $frontName = time() . '_front_' . basename($_FILES['license_front']['name']);
                    $backName = time() . '_back_' . basename($_FILES['license_back']['name']);
                    
                    move_uploaded_file($_FILES['license_front']['tmp_name'], $uploadDir . $frontName);
                    move_uploaded_file($_FILES['license_back']['tmp_name'], $uploadDir . $backName);
                    
                    $stmt = $db->prepare('INSERT INTO driving_license_images (license_id, front_image_path, back_image_path) VALUES (?, ?, ?)');
                    $stmt->execute([$licenseId, 'licenses/' . $frontName, 'licenses/' . $backName]);
                }
                
                $db->commit();
                $successMsg = "License uploaded successfully. Please wait for admin verification.";
                
                // Refresh license var
                $stmtLicense = $db->prepare("SELECT * FROM driving_licenses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                $stmtLicense->execute([$userId]);
                $license = $stmtLicense->fetch();
            } catch (Exception $e) {
                $db->rollBack();
                $errorMsg = $e->getMessage();
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

$extraCss = ['profile'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div style="background-color: #f8fafc; min-height: calc(100vh - 80px);">
    <div class="profile-page-hero">
        <div class="container">
            <h1>Account Settings</h1>
            <p>Manage your personal profile, security preferences, and role settings in one place.</p>
        </div>
    </div>

    <div class="container">
        <?php if ($successMsg): ?>
            <div class="alert-profile success">
                <span class="material-symbols-outlined" style="font-size:20px;">check_circle</span>
                <span><?= escapeHtml($successMsg) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert-profile error">
                <span class="material-symbols-outlined" style="font-size:20px;">error</span>
                <span><?= escapeHtml($errorMsg) ?></span>
            </div>
        <?php endif; ?>

        <div class="profile-layout">
            <!-- Sidebar / User Overview Card -->
            <aside>
                <div class="profile-user-card">
                    <div class="profile-avatar-circle">
                        <?= escapeHtml($initials) ?>
                    </div>
                    <div class="profile-user-name"><?= escapeHtml($user['full_name']) ?></div>
                    <div class="profile-user-email"><?= escapeHtml($user['email']) ?></div>

                    <div class="profile-role-badges">
                        <?php if (!empty($user['is_admin'])): ?>
                            <span class="role-badge admin">Admin</span>
                        <?php endif; ?>
                        <?php if (!empty($user['is_owner'])): ?>
                            <span class="role-badge owner">Owner</span>
                        <?php endif; ?>
                        <?php if (!empty($user['is_driver'])): ?>
                            <span class="role-badge driver">Driver</span>
                        <?php endif; ?>
                        <?php if (!empty($user['is_borrower'])): ?>
                            <span class="role-badge borrower">Borrower</span>
                        <?php endif; ?>
                    </div>

                    <hr class="profile-stats-divider">

                    <div class="profile-meta-list">
                        <div class="profile-meta-item">
                            <span class="material-symbols-outlined">calendar_month</span>
                            <span>Member since <?= date('M Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        <div class="profile-meta-item">
                            <span class="material-symbols-outlined">call</span>
                            <span><?= !empty($user['contact_number']) ? escapeHtml($user['contact_number']) : 'No phone set' ?></span>
                        </div>
                        <?php if (!empty($user['is_driver']) && $driver): ?>
                            <div class="profile-meta-item">
                                <span class="material-symbols-outlined">payments</span>
                                <span>LKR <?= number_format($driver['daily_fee'], 2) ?> / day</span>
                            </div>
                            <div class="profile-meta-item">
                                <span class="material-symbols-outlined">tune</span>
                                <span>Pref: <?= escapeHtml($driver['transmission_preference']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>

            <!-- Main Settings Area -->
            <div class="profile-content-area">
                <!-- 1. Personal Details Form -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <span class="material-symbols-outlined">person</span>
                        <div>
                            <h2>Personal Information</h2>
                            <p>Update your display name, contact number, and primary email address.</p>
                        </div>
                    </div>

                    <form method="POST" action="<?= baseUrl('/profile.php') ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-grid-2">
                            <div class="form-field">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" value="<?= escapeHtml($user['full_name']) ?>" required>
                            </div>

                            <div class="form-field">
                                <label for="contact_number">Contact Number</label>
                                <input type="text" id="contact_number" name="contact_number" value="<?= escapeHtml($user['contact_number'] ?? '') ?>" placeholder="e.g. +1 555-0199">
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= escapeHtml($user['email']) ?>" required>
                            <div class="field-hint">Your email is used for sign-in and booking notifications.</div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save">
                                <span class="material-symbols-outlined" style="font-size:18px;">save</span> Save Details
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 2. Account Roles & Driver Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <span class="material-symbols-outlined">badge</span>
                        <div>
                            <h2>Account Roles & Preferences</h2>
                            <p>Manage how you use EliteDrive and configure your driver settings.</p>
                        </div>
                    </div>

                    <form method="POST" action="<?= baseUrl('/profile.php') ?>">
                        <input type="hidden" name="action" value="update_roles">

                        <div class="form-field">
                            <label class="form-label" style="font-weight: 500; margin-bottom: 12px; display: block;">How will you use EliteDrive? (Select all that apply)</label>
                            <div class="role-options" style="display: flex; flex-direction: column; gap: 12px; margin-top: 8px;">
                                <label class="role-option" style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                                    <input type="checkbox" name="role_borrower" value="1" <?= !empty($user['is_borrower']) ? 'checked' : '' ?> style="width: auto; height: auto;">
                                    <span>I want to rent vehicles</span>
                                </label>
                                <label class="role-option" style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                                    <input type="checkbox" name="role_owner" value="1" <?= !empty($user['is_owner']) ? 'checked' : '' ?> style="width: auto; height: auto;">
                                    <span>I want to list my vehicles for rent</span>
                                </label>
                                <label class="role-option" style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                                    <input type="checkbox" name="role_driver" id="role_driver" value="1" <?= !empty($user['is_driver']) ? 'checked' : '' ?> style="width: auto; height: auto;">
                                    <span>I want to be a hired driver</span>
                                </label>
                            </div>
                        </div>

                        <div id="driver-settings-group" style="display: <?= !empty($user['is_driver']) ? 'block' : 'none' ?>; border-top: 1px solid var(--color-outline); padding-top: 24px; margin-top: 24px;">
                            <h3 style="font-size: 16px; margin-bottom: 16px; color: var(--color-text-primary);">Driver Preferences & Rates</h3>
                            <div class="form-grid-2">
                                <div class="form-field">
                                    <label for="daily_fee">Driver Daily Fee (LKR/day)</label>
                                    <input type="number" id="daily_fee" name="daily_fee" min="2500" max="7000" step="50.00" value="<?= number_format($driver['daily_fee'] ?? 2500.00, 2, '.', '') ?>" <?= !empty($user['is_driver']) ? 'required' : '' ?>>
                                    <div class="field-hint">Permitted fee range is LKR 2500.00 – LKR 7000.00 per day.</div>
                                </div>

                                <div class="form-field">
                                    <label for="driving_preference">Vehicle Preference</label>
                                    <select id="driving_preference" name="driving_preference">
                                        <option value="any_vehicle" <?= ($driver['driving_preference'] ?? '') === 'any_vehicle' ? 'selected' : '' ?>>Drive Any Approved Fleet Vehicle</option>
                                        <option value="own_vehicles" <?= ($driver['driving_preference'] ?? '') === 'own_vehicles' ? 'selected' : '' ?>>Drive Own Registered Vehicles Only</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-field">
                                <label>Preferred Driving Transmission</label>
                                <div class="radio-options-grid">
                                    <label class="radio-card-label">
                                        <input type="radio" name="transmission_preference" value="Both" <?= ($driver['transmission_preference'] ?? 'Both') === 'Both' ? 'checked' : '' ?>>
                                        <span>Both (Auto & Manual)</span>
                                    </label>
                                    <label class="radio-card-label">
                                        <input type="radio" name="transmission_preference" value="Auto" <?= ($driver['transmission_preference'] ?? '') === 'Auto' ? 'checked' : '' ?>>
                                        <span>Automatic Only</span>
                                    </label>
                                    <label class="radio-card-label">
                                        <input type="radio" name="transmission_preference" value="Manual" <?= ($driver['transmission_preference'] ?? '') === 'Manual' ? 'checked' : '' ?>>
                                        <span>Manual Only</span>
                                    </label>
                                </div>
                                <div class="field-hint">You will only be assigned to vehicles matching your supported transmission.</div>
                            </div>

                            <?php if ($license): ?>
                                <div style="margin-top: 16px; padding: 12px 16px; background: #f8fafc; border: 1px solid var(--color-outline); border-radius: var(--radius-sm); font-size: 13px; display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <strong>License:</strong> <?= escapeHtml($license['license_number']) ?> 
                                        <span style="color: var(--color-secondary); margin-left: 8px;">(Expires <?= date('d M Y', strtotime($license['expiry_date'])) ?>)</span>
                                    </div>
                                    <span class="role-badge <?= $license['status'] === 'verified' ? 'owner' : ($license['status'] === 'pending' ? 'driver' : 'admin') ?>">
                                        <?= ucfirst(escapeHtml($license['status'])) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions" style="margin-top: 24px;">
                            <button type="submit" class="btn-save">
                                <span class="material-symbols-outlined" style="font-size:18px;">save</span> Save Roles & Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 3. Driving License Upload -->
                <?php if (!empty($user['is_driver'])): ?>
                <div class="settings-card">
                    <div class="settings-card-header">
                        <span class="material-symbols-outlined">id_card</span>
                        <div>
                            <h2>Driving License Verification</h2>
                            <p>Upload your valid driving license to accept trips.</p>
                        </div>
                    </div>
                    
                    <?php if ($license && $license['status'] === 'pending'): ?>
                        <div class="alert-profile warning" style="margin-bottom: 24px;">
                            <span class="material-symbols-outlined" style="font-size:20px;">info</span>
                            <span>Your license is currently under review by an admin.</span>
                        </div>
                    <?php elseif ($license && $license['status'] === 'verified'): ?>
                        <div class="alert-profile success" style="margin-bottom: 24px;">
                            <span class="material-symbols-outlined" style="font-size:20px;">check_circle</span>
                            <span>Your license has been verified. You are ready to drive!</span>
                        </div>
                    <?php elseif ($license && $license['status'] === 'rejected'): ?>
                        <div class="alert-profile error" style="margin-bottom: 24px;">
                            <span class="material-symbols-outlined" style="font-size:20px;">error</span>
                            <span><strong>License rejected:</strong> <?= escapeHtml($license['rejection_reason'] ?: 'No reason provided') ?>. Please upload again.</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!$license || $license['status'] === 'rejected'): ?>
                    <form method="POST" action="<?= baseUrl('/profile.php') ?>" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_license">

                        <div class="form-grid-2">
                            <div class="form-field">
                                <label for="license_number">License Number</label>
                                <input type="text" id="license_number" name="license_number" class="input" required>
                            </div>
                            <div class="form-field">
                                <label for="expiry_date">Expiry Date</label>
                                <input type="date" id="expiry_date" name="expiry_date" class="input" required>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="form-label" style="display:block; margin-bottom: 8px;">Upload Format</label>
                            <div style="display: flex; gap: var(--space-md);">
                                <label><input type="radio" name="upload_format" value="pdf" checked> PDF Document</label>
                                <label><input type="radio" name="upload_format" value="image"> Front & Back Images</label>
                            </div>
                        </div>

                        <!-- PDF Upload Zone -->
                        <div id="pdf-zone-container" style="margin-bottom: 16px;">
                            <div class="drop-zone" id="pdf-drop-zone" style="border: 2px dashed var(--color-outline); border-radius: var(--radius-md); padding: 32px; text-align: center; cursor: pointer;">
                                <div class="drop-zone-icon" style="font-size: 32px; color: var(--color-secondary); margin-bottom: 8px;">📄</div>
                                <div class="drop-zone-text" id="pdf-drop-zone-text" style="color: var(--color-secondary);">
                                    Drag and drop your PDF license here or click to browse
                                </div>
                                <input type="file" name="license_pdf" id="pdf-file-input" accept=".pdf" style="display:none;">
                            </div>
                        </div>

                        <!-- Image Upload Zones -->
                        <div id="image-zone-container" style="display: none; gap: var(--space-md); grid-template-columns: 1fr 1fr; margin-bottom: 16px;">
                            <div class="drop-zone" id="front-drop-zone" style="border: 2px dashed var(--color-outline); border-radius: var(--radius-md); padding: 32px; text-align: center; cursor: pointer;">
                                <div class="drop-zone-icon" style="font-size: 32px; color: var(--color-secondary); margin-bottom: 8px;">🖼️</div>
                                <div class="drop-zone-text" id="front-drop-zone-text" style="color: var(--color-secondary);">
                                    Front Image<br><small>Drag or click</small>
                                </div>
                                <input type="file" name="license_front" id="front-file-input" accept=".jpg,.jpeg,.png" style="display:none;">
                            </div>
                            
                            <div class="drop-zone" id="back-drop-zone" style="border: 2px dashed var(--color-outline); border-radius: var(--radius-md); padding: 32px; text-align: center; cursor: pointer;">
                                <div class="drop-zone-icon" style="font-size: 32px; color: var(--color-secondary); margin-bottom: 8px;">🖼️</div>
                                <div class="drop-zone-text" id="back-drop-zone-text" style="color: var(--color-secondary);">
                                    Back Image<br><small>Drag or click</small>
                                </div>
                                <input type="file" name="license_back" id="back-file-input" accept=".jpg,.jpeg,.png" style="display:none;">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save">
                                <span class="material-symbols-outlined" style="font-size:18px;">upload</span> Upload License
                            </button>
                        </div>
                    </form>
                    
                    <script>
                        // Toggle format visibility
                        const formatRadios = document.querySelectorAll('input[name="upload_format"]');
                        const pdfContainer = document.getElementById('pdf-zone-container');
                        const imageContainer = document.getElementById('image-zone-container');
                        
                        const pdfInput = document.getElementById('pdf-file-input');
                        const frontInput = document.getElementById('front-file-input');
                        const backInput = document.getElementById('back-file-input');

                        if (formatRadios.length) {
                            formatRadios.forEach(radio => {
                                radio.addEventListener('change', (e) => {
                                    if (e.target.value === 'pdf') {
                                        pdfContainer.style.display = 'block';
                                        imageContainer.style.display = 'none';
                                        pdfInput.required = true;
                                        if (frontInput) frontInput.required = false;
                                        if (backInput) backInput.required = false;
                                    } else {
                                        pdfContainer.style.display = 'none';
                                        imageContainer.style.display = 'grid';
                                        pdfInput.required = false;
                                        if (frontInput) frontInput.required = true;
                                        if (backInput) backInput.required = true;
                                    }
                                });
                            });
                            
                            // Set initial required state
                            if (pdfInput) pdfInput.required = true;

                            // Setup Drop Zones Helper
                            function setupDropZone(zoneId, inputId, textId, defaultText) {
                                const zone = document.getElementById(zoneId);
                                const input = document.getElementById(inputId);
                                const text = document.getElementById(textId);
                                if (!zone || !input || !text) return;

                                zone.addEventListener('click', () => input.click());

                                zone.addEventListener('dragover', (e) => {
                                    e.preventDefault();
                                    zone.classList.add('dragover');
                                });

                                zone.addEventListener('dragleave', () => {
                                    zone.classList.remove('dragover');
                                });

                                zone.addEventListener('drop', (e) => {
                                    e.preventDefault();
                                    zone.classList.remove('dragover');
                                    if (e.dataTransfer.files.length) {
                                        input.files = e.dataTransfer.files;
                                        updateText();
                                    }
                                });

                                input.addEventListener('change', updateText);

                                function updateText() {
                                    if (input.files.length > 0) {
                                        text.innerHTML = `<strong>Selected:</strong><br>${input.files[0].name}`;
                                        text.style.color = 'var(--color-primary)';
                                    } else {
                                        text.innerHTML = defaultText;
                                        text.style.color = 'var(--color-secondary)';
                                    }
                                }
                            }

                            setupDropZone('pdf-drop-zone', 'pdf-file-input', 'pdf-drop-zone-text', 'Drag and drop your PDF license here or click to browse');
                            setupDropZone('front-drop-zone', 'front-file-input', 'front-drop-zone-text', 'Front Image<br><small>Drag or click</small>');
                            setupDropZone('back-drop-zone', 'back-file-input', 'back-drop-zone-text', 'Back Image<br><small>Drag or click</small>');
                        }
                    </script>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- 4. Security & Password -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <span class="material-symbols-outlined">lock</span>
                        <div>
                            <h2>Change Password</h2>
                            <p>Ensure your account is using a secure and strong password.</p>
                        </div>
                    </div>

                    <form method="POST" action="<?= baseUrl('/profile.php') ?>">
                        <input type="hidden" name="action" value="update_password">

                        <div class="form-field">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required placeholder="Enter current password">
                        </div>

                        <div class="form-grid-2">
                            <div class="form-field">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" minlength="8" required placeholder="At least 8 characters">
                            </div>

                            <div class="form-field">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" minlength="8" required placeholder="Re-type new password">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save">
                                <span class="material-symbols-outlined" style="font-size:18px;">key</span> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleDriver = document.getElementById('role_driver');
    const driverSettings = document.getElementById('driver-settings-group');
    const dailyFee = document.getElementById('daily_fee');

    if (roleDriver && driverSettings) {
        roleDriver.addEventListener('change', function() {
            if (this.checked) {
                driverSettings.style.display = 'block';
                if (dailyFee) dailyFee.required = true;
            } else {
                driverSettings.style.display = 'none';
                if (dailyFee) dailyFee.required = false;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
