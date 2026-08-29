<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
$user = currentUser();

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!$id || !in_array($type, ['license', 'id', 'vehicle_doc'], true)) {
    http_response_code(400);
    exit('Invalid request');
}

$db = getDb();
$path = null;
$isOwnerOfDoc = false;

if ($type === 'license') {
    $stmt = $db->prepare('SELECT user_id, document_path FROM driving_licenses WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $isOwnerOfDoc = $row['user_id'] === $user['id'];
        $path = $row['document_path'];
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

$mime = mime_content_type($fullPath);
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="document"');
readfile($fullPath);
