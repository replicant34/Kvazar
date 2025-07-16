<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo 'Unauthorized access';
    exit();
}

// Check if contract ID and type are provided
if (!isset($_GET['contract_id']) || !isset($_GET['contract_type'])) {
    http_response_code(400);
    echo 'Missing contract ID or type';
    exit();
}

$contractId = (int)$_GET['contract_id'];
$contractType = $_GET['contract_type'];

try {
    // Determine table based on contract type
    switch ($contractType) {
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
            throw new Exception('Invalid contract type');
    }

    // Get contract information
    $stmt = $pdo->prepare("SELECT file_path, Contract_number FROM {$table} WHERE {$idColumn} = ?");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contract) {
        http_response_code(404);
        echo 'Contract not found';
        exit();
    }

    if (!$contract['file_path']) {
        http_response_code(404);
        echo 'No file attached to this contract';
        exit();
    }

    $filePath = '../' . $contract['file_path'];
    
    // Security check: ensure file exists and is within uploads directory
    if (!file_exists($filePath) || !is_file($filePath)) {
        http_response_code(404);
        echo 'File not found';
        exit();
    }

    // Additional security: ensure file is within uploads directory
    $realPath = realpath($filePath);
    $uploadsDir = realpath('../uploads/');
    
    if (!$realPath || strpos($realPath, $uploadsDir) !== 0) {
        http_response_code(403);
        echo 'Access denied';
        exit();
    }

    // Get file information
    $fileInfo = pathinfo($filePath);
    $fileName = $contract['Contract_number'] . '_' . $fileInfo['basename'];
    
    // Set appropriate headers for file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output file content
    readfile($filePath);
    exit();

} catch (Exception $e) {
    error_log("Error downloading contract file: " . $e->getMessage());
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
?> 