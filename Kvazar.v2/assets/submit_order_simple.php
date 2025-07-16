<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    error_log("=== SIMPLE ORDER SUBMISSION DEBUG ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("Session user_id: " . $userId);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Validate minimum required fields
    if (empty($_POST['client_id']) || empty($_POST['display_order_number'])) {
        throw new Exception("Missing required fields: client_id or display_order_number");
    }
    
    // Use the simplest possible INSERT
    $sql = "INSERT INTO Orders (display_order_number, User_id, Client_id, Status, Created_at) VALUES (?, ?, ?, 1, NOW())";
    
    error_log("About to execute simple INSERT: " . $sql);
    error_log("Values: [" . $_POST['display_order_number'] . ", " . $userId . ", " . $_POST['client_id'] . "]");
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['display_order_number'],
        $userId,
        $_POST['client_id']
    ]);
    
    if (!$result) {
        error_log("Simple INSERT failed");
        error_log("Error info: " . print_r($stmt->errorInfo(), true));
        throw new Exception('Simple INSERT failed');
    }
    
    $orderId = $pdo->lastInsertId();
    error_log("Simple INSERT successful, Order_id: " . $orderId);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Simple order created successfully',
        'order_id' => $orderId,
        'order_number' => $_POST['display_order_number']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Simple order creation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 