<?php
session_start();
require_once '../config/db_connect.php';
require_once 'manage_partners_log_action.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Check if it's a POST request with form data
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Validate required fields
    $requiredFields = ['entity_type', 'entity_id', 'contract_type', 
                      'contract_number', 'contract_date', 'contract_status'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Determine table and entity based on type
    switch ($_POST['entity_type']) {
        case 'client':
            $table = 'Client_contracts';
            $entityId = 'Client_id';
            $entityTable = 'Clients';
            break;
        case 'courier':
            $table = 'Courier_contracts';
            $entityId = 'Courier_id';
            $entityTable = 'Couriers';
            break;
        case 'agent':
            $table = 'Agent_contracts';
            $entityId = 'Agent_id';
            $entityTable = 'Agents';
            break;
        default:
            throw new Exception('Invalid entity type');
    }

    // Verify that the status exists
    $statusCheckStmt = $pdo->prepare("SELECT Status_name FROM list_contract_status WHERE Status_name = ?");
    $statusCheckStmt->execute([$_POST['contract_status']]);
    if (!$statusCheckStmt->fetch()) {
        throw new Exception('Invalid status');
    }

    // Handle file upload
    $filePath = null;
    if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/contracts/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Не удалось создать директорию для загрузки файлов: ' . $uploadDir);
            }
        }
        
        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            throw new Exception('Директория для загрузки файлов недоступна для записи: ' . $uploadDir);
        }
        
        $file = $_FILES['contract_file'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmpName = $file['tmp_name'];
        $fileType = $file['type'];
        
        // Debug logging
        error_log("File upload attempt - Name: $fileName, Size: $fileSize, Tmp: $fileTmpName, Type: $fileType");
        
        // Validate file type
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Неподдерживаемый тип файла. Разрешены только PDF, DOC, DOCX.');
        }
        
        // Validate file size (10MB max)
        if ($fileSize > 10 * 1024 * 1024) {
            throw new Exception('Файл слишком большой. Максимальный размер: 10MB.');
        }
        
        // Generate unique filename
        $uniqueFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
        $filePath = 'uploads/contracts/' . $uniqueFileName;
        $fullPath = '../' . $filePath;
        
        // Debug logging
        error_log("Attempting to move file from $fileTmpName to $fullPath");
        
        // Move uploaded file
        if (!move_uploaded_file($fileTmpName, $fullPath)) {
            $error = error_get_last();
            error_log("File upload failed - Error: " . print_r($error, true));
            throw new Exception('Ошибка при сохранении файла. Проверьте права доступа к директории.');
        }
        
        // Verify file was actually saved
        if (!file_exists($fullPath)) {
            throw new Exception('Файл не был сохранен после загрузки.');
        }
        
        error_log("File successfully uploaded to: $fullPath");
    }

    // Start transaction
    $pdo->beginTransaction();

    // Create the contract
    $stmt = $pdo->prepare("
        INSERT INTO {$table} (
            {$entityId},
            Contract_number,
            Contract_type,
            Contract_date,
            Contract_status,
            file_path,
            Created_at,
            Created_by
        ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?)
    ");

    $success = $stmt->execute([
        $_POST['entity_id'],
        $_POST['contract_number'],
        $_POST['contract_type'],
        $_POST['contract_date'],
        $_POST['contract_status'],
        $filePath,
        $_SESSION['user_id']
    ]);

    if ($success) {
        // Get the company name for logging
        $companyStmt = $pdo->prepare("SELECT Full_company_name FROM {$entityTable} WHERE {$entityId} = ?");
        $companyStmt->execute([$_POST['entity_id']]);
        $companyName = $companyStmt->fetchColumn();

        // Get contract type name
        $typeStmt = $pdo->prepare("SELECT Type_name FROM list_contract_type WHERE Type_id = ?");
        $typeStmt->execute([$_POST['contract_type']]);
        $typeName = $typeStmt->fetchColumn();

        // Log the action
        $logDetails = json_encode([
            'action' => 'create_contract',
            'contract_number' => $_POST['contract_number'],
            'contract_id' => $pdo->lastInsertId(),
            'entity_type' => $_POST['entity_type'],
            'company_name' => $companyName,
            'contract_type' => $typeName,
            'contract_date' => $_POST['contract_date'],
            'status' => $_POST['contract_status'],
            'file_uploaded' => $filePath ? true : false
        ]);

        logAction($pdo, $_SESSION['user_id'], 'Добавление контракта', $logDetails);
        
        $pdo->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to create contract');
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Delete uploaded file if there was an error
    if (isset($fullPath) && file_exists($fullPath)) {
        unlink($fullPath);
    }
    
    error_log("Error creating contract: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 