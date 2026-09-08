<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireRole('admin');
header('Content-Type: application/json');

$db = getDb();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$countStmt = $db->query('SELECT COUNT(*) FROM bookings b WHERE b.driver_arrangement = "hired" AND b.assigned_driver_id IS NULL AND b.status IN ("pending_assignment", "pending_verification", "confirmed", "active")');
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$stmt = $db->prepare('
    SELECT b.id, v.id as vehicle_id, v.make, v.model, v.transmission, u.full_name as renter_name, b.pickup_date, b.return_date, b.pickup_location, b.return_location
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users u ON b.borrower_id = u.id
    WHERE b.driver_arrangement = "hired" 
      AND b.assigned_driver_id IS NULL 
      AND b.status IN ("pending_assignment", "pending_verification", "confirmed", "active")
    ORDER BY b.created_at ASC
    LIMIT ? OFFSET ?
');
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll();

echo json_encode([
    'data' => $bookings,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords
    ]
]);
