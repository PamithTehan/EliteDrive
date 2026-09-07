<?php
// Pagination Setup
$perPage = 10;
$pageBorrowers = isset($_GET['page_borrowers']) ? max(1, (int)$_GET['page_borrowers']) : 1;
$pageOwners = isset($_GET['page_owners']) ? max(1, (int)$_GET['page_owners']) : 1;
$pageDrivers = isset($_GET['page_drivers']) ? max(1, (int)$_GET['page_drivers']) : 1;
$pageVehicles = isset($_GET['page_vehicles']) ? max(1, (int)$_GET['page_vehicles']) : 1;
$pageRejections = isset($_GET['page_rejections']) ? max(1, (int)$_GET['page_rejections']) : 1;
$pageBookings = isset($_GET['page_bookings']) ? max(1, (int)$_GET['page_bookings']) : 1;
$bookingStatus = $_GET['booking_status'] ?? '';

$offsetBorrowers = ($pageBorrowers - 1) * $perPage;
$offsetOwners = ($pageOwners - 1) * $perPage;
$offsetDrivers = ($pageDrivers - 1) * $perPage;
$offsetVehicles = ($pageVehicles - 1) * $perPage;
$offsetRejections = ($pageRejections - 1) * $perPage;
$offsetBookings = ($pageBookings - 1) * $perPage;

$db = getDb();

// Fetch Borrowers
$totalBorrowers = $db->query("SELECT COUNT(*) FROM users WHERE is_borrower = 1")->fetchColumn();
$stmtBorrowers = $db->prepare("SELECT id, full_name, email, contact_number, created_at FROM users WHERE is_borrower = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmtBorrowers->bindValue(1, $perPage, PDO::PARAM_INT);
$stmtBorrowers->bindValue(2, $offsetBorrowers, PDO::PARAM_INT);
$stmtBorrowers->execute();
$borrowers = $stmtBorrowers->fetchAll();
$totalPagesBorrowers = ceil($totalBorrowers / $perPage);

// Fetch Owners
$totalOwners = $db->query("SELECT COUNT(*) FROM users WHERE is_owner = 1")->fetchColumn();
$stmtOwners = $db->prepare("SELECT id, full_name, email, contact_number, created_at FROM users WHERE is_owner = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmtOwners->bindValue(1, $perPage, PDO::PARAM_INT);
$stmtOwners->bindValue(2, $offsetOwners, PDO::PARAM_INT);
$stmtOwners->execute();
$owners = $stmtOwners->fetchAll();
$totalPagesOwners = ceil($totalOwners / $perPage);

// Fetch Drivers
$totalDrivers = $db->query("SELECT COUNT(*) FROM users WHERE is_driver = 1")->fetchColumn();
$stmtDrivers = $db->prepare("
    SELECT u.id, u.full_name, u.email, u.contact_number, u.created_at, d.daily_fee, d.transmission_preference 
    FROM users u 
    JOIN drivers d ON u.id = d.user_id 
    WHERE u.is_driver = 1 
    ORDER BY u.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmtDrivers->bindValue(1, $perPage, PDO::PARAM_INT);
$stmtDrivers->bindValue(2, $offsetDrivers, PDO::PARAM_INT);
$stmtDrivers->execute();
$drivers = $stmtDrivers->fetchAll();
$totalPagesDrivers = ceil($totalDrivers / $perPage);

// Fetch Vehicles
$totalVehicles = $db->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$stmtVehicles = $db->prepare("
    SELECT v.id, v.make, v.model, v.yom, v.category, v.status, u.full_name as owner_name 
    FROM vehicles v 
    JOIN users u ON v.owner_id = u.id 
    ORDER BY v.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmtVehicles->bindValue(1, $perPage, PDO::PARAM_INT);
$stmtVehicles->bindValue(2, $offsetVehicles, PDO::PARAM_INT);
$stmtVehicles->execute();
$vehicles = $stmtVehicles->fetchAll();
$totalPagesVehicles = ceil($totalVehicles / $perPage);

// Fetch Rejections
$totalRejections = $db->query("
    SELECT COUNT(*) FROM (
        SELECT 'Vehicle' as type FROM vehicles WHERE status = 'rejected'
        UNION ALL
        SELECT 'Driver License' as type FROM driving_licenses WHERE status = 'rejected'
    ) t
")->fetchColumn();

$stmtRejections = $db->prepare("
    SELECT 'Vehicle' as type, id as target_id, CONCAT(make, ' ', model) as name, rejection_reason as reason, updated_at as date 
    FROM vehicles WHERE status = 'rejected'
    UNION ALL
    SELECT 'Driver License' as type, dl.user_id as target_id, u.full_name as name, dl.rejection_reason as reason, dl.updated_at as date
    FROM driving_licenses dl JOIN users u ON u.id = dl.user_id WHERE dl.status = 'rejected'
    ORDER BY date DESC
    LIMIT ? OFFSET ?
");
$stmtRejections->bindValue(1, $perPage, PDO::PARAM_INT);
$stmtRejections->bindValue(2, $offsetRejections, PDO::PARAM_INT);
$stmtRejections->execute();
$rejections = $stmtRejections->fetchAll();
$totalPagesRejections = ceil($totalRejections / $perPage);

// Fetch Bookings
$bookingWhere = "";
$bookingParams = [];
if ($bookingStatus !== '') {
    $bookingWhere = "WHERE b.status = ?";
    $bookingParams[] = $bookingStatus;
}
$totalBookingsStmt = $db->prepare("SELECT COUNT(*) FROM bookings b $bookingWhere");
$totalBookingsStmt->execute($bookingParams);
$totalBookings = $totalBookingsStmt->fetchColumn();

$bookingParams[] = $perPage;
$bookingParams[] = $offsetBookings;

$stmtBookings = $db->prepare("
    SELECT b.id, b.pickup_date, b.return_date, b.status, b.total_price, 
           u.full_name as borrower_name, v.make, v.model, o.full_name as owner_name 
    FROM bookings b
    JOIN users u ON b.borrower_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users o ON v.owner_id = o.id
    $bookingWhere
    ORDER BY b.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmtBookings->execute($bookingParams);
$bookings = $stmtBookings->fetchAll();
$totalPagesBookings = ceil($totalBookings / $perPage);
