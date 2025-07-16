<?php
session_start();
require_once '../config/db_connect.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $orderId = intval($_GET['order_id'] ?? 0);
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        exit();
    }
    
    // Get the PDF file for this order
    $stmt = $pdo->prepare("
        SELECT File_path, File_name 
        FROM Order_files 
        WHERE Order_id = ? AND File_type = 'application/pdf' AND Status = 1
        ORDER BY Created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$orderId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'PDF file not found for this order']);
        exit();
    }
    
    // Check if file exists
    $fullPath = '../' . $file['File_path'];
    if (!file_exists($fullPath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'PDF file not found on server']);
        exit();
    }
    
    // Get order info for filename
    $orderStmt = $pdo->prepare("SELECT display_order_number FROM Orders WHERE Order_id = ?");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    $orderNumber = $order['display_order_number'] ?? $orderId;
    $filename = "Order_{$orderNumber}.pdf";
    
    // Force download with proper headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output file content
    readfile($fullPath);
    exit();
    
} catch (Exception $e) {
    error_log("Error downloading order PDF: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred'
    ]);
}
?> 