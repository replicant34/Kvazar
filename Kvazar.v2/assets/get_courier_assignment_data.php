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
    
    // Get current order info
    $orderStmt = $pdo->prepare("
        SELECT 
            o.Order_id,
            o.display_order_number,
            o.Courier_id as current_courier_id,
            c.Full_Company_name as client_name,
            cr.Full_Company_name as current_courier_name
        FROM Orders o
        LEFT JOIN Clients c ON o.Client_id = c.Client_id
        LEFT JOIN Couriers cr ON o.Courier_id = cr.Courier_id
        WHERE o.Order_id = ?
    ");
    
    $orderStmt->execute([$orderId]);
    $orderInfo = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orderInfo) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    // Get all couriers
    $couriersStmt = $pdo->query("
        SELECT Courier_id, Full_Company_name, Phone_number, Main_address
        FROM Couriers 
        WHERE Status = 'active'
        ORDER BY Full_Company_name
    ");
    $couriers = $couriersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current assignments for this order
    $currentAssignments = [
        'drivers' => [],
        'vehicles' => []
    ];
    
    // Get current drivers assigned to this order
    $driversStmt = $pdo->prepare("
        SELECT 
            dl.Driver_id,
            d.Name as driver_name,
            d.Phone_number as driver_phone,
            d.Passport as driver_passport,
            d.Courier_id
        FROM Drivers_list dl
        INNER JOIN Drivers d ON dl.Driver_id = d.Driver_id
        WHERE dl.Order_id = ?
    ");
    
    $driversStmt->execute([$orderId]);
    $currentAssignments['drivers'] = $driversStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current vehicles assigned to this order
    $vehiclesStmt = $pdo->prepare("
        SELECT 
            vl.Vehicle_id,
            v.Brand as vehicle_brand,
            v.Plate_number as vehicle_plate,
            v.Courier_id
        FROM Vehicle_list vl
        INNER JOIN Vehicles v ON vl.Vehicle_id = v.Vehicle_id
        WHERE vl.Order_id = ?
    ");
    
    $vehiclesStmt->execute([$orderId]);
    $currentAssignments['vehicles'] = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'order_info' => $orderInfo,
        'couriers' => $couriers,
        'current_assignments' => $currentAssignments
    ]);
    
} catch (Exception $e) {
    error_log("Error getting courier assignment data: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 