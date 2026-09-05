<?php
// includes/functions.php
if (defined('FUNCTIONS_PHP_LOADED')) return;
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

function calculatePrice(array $booking, PDO $db): float {
    $stmt = $db->prepare('SELECT daily_rate FROM vehicles WHERE id = ?');
    $stmt->execute([$booking['vehicle_id']]);
    $dailyRate = (float) $stmt->fetchColumn();

    $driverDailyFee = 0.0;
    if (!empty($booking['assigned_driver_id']) && ($booking['driver_arrangement'] ?? '') === 'hired') {
        $stmtD = $db->prepare('SELECT daily_fee FROM drivers WHERE user_id = ?');
        $stmtD->execute([$booking['assigned_driver_id']]);
        $driverDailyFee = (float) ($stmtD->fetchColumn() ?: 0.0);
    }

    $effectiveDailyRate = $dailyRate + $driverDailyFee;

    $pickup = new DateTime($booking['pickup_date']);
    $return = new DateTime($booking['return_date']);
    
    $diffSeconds = $return->getTimestamp() - $pickup->getTimestamp();
    if ($diffSeconds <= 0) {
        return 0.0;
    }

    // Convert to full hours (rounding up any partial hour)
    $borrowPeriodInHours = (int) ceil($diffSeconds / 3600);
    
    $chargeBlock = intdiv($borrowPeriodInHours, 4);
    $uncompletedChargeBlock = ($borrowPeriodInHours % 4 > 0) ? 1 : 0;
    
    $chargeForDayQuarter = $effectiveDailyRate / 4;
    
    $chargeTotal = ($chargeBlock + $uncompletedChargeBlock) * $chargeForDayQuarter;
    
    return $chargeTotal;
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
