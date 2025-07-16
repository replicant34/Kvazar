<?php
/**
 * Process supporting documents for an order
 * Move files from temp storage to permanent storage and create database records
 */

function processSupportingDocuments($orderId, $uploadedFiles, $userId, $pdo) {
    if (empty($uploadedFiles) || !is_array($uploadedFiles)) {
        return true; // No files to process
    }

    $tempDir = '../uploads/temp/';
    $permanentDir = '../uploads/supporting_files/';
    
    // Create permanent directory if it doesn't exist
    if (!is_dir($permanentDir)) {
        if (!mkdir($permanentDir, 0755, true)) {
            throw new Exception('Failed to create permanent upload directory');
        }
    }

    $processedFiles = [];
    $errors = [];

    try {
        $pdo->beginTransaction();

        foreach ($uploadedFiles as $fileData) {
            // Validate file data
            if (!isset($fileData['temp_name']) || !isset($fileData['original_name']) || 
                !isset($fileData['size']) || !isset($fileData['type'])) {
                $errors[] = "Invalid file data for file: " . ($fileData['original_name'] ?? 'unknown');
                continue;
            }

            $tempFilePath = $tempDir . $fileData['temp_name'];
            
            // Check if temp file exists
            if (!file_exists($tempFilePath)) {
                $errors[] = "Temporary file not found: " . $fileData['original_name'];
                continue;
            }

            // Generate permanent filename
            $fileExtension = strtolower(pathinfo($fileData['original_name'], PATHINFO_EXTENSION));
            $permanentFileName = 'order_' . $orderId . '_' . uniqid() . '.' . $fileExtension;
            $permanentFilePath = $permanentDir . $permanentFileName;

            // Move file from temp to permanent location
            if (rename($tempFilePath, $permanentFilePath)) {
                // Insert record into database
                $stmt = $pdo->prepare("
                    INSERT INTO Documents (Order_id, File_path, Original_filename, File_type, File_size, Uploaded_by) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $orderId,
                    $permanentFilePath,
                    $fileData['original_name'],
                    $fileExtension,
                    $fileData['size'],
                    $userId
                ]);

                if ($result) {
                    $processedFiles[] = [
                        'document_id' => $pdo->lastInsertId(),
                        'original_name' => $fileData['original_name'],
                        'permanent_path' => $permanentFilePath
                    ];
                } else {
                    // If database insert fails, remove the moved file
                    unlink($permanentFilePath);
                    $errors[] = "Failed to save database record for: " . $fileData['original_name'];
                }
            } else {
                $errors[] = "Failed to move file: " . $fileData['original_name'];
            }
        }

        if (!empty($errors)) {
            $pdo->rollBack();
            // Clean up any successfully moved files
            foreach ($processedFiles as $file) {
                if (file_exists($file['permanent_path'])) {
                    unlink($file['permanent_path']);
                }
            }
            throw new Exception("File processing errors: " . implode(', ', $errors));
        }

        $pdo->commit();
        
        // Clean up any remaining temp files for this session
        cleanupTempFiles();
        
        return [
            'success' => true,
            'processed_files' => $processedFiles,
            'errors' => $errors
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Clean up any files that were moved
        foreach ($processedFiles as $file) {
            if (file_exists($file['permanent_path'])) {
                unlink($file['permanent_path']);
            }
        }
        
        throw $e;
    }
}

/**
 * Clean up old temporary files (older than 24 hours)
 */
function cleanupTempFiles() {
    $tempDir = '../uploads/temp/';
    
    if (!is_dir($tempDir)) {
        return;
    }

    $files = glob($tempDir . 'temp_*');
    $cutoffTime = time() - (24 * 60 * 60); // 24 hours ago

    foreach ($files as $file) {
        if (is_file($file) && filemtime($file) < $cutoffTime) {
            unlink($file);
        }
    }
}

/**
 * Get documents for an order
 */
function getOrderDocuments($orderId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT d.*, u.Full_name as uploaded_by_name 
        FROM Documents d 
        LEFT JOIN Users u ON d.Uploaded_by = u.User_id 
        WHERE d.Order_id = ? 
        ORDER BY d.Upload_date DESC
    ");
    
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Delete a document
 */
function deleteDocument($documentId, $userId, $userRole, $pdo) {
    // Check permissions (admin and CEO can delete any document)
    if (!in_array($userRole, ['admin', 'ceo'])) {
        throw new Exception('Insufficient permissions to delete documents');
    }

    $stmt = $pdo->prepare("SELECT File_path FROM Documents WHERE Document_id = ?");
    $stmt->execute([$documentId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        throw new Exception('Document not found');
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM Documents WHERE Document_id = ?");
    $result = $stmt->execute([$documentId]);

    if ($result) {
        // Delete physical file
        if (file_exists($document['File_path'])) {
            unlink($document['File_path']);
        }
        return true;
    }

    return false;
}
?> 