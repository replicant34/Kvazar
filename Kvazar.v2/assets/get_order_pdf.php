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
        echo json_encode(['success' => false, 'error' => 'PDF file not found for this order']);
        exit();
    }
    
    // Check if file exists
    $fullPath = '../' . $file['File_path'];
    if (!file_exists($fullPath)) {
        echo json_encode(['success' => false, 'error' => 'PDF file not found on server']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'pdf_path' => $file['File_path'],
        'file_name' => $file['File_name']
    ]);
    
} catch (Exception $e) {
    error_log("Error getting order PDF: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 