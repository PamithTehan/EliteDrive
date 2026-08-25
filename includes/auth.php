<?php
// includes/auth.php
require_once __DIR__ . '/functions.php';

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    // 'cookie_secure' => true, // enable once served over HTTPS
]);

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void {
    if (!currentUser()) {
        header('Location: ' . baseUrl('/login.php'));
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    $u = currentUser();
    $map = ['admin' => 'is_admin', 'owner' => 'is_owner', 'driver' => 'is_driver', 'borrower' => 'is_borrower'];
    if (empty($u[$map[$role]])) {
        http_response_code(403);
        exit('Forbidden: requires ' . $role . ' role.');
    }
}

function loginUser(array $userRow): void {
    session_regenerate_id(true);
    $_SESSION['user'] = $userRow;
}

function logoutUser(): void {
    session_destroy();
    $_SESSION = [];
    header('Location: ' . baseUrl('/'));
    exit;
}
