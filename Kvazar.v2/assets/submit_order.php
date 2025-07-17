<?php
// Start output buffering to catch any unexpected output
ob_start();

session_start();
require_once '../config/db_connect.php';
require_once 'generate_order_pdf.php';
require_once 'order_logging.php';

// Clear any buffered output before sending JSON
ob_clean();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // Debug logging
    error_log("=== ORDER SUBMISSION DEBUG START ===");
    error_log("POST data received: " . print_r($_POST, true));
    
    // Helper function to convert empty strings to null for database
    function emptyToNull($value) {
        return (empty($value) && $value !== '0') ? null : $value;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Validate only essential required fields
    $requiredFields = ['client_id', 'display_order_number'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate contract if provided
    if (!empty($_POST['client_id'])) {
        $clientId = $_POST['client_id'];
        
        // Check if client has contracts available
        $contractCheck = $pdo->prepare("SELECT COUNT(*) FROM Client_contracts WHERE Client_id = ?");
        $contractCheck->execute([$clientId]);
        $hasContracts = $contractCheck->fetchColumn() > 0;
        
        if ($hasContracts && empty($_POST['contract_number'])) {
            throw new Exception("Contract selection is required for this client");
        }
    }
    
    // Generate unique order number
    $displayOrderNumber = $_POST['display_order_number'] ?? '';
    if (empty($displayOrderNumber)) {
        throw new Exception("Order number is required");
    }
    
    // Check if order number already exists and generate unique one if needed
    $finalOrderNumber = $displayOrderNumber;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Orders WHERE display_order_number = ?");
        $stmt->execute([$finalOrderNumber]);
        if ($stmt->fetchColumn() == 0) {
            break;
        }
        $finalOrderNumber = $displayOrderNumber . '_' . $counter;
        $counter++;
    }
    
    // Get contract ID from contract number if provided
    $contractId = null;
    if (!empty($_POST['contract_number'])) {
        $contractStmt = $pdo->prepare("SELECT Contract_id FROM Client_contracts WHERE Contract_number = ?");
        $contractStmt->execute([$_POST['contract_number']]);
        $contractId = $contractStmt->fetchColumn();
    }
    
    // We'll create order notes AFTER the main order is created
    $noteId = null;
    
    // Prepare size string
    $sizeString = null;
    if (!empty($_POST['length']) && !empty($_POST['width']) && !empty($_POST['height'])) {
        $sizeString = $_POST['length'] . ' x ' . $_POST['width'] . ' x ' . $_POST['height'];
    }
    
    // Insert main order
    $orderStmt = $pdo->prepare("
        INSERT INTO Orders (
            display_order_number, User_id, Client_id, Contractor, Order_date, 
            Shipping_type, Vehicle_type, Weight, Weight_unit, Volume, Cargo_type,
            Min_temperature, Max_temperature, Temperature_record, Loading_type, 
            Packing_type, Quantity, Size, Cargo_price, Rate, Insurance_price,
            Rate_2, Hours, Extra_hours, Total_price_vehicle, Total_price_extra_service,
            Contract_id, Order_total, Currency_id, Note_id, Status, Created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    // Debug: Log the values being inserted
    $insertValues = [
        $finalOrderNumber,
        $userId,
        $_POST['client_id'],
        emptyToNull($_POST['contractor_id'] ?? null),
        emptyToNull($_POST['order_date'] ?? null),
        emptyToNull($_POST['shipping_type'] ?? null),
        emptyToNull($_POST['transport_type'] ?? null),
        emptyToNull($_POST['cargo_weight'] ?? null),
        $_POST['weight_unit'] ?? 'тонн',
        emptyToNull($_POST['cargo_volume'] ?? null),
        emptyToNull($_POST['cargo_name'] ?? null),
        emptyToNull($_POST['min_temperature'] ?? null),
        emptyToNull($_POST['max_temperature'] ?? null),
        emptyToNull($_POST['temp_print_list'] ?? null),
        emptyToNull($_POST['loading_type'] ?? null),
        emptyToNull($_POST['packaging_type'] ?? null),
        emptyToNull($_POST['cargo_quantity'] ?? null),
        $sizeString,
        emptyToNull($_POST['cargo_price'] ?? null),
        emptyToNull($_POST['rate'] ?? null),
        emptyToNull($_POST['total_insurance'] ?? null),
        emptyToNull($_POST['transport_rate'] ?? null),
        emptyToNull($_POST['transport_hours'] ?? null),
        emptyToNull($_POST['overwork_hours'] ?? null),
        emptyToNull($_POST['transport_total'] ?? null),
        emptyToNull($_POST['extra_services_total'] ?? null),
        $contractId,
        emptyToNull($_POST['order_total'] ?? null),
        emptyToNull($_POST['currency_id'] ?? null),
        $noteId
    ];
    
    error_log("Attempting to insert order with values: " . print_r($insertValues, true));
    
    try {
        $orderResult = $orderStmt->execute($insertValues);
        
        if (!$orderResult) {
            error_log("INSERT returned false");
            error_log("PDO errorInfo: " . print_r($orderStmt->errorInfo(), true));
            throw new Exception('Failed to create order');
        }
        
        error_log("INSERT executed successfully");
    } catch (PDOException $e) {
        error_log("PDO Exception during order insert: " . $e->getMessage());
        error_log("SQLSTATE: " . $e->getCode());
        throw new Exception('Database error: ' . $e->getMessage());
    }
    
    $orderId = $pdo->lastInsertId();
    error_log("Order created successfully with ID: " . $orderId);
    
    // Process order notes if provided (now that we have Order_id)
    if (!empty($_POST['order_notes'])) {
        error_log("Processing order notes: " . $_POST['order_notes']);
        try {
            $noteStmt = $pdo->prepare("
                INSERT INTO Order_notes (Order_id, Note_content, Created_by, Created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $noteStmt->execute([$orderId, $_POST['order_notes'], $userId]);
            $noteId = $pdo->lastInsertId();
            error_log("Order note created with ID: " . $noteId);
            
            // Update the main order with the note_id reference
            $updateOrderStmt = $pdo->prepare("UPDATE Orders SET Note_id = ? WHERE Order_id = ?");
            $updateOrderStmt->execute([$noteId, $orderId]);
            error_log("Order updated with Note_id: " . $noteId);
        } catch (PDOException $e) {
            error_log("Error creating order note: " . $e->getMessage());
            // Don't throw exception here - order was created successfully, note is optional
        }
    }
    
    // Process extra services
    if (!empty($_POST['extra_services'])) {
        error_log("Processing extra services...");
        $extraServices = json_decode($_POST['extra_services'], true);
        error_log("Decoded extra services: " . print_r($extraServices, true));
        
        if ($extraServices && is_array($extraServices)) {
            $extraServiceStmt = $pdo->prepare("
                INSERT INTO Extra_service (Order_id, Service_name, Service_price, Quantity, Total)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($extraServices as $service) {
                error_log("Processing extra service: " . print_r($service, true));
                try {
                    $extraServiceStmt->execute([
                        $orderId,
                        $service['service_name'] ?? '',
                        $service['service_price'] ?? 0,
                        $service['quantity'] ?? 1,
                        $service['total'] ?? 0
                    ]);
                    error_log("Extra service inserted successfully");
                } catch (PDOException $e) {
                    error_log("Failed to insert extra service: " . $e->getMessage());
                    error_log("Service data: " . print_r($service, true));
                }
            }
        } else {
            error_log("No valid extra services array found");
        }
    } else {
        error_log("No extra_services in POST data");
    }
    
    // Process route points
    if (!empty($_POST['route_points'])) {
        $routePoints = json_decode($_POST['route_points'], true);
        if ($routePoints && is_array($routePoints)) {
            $pointStmt = $pdo->prepare("
                INSERT INTO Points (Order_id, Position, Action_type, Date, Time, Address_Loading, Company_name)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $contactStmt = $pdo->prepare("
                INSERT INTO Contact_list (Point_id, Name, Phone_number)
                VALUES (?, ?, ?)
            ");
            
            foreach ($routePoints as $index => $point) {
                // Insert point
                $pointStmt->execute([
                    $orderId,
                    $index + 1, // Position starts from 1
                    $point['action_type'] ?? 'Погрузка',
                    $point['date'] ?? null,
                    $point['time'] ?? null,
                    $point['address'] ?? '',
                    $point['company_name'] ?? ''
                ]);
                
                $pointId = $pdo->lastInsertId();
                
                // Insert main contact
                if (!empty($point['contact_person']) && !empty($point['phone_number'])) {
                    $contactStmt->execute([
                        $pointId,
                        $point['contact_person'],
                        $point['phone_number']
                    ]);
                }
                
                // Insert additional contacts
                if (!empty($point['additional_contacts']) && is_array($point['additional_contacts'])) {
                    foreach ($point['additional_contacts'] as $contact) {
                        if (!empty($contact['contact_person']) && !empty($contact['phone_number'])) {
                            $contactStmt->execute([
                                $pointId,
                                $contact['contact_person'],
                                $contact['phone_number']
                            ]);
                        }
                    }
                }
            }
        }
    }
    
    // Process uploaded files
    if (!empty($_POST['uploaded_files'])) {
        error_log("Processing uploaded files...");
        $uploadedFiles = json_decode($_POST['uploaded_files'], true);
        error_log("Decoded uploaded files: " . print_r($uploadedFiles, true));
        
        if ($uploadedFiles && is_array($uploadedFiles)) {
            $fileStmt = $pdo->prepare("
                INSERT INTO Attached_order_files (Order_id, File_path, Original_filename, File_type, File_size, Upload_date, Uploaded_by)
                VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ");
            
            foreach ($uploadedFiles as $file) {
                error_log("Processing file: " . print_r($file, true));
                
                // Fix the temp path - don't add ../ if it's already there
                $tempPath = $file['temp_path'];
                if (strpos($tempPath, '../') !== 0) {
                    $tempPath = '../' . $tempPath;
                }
                
                $originalName = $file['original_name'];
                $fileSize = $file['size'];
                $fileType = $file['type'];
                
                // Generate new filename: date-order_number-original_name.ext
                $orderDate = $_POST['order_date'] ?? date('Y-m-d');
                $orderNumber = $finalOrderNumber;
                
                // Get file extension
                $pathInfo = pathinfo($originalName);
                $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
                $baseNameWithoutExt = $pathInfo['filename'];
                
                // Create new filename
                $newFileName = $orderDate . '-' . $orderNumber . '-' . $baseNameWithoutExt . $extension;
                
                // Ensure filename is safe for filesystem
                $newFileName = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $newFileName);
                
                // Create permanent file path first
                $permanentDir = '../uploads/supporting_files/';
                if (!is_dir($permanentDir)) {
                    error_log("Creating directory: $permanentDir");
                    mkdir($permanentDir, 0755, true);
                }
                
                // Handle duplicate filenames
                $finalFileName = $newFileName;
                $counter = 1;
                while (file_exists($permanentDir . $finalFileName)) {
                    $finalFileName = $orderDate . '-' . $orderNumber . '-' . $baseNameWithoutExt . '_' . $counter . $extension;
                    $finalFileName = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $finalFileName);
                    $counter++;
                }
                $newFileName = $finalFileName;
                
                error_log("Original filename: $originalName");
                error_log("New filename: $newFileName");
                error_log("Temp path: $tempPath");
                error_log("File exists check: " . (file_exists($tempPath) ? 'YES' : 'NO'));
                
                $permanentPath = $permanentDir . $newFileName;
                $dbPath = 'uploads/supporting_files/' . $newFileName;
                
                error_log("Moving file from: $tempPath to: $permanentPath");
                error_log("Final filename: $newFileName");
                
                // Move file from temp to permanent location
                if (file_exists($tempPath)) {
                    // Use copy + unlink instead of rename for better cross-ownership compatibility
                    if (copy($tempPath, $permanentPath)) {
                        error_log("File copied successfully");
                        
                        // Delete the temp file
                        if (unlink($tempPath)) {
                            error_log("Temp file deleted successfully");
                        } else {
                            error_log("Warning: Could not delete temp file, but copy succeeded");
                        }
                        
                        // Insert file record
                        try {
                            $fileStmt->execute([
                                $orderId,
                                $dbPath,
                                $originalName,
                                $fileType,
                                $fileSize,
                                $userId
                            ]);
                            error_log("File record inserted successfully for: $originalName (stored as: $newFileName)");
                        } catch (PDOException $e) {
                            error_log("Failed to insert file record: " . $e->getMessage());
                            error_log("File data: Order_id=$orderId, Path=$dbPath, Original=$originalName, Type=$fileType, Size=$fileSize, User=$userId");
                        }
                    } else {
                        error_log("Failed to copy file: $tempPath to $permanentPath");
                        error_log("Source exists: " . (file_exists($tempPath) ? 'YES' : 'NO'));
                        error_log("Destination dir writable: " . (is_writable($permanentDir) ? 'YES' : 'NO'));
                    }
                } else {
                    error_log("Temp file not found: $tempPath");
                    // List what files are actually in the temp directory
                    $tempDir = dirname($tempPath);
                    if (is_dir($tempDir)) {
                        $files = scandir($tempDir);
                        error_log("Files in temp directory $tempDir: " . print_r($files, true));
                    } else {
                        error_log("Temp directory does not exist: $tempDir");
                    }
                }
            }
        } else {
            error_log("No valid uploaded files array found");
        }
    } else {
        error_log("No uploaded_files in POST data");
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Log order creation
    logOrderCreate($pdo, $orderId, ['client_id' => $_POST['client_id'] ?? null]);
    
    // After successful order creation, generate PDF
    try {
        error_log("Starting PDF generation for order ID: $orderId");
        
        // Clear any buffered output before PDF generation
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Prepare order data for PDF generation
        $orderData = [
            'order_number' => $finalOrderNumber,
            'order_date' => $_POST['order_date'] ?? '',
            'client_name' => $_POST['client_name'] ?? '',
            'contractor_name' => $_POST['contractor_name'] ?? '',
            'contract_number' => $_POST['contract_number'] ?? '',
            'contract_date' => $_POST['contract_date'] ?? '',
            'shipping_type' => $_POST['shipping_type'] ?? '',
            'transport_type' => $_POST['transport_type'] ?? '',
            'cargo_name' => $_POST['cargo_name'] ?? '',
            'cargo_weight' => $_POST['cargo_weight'] ?? '',
            'weight_unit' => $_POST['weight_unit'] ?? '',
            'cargo_volume' => $_POST['cargo_volume'] ?? '',
            'length' => $_POST['length'] ?? '',
            'width' => $_POST['width'] ?? '',
            'height' => $_POST['height'] ?? '',
            'cargo_quantity' => $_POST['cargo_quantity'] ?? '',
            'loading_type' => $_POST['loading_type'] ?? '',
            'packaging_type' => $_POST['packaging_type'] ?? '',
            'min_temperature' => $_POST['min_temperature'] ?? '',
            'max_temperature' => $_POST['max_temperature'] ?? '',
            'temp_print_list' => $_POST['temp_print_list'] ?? '',
            'cargo_price' => $_POST['cargo_price'] ?? '',
            'currency' => $_POST['currency'] ?? '',
            'rate' => $_POST['rate'] ?? '',
            'total_insurance' => $_POST['total_insurance'] ?? '',
            'transport_total' => $_POST['transport_total'] ?? '',
            'order_total' => $_POST['order_total'] ?? '',
            'order_notes' => $_POST['order_notes'] ?? ''
        ];
        
        // Add route points if available
        if (!empty($_POST['route_points'])) {
            $orderData['route_points'] = json_decode($_POST['route_points'], true);
        }
        
        // Add extra services if available
        if (!empty($_POST['extra_services'])) {
            $orderData['extra_services'] = json_decode($_POST['extra_services'], true);
        }
        
        // Generate and store PDF
        $pdfResult = generateAndStoreOrderPDF($pdo, $orderData, $orderId);
        
        if ($pdfResult['success']) {
            error_log("PDF generated successfully: " . $pdfResult['filename']);
            
            // Ensure clean output
            if (ob_get_level()) {
                ob_clean();
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => (int)$orderId,
                'order_number' => $finalOrderNumber,
                'pdf_generated' => true,
                'pdf_filename' => $pdfResult['filename']
            ], JSON_UNESCAPED_UNICODE);
        } else {
            error_log("PDF generation failed: " . ($pdfResult['error'] ?? 'Unknown error'));
            
            // Ensure clean output
            if (ob_get_level()) {
                ob_clean();
            }
            
            // Order was created successfully, but PDF failed - still return success
            echo json_encode([
                'success' => true,
                'message' => 'Order created successfully (PDF generation failed)',
                'order_id' => (int)$orderId,
                'order_number' => $finalOrderNumber,
                'pdf_generated' => false,
                'pdf_error' => $pdfResult['error'] ?? 'Unknown error'
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        error_log("PDF generation error: " . $e->getMessage());
        
        // Ensure clean output
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Order was created successfully, but PDF failed - still return success
        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully (PDF generation failed)',
            'order_id' => (int)$orderId,
            'order_number' => $finalOrderNumber,
            'pdf_generated' => false,
            'pdf_error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Order creation error: " . $e->getMessage());
    
    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?> 