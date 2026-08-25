<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';

requireRole('admin');
header('Content-Type: application/json');

$db = getDb();
$stmt = $db->query(
    "SELECT dl.id, u.full_name, dl.license_number, dl.expiry_date, dl.document_path
     FROM driving_licenses dl 
     JOIN users u ON u.id = dl.user_id
     WHERE dl.status = 'pending'
     ORDER BY dl.created_at ASC"
);
$licenses = $stmt->fetchAll();

echo json_encode($licenses);
