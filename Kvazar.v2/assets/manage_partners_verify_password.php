<?php
require_once '../config/db_connect.php';
header('Content-Type: application/json');

// Partners page specific action mapping
define('PARTNERS_ACTION_MAP', [
    'download_csv' => 3,  // Download CSV with partners
    'edit' => 2,          // Edit partner
    'delete' => 2         // Delete partner (same as edit)
]);

$input = json_decode(file_get_contents('php://input'), true);
$action_type = $input['action_type'] ?? '';
$action_password = $input['action_password'] ?? '';
$action_id = PARTNERS_ACTION_MAP[$action_type] ?? null;

if (!$action_id) {
    echo json_encode(['error' => 'Invalid action type']);
    exit;
}

$stmt = $pdo->prepare('SELECT Action_password, request_password FROM list_actions_passwords WHERE Action_id = ?');
$stmt->execute([$action_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['error' => 'Action not found']);
    exit;
}

if ((int)$row['request_password'] === 0) {
    echo json_encode(['success' => true]); // No password required
    exit;
}

if ($action_password === $row['Action_password']) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Неверный пароль']);
}
?> 