<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /fleet/search.php');
    exit;
}

$db = getDb();
$stmt = $db->prepare('SELECT v.*, u.full_name as owner_name FROM vehicles v JOIN users u ON u.id = v.owner_id WHERE v.id = ? AND v.status = "approved"');
$stmt->execute([$id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    http_response_code(404);
    echo "Vehicle not found.";
    exit;
}

$extraCss = ['fleet-search'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<div class="detail-hero">
    <h1 class="headline-xl" style="color:var(--color-primary);"><?= escapeHtml($vehicle['make'] . ' ' . $vehicle['model']) ?></h1>
</div>

<div class="container grid grid-3" style="margin-top: var(--space-lg); margin-bottom: var(--space-lg);">
    <main style="grid-column: 1 / 3;" class="detail-content">
        <div class="card" style="margin-bottom: var(--space-lg);">
            <div class="card-body">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-md);">
                    <span class="badge badge-<?= strtolower($vehicle['category']) ?>"><?= escapeHtml($vehicle['category']) ?></span>
                    <span class="label-md" style="color:var(--color-secondary);">Listed by <?= escapeHtml($vehicle['owner_name']) ?></span>
                </div>
                <h2 class="headline-md" style="margin-bottom: var(--space-sm);">About this vehicle</h2>
                <p class="body-md" style="white-space:pre-wrap;"><?= escapeHtml($vehicle['description'] ?? 'No description provided.') ?></p>
            </div>
        </div>
    </main>
    
    <aside style="grid-column: 3 / 4; position:relative; z-index: 10; margin-top: -64px;">
        <div class="booking-card">
            <h3 class="headline-lg" style="color:var(--color-primary); margin-bottom: var(--space-sm);">$<?= escapeHtml($vehicle['daily_rate']) ?> <span class="body-md" style="color:var(--color-secondary); font-weight:normal;">/ day</span></h3>
            
            <form method="POST" action="<?= baseUrl('/borrower/booking_confirm.php') ?>" id="booking-form">
                <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">
                
                <div class="form-group">
                    <label class="form-label" for="pickup_date">Pick-up Date</label>
                    <input type="date" id="pickup_date" name="pickup_date" class="input" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="return_date">Return Date</label>
                    <input type="date" id="return_date" name="return_date" class="input" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="pickup_location">Pick-up Location</label>
                    <input type="text" id="pickup_location" name="pickup_location" class="input" required placeholder="Where will you pick this up?">
                </div>
                
                <fieldset class="form-group" style="border: 1px solid var(--color-outline); padding: var(--space-sm); border-radius: var(--radius-sm);">
                    <legend class="label-md">Who will drive?</legend>
                    <label style="display:block; margin-bottom:8px;">
                        <input type="radio" name="driver_arrangement" value="self" checked> I'll drive myself
                    </label>
                    <label style="display:block; margin-bottom:8px;">
                        <input type="radio" name="driver_arrangement" value="owner"> Owner drives (chauffeur)
                    </label>
                    <label style="display:block;">
                        <input type="radio" name="driver_arrangement" value="hired"> Assign a hired driver
                    </label>
                </fieldset>
                
                <div id="hired-driver-block" style="display:none;" class="form-group">
                    <label class="form-label" for="driver_id">Select a Driver</label>
                    <select name="driver_id" id="driver-select" class="input">
                        <option value="">Loading drivers...</option>
                    </select>
                </div>
                
                <?php if (currentUser()): ?>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Continue to Booking</button>
                <?php else: ?>
                    <a href="<?= baseUrl('/login.php') ?>" class="btn btn-primary" style="width: 100%; display:block; text-align:center;">Log in to Book</a>
                <?php endif; ?>
            </form>
        </div>
    </aside>
</div>

<script src="/assets/js/booking-form.js"></script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
