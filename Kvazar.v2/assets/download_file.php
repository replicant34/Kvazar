<?php
session_start();
require_once '../config/db_connect.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    header('HTTP/1.0 403 Forbidden');
    exit();
}

try {
    $filePath = $_GET['file'] ?? '';
    $originalName = $_GET['name'] ?? 'download';
    
    if (!$filePath) {
        header('HTTP/1.0 400 Bad Request');
        exit();
    }
    
    // Security: Ensure file path is within allowed directories
    $allowedPaths = ['uploads/supporting_files/', 'uploads/order_files/', 'uploads/contracts/'];
    $isAllowed = false;
    
    foreach ($allowedPaths as $allowedPath) {
        if (strpos($filePath, $allowedPath) === 0) {
            $isAllowed = true;
            break;
        }
    }
    
    if (!$isAllowed) {
        header('HTTP/1.0 403 Forbidden');
        exit();
    }
    
    $fullPath = '../' . $filePath;
    
    if (!file_exists($fullPath)) {
        header('HTTP/1.0 404 Not Found');
        exit();
    }
    
    // Get file info
    $fileSize = filesize($fullPath);
    $mimeType = mime_content_type($fullPath);
    
    // Set headers for file download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($originalName) . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output file
    readfile($fullPath);
    
} catch (Exception $e) {
    error_log("Error downloading file: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
}
?> 