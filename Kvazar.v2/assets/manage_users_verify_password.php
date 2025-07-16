<?php
require_once '../config/db_connect.php';
header('Content-Type: application/json');

define('ACTION_MAP', [
    'download_csv' => 4,
    'delete' => 5,
    'edit' => 6
]);

$action_type = $_POST['action_type'] ?? '';
$action_password = $_POST['action_password'] ?? '';
$action_id = ACTION_MAP[$action_type] ?? null;

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