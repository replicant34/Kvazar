<?php
session_start();
require_once '../config/db_connect.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    http_response_code(403);
    exit('Unauthorized');
}

try {
    // Get parameters
    $contractId = $_GET['contract_id'] ?? null;
    $entityType = $_GET['entity_type'] ?? null;
    
    if (!$contractId || !$entityType) {
        throw new Exception('Missing required parameters');
    }
    
    // Determine table based on entity type
    switch ($entityType) {
        case 'client':
            $table = 'Client_contracts';
            $idColumn = 'Contract_id';
            break;
        case 'courier':
            $table = 'Courier_contracts';
            $idColumn = 'Contract_id';
            break;
        case 'agent':
            $table = 'Agent_contracts';
            $idColumn = 'Contract_id';
            break;
        default:
            throw new Exception('Invalid entity type');
    }
    
    // Get file path from database
    $stmt = $pdo->prepare("SELECT file_path, Contract_number FROM {$table} WHERE {$idColumn} = ?");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contract) {
        throw new Exception('Contract not found');
    }
    
    if (!$contract['file_path']) {
        throw new Exception('No file associated with this contract');
    }
    
    $filePath = '../' . $contract['file_path'];
    
    // Check if file exists
    if (!file_exists($filePath)) {
        throw new Exception('File not found on server');
    }
    
    // Get file info
    $fileInfo = pathinfo($filePath);
    $extension = strtolower($fileInfo['extension']);
    $fileName = $fileInfo['basename'];
    
    // Set appropriate content type based on file extension
    $contentTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'tiff' => 'image/tiff',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain',
        'rtf' => 'application/rtf'
    ];
    
    $contentType = $contentTypes[$extension] ?? 'application/octet-stream';
    
    // Set headers for file preview
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: inline; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=3600');
    header('Pragma: public');
    
    // Output file content
    readfile($filePath);
    
} catch (Exception $e) {
    http_response_code(404);
    echo 'Error: ' . $e->getMessage();
}
?> 