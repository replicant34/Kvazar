<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['order_id']) || !isset($input['route_points'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$order_id = intval($input['order_id']);
$route_points = $input['route_points'];

try {
    $pdo->beginTransaction();
    
    // First, check if Contact_list table exists and delete existing contacts
    $contactTableExists = false;
    try {
        $checkTable = $pdo->prepare("SHOW TABLES LIKE 'Contact_list'");
        $checkTable->execute();
        $contactTableExists = $checkTable->fetch() ? true : false;
    } catch (Exception $e) {
        // Contact_list table doesn't exist
    }
    
    if ($contactTableExists) {
        $delete_contacts_sql = "DELETE c FROM Contact_list c 
                               INNER JOIN Points p ON c.Point_id = p.Point_id 
                               WHERE p.Order_id = ?";
        $stmt = $pdo->prepare($delete_contacts_sql);
        $stmt->execute([$order_id]);
    }
    
    $delete_points_sql = "DELETE FROM Points WHERE Order_id = ?";
    $stmt = $pdo->prepare($delete_points_sql);
    $stmt->execute([$order_id]);
    
    // Insert new route points
    $insert_point_sql = "INSERT INTO Points (Order_id, Position, Action_type, Company_name, Date, Time, Address_Loading) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_point = $pdo->prepare($insert_point_sql);
    
    $insert_contact_sql = null;
    $stmt_contact = null;
    
    if ($contactTableExists) {
        $insert_contact_sql = "INSERT INTO Contact_list (Point_id, Name, Phone_number) VALUES (?, ?, ?)";
        $stmt_contact = $pdo->prepare($insert_contact_sql);
    }
    
    foreach ($route_points as $index => $point) {
        // Validate required fields
        if (empty($point['action_type']) || empty($point['date']) || empty($point['address'])) {
            throw new Exception('Missing required route point data');
        }
        
        // Insert the point
        $stmt_point->execute([
            $order_id,
            $index + 1, // Position starts from 1
            $point['action_type'],
            $point['company_name'] ?? null,
            $point['date'],
            $point['time'] ?? null,
            $point['address'] // Will be stored in Address_Loading field
        ]);
        
        $point_id = $pdo->lastInsertId();
        
        // Insert contact if provided and Contact table exists
        if ($stmt_contact && (!empty($point['contact_name']) || !empty($point['contact_phone']))) {
            $stmt_contact->execute([
                $point_id,
                $point['contact_name'] ?? null,
                $point['contact_phone'] ?? null
            ]);
        }
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Route points saved successfully']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error saving route points: ' . $e->getMessage()]);
}
?> 