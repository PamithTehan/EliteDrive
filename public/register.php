<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';

if (currentUser()) {
    header('Location: /');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $error = 'Invalid session token. Please try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $contactNumber = trim($_POST['contact_number'] ?? '');
        
        $isOwner = isset($_POST['role_owner']) ? 1 : 0;
        $isDriver = isset($_POST['role_driver']) ? 1 : 0;
        $isBorrower = isset($_POST['role_borrower']) ? 1 : 0;

        if (!$fullName || !$email || !$password) {
            $error = 'Please fill in all required fields.';
        } elseif (!$isOwner && !$isDriver && !$isBorrower) {
            $error = 'Please select at least one role.';
        } else {
            $db = getDb();
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare('INSERT INTO users (full_name, email, password_hash, contact_number, is_owner, is_driver, is_borrower) VALUES (?, ?, ?, ?, ?, ?, ?)');
                try {
                    $stmt->execute([$fullName, $email, $hash, $contactNumber, $isOwner, $isDriver, $isBorrower]);
                    // Log the user in
                    $userId = $db->lastInsertId();
                    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
                    $stmt->execute([$userId]);
                    $userRow = $stmt->fetch();
                    unset($userRow['password_hash']);
                    loginUser($userRow);
                    
                    header('Location: /');
                    exit;
                } catch (Exception $e) {
                    $error = 'An error occurred during registration. Please try again.';
                }
            }
        }
    }
}

$extraCss = ['auth'];
require __DIR__ . '/../includes/partials/head.php';
?>

<div class="container">
    <div class="auth-container">
        <h1 class="headline-lg auth-title">Create an Account</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= escapeHtml($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/register.php">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            
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
                <input type="password" id="password" name="password" class="input" required>
            </div>

            <div class="form-group">
                <label class="form-label">How will you use EliteDrive? (Select all that apply)</label>
                <div class="role-options">
                    <label class="role-option">
                        <input type="checkbox" name="role_borrower" value="1" <?= isset($_POST['role_borrower']) ? 'checked' : '' ?>>
                        <span>I want to rent vehicles</span>
                    </label>
                    <label class="role-option">
                        <input type="checkbox" name="role_owner" value="1" <?= isset($_POST['role_owner']) ? 'checked' : '' ?>>
                        <span>I want to list my vehicles</span>
                    </label>
                    <label class="role-option">
                        <input type="checkbox" name="role_driver" value="1" <?= isset($_POST['role_driver']) ? 'checked' : '' ?>>
                        <span>I want to be a hired driver</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Sign Up</button>
        </form>
        
        <div class="auth-footer">
            Already have an account? <a href="<?= baseUrl('/login.php') ?>" style="color: var(--color-accent);">Log In</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
