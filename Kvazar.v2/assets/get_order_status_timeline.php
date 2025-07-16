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
    
    // Get current order status and info
    $orderStmt = $pdo->prepare("
        SELECT 
            o.Status as current_status_id,
            o.display_order_number,
            o.Courier_id
        FROM Orders o
        WHERE o.Order_id = ?
    ");
    
    $orderStmt->execute([$orderId]);
    $orderInfo = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orderInfo) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    // Get all available statuses in order
    $statusStmt = $pdo->prepare("
        SELECT Status_id, Status_name, Status_color
        FROM list_order_status 
        ORDER BY Status_id
    ");
    $statusStmt->execute();
    $allStatuses = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get status history for this order
    $historyStmt = $pdo->prepare("
        SELECT 
            osh.New_status,
            osh.Change_date,
            osh.Change_reason,
            u.Full_name as changed_by_name,
            u.Login as changed_by_login
        FROM Order_status_history osh
        LEFT JOIN Users u ON osh.Changed_by = u.User_id
        WHERE osh.Order_id = ?
        ORDER BY osh.Change_date ASC
    ");
    $historyStmt->execute([$orderId]);
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create history lookup by status ID
    $historyLookup = [];
    foreach ($history as $record) {
        $historyLookup[$record['New_status']] = [
            'changed_by_name' => $record['changed_by_name'],
            'changed_by_login' => $record['changed_by_login'],
            'change_date' => $record['Change_date'] ? date('d.m.Y H:i', strtotime($record['Change_date'])) : null,
            'change_reason' => $record['Change_reason']
        ];
    }
    
    // Build timeline status array
    $currentStatusId = $orderInfo['current_status_id'];
    $timeline = [];
    
    foreach ($allStatuses as $status) {
        $statusId = $status['Status_id'];
        $isCompleted = false;
        $isCurrent = false;
        $isNextAvailable = false;
        
        // Check if this status has been completed
        if (isset($historyLookup[$statusId])) {
            $isCompleted = true;
            if ($statusId == $currentStatusId) {
                $isCurrent = true;
            }
        }
        
        // Check if this status is available for transition
        // Allow moving to any future status that hasn't been completed yet
        if (!$isCompleted && $statusId > $currentStatusId) {
            // Additional business rule checks
            if ($statusId == 3 && !$orderInfo['Courier_id']) {
                // Cannot move to "In Progress" without courier
                $isNextAvailable = false;
            } else {
                $isNextAvailable = true;
            }
        }
        
        $timeline[] = [
            'status_id' => $statusId,
            'status_name' => $status['Status_name'],
            'status_color' => $status['Status_color'],
            'is_completed' => $isCompleted,
            'is_current' => $isCurrent,
            'is_next_available' => $isNextAvailable,
            'history' => $historyLookup[$statusId] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'order_number' => $orderInfo['display_order_number'],
        'current_status_id' => $currentStatusId,
        'timeline' => $timeline
    ]);
    
} catch (Exception $e) {
    error_log("Error getting order status timeline: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 