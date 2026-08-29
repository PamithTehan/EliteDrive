<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
$user = currentUser();

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!$id || !in_array($type, ['license_pdf', 'license_front', 'license_back', 'id', 'vehicle_doc'], true)) {
    http_response_code(400);
    exit('Invalid request');
}

$db = getDb();
$path = null;
$isOwnerOfDoc = false;

if (strpos($type, 'license_') === 0) {
    $stmt = $db->prepare('SELECT user_id FROM driving_licenses WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $isOwnerOfDoc = $row['user_id'] === $user['id'];
        
        if ($type === 'license_pdf') {
            $stmt = $db->prepare('SELECT file_path FROM driving_license_pdfs WHERE license_id = ?');
            $stmt->execute([$id]);
            $path = $stmt->fetchColumn();
        } elseif ($type === 'license_front') {
            $stmt = $db->prepare('SELECT front_image_path FROM driving_license_images WHERE license_id = ?');
            $stmt->execute([$id]);
            $path = $stmt->fetchColumn();
        } elseif ($type === 'license_back') {
            $stmt = $db->prepare('SELECT back_image_path FROM driving_license_images WHERE license_id = ?');
            $stmt->execute([$id]);
            $path = $stmt->fetchColumn();
        }
    }
}
// Expand later for ID or vehicle docs

$isAdmin = !empty($user['is_admin']);

if (!$isOwnerOfDoc && !$isAdmin) {
    http_response_code(403);
    exit('Forbidden');
}

if (!$path) {
    http_response_code(404);
    exit('Document not found');
}

$fullPath = __DIR__ . '/../storage/' . $path;
if (!file_exists($fullPath)) {
    http_response_code(404);
    exit('File missing on disk');
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimes = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];
$mime = $mimes[$ext] ?? mime_content_type($fullPath);

if (!$mime) {
    $mime = 'application/octet-stream';
}

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
