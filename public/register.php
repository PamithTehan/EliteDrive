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
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!$fullName || !$email || !$password || !$confirmPassword) {
            $error = 'Please fill in all required fields.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            // Check if email already exists
            $db = getDb();
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email is already registered.';
            } else {
                // Save Phase 1 data to session and redirect
                $_SESSION['register_phase1'] = [
                    'full_name' => $fullName,
                    'email' => $email,
                    'contact_number' => $contactNumber,
                    'password' => $password
                ];
                header('Location: ' . baseUrl('/register_step2.php'));
                exit;
            }
        }
    }
}

// Prefill if back from step 2
$prefill = $_SESSION['register_phase1'] ?? [];

$extraCss = ['auth'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div class="container">
    <div class="auth-container">
        <h1 class="headline-lg auth-title">Create an Account - Step 1</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= escapeHtml($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="input" required value="<?= escapeHtml($_POST['full_name'] ?? $prefill['full_name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="input" required value="<?= escapeHtml($_POST['email'] ?? $prefill['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="contact_number">Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number" class="input" value="<?= escapeHtml($_POST['contact_number'] ?? $prefill['contact_number'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="input" required value="<?= escapeHtml($prefill['password'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="input" required value="<?= escapeHtml($prefill['password'] ?? '') ?>">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Next Step</button>
        </form>
        
        <div class="auth-footer">
            Already have an account? <a href="<?= baseUrl('/login.php') ?>" style="color: var(--color-accent);">Log In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
