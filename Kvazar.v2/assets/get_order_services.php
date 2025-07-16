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
    // Get extra services with service names
    $stmt = $pdo->prepare("
        SELECT 
            es.*,
            les.Service_name
        FROM Order_extra_service es
        LEFT JOIN list_extra_service les ON es.Service_id = les.Service_id
        WHERE es.Order_id = ?
        ORDER BY es.Service_id
    ");
    
    $stmt->execute([$orderId]);
    $extraServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'extra_services' => $extraServices
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching order services: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?> 