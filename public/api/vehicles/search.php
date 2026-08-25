<?php
require __DIR__ . '/../../../includes/db.php';
require __DIR__ . '/../../../includes/functions.php';

header('Content-Type: application/json');

try {
    $db = getDb();
    $category = $_GET['category'] ?? '';
    $maxPrice = $_GET['max_price'] ?? '';

    $sql = "SELECT v.id, v.make, v.model, v.category, v.daily_rate, p.photo_path 
            FROM vehicles v 
            LEFT JOIN vehicle_photos p ON v.id = p.vehicle_id AND p.is_primary = 1 
            WHERE v.status = 'approved'";
    $params = [];

    if ($category) {
        $sql .= " AND v.category = ?";
        $params[] = $category;
    }
    if ($maxPrice && is_numeric($maxPrice)) {
        $sql .= " AND v.daily_rate <= ?";
        $params[] = $maxPrice;
    }

    $sql .= " ORDER BY v.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $vehicles = $stmt->fetchAll();

    echo json_encode($vehicles);
} catch (\Throwable $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
