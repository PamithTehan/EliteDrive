<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';

header('Content-Type: application/json');

$db = getDb();
$pickupDate = $_GET['pickup_date'] ?? null;
$returnDate = $_GET['return_date'] ?? null;

if ($pickupDate) $pickupDate = str_replace('T', ' ', $pickupDate);
if ($returnDate) $returnDate = str_replace('T', ' ', $returnDate);

$sql = "SELECT d.user_id as id, u.full_name
        FROM drivers d
        JOIN users u ON u.id = d.user_id
        JOIN driving_licenses dl ON dl.user_id = d.user_id
        WHERE dl.status = 'verified' AND dl.expiry_date > NOW()";
$params = [];

if ($pickupDate && $returnDate) {
    $sql .= " AND NOT EXISTS (
                SELECT 1 FROM bookings b 
                WHERE b.assigned_driver_id = d.user_id 
                AND b.status NOT IN ('cancelled', 'rejected')
                AND b.pickup_date < ? 
                AND b.return_date > ?
              )";
    $params[] = $returnDate;
    $params[] = $pickupDate;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$drivers = $stmt->fetchAll();

echo json_encode($drivers);
