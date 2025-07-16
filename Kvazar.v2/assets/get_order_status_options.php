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
    $currentStatusStmt = $pdo->prepare("
        SELECT 
            o.Status as current_status_id,
            o.display_order_number,
            o.Courier_id,
            los.Status_name as current_status_name,
            los.Status_color as current_status_color
        FROM Orders o
        LEFT JOIN list_order_status los ON o.Status = los.Status_id
        WHERE o.Order_id = ?
    ");
    
    $currentStatusStmt->execute([$orderId]);
    $orderInfo = $currentStatusStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orderInfo) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    // Define valid status transitions based on actual database statuses
    $validTransitions = [
        1 => [2, 3, 5], // "Pending" can go to "Confirmed", "In Progress", "Cancelled"
        2 => [3, 4, 5], // "Confirmed" can go to "In Progress", "Completed", "Cancelled"
        3 => [4, 6, 5], // "In Progress" can go to "Completed", "On Hold", "Cancelled"
        4 => [],        // "Completed" - final status, no transitions
        5 => [],        // "Cancelled" - final status, no transitions
        6 => [3, 5]     // "On Hold" can go to "In Progress", "Cancelled"
    ];
    
    $currentStatusId = $orderInfo['current_status_id'];
    $allowedStatusIds = $validTransitions[$currentStatusId] ?? [];
    
    // Get available status options
    $availableStatuses = [];
    if (!empty($allowedStatusIds)) {
        $placeholders = str_repeat('?,', count($allowedStatusIds) - 1) . '?';
        $statusStmt = $pdo->prepare("
            SELECT Status_id, Status_name, Status_color
            FROM list_order_status 
            WHERE Status_id IN ($placeholders)
            ORDER BY Status_id
        ");
        $statusStmt->execute($allowedStatusIds);
        $availableStatuses = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filter out statuses that have business rule restrictions
        $availableStatuses = array_filter($availableStatuses, function($status) use ($orderInfo) {
            return validateStatusAvailability($status['Status_id'], $orderInfo);
        });
        
        // Re-index array after filtering
        $availableStatuses = array_values($availableStatuses);
    }
    
    echo json_encode([
        'success' => true,
        'current_status' => [
            'id' => $orderInfo['current_status_id'],
            'name' => $orderInfo['current_status_name'],
            'color' => $orderInfo['current_status_color']
        ],
        'available_statuses' => $availableStatuses,
        'order_number' => $orderInfo['display_order_number']
    ]);
    
} catch (Exception $e) {
    error_log("Error getting order status options: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

function validateStatusAvailability($statusId, $orderInfo) {
    // Business rule: Cannot move to "In Progress" (3) without assigned courier
    if ($statusId == 3 && !$orderInfo['Courier_id']) {
        return false;
    }
    
    // Add more business rules here as needed
    
    return true;
}
?> 