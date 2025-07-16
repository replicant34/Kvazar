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
    $userId = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderId = intval($input['order_id'] ?? 0);
    if (!$orderId) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        exit();
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Helper function to convert empty strings to null
    function emptyToNull($value) {
        return (empty($value) && $value !== '0') ? null : $value;
    }
    
    // Prepare size string
    $sizeString = null;
    if (!empty($input['length']) && !empty($input['width']) && !empty($input['height'])) {
        $sizeString = $input['length'] . ' x ' . $input['width'] . ' x ' . $input['height'];
    }
    
    // Update main order
    $orderStmt = $pdo->prepare("
        UPDATE Orders SET
            display_order_number = ?,
            Contractor = ?,
            Order_date = ?,
            Shipping_type = ?,
            Vehicle_type = ?,
            Weight = ?,
            Weight_unit = ?,
            Volume = ?,
            Cargo_type = ?,
            Min_temperature = ?,
            Max_temperature = ?,
            Temperature_record = ?,
            Loading_type = ?,
            Packing_type = ?,
            Quantity = ?,
            Size = ?,
            Cargo_price = ?,
            Rate = ?,
            Insurance_price = ?,
            Rate_2 = ?,
            Hours = ?,
            Extra_hours = ?,
            Total_price_vehicle = ?,
            Total_price_extra_service = ?,
            Order_total = ?,
            Currency_id = ?,
            Updated_at = NOW()
        WHERE Order_id = ?
    ");
    
    $orderStmt->execute([
        emptyToNull($input['display_order_number'] ?? null),
        emptyToNull($input['contractor_id'] ?? null),
        emptyToNull($input['order_date'] ?? null),
        emptyToNull($input['shipping_type'] ?? null),
        emptyToNull($input['transport_type'] ?? null),
        emptyToNull($input['cargo_weight'] ?? null),
        $input['weight_unit'] ?? 'тонн',
        emptyToNull($input['cargo_volume'] ?? null),
        emptyToNull($input['cargo_name'] ?? null),
        emptyToNull($input['min_temperature'] ?? null),
        emptyToNull($input['max_temperature'] ?? null),
        emptyToNull($input['temp_print_list'] ?? null),
        emptyToNull($input['loading_type'] ?? null),
        emptyToNull($input['packaging_type'] ?? null),
        emptyToNull($input['cargo_quantity'] ?? null),
        $sizeString,
        emptyToNull($input['cargo_price'] ?? null),
        emptyToNull($input['rate'] ?? null),
        emptyToNull($input['total_insurance'] ?? null),
        emptyToNull($input['transport_rate'] ?? null),
        emptyToNull($input['transport_hours'] ?? null),
        emptyToNull($input['overwork_hours'] ?? null),
        emptyToNull($input['transport_total'] ?? null),
        emptyToNull($input['extra_services_total'] ?? null),
        emptyToNull($input['order_total'] ?? null),
        emptyToNull($input['currency_id'] ?? null),
        $orderId
    ]);
    
    // Update order notes if provided
    if (isset($input['order_notes'])) {
        // Check if order has existing note
        $noteCheckStmt = $pdo->prepare("SELECT Note_id FROM Orders WHERE Order_id = ?");
        $noteCheckStmt->execute([$orderId]);
        $existingNoteId = $noteCheckStmt->fetchColumn();
        
        if ($existingNoteId) {
            // Update existing note
            $noteUpdateStmt = $pdo->prepare("
                UPDATE Order_notes SET Note_content = ?, Created_by = ?, Created_at = NOW() 
                WHERE Note_id = ?
            ");
            $noteUpdateStmt->execute([$input['order_notes'], $userId, $existingNoteId]);
        } else if (!empty($input['order_notes'])) {
            // Create new note
            $noteInsertStmt = $pdo->prepare("
                INSERT INTO Order_notes (Order_id, Note_content, Created_by, Created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $noteInsertStmt->execute([$orderId, $input['order_notes'], $userId]);
            $newNoteId = $pdo->lastInsertId();
            
            // Update order with new note_id
            $orderNoteUpdateStmt = $pdo->prepare("UPDATE Orders SET Note_id = ? WHERE Order_id = ?");
            $orderNoteUpdateStmt->execute([$newNoteId, $orderId]);
        }
    }
    
    // Update extra services
    if (isset($input['extra_services'])) {
        // Delete existing extra services
        $deleteServicesStmt = $pdo->prepare("DELETE FROM Extra_service WHERE Order_id = ?");
        $deleteServicesStmt->execute([$orderId]);
        
        // Insert updated extra services
        if (!empty($input['extra_services']) && is_array($input['extra_services'])) {
            $serviceInsertStmt = $pdo->prepare("
                INSERT INTO Extra_service (Order_id, Service_name, Service_price, Quantity, Total)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($input['extra_services'] as $service) {
                $serviceInsertStmt->execute([
                    $orderId,
                    $service['service_name'] ?? '',
                    $service['service_price'] ?? 0,
                    $service['quantity'] ?? 1,
                    $service['total'] ?? 0
                ]);
            }
        }
    }
    
    // Update route points
    if (isset($input['route_points'])) {
        // Delete existing route points and their contacts
        $deleteContactsStmt = $pdo->prepare("
            DELETE cl FROM Contact_list cl 
            INNER JOIN Points p ON cl.Point_id = p.Point_id 
            WHERE p.Order_id = ?
        ");
        $deleteContactsStmt->execute([$orderId]);
        
        $deletePointsStmt = $pdo->prepare("DELETE FROM Points WHERE Order_id = ?");
        $deletePointsStmt->execute([$orderId]);
        
        // Insert updated route points
        if (!empty($input['route_points']) && is_array($input['route_points'])) {
            $pointInsertStmt = $pdo->prepare("
                INSERT INTO Points (Order_id, Position, Action_type, Date, Time, Address_Loading, Company_name)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $contactInsertStmt = $pdo->prepare("
                INSERT INTO Contact_list (Point_id, Name, Phone_number)
                VALUES (?, ?, ?)
            ");
            
            foreach ($input['route_points'] as $index => $point) {
                $pointInsertStmt->execute([
                    $orderId,
                    $index + 1,
                    $point['action_type'] ?? 'Погрузка',
                    $point['date'] ?? null,
                    $point['time'] ?? null,
                    $point['address'] ?? '',
                    $point['company_name'] ?? ''
                ]);
                
                $pointId = $pdo->lastInsertId();
                
                // Insert contacts for this point
                if (!empty($point['contact_list']) && is_array($point['contact_list'])) {
                    foreach ($point['contact_list'] as $contact) {
                        if (!empty($contact['name']) && !empty($contact['phone'])) {
                            $contactInsertStmt->execute([
                                $pointId,
                                $contact['name'],
                                $contact['phone']
                            ]);
                        }
                    }
                }
            }
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order verified and updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error saving verified order: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 