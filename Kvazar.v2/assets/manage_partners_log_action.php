<?php
function logAction($pdo, $userId, $actionType, $details) {
    try {
        error_log("Attempting to log action: " . $actionType);
        error_log("User ID: " . $userId);
        error_log("Details: " . $details);

        // Parse the JSON details
        $detailsArray = json_decode($details, true);
        
        // Build a human-readable description based on action type
        switch ($actionType) {
            case 'update_status':
                $description = sprintf(
                    "Changed status for contract %s from '%s' to '%s'",
                    $detailsArray['contract_number'],
                    $detailsArray['old_status'],
                    $detailsArray['new_status']
                );
                break;
            case 'delete_contract':
                $description = sprintf(
                    "Deleted contract %s",
                    $detailsArray['contract_number']
                );
                break;
            case 'download_contracts':
                $filters = $detailsArray['filters'];
                $filterDesc = [];
                if ($filters['search']) $filterDesc[] = "search: {$filters['search']}";
                if ($filters['type']) $filterDesc[] = "type: {$filters['type']}";
                if ($filters['status']) $filterDesc[] = "status: {$filters['status']}";
                if ($filters['dateFrom']) $filterDesc[] = "from: {$filters['dateFrom']}";
                if ($filters['dateTo']) $filterDesc[] = "to: {$filters['dateTo']}";
                
                $description = sprintf(
                    "Downloaded %s contracts (%d records)%s",
                    $detailsArray['entity_type'],
                    $detailsArray['record_count'],
                    $filterDesc ? " with filters: " . implode(", ", $filterDesc) : ""
                );
                break;
            case 'create_contract':
                $description = sprintf(
                    "Created new contract %s for %s (Type: %s, Date: %s, Status: %s)",
                    $detailsArray['contract_number'],
                    $detailsArray['company_name'],
                    $detailsArray['contract_type'],
                    $detailsArray['contract_date'],
                    $detailsArray['status']
                );
                break;
            default:
                $description = $details;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO action_logs (
                user_id,
                action_type,
                table_name,
                record_id,
                description,
                ip_address
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        // Get the client's IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // Determine table name and record id from the details
        $tableName = isset($detailsArray['entity_type']) ? 
            ucfirst($detailsArray['entity_type']) . '_contracts' : null;
        $recordId = $detailsArray['contract_id'] ?? null;
        
        $result = $stmt->execute([
            $userId,
            $actionType,
            $tableName,
            $recordId,
            $description,
            $ipAddress
        ]);
        
        if (!$result) {
            error_log("Failed to insert log. Error info: " . print_r($stmt->errorInfo(), true));
        } else {
            error_log("Successfully logged action with ID: " . $pdo->lastInsertId());
            error_log("Stored description: " . $description);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error logging action: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}
?> 