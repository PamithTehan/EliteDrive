<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';

header('Content-Type: application/json');

$db = getDb();
// Simplified available driver fetch
// In a real app this would check driver schedules, locations, and vehicle category matches.
// For now, we return any user marked as driver who has a verified license.

$stmt = $db->query(
    "SELECT u.id, u.full_name
     FROM users u
     JOIN driving_licenses dl ON dl.user_id = u.id
     WHERE u.is_driver = 1 AND dl.status = 'verified' AND dl.expiry_date > NOW()"
);
$drivers = $stmt->fetchAll();

echo json_encode($drivers);
