<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$db = getDb();
$category = $_GET['category'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';

$sql = "SELECT id, make, model, category, daily_rate FROM vehicles WHERE status = 'approved'";
$params = [];

if ($category) {
    $sql .= " AND category = ?";
    $params[] = $category;
}
if ($maxPrice && is_numeric($maxPrice)) {
    $sql .= " AND daily_rate <= ?";
    $params[] = $maxPrice;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

echo json_encode($vehicles);
