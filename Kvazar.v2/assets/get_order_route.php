<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

$orderId = intval($_GET['order_id'] ?? 0);

if (!$orderId) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit();
}

try {
    // Get route points (Company_name is stored as text, not foreign key)
    $stmt = $pdo->prepare("
        SELECT 
            p.Point_id,
            p.Order_id,
            p.Position,
            p.Action_type,
            p.Company_name,
            p.Date,
            p.Time,
            p.Address_Loading as Address
        FROM Points p
        WHERE p.Order_id = ?
        ORDER BY p.Position, p.Point_id
    ");
    
    $stmt->execute([$orderId]);
    $routePoints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if Contact_list table exists and get contacts for each point
    $contactTableExists = false;
    try {
        $checkTable = $pdo->prepare("SHOW TABLES LIKE 'Contact_list'");
        $checkTable->execute();
        $contactTableExists = $checkTable->fetch() ? true : false;
    } catch (Exception $e) {
        // Contact_list table doesn't exist
    }
    
    foreach ($routePoints as &$point) {
        if ($contactTableExists) {
            $contactStmt = $pdo->prepare("
                SELECT 
                    Name as name,
                    Phone_number as phone
                FROM Contact_list
                WHERE Point_id = ?
            ");
            
            $contactStmt->execute([$point['Point_id']]);
            $point['contact_list'] = $contactStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $point['contact_list'] = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'route_points' => $routePoints
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching order route: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?> 