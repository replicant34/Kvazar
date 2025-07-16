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
    
    // Get attached files for this order
    $stmt = $pdo->prepare("
        SELECT 
            File_path,
            Original_filename,
            File_type,
            File_size,
            Upload_date
        FROM Attached_order_files 
        WHERE Order_id = ?
        ORDER BY Upload_date DESC
    ");
    
    $stmt->execute([$orderId]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format file data
    foreach ($files as &$file) {
        $file['file_path'] = $file['File_path'];
        $file['original_filename'] = $file['Original_filename'];
        $file['file_type'] = $file['File_type'];
        $file['file_size'] = intval($file['File_size']);
        $file['upload_date'] = $file['Upload_date'];
    }
    
    echo json_encode([
        'success' => true,
        'files' => $files
    ]);
    
} catch (Exception $e) {
    error_log("Error getting attached files: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 