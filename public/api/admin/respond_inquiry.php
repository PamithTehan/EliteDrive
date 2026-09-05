<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

$inquiryId = $_POST['inquiry_id'] ?? null;
$response = trim($_POST['response'] ?? '');
$csrfToken = $_POST['csrf_token'] ?? '';

if (!csrfCheck($csrfToken)) {
    http_response_code(403);
    exit("Invalid CSRF token");
}

if (!$inquiryId || empty($response)) {
    http_response_code(400);
    exit("Missing required fields");
}

try {
    $stmt = getDb()->prepare("UPDATE inquiries SET status = 'responded', admin_response = ?, responded_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$response, $inquiryId]);
    
    // In a real application, you would send an email here using a library like PHPMailer.
    // For this prototype, we're just updating the database to simulate it.
    
    header('Location: ' . baseUrl('/admin/inquiries.php?success=1'));
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("Database error");
}
