<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (isset($_GET['order_number'])) {
    $orderNumber = trim($_GET['order_number']);
    
    try {
        // Validate format (1-9 digits)
        if (!preg_match('/^\d{1,9}$/', $orderNumber)) {
            echo json_encode([
                'success' => true,
                'isUnique' => false,
                'error' => 'Please enter a number between 1 and 999999999'
            ]);
            exit();
        }

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM Orders 
            WHERE display_order_number = ?
        ");
        $stmt->execute([$orderNumber]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'isUnique' => $result['count'] == 0
        ]);
    } catch (PDOException $e) {
        error_log("Error checking order number: " . $e->getMessage());
        echo json_encode(['error' => 'Database error']);
    }
} else {
    echo json_encode(['error' => 'No order number provided']);
}
?> 