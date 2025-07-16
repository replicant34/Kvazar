<?php
session_start();
require_once '../config/db_connect.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    http_response_code(403);
    header('Location: ../index.php');
    exit();
}

try {
    $orderId = intval($_GET['order_id'] ?? 0);
    $format = $_GET['format'] ?? 'pdf';
    $type = $_GET['type'] ?? 'official_order';
    
    if (!$orderId) {
        http_response_code(400);
        echo 'Order ID is required';
        exit();
    }
    
    // Get the most recent official order file for this order
    $mimeType = $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    $extension = $format === 'pdf' ? '.pdf' : '.docx';
    
    $stmt = $pdo->prepare("
        SELECT File_path, File_name 
        FROM Order_files 
        WHERE Order_id = ? 
        AND File_type = ? 
        AND Status = 1 
        AND File_name LIKE 'official_order_%'
        ORDER BY Created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$orderId, $mimeType]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        http_response_code(404);
        echo 'Official order file not found for this order';
        exit();
    }
    
    // Check if file exists on filesystem
    $fullPath = '../' . $file['File_path'];
    if (!file_exists($fullPath)) {
        http_response_code(404);
        echo 'Official order file not found on server';
        exit();
    }
    
    // Get order info for filename
    $orderStmt = $pdo->prepare("SELECT display_order_number FROM Orders WHERE Order_id = ?");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    $orderNumber = $order['display_order_number'] ?? $orderId;
    $filename = "Official_Order_{$orderNumber}" . $extension;
    
    // Set appropriate headers for download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output file content
    readfile($fullPath);
    exit();
    
} catch (Exception $e) {
    error_log("Error downloading official order: " . $e->getMessage());
    http_response_code(500);
    echo 'Server error occurred';
}
?> 