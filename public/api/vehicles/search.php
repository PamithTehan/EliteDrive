<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

header('Content-Type: application/json');

try {
    $db = getDb();
    $category = $_GET['category'] ?? '';
    $maxPrice = $_GET['max_price'] ?? '';
    $pickupDate = $_GET['pickup_date'] ?? null;
    $returnDate = $_GET['return_date'] ?? null;
    $query = $_GET['q'] ?? '';

    if ($pickupDate) {
        $pickupDate = explode('T', $pickupDate)[0] . ' 00:00:00';
    }
    if ($returnDate) {
        $returnDate = explode('T', $returnDate)[0] . ' 23:59:59';
    }

    $sql = "SELECT v.id, v.make, v.model, v.category, v.daily_rate, p.photo_path 
            FROM vehicles v 
            LEFT JOIN vehicle_photos p ON v.id = p.vehicle_id AND p.is_primary = 1 
            WHERE v.status = 'approved'";
    $params = [];

    if ($query) {
        $sql .= " AND (v.make LIKE ? OR v.model LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }

    if ($category) {
        $categories = explode(',', $category);
        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $sql .= " AND v.category IN ($placeholders)";
        foreach ($categories as $cat) {
            $params[] = trim($cat);
        }
    }
    if ($maxPrice && is_numeric($maxPrice)) {
        $sql .= " AND v.daily_rate <= ?";
        $params[] = $maxPrice;
    }
    if ($pickupDate && $returnDate) {
        $sql .= " AND NOT EXISTS (
                    SELECT 1 FROM bookings b 
                    WHERE b.vehicle_id = v.id 
                    AND b.status NOT IN ('cancelled', 'rejected')
                    AND b.pickup_date < ? 
                    AND b.return_date > ?
                  )";
        $params[] = $returnDate;
        $params[] = $pickupDate;
    }

    $sql .= " ORDER BY v.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $vehicles = $stmt->fetchAll();

    foreach ($vehicles as &$v) {
        if (!empty($v['photo_path']) && strpos($v['photo_path'], 'http') !== 0) {
            $v['photo_path'] = baseUrl($v['photo_path']);
        }
    }

    echo json_encode($vehicles);
} catch (\Throwable $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
