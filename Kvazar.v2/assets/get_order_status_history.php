<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $orderId = intval($_GET['order_id'] ?? 0);
    
    if (!$orderId) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        exit();
    }
    
    // Get order info
    $orderStmt = $pdo->prepare("
        SELECT 
            o.display_order_number,
            o.Order_date,
            c.Full_Company_name as client_name,
            los.Status_name as current_status
        FROM Orders o
        LEFT JOIN Clients c ON o.Client_id = c.Client_id
        LEFT JOIN list_order_status los ON o.Status = los.Status_id
        WHERE o.Order_id = ?
    ");
    
    $orderStmt->execute([$orderId]);
    $orderInfo = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orderInfo) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    // Get status history
    $historyStmt = $pdo->prepare("
        SELECT 
            osh.History_id,
            osh.Change_date,
            osh.Change_reason,
            prev_status.Status_name as previous_status_name,
            prev_status.Status_color as previous_status_color,
            new_status.Status_name as new_status_name,
            new_status.Status_color as new_status_color,
            u.Full_name as changed_by_name,
            u.Login as changed_by_login
        FROM Order_status_history osh
        LEFT JOIN list_order_status prev_status ON osh.Previous_status = prev_status.Status_id
        LEFT JOIN list_order_status new_status ON osh.New_status = new_status.Status_id
        LEFT JOIN Users u ON osh.Changed_by = u.User_id
        WHERE osh.Order_id = ?
        ORDER BY osh.Change_date DESC
    ");
    
    $historyStmt->execute([$orderId]);
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($history as &$record) {
        if ($record['Change_date']) {
            $record['Change_date'] = date('d.m.Y H:i', strtotime($record['Change_date']));
        }
    }
    
    echo json_encode([
        'success' => true,
        'order_info' => $orderInfo,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    error_log("Error getting order status history: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 