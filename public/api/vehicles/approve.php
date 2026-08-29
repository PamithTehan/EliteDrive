<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

$input = json_decode(file_get_contents('php://input'), true);

if (!csrfCheck($input['csrf'] ?? '')) {
    http_response_code(419);
    exit(json_encode(['error' => 'Invalid session token']));
}

$id = (int) ($input['vehicle_id'] ?? 0);
$decision = $input['decision'] ?? ''; // 'approved' | 'rejected'
$validDecisions = ['approved', 'rejected'];

if (!$id || !in_array($decision, $validDecisions, true)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid input']));
}

$db = getDb();
$stmt = $db->prepare('UPDATE vehicles SET status = ? WHERE id = ?');
$stmt->execute([$decision, $id]);

echo json_encode(['ok' => true]);
