<?php
/**
 * File: functions.php
 * Purpose: Global helper functions
 */
// includes/functions.php
if (defined('FUNCTIONS_PHP_LOADED')) return;



function getConfig(): array {
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }
    return $config;
}
define('FUNCTIONS_PHP_LOADED', 1);

function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCheck(string $token): bool {
    return hash_equals($_SESSION['csrf'] ?? '', $token);
}

function escapeHtml(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function getVehicleOwnerId(int $vehicleId, PDO $db): ?int {
    $stmt = $db->prepare('SELECT owner_id FROM vehicles WHERE id = ?');
    $stmt->execute([$vehicleId]);
    return $stmt->fetchColumn() ?: null;
}

function calculateRentalBreakdown(array $booking, PDO $db): array {
    $breakdown = [
        'vehicle_daily_rate' => 0.0,
        'driver_daily_fee' => 0.0,
        'effective_daily_rate' => 0.0,
        'total_days' => 0,
        'remaining_blocks' => 0,
        'total_blocks' => 0,
        'block_rate' => 0.0,
        'grace_period_applied' => false,
        'base_price' => 0.0,
        'commission_rate' => 0.0,
        'commission_amount' => 0.0,
        'owner_earnings' => 0.0,
        'driver_earnings' => 0.0,
        'total_price' => 0.0
    ];

    if (empty($booking['vehicle_id']) || empty($booking['pickup_date']) || empty($booking['return_date'])) {
        return $breakdown;
    }

    $stmt = $db->prepare('SELECT daily_rate FROM vehicles WHERE id = ?');
    $stmt->execute([$booking['vehicle_id']]);
    $breakdown['vehicle_daily_rate'] = (float) $stmt->fetchColumn();

    if (!empty($booking['assigned_driver_id']) && in_array($booking['driver_arrangement'] ?? '', ['hired', 'owner'])) {
        $stmtD = $db->prepare('SELECT daily_fee FROM drivers WHERE user_id = ?');
        $stmtD->execute([$booking['assigned_driver_id']]);
        $breakdown['driver_daily_fee'] = (float) ($stmtD->fetchColumn() ?: 0.0);
    }

    $breakdown['effective_daily_rate'] = $breakdown['vehicle_daily_rate'] + $breakdown['driver_daily_fee'];
    $breakdown['block_rate'] = $breakdown['effective_daily_rate'] / 4; // 6-hour blocks (4 per day)

    $pickup = new DateTime($booking['pickup_date']);
    $return = new DateTime($booking['return_date']);
    $diffSeconds = $return->getTimestamp() - $pickup->getTimestamp();

    if ($diffSeconds <= 0) {
        return $breakdown;
    }

    // 1-Hour Grace Period Logic
    $totalMinutes = ceil($diffSeconds / 60);
    
    // If they go over a multiple of 6 hours by less than 60 minutes, we waive it.
    // To do this, we just subtract 60 minutes from the total duration, with a minimum of 1 minute.
    $billableMinutes = max(1, $totalMinutes - 60);
    if ($totalMinutes > 60 && $totalMinutes > $billableMinutes) {
        $breakdown['grace_period_applied'] = true;
    }
    
    $billableHours = ceil($billableMinutes / 60);
    
    // Full 24-hour days
    $breakdown['total_days'] = floor($billableHours / 24);
    
    // Remaining hours are converted into 6-hour blocks
    $remainingHours = $billableHours % 24;
    $breakdown['remaining_blocks'] = ceil($remainingHours / 6);
    
    // Total blocks across the whole period (just for display if needed)
    $breakdown['total_blocks'] = ($breakdown['total_days'] * 4) + $breakdown['remaining_blocks'];
    
    // Calculate base price: (Full Days * Daily Rate) + (Remaining Blocks * Block Rate)
    $breakdown['base_price'] = ($breakdown['total_days'] * $breakdown['effective_daily_rate']) + 
                                ($breakdown['remaining_blocks'] * $breakdown['block_rate']);

    // Earnings split calculation
    $vehicle_block_rate = $breakdown['vehicle_daily_rate'] / 4;
    $driver_block_rate = $breakdown['driver_daily_fee'] / 4;
    
    $vehicle_total = ($breakdown['total_days'] * $breakdown['vehicle_daily_rate']) + ($breakdown['remaining_blocks'] * $vehicle_block_rate);
    $driver_total = ($breakdown['total_days'] * $breakdown['driver_daily_fee']) + ($breakdown['remaining_blocks'] * $driver_block_rate);

    if ($booking['driver_arrangement'] === 'hired') {
        $breakdown['owner_earnings'] = $vehicle_total;
        $breakdown['driver_earnings'] = $driver_total;
    } elseif ($booking['driver_arrangement'] === 'owner') {
        $breakdown['owner_earnings'] = $vehicle_total + $driver_total;
        $breakdown['driver_earnings'] = 0.0;
    } else {
        $breakdown['owner_earnings'] = $vehicle_total;
        $breakdown['driver_earnings'] = 0.0;
    }

    // Commission logic
    $config = require __DIR__ . '/../config/config.php';
    if ($breakdown['base_price'] > 100000) {
        $breakdown['commission_rate'] = 10.0;
    } else {
        $breakdown['commission_rate'] = (float)($config['commission_pct'] ?? 15.0);
    }
    
    $breakdown['commission_amount'] = $breakdown['base_price'] * ($breakdown['commission_rate'] / 100);
    $breakdown['total_price'] = $breakdown['base_price'] + $breakdown['commission_amount'];

    return $breakdown;
}

function calculatePrice(array $booking, PDO $db): float {
    $breakdown = calculateRentalBreakdown($booking, $db);
    return $breakdown['total_price'];
}

function isVehicleAvailable(int $vehicleId, string $pickupDate, string $returnDate, PDO $db): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM bookings 
        WHERE vehicle_id = ? 
          AND status NOT IN ('cancelled', 'rejected') 
          AND pickup_date < ? 
          AND return_date > ?
    ");
    $stmt->execute([$vehicleId, $returnDate, $pickupDate]);
    return ((int)$stmt->fetchColumn()) === 0;
}

function isDriverAvailable(int $driverId, string $pickupDate, string $returnDate, PDO $db): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM bookings 
        WHERE assigned_driver_id = ? 
          AND status NOT IN ('cancelled', 'rejected') 
          AND pickup_date < ? 
          AND return_date > ?
    ");
    $stmt->execute([$driverId, $returnDate, $pickupDate]);
    return ((int)$stmt->fetchColumn()) === 0;
}

function isDriverTransmissionCompatible(int $driverId, int $vehicleId, PDO $db): bool {
    $stmt = $db->prepare('
        SELECT d.transmission_preference, v.transmission 
        FROM drivers d 
        JOIN vehicles v ON v.id = ? 
        WHERE d.user_id = ?
    ');
    $stmt->execute([$vehicleId, $driverId]);
    $row = $stmt->fetch();
    if (!$row) return false;

    $driverPref = $row['transmission_preference'];
    $vehicleTrans = $row['transmission'];

    if ($driverPref === 'Both') return true;
    return strcasecmp($driverPref, $vehicleTrans) === 0;
}

/**
 * Resolves which user must hold a verified license for a booking,
 * and whether that requirement is currently satisfied.
 */
function resolveLicenseRequirement(array $booking, PDO $db): array {
    $arrangement = $booking['driver_arrangement']; // 'owner' | 'self' | 'hired'

    $userIdToCheck = match ($arrangement) {
        'owner' => getVehicleOwnerId($booking['vehicle_id'], $db),
        'self'  => $booking['borrower_id'],
        'hired' => $booking['assigned_driver_id'],
    };

    if (!$userIdToCheck) {
        if ($arrangement === 'hired') {
            return [
                'user_id_required'   => null,
                'satisfied'          => false,
                'license_status'     => 'assignment_pending',
            ];
        }
        
        return [
            'user_id_required'   => null,
            'satisfied'          => false,
            'license_status'     => 'missing',
        ];
    }

    $stmt = $db->prepare(
        'SELECT status, expiry_date FROM driving_licenses
         WHERE user_id = ? ORDER BY created_at DESC LIMIT 1'
    );
    $stmt->execute([$userIdToCheck]);
    $license = $stmt->fetch();

    $satisfied = $license
        && $license['status'] === 'verified'
        && strtotime($license['expiry_date']) > time();

    return [
        'user_id_required'   => $userIdToCheck,
        'satisfied'          => $satisfied,
        'license_status'     => $license['status'] ?? 'missing',
    ];
}

function baseUrl(string $path = ''): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $publicPos = strpos($scriptName, '/public/');
    
    if ($publicPos !== false) {
        // We are running in a subdirectory where /public/ is visible in the URL
        $basePath = substr($scriptName, 0, $publicPos + 7);
    } else {
        // We are running directly from the public folder as document root
        $basePath = '';
    }
    
    // Ensure properly formatted absolute path relative to the domain
    return rtrim($basePath, '/') . '/' . ltrim($path, '/');
}
