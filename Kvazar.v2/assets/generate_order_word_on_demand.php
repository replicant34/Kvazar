<?php
session_start();
require_once '../config/db_connect.php';
require_once 'generate_order_word.php';

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
    
    // Check if Word file already exists
    $stmt = $pdo->prepare("
        SELECT File_path, File_name 
        FROM Order_files 
        WHERE Order_id = ? AND File_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' AND Status = 1
        ORDER BY Created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$orderId]);
    $existingFile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingFile && file_exists('../' . $existingFile['File_path'])) {
        // Word file already exists, return its info
        echo json_encode([
            'success' => true,
            'word_path' => $existingFile['File_path'],
            'file_name' => $existingFile['File_name'],
            'generated' => false
        ]);
        exit();
    }
    
    // Get complete order data
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            c.Full_Company_name as client_name,
            cr.Full_Company_name as courier_name,
            cont.Full_Company_name as contractor_name,
            curr.currency_name as currency,
            cc.Contract_number,
            cc.Contract_date,
            ord_notes.Note_content as order_notes,
            st.Type_name as shipping_type,
            tt.Type_name as transport_type,
            cn.cargo_name as cargo_name,
            lt.loading_name as loading_type,
            pt.packaging_name as packaging_type
        FROM Orders o
        LEFT JOIN Clients c ON o.Client_id = c.Client_id
        LEFT JOIN Couriers cr ON o.Courier_id = cr.Courier_id
        LEFT JOIN Contractors cont ON o.Contractor = cont.Contractors_id
        LEFT JOIN list_currency curr ON o.Currency_id = curr.id
        LEFT JOIN Client_contracts cc ON o.Contract_id = cc.Contract_id
        LEFT JOIN Order_notes ord_notes ON o.Note_id = ord_notes.Note_id
        LEFT JOIN list_shipping_type st ON o.Shipping_type = st.Type_id
        LEFT JOIN list_transport_type tt ON o.Vehicle_type = tt.Type_id
        LEFT JOIN list_cargo_name cn ON o.Cargo_type = cn.id
        LEFT JOIN list_loading_type lt ON o.Loading_type = lt.id
        LEFT JOIN list_packaging_type pt ON o.Packing_type = pt.id
        WHERE o.Order_id = ?
    ");
    
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    // Get route points
    $routeStmt = $pdo->prepare("
        SELECT 
            p.Point_id,
            p.Action_type,
            p.Date,
            p.Time,
            p.Address_Loading as address,
            p.Company_name,
            cl.Name as contact_person,
            cl.Phone_number as phone_number
        FROM Points p
        LEFT JOIN Contact_list cl ON p.Point_id = cl.Point_id
        WHERE p.Order_id = ?
        ORDER BY p.Position
    ");
    
    $routeStmt->execute([$orderId]);
    $routePoints = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get extra services
    $servicesStmt = $pdo->prepare("
        SELECT 
            COALESCE(les.service_name, es.Service_name) as service_name,
            es.Service_price, 
            es.Quantity, 
            es.Total
        FROM Extra_service es
        LEFT JOIN list_extra_service les ON es.Service_name = les.id
        WHERE es.Order_id = ?
        ORDER BY COALESCE(les.service_name, es.Service_name)
    ");
    
    $servicesStmt->execute([$orderId]);
    $extraServices = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare order data for Word generation
    $orderData = [
        'order_number' => $order['display_order_number'] ?? $order['Order_id'],
        'order_date' => $order['Order_date'],
        'client_name' => $order['client_name'],
        'contractor_name' => $order['contractor_name'],
        'contract_number' => $order['Contract_number'],
        'contract_date' => $order['Contract_date'],
        'shipping_type' => $order['shipping_type'],
        'transport_type' => $order['transport_type'],
        'cargo_name' => $order['cargo_name'],
        'cargo_weight' => $order['Weight'],
        'weight_unit' => $order['Weight_unit'],
        'cargo_volume' => $order['Volume'],
        'length' => null,
        'width' => null,
        'height' => null,
        'cargo_quantity' => $order['Quantity'],
        'loading_type' => $order['loading_type'],
        'packaging_type' => $order['packaging_type'],
        'min_temperature' => $order['Min_temperature'],
        'max_temperature' => $order['Max_temperature'],
        'temp_print_list' => $order['Temperature_record'],
        'cargo_price' => $order['Cargo_price'],
        'currency' => $order['currency'],
        'rate' => $order['Rate'],
        'total_insurance' => $order['Insurance_price'],
        'transport_total' => $order['Total_price_vehicle'],
        'order_total' => $order['Order_total'],
        'order_notes' => $order['order_notes'],
        'route_points' => $routePoints,
        'extra_services' => $extraServices
    ];
    
    // Handle dimensions if available
    if (!empty($order['Size'])) {
        $dimensions = explode(' x ', $order['Size']);
        if (count($dimensions) >= 3) {
            $orderData['length'] = $dimensions[0];
            $orderData['width'] = $dimensions[1];
            $orderData['height'] = $dimensions[2];
        }
    }
    
    // Generate Word document
    $wordResult = generateAndStoreOrderWord($pdo, $orderData, $orderId);
    
    if ($wordResult['success']) {
        echo json_encode([
            'success' => true,
            'word_path' => $wordResult['filepath'],
            'file_name' => $wordResult['filename'],
            'generated' => true
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $wordResult['error'] ?? 'Word generation failed'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error generating order Word: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 