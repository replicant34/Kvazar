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
    
    // Get courier information for this order
    $courierStmt = $pdo->prepare("
        SELECT 
            o.Order_id,
            o.Courier_id,
            c.Full_Company_name as courier_name,
            c.Contact_person,
            c.Contact_person_phone,
            c.Contact_person_email,
            c.Physical_address
        FROM Orders o
        LEFT JOIN Couriers c ON o.Courier_id = c.Courier_id
        WHERE o.Order_id = ?
    ");
    
    $courierStmt->execute([$orderId]);
    $courierInfo = $courierStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$courierInfo) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    $result = [
        'success' => true,
        'courier' => null,
        'drivers' => [],
        'vehicles' => []
    ];
    
    // If courier is assigned, get courier details
    if (!empty($courierInfo['Courier_id'])) {
        $result['courier'] = [
            'courier_id' => $courierInfo['Courier_id'],
            'name' => $courierInfo['courier_name'],
            'contact_person' => $courierInfo['Contact_person'],
            'phone' => $courierInfo['Contact_person_phone'],
            'email' => $courierInfo['Contact_person_email'],
            'address' => $courierInfo['Physical_address']
        ];
        
        // Get assigned drivers for this order
        $driversStmt = $pdo->prepare("
            SELECT 
                d.Driver_id,
                d.Name as driver_name,
                d.Phone_number as driver_phone,
                d.Passport as driver_passport,
                dl.Created_at as assigned_at
            FROM Drivers_list dl
            INNER JOIN Drivers d ON dl.Driver_id = d.Driver_id
            WHERE dl.Order_id = ?
            ORDER BY dl.Created_at
        ");
        
        $driversStmt->execute([$orderId]);
        $result['drivers'] = $driversStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get assigned vehicles for this order
        $vehiclesStmt = $pdo->prepare("
            SELECT 
                v.Vehicle_id,
                v.Brand as vehicle_brand,
                v.Plate_number as vehicle_plate,
                CONCAT(v.Brand, ' - ', v.Plate_number) as vehicle_display,
                vl.Created_at as assigned_at
            FROM Vehicle_list vl
            INNER JOIN Vehicles v ON vl.Vehicle_id = v.Vehicle_id
            WHERE vl.Order_id = ?
            ORDER BY vl.Created_at
        ");
        
        $vehiclesStmt->execute([$orderId]);
        $result['vehicles'] = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Error getting order courier details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 