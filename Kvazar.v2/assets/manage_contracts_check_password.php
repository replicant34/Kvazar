<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Define action mapping
define('ACTION_MAP', [
    'download_csv' => 7,
    'delete' => 1,
    'edit' => 1
]);

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $actionType = $input['action_type'] ?? '';
    
    error_log("Check password request - Action: $actionType");
    
    if (!$actionType) {
        echo json_encode(['success' => false, 'error' => 'Missing action type', 'request_password' => 0]);
        exit();
    }
    
    // Get action ID
    $actionId = ACTION_MAP[$actionType] ?? null;
    if (!$actionId) {
        error_log("Invalid action type: $actionType");
        echo json_encode(['success' => false, 'error' => 'Invalid action type', 'request_password' => 0]);
        exit();
    }
    
    error_log("Action ID: $actionId");
    
    // Get request_password setting for this action
    $stmt = $pdo->prepare('SELECT request_password FROM list_actions_passwords WHERE Action_id = ?');
    $stmt->execute([$actionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $requestPassword = (int)$row['request_password'];
        error_log("Request password setting: $requestPassword");
        echo json_encode(['success' => true, 'request_password' => $requestPassword]);
    } else {
        error_log("Action not found in database for ID: $actionId");
        echo json_encode(['success' => false, 'error' => 'Action not found', 'request_password' => 0]);
    }
    
} catch (Exception $e) {
    error_log("Check password error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error', 'request_password' => 0]);
}
?> 