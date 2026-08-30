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
$stmt = $db->prepare('SELECT v.*, u.full_name as owner_name, p.photo_path 
                      FROM vehicles v 
                      JOIN users u ON u.id = v.owner_id 
                      LEFT JOIN vehicle_photos p ON p.vehicle_id = v.id AND p.is_primary = 1 
                      WHERE v.id = ? AND v.status = "approved"');
$stmt->execute([$id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    http_response_code(404);
    exit('Vehicle not found.');
}

$stmtPhotos = $db->prepare('SELECT photo_path FROM vehicle_photos WHERE vehicle_id = ? ORDER BY is_primary DESC, id ASC');
$stmtPhotos->execute([$id]);
$allPhotos = $stmtPhotos->fetchAll(PDO::FETCH_COLUMN);

$isOwnerDriver = false;
$stmt = $db->prepare("
    SELECT d.user_id, dl.status as license_status 
    FROM drivers d 
    LEFT JOIN driving_licenses dl ON dl.user_id = d.user_id 
    WHERE d.user_id = ? ORDER BY dl.created_at DESC LIMIT 1
");
$stmt->execute([$vehicle['owner_id']]);
$ownerDriverData = $stmt->fetch();

if ($ownerDriverData && $ownerDriverData['license_status'] === 'verified') {
    $isOwnerDriver = true;
}

$extraCss = ['fleet-search'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<?php
// Fallback to a high-quality stock car image if no photo is uploaded
$imageUrl = !empty($vehicle['photo_path']) 
    ? (strpos($vehicle['photo_path'], 'http') === 0 ? $vehicle['photo_path'] : baseUrl($vehicle['photo_path'])) 
    : baseUrl('/assets/images/placeholder-car.jpg');

$bgStyle = "background-image: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7)), url('" . escapeHtml($imageUrl) . "'); background-size: cover; background-position: center;";
$titleColor = "color: white;";
?>
<div class="detail-hero" style="<?= $bgStyle ?>">
    <h1 class="headline-xl" style="<?= $titleColor ?>"><?= escapeHtml($vehicle['make'] . ' ' . $vehicle['model']) ?></h1>
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
                
                <?php if (!empty($allPhotos)): ?>
                <h2 class="headline-md" style="margin-top: var(--space-lg); margin-bottom: var(--space-sm);">Photos</h2>
                <div class="photo-slider-container" style="position: relative; overflow: hidden; border-radius: var(--radius-md); border: 1px solid var(--color-outline);">
                    <div class="photo-slider" style="display: flex; transition: transform 0.3s ease;">
                        <?php foreach ($allPhotos as $photo): ?>
                            <?php $pUrl = (strpos($photo, 'http') === 0) ? $photo : baseUrl($photo); ?>
                            <img src="<?= escapeHtml($pUrl) ?>" style="width: 100%; max-height: 400px; flex-shrink: 0; object-fit: cover;" alt="Vehicle Photo">
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($allPhotos) > 1): ?>
                    <button class="slider-btn prev-btn" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.6); color: white; border: none; width: 40px; height: 40px; cursor: pointer; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">&#10094;</button>
                    <button class="slider-btn next-btn" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.6); color: white; border: none; width: 40px; height: 40px; cursor: pointer; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">&#10095;</button>
                    <?php endif; ?>
                </div>
                
                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const slider = document.querySelector('.photo-slider');
                    const prevBtn = document.querySelector('.prev-btn');
                    const nextBtn = document.querySelector('.next-btn');
                    let currentIndex = 0;
                    const totalSlides = <?= count($allPhotos) ?>;
                    
                    if (prevBtn && nextBtn && slider) {
                        prevBtn.addEventListener('click', () => {
                            currentIndex = (currentIndex > 0) ? currentIndex - 1 : totalSlides - 1;
                            slider.style.transform = `translateX(-${currentIndex * 100}%)`;
                        });
                        nextBtn.addEventListener('click', () => {
                            currentIndex = (currentIndex < totalSlides - 1) ? currentIndex + 1 : 0;
                            slider.style.transform = `translateX(-${currentIndex * 100}%)`;
                        });
                    }
                });
                </script>
                <?php endif; ?>
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
                    <?php if ($isOwnerDriver): ?>
                    <label style="display:block; margin-bottom:8px;">
                        <input type="radio" name="driver_arrangement" value="owner"> Owner drives (chauffeur)
                    </label>
                    <?php endif; ?>
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

<script>window.appBaseUrl = "<?= rtrim(baseUrl(), '/') ?>";</script>
<script src="<?= baseUrl('/assets/js/booking-form.js') ?>"></script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
