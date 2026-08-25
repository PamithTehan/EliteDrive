<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/functions.php';

requireRole('owner');
$user = currentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $error = 'Invalid session token.';
    } else {
        $make = trim($_POST['make'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $category = $_POST['category'] ?? '';
        $dailyRate = $_POST['daily_rate'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$make || !$model || !$category || !$dailyRate || !$location) {
            $error = 'Please fill in all required fields.';
        } else {
            $db = getDb();
            $stmt = $db->prepare('INSERT INTO vehicles (owner_id, make, model, category, daily_rate, location, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, "pending_review")');
            try {
                $stmt->execute([$user['id'], $make, $model, $category, $dailyRate, $location, $description]);
                $success = 'Vehicle added successfully and is pending admin review.';
            } catch (Exception $e) {
                $error = 'Failed to add vehicle.';
            }
        }
    }
}

$extraCss = ['dashboard'];
require __DIR__ . '/../../includes/partials/head.php';
?>

<div class="container grid grid-3" style="margin-top: var(--space-lg); margin-bottom: var(--space-lg);">
    <aside class="dashboard-sidebar" style="grid-column: 1 / 2;">
        <div class="card">
            <div class="card-body stack-sm">
                <h2 class="headline-md"><?= escapeHtml($user['full_name']) ?></h2>
                <p class="body-md">Owner Dashboard</p>
                <hr style="border:0; border-top: 1px solid var(--color-outline); margin: var(--space-md) 0;">
                <ul class="stack-sm" style="list-style:none; padding:0;">
                    <li><a href="/owner/dashboard.php" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">My Vehicles</a></li>
                    <li><a href="/owner/bookings.php" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">Bookings</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="dashboard-content" style="grid-column: 2 / 4;">
        <div class="form-container">
            <h1 class="headline-lg">Add a Vehicle</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= escapeHtml($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= escapeHtml($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="/owner/vehicle_form.php">
                <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="make">Make</label>
                        <input type="text" id="make" name="make" class="input" required placeholder="e.g. Tesla">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="model">Model</label>
                        <input type="text" id="model" name="model" class="input" required placeholder="e.g. Model 3">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="category">Category</label>
                    <select id="category" name="category" class="input" required>
                        <option value="">Select a category</option>
                        <option value="Premium">Premium</option>
                        <option value="Luxury">Luxury</option>
                        <option value="Budget">Budget</option>
                        <option value="Offroad">Offroad</option>
                    </select>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="daily_rate">Daily Rate ($)</label>
                        <input type="number" id="daily_rate" name="daily_rate" class="input" required min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="location">Location</label>
                        <input type="text" id="location" name="location" class="input" required placeholder="City or Zip">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="input" rows="4" placeholder="Optional details about the vehicle..."></textarea>
                </div>
                
                <!-- Placeholder for photo upload -->
                <div class="form-group">
                    <label class="form-label">Photos (Coming soon)</label>
                    <div class="file-drop">
                        <p class="body-md" style="color:var(--color-secondary);">Photo upload functionality will be added in a later phase.</p>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit for Approval</button>
            </form>
        </div>
    </main>
</div>

<?php require __DIR__ . '/../../includes/partials/footer.php'; ?>
