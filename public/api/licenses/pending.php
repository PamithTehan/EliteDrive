<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireRole('admin');
header('Content-Type: application/json');

$db = getDb();
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$countStmt = $db->query("SELECT COUNT(*) FROM driving_licenses dl WHERE dl.status = 'pending'");
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$stmt = $db->prepare(
    "SELECT dl.id, u.full_name, dl.license_number, dl.expiry_date, dl.upload_format
     FROM driving_licenses dl 
     JOIN users u ON u.id = dl.user_id
     WHERE dl.status = 'pending'
     ORDER BY dl.created_at ASC
     LIMIT ? OFFSET ?"
);
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$licenses = $stmt->fetchAll();

echo json_encode([
    'data' => $licenses,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords
    ]
]);
