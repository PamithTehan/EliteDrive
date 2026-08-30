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
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (!$email || !$password) {
            $error = 'Please fill in all fields.';
        } else {
            $db = getDb();
            $stmt = $db->prepare('
                SELECT u.*, 
                       (o.user_id IS NOT NULL) AS is_owner,
                       (d.user_id IS NOT NULL) AS is_driver,
                       (b.user_id IS NOT NULL) AS is_borrower
                FROM users u
                LEFT JOIN owners o ON u.id = o.user_id
                LEFT JOIN drivers d ON u.id = d.user_id
                LEFT JOIN borrowers b ON u.id = b.user_id
                WHERE u.email = ?
            ');
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
    }
}

$extraCss = ['auth'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div class="container">
    <div class="auth-container">
        <h1 class="headline-lg auth-title">Welcome Back</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= escapeHtml($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="input" required value="<?= escapeHtml($_POST['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="input" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Log In</button>
        </form>
        
        <div class="auth-footer">
            Don't have an account? <a href="<?= baseUrl('/register.php') ?>" style="color: var(--color-accent);">Sign Up</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
