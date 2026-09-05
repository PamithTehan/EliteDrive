<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

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
        $transmission = $_POST['transmission'] ?? 'Auto';
        $mileage = (int)($_POST['mileage'] ?? 0);
        $kmRate = (float)($_POST['km_rate'] ?? 0);
        $yom = (int)($_POST['yom'] ?? date('Y'));
        $yor = (int)($_POST['yor'] ?? date('Y'));

        if (!$make || !$model || !$category || !$dailyRate || !$location) {
            $error = 'Please fill in all required fields.';
        } else {
            $db = getDb();
            $stmt = $db->prepare('INSERT INTO vehicles (owner_id, make, model, category, daily_rate, location, transmission, mileage, km_rate, yom, yor, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending_review")');
            try {
                $stmt->execute([$user['id'], $make, $model, $category, $dailyRate, $location, $transmission, $mileage, $kmRate, $yom, $yor, $description]);
                $vehicleId = $db->lastInsertId();

                if (!empty($_FILES['photos']['name'][0])) {
                    $uploadDir = __DIR__ . '/../../public/assets/uploads/vehicles/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $isPrimary = 1;
                    foreach ($_FILES['photos']['tmp_name'] as $index => $tmpName) {
                        if ($_FILES['photos']['error'][$index] === UPLOAD_ERR_OK) {
                            $ext = strtolower(pathinfo($_FILES['photos']['name'][$index], PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                                $filename = uniqid('veh_') . '.' . $ext;
                                if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                                    $photoPath = '/assets/uploads/vehicles/' . $filename;
                                    $stmtPhoto = $db->prepare('INSERT INTO vehicle_photos (vehicle_id, photo_path, is_primary) VALUES (?, ?, ?)');
                                    $stmtPhoto->execute([$vehicleId, $photoPath, $isPrimary]);
                                    $isPrimary = 0;
                                }
                            }
                        }
                    }
                }

                $success = 'Vehicle added successfully and is pending admin review.';
            } catch (Exception $e) {
                $error = 'Failed to add vehicle.';
            }
        }
    }
}

$extraCss = ['dashboard'];
require_once __DIR__ . '/../../includes/partials/head.php';
?>

<div class="container grid grid-3" style="margin-top: var(--space-lg); margin-bottom: var(--space-lg);">
    <aside class="dashboard-sidebar" style="grid-column: 1 / 2;">
        <div class="card">
            <div class="card-body stack-sm">
                <h2 class="headline-md"><?= escapeHtml($user['full_name']) ?></h2>
                <p class="body-md">Owner Dashboard</p>
                <hr style="border:0; border-top: 1px solid var(--color-outline); margin: var(--space-md) 0;">
                <ul class="stack-sm" style="list-style:none; padding:0;">
                    <li><a href="<?= baseUrl('/owner/dashboard.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">My Vehicles</a></li>
                    <li><a href="<?= baseUrl('/owner/bookings.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">Bookings</a></li>
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
            
            <form method="POST" action="<?= baseUrl('/owner/vehicle_form.php') ?>" enctype="multipart/form-data">
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
                        <option value="Electric">Electric</option>
                    </select>
                </div>
                
                <div class="grid grid-3">
                    <div class="form-group">
                        <label class="form-label" for="transmission">Transmission</label>
                        <select id="transmission" name="transmission" class="input" required>
                            <option value="Auto">Auto</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="yom">Year of Manufacture (YOM)</label>
                        <input type="number" id="yom" name="yom" class="input" required min="1900" max="2100" placeholder="e.g. 2022">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="yor">Year of Registration (YOR)</label>
                        <input type="number" id="yor" name="yor" class="input" required min="1900" max="2100" placeholder="e.g. 2023">
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="mileage">Mileage (Total km)</label>
                        <input type="number" id="mileage" name="mileage" class="input" required min="0" placeholder="e.g. 15000">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="km_rate">Efficiency (km/l or km/charge)</label>
                        <input type="number" id="km_rate" name="km_rate" class="input" required min="0" step="0.1" placeholder="e.g. 15.5">
                    </div>
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
                
                <div class="form-group">
                    <label class="form-label" for="photos">Photos</label>
                    <div class="file-drop" id="file-drop-area">
                        <p class="body-md" style="color:var(--color-secondary);">Drag & drop photos here or click to browse.</p>
                        <input type="file" id="photos" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('photos').click()" style="margin-top: 10px;">Browse Files</button>
                    </div>
                    <div id="file-preview-list" style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;"></div>
                    <p class="body-sm" style="color:var(--color-secondary); margin-top: 4px;">You can select multiple photos. The first photo will be the primary image.</p>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit for Approval</button>
            </form>
        </div>
    </main>
</div>

<style>
.file-drop {
    border: 2px dashed var(--color-border);
    border-radius: var(--radius-md);
    padding: 30px;
    text-align: center;
    background: var(--color-surface);
    transition: all 0.2s ease;
}
.file-drop.highlight {
    border-color: var(--color-primary);
    background: rgba(37, 99, 235, 0.05);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dropArea = document.getElementById('file-drop-area');
    const fileInput = document.getElementById('photos');
    const previewList = document.getElementById('file-preview-list');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('highlight'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('highlight'), false);
    });

    dropArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        fileInput.files = files; 
        handleFiles(files);
    }
    
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        previewList.innerHTML = '';
        [...files].forEach(file => {
            if (file.type.startsWith('image/')) {
                let reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onloadend = function() {
                    let img = document.createElement('img');
                    img.src = reader.result;
                    img.style.width = '80px';
                    img.style.height = '80px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = 'var(--radius-sm)';
                    img.style.border = '1px solid var(--color-border)';
                    previewList.appendChild(img);
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
