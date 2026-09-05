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
    } elseif ($action === 'update_driver_settings' && !empty($user['is_driver'])) {
        $dailyFee = floatval($_POST['daily_fee'] ?? 25.00);
        $transmissionPref = $_POST['transmission_preference'] ?? 'Both';
        $drivingPref = $_POST['driving_preference'] ?? 'any_vehicle';

        if ($dailyFee < 20.00 || $dailyFee > 35.00) {
            $errorMsg = 'Driver daily fee must be between $20.00 and $35.00 per day.';
        } elseif (!in_array($transmissionPref, ['Manual', 'Auto', 'Both'], true)) {
            $errorMsg = 'Invalid transmission preference selected.';
        } elseif (!in_array($drivingPref, ['any_vehicle', 'own_vehicles'], true)) {
            $errorMsg = 'Invalid driving preference selected.';
        } else {
            $stmtUpdDriver = $db->prepare("
                INSERT INTO drivers (user_id, daily_fee, transmission_preference, driving_preference)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    daily_fee = VALUES(daily_fee),
                    transmission_preference = VALUES(transmission_preference),
                    driving_preference = VALUES(driving_preference)
            ");
            $stmtUpdDriver->execute([$userId, $dailyFee, $transmissionPref, $drivingPref]);

            // Re-fetch driver
            $stmtDriver->execute([$userId]);
            $driver = $stmtDriver->fetch();

            $successMsg = 'Driver rates and transmission preferences updated successfully.';
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
                                <span>$<?= number_format($driver['daily_fee'], 2) ?> / day</span>
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

                <!-- 2. Driver Settings (Only shown for registered drivers) -->
                <?php if (!empty($user['is_driver'])): ?>
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <span class="material-symbols-outlined">drive_eta</span>
                            <div>
                                <h2>Driver Preferences & Rates</h2>
                                <p>Set your daily hire fee and transmission driving capabilities.</p>
                            </div>
                        </div>

                        <form method="POST" action="<?= baseUrl('/profile.php') ?>">
                            <input type="hidden" name="action" value="update_driver_settings">

                            <div class="form-grid-2">
                                <div class="form-field">
                                    <label for="daily_fee">Driver Daily Fee ($/day)</label>
                                    <input type="number" id="daily_fee" name="daily_fee" min="20" max="35" step="0.50" value="<?= number_format($driver['daily_fee'] ?? 25.00, 2, '.', '') ?>" required>
                                    <div class="field-hint">Permitted fee range is $20.00 – $35.00 per day.</div>
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

                            <div class="form-actions">
                                <button type="submit" class="btn-save">
                                    <span class="material-symbols-outlined" style="font-size:18px;">save</span> Save Driver Settings
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- 3. Security & Password -->
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

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
