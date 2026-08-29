<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$db = getDb();
$stmt = $db->query(
    "SELECT v.id, u.full_name as owner_name, v.make, v.model, v.category, v.daily_rate
     FROM vehicles v
     JOIN users u ON u.id = v.owner_id
     WHERE v.status = 'pending_review'
     ORDER BY v.created_at ASC"
);
$vehicles = $stmt->fetchAll();

echo json_encode($vehicles);
