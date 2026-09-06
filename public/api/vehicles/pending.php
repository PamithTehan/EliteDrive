<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
requireRole('admin');

$db = getDb();
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$countStmt = $db->query("SELECT COUNT(*) FROM vehicles v WHERE v.status = 'pending_review'");
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$stmt = $db->prepare(
    "SELECT v.id, u.full_name as owner_name, v.make, v.model, v.category, v.daily_rate
     FROM vehicles v
     JOIN users u ON u.id = v.owner_id
     WHERE v.status = 'pending_review'
     ORDER BY v.created_at ASC
     LIMIT ? OFFSET ?"
);
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$vehicles = $stmt->fetchAll();

echo json_encode([
    'data' => $vehicles,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords
    ]
]);
