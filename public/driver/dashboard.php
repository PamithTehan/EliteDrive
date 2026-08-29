<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('driver');
$user = currentUser();
$db = getDb();

// Check license status
$stmt = $db->prepare('SELECT status FROM driving_licenses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
$stmt->execute([$user['id']]);
$licenseStatus = $stmt->fetchColumn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $licenseNumber = trim($_POST['license_number'] ?? '');
    $expiryDate = $_POST['expiry_date'] ?? '';
    $format = $_POST['upload_format'] ?? 'pdf';
    
    if (!$licenseNumber || !$expiryDate) {
        $error = 'License number and expiry date are required.';
    } else {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('INSERT INTO driving_licenses (user_id, license_number, expiry_date, upload_format, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$user['id'], $licenseNumber, $expiryDate, $format, 'pending']);
            $licenseId = $db->lastInsertId();
            
            $uploadDir = __DIR__ . '/../../storage/licenses/';
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
            $success = "License uploaded successfully. Please wait for admin verification.";
            $licenseStatus = 'pending';
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
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
                <p class="body-md">Driver Dashboard</p>
                <hr style="border:0; border-top: 1px solid var(--color-outline); margin: var(--space-md) 0;">
                <ul class="stack-sm" style="list-style:none; padding:0;">
                    <li><a href="<?= baseUrl('/driver/dashboard.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start; font-weight: bold;">Overview</a></li>
                    <li><a href="<?= baseUrl('/driver/assignments.php') ?>" class="btn btn-ghost" style="width:100%; justify-content:flex-start;">Assignments</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="dashboard-content" style="grid-column: 2 / 4;">
        <h1 class="headline-lg" style="margin-bottom: var(--space-lg);">Driver Overview</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: var(--space-md);"><?= escapeHtml($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: var(--space-md);"><?= escapeHtml($error) ?></div>
        <?php endif; ?>
        
        <?php if ($licenseStatus === 'verified'): ?>
            <div class="alert alert-success">
                <strong>You are ready to drive!</strong> Your license is verified and you can accept assignments.
            </div>
        <?php elseif ($licenseStatus === 'pending'): ?>
            <div class="alert alert-warning" style="background:#fef9c3; color:#713f12;">
                <strong>License under review.</strong> You cannot accept assignments until an admin verifies your license.
            </div>
        <?php elseif ($licenseStatus === 'rejected'): ?>
            <div class="alert alert-error">
                <strong>License rejected.</strong> Please upload a valid document.
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <h2 class="headline-md" style="margin-bottom: var(--space-sm);">Upload Driving License</h2>
                    <p class="body-md" style="margin-bottom: var(--space-md); color:var(--color-secondary);">You must upload a valid driving license to start accepting trips.</p>
                    

                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">License Number</label>
                            <input type="text" name="license_number" class="input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Upload Format</label>
                            <div style="display: flex; gap: var(--space-md);">
                                <label><input type="radio" name="upload_format" value="pdf" checked> PDF Document</label>
                                <label><input type="radio" name="upload_format" value="image"> Front & Back Images</label>
                            </div>
                        </div>

                        <!-- PDF Upload Zone -->
                        <div id="pdf-zone-container">
                            <div class="drop-zone" id="pdf-drop-zone">
                                <div class="drop-zone-icon">📄</div>
                                <div class="drop-zone-text" id="pdf-drop-zone-text">
                                    Drag and drop your PDF license here or click to browse
                                </div>
                                <input type="file" name="license_pdf" id="pdf-file-input" accept=".pdf">
                            </div>
                        </div>

                        <!-- Image Upload Zones -->
                        <div id="image-zone-container" style="display: none; gap: var(--space-md); grid-template-columns: 1fr 1fr;">
                            <div class="drop-zone" id="front-drop-zone" style="margin-bottom: 0;">
                                <div class="drop-zone-icon">🖼️</div>
                                <div class="drop-zone-text" id="front-drop-zone-text">
                                    Front Image<br><small>Drag or click</small>
                                </div>
                                <input type="file" name="license_front" id="front-file-input" accept=".jpg,.jpeg,.png">
                            </div>
                            
                            <div class="drop-zone" id="back-drop-zone" style="margin-bottom: 0;">
                                <div class="drop-zone-icon">🖼️</div>
                                <div class="drop-zone-text" id="back-drop-zone-text">
                                    Back Image<br><small>Drag or click</small>
                                </div>
                                <input type="file" name="license_back" id="back-file-input" accept=".jpg,.jpeg,.png">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: var(--space-md);">Upload License</button>
                    </form>
                    
                    <script>
                        // Toggle format visibility
                        const formatRadios = document.querySelectorAll('input[name="upload_format"]');
                        const pdfContainer = document.getElementById('pdf-zone-container');
                        const imageContainer = document.getElementById('image-zone-container');
                        
                        const pdfInput = document.getElementById('pdf-file-input');
                        const frontInput = document.getElementById('front-file-input');
                        const backInput = document.getElementById('back-file-input');

                        formatRadios.forEach(radio => {
                            radio.addEventListener('change', (e) => {
                                if (e.target.value === 'pdf') {
                                    pdfContainer.style.display = 'block';
                                    imageContainer.style.display = 'none';
                                    pdfInput.required = true;
                                    frontInput.required = false;
                                    backInput.required = false;
                                } else {
                                    pdfContainer.style.display = 'none';
                                    imageContainer.style.display = 'grid';
                                    pdfInput.required = false;
                                    frontInput.required = true;
                                    backInput.required = true;
                                }
                            });
                        });
                        
                        // Set initial required state
                        pdfInput.required = true;

                        // Setup Drop Zones Helper
                        function setupDropZone(zoneId, inputId, textId, defaultText) {
                            const zone = document.getElementById(zoneId);
                            const input = document.getElementById(inputId);
                            const text = document.getElementById(textId);

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
                    </script>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
