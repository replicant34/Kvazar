<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get the last display order number
    $stmt = $pdo->prepare("
        SELECT MAX(CAST(display_order_number AS DECIMAL)) as last_number
        FROM Orders
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $lastNumber = $result['last_number'] ?? 0;
    $nextNumber = $lastNumber + 1;
    
    // Format: Simple number (e.g., 1, 123, 123456789)
    $orderNumber = (string)$nextNumber;
    
    echo json_encode([
        'success' => true,
        'number' => $orderNumber
    ]);
} catch (Exception $e) {
    error_log("Error generating order number: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to generate order number']);
}
?> 