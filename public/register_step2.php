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

        if (!$isOwner && !$isDriver && !$isBorrower) {
            $error = 'Please select at least one role.';
        } else {
            $phase1 = $_SESSION['register_phase1'];
            $hash = password_hash($phase1['password'], PASSWORD_BCRYPT);
            
            $db = getDb();
            try {
                $db->beginTransaction();

                // Insert into main users table
                $stmt = $db->prepare('INSERT INTO users (full_name, email, password_hash, contact_number) VALUES (?, ?, ?, ?)');
                $stmt->execute([$phase1['full_name'], $phase1['email'], $hash, $phase1['contact_number']]);
                $userId = $db->lastInsertId();

                // Insert into child tables
                if ($isBorrower) {
                    $stmt = $db->prepare('INSERT INTO borrowers (user_id) VALUES (?)');
                    $stmt->execute([$userId]);
                }
                
                if ($isOwner) {
                    $stmt = $db->prepare('INSERT INTO owners (user_id) VALUES (?)');
                    $stmt->execute([$userId]);
                }
                
                if ($isDriver) {
                    // Only apply own_vehicles preference if they are also an owner
                    $pref = ($isOwner && $drivingPreference === 'own_vehicles') ? 'own_vehicles' : 'any_vehicle';
                    $stmt = $db->prepare('INSERT INTO drivers (user_id, driving_preference) VALUES (?, ?)');
                    $stmt->execute([$userId, $pref]);
                }

                $db->commit();
                
                // Fetch user data for login
                $stmt = $db->prepare('
                    SELECT u.*, 
                           (o.user_id IS NOT NULL) AS is_owner,
                           (d.user_id IS NOT NULL) AS is_driver,
                           (b.user_id IS NOT NULL) AS is_borrower
                    FROM users u
                    LEFT JOIN owners o ON u.id = o.user_id
                    LEFT JOIN drivers d ON u.id = d.user_id
                    LEFT JOIN borrowers b ON u.id = b.user_id
                    WHERE u.id = ?
                ');
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

            <div class="form-group" id="driver-preference-group" style="display: none; background: var(--color-surface); padding: 15px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
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
        const prefGroup = document.getElementById('driver-preference-group');

        function togglePrefGroup() {
            if (ownerCheckbox.checked && driverCheckbox.checked) {
                prefGroup.style.display = 'block';
            } else {
                prefGroup.style.display = 'none';
            }
        }

        ownerCheckbox.addEventListener('change', togglePrefGroup);
        driverCheckbox.addEventListener('change', togglePrefGroup);
        togglePrefGroup();
    });
</script>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
