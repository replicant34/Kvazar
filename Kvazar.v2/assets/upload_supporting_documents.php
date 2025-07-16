<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Set JSON response header
header('Content-Type: application/json');

try {
    // Check if files were uploaded
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        throw new Exception('No files uploaded');
    }

    $uploadedFiles = [];
    $errors = [];
    $maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
    $maxFiles = 3;
    $allowedTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'txt' => 'text/plain'
    ];

    $uploadDir = '../uploads/temp/';
    
    // Create temp directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    // Process each uploaded file
    $fileCount = count($_FILES['files']['name']);
    
    if ($fileCount > $maxFiles) {
        throw new Exception("Maximum $maxFiles files allowed");
    }

    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = $_FILES['files']['name'][$i];
        $fileTmpName = $_FILES['files']['tmp_name'][$i];
        $fileSize = $_FILES['files']['size'][$i];
        $fileError = $_FILES['files']['error'][$i];
        $fileMimeType = $_FILES['files']['type'][$i];

        // Skip empty files
        if (empty($fileName)) {
            continue;
        }

        // Check for upload errors
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading file '$fileName': " . getUploadErrorMessage($fileError);
            continue;
        }

        // Check file size
        if ($fileSize > $maxFileSize) {
            $errors[] = "File '$fileName' is too large. Maximum size is 10MB.";
            continue;
        }

        // Get file extension
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Check file type
        if (!array_key_exists($fileExtension, $allowedTypes)) {
            $errors[] = "File '$fileName' has unsupported format. Allowed formats: " . implode(', ', array_keys($allowedTypes));
            continue;
        }

        // Additional MIME type check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMimeType = finfo_file($finfo, $fileTmpName);
        finfo_close($finfo);

        if ($actualMimeType !== $allowedTypes[$fileExtension] && 
            !($fileExtension === 'jpg' && $actualMimeType === 'image/jpeg')) {
            $errors[] = "File '$fileName' appears to be corrupted or has incorrect format.";
            continue;
        }

        // Generate unique temporary filename
        $tempFileName = 'temp_' . uniqid() . '_' . time() . '.' . $fileExtension;
        $tempFilePath = $uploadDir . $tempFileName;

        // Move uploaded file to temp directory
        if (move_uploaded_file($fileTmpName, $tempFilePath)) {
            $uploadedFiles[] = [
                'original_name' => $fileName,
                'temp_name' => $tempFileName,
                'temp_path' => $tempFilePath,
                'size' => $fileSize,
                'type' => $fileExtension,
                'mime_type' => $actualMimeType
            ];
        } else {
            $errors[] = "Failed to save file '$fileName'";
        }
    }

    // Return response
    $response = [
        'success' => true,
        'files' => $uploadedFiles,
        'errors' => $errors
    ];

    if (!empty($errors) && empty($uploadedFiles)) {
        $response['success'] = false;
        $response['error'] = 'All files failed to upload';
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'File is too large';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}
?> 