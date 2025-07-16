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
    $password = $input['password'] ?? '';
    
    error_log("Password verification request - Action: $actionType, Password: " . substr($password, 0, 3) . "***");
    
    if (!$actionType || !$password) {
        echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
        exit();
    }
    
    // Get action ID
    $actionId = ACTION_MAP[$actionType] ?? null;
    if (!$actionId) {
        echo json_encode(['success' => false, 'error' => 'Invalid action type']);
        exit();
    }
    
    error_log("Action ID: $actionId");
    
    // Get stored password for this action
    $stmt = $pdo->prepare('SELECT Action_password, request_password FROM list_actions_passwords WHERE Action_id = ?');
    $stmt->execute([$actionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        error_log("Action not found in database for ID: $actionId");
        echo json_encode(['success' => false, 'error' => 'Action not found']);
        exit();
    }
    
    error_log("Database row found - request_password: " . $row['request_password'] . ", stored_password: " . substr($row['Action_password'], 0, 10) . "...");
    
    // Check if password is required
    if ((int)$row['request_password'] === 0) {
        error_log("Password not required for action $actionId");
        // Password not required, allow action
        echo json_encode(['success' => true, 'isValid' => true]);
        exit();
    }
    
    // Check if stored password is hashed or plain text
    $storedPassword = $row['Action_password'];
    $isHashed = password_get_info($storedPassword)['algoName'] !== 'unknown';
    
    error_log("Password verification - Is hashed: " . ($isHashed ? 'yes' : 'no'));
    
    // Verify password
    $isValid = false;
    if ($isHashed) {
        // Password is hashed, use password_verify
        $isValid = password_verify($password, $storedPassword);
        error_log("Using password_verify - Result: " . ($isValid ? 'true' : 'false'));
    } else {
        // Password is plain text, compare directly
        $isValid = ($password === $storedPassword);
        error_log("Using direct comparison - Result: " . ($isValid ? 'true' : 'false'));
    }
    
    if ($isValid) {
        error_log("Password verification successful for action $actionId");
        echo json_encode(['success' => true, 'isValid' => true]);
    } else {
        error_log("Password verification failed for action $actionId");
        echo json_encode(['success' => true, 'isValid' => false]);
    }
    
} catch (Exception $e) {
    error_log("Password verification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?> 