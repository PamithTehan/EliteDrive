<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireRole('admin');
header('Content-Type: application/json');

$db = getDb();

$stmt = $db->prepare('
    SELECT b.id, v.id as vehicle_id, v.make, v.model, v.transmission, u.full_name as renter_name, b.pickup_date, b.return_date, b.pickup_location, b.return_location
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users u ON b.borrower_id = u.id
    WHERE b.driver_arrangement = "hired" 
      AND b.assigned_driver_id IS NULL 
      AND b.status = "pending_verification"
    ORDER BY b.created_at ASC
');
$stmt->execute();
$bookings = $stmt->fetchAll();

echo json_encode($bookings);
