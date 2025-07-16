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
    $orderId = intval($_GET['order_id'] ?? 0);
    
    if (!$orderId) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        exit();
    }
    
    // Get complete order data
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            c.Full_Company_name as client_name,
            cr.Full_Company_name as courier_name,
            cont.Full_Company_name as contractor_name,
            curr.currency_name as currency_name,
            curr.id as currency_id,
            cc.Contract_number,
            cc.Contract_date,
            ord_notes.Note_content as order_notes,
            st.Type_name as shipping_type_name,
            tt.Type_name as transport_type_name,
            cn.cargo_name as cargo_name_display,
            lt.loading_name as loading_type_name,
            pt.packaging_name as packaging_type_name
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
            p.Order_id,
            p.Position,
            p.Action_type,
            p.Date,
            p.Time,
            p.Address_Loading as Address,
            p.Company_name,
            GROUP_CONCAT(
                CONCAT(cl.Name, '|', cl.Phone_number) 
                SEPARATOR ';;'
            ) as contacts
        FROM Points p
        LEFT JOIN Contact_list cl ON p.Point_id = cl.Point_id
        WHERE p.Order_id = ?
        GROUP BY p.Point_id, p.Order_id, p.Position, p.Action_type, p.Date, p.Time, p.Address_Loading, p.Company_name
        ORDER BY p.Position
    ");
    
    $routeStmt->execute([$orderId]);
    $routePoints = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process contacts for each route point
    foreach ($routePoints as &$point) {
        $point['contact_list'] = [];
        if (!empty($point['contacts'])) {
            $contacts = explode(';;', $point['contacts']);
            foreach ($contacts as $contact) {
                if (!empty($contact)) {
                    $contactData = explode('|', $contact);
                    if (count($contactData) >= 2) {
                        $point['contact_list'][] = [
                            'name' => $contactData[0],
                            'phone' => $contactData[1]
                        ];
                    }
                }
            }
        }
        unset($point['contacts']);
    }
    
    // Get extra services
    $servicesStmt = $pdo->prepare("
        SELECT 
            COALESCE(les.service_name, es.Service_name) as Service_name,
            es.Service_price, 
            es.Quantity, 
            es.Total,
            es.Service_name as original_service_name
        FROM Extra_service es
        LEFT JOIN list_extra_service les ON es.Service_name = les.id
        WHERE es.Order_id = ?
        ORDER BY COALESCE(les.service_name, es.Service_name)
    ");
    
    $servicesStmt->execute([$orderId]);
    $extraServices = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get dropdown options for the form
    $dropdownData = [];
    
    // Shipping types
    $shippingStmt = $pdo->query("SELECT Type_id, Type_name FROM list_shipping_type ORDER BY Type_name");
    $dropdownData['shipping_types'] = $shippingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transport types
    $transportStmt = $pdo->query("SELECT Type_id, Type_name FROM list_transport_type ORDER BY Type_name");
    $dropdownData['transport_types'] = $transportStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cargo names
    $cargoStmt = $pdo->query("SELECT id, cargo_name FROM list_cargo_name ORDER BY cargo_name");
    $dropdownData['cargo_names'] = $cargoStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Loading types
    $loadingStmt = $pdo->query("SELECT id, loading_name FROM list_loading_type ORDER BY loading_name");
    $dropdownData['loading_types'] = $loadingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Packaging types
    $packagingStmt = $pdo->query("SELECT id, packaging_name FROM list_packaging_type ORDER BY packaging_name");
    $dropdownData['packaging_types'] = $packagingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Currencies
    $currencyStmt = $pdo->query("SELECT id as Currency_id, currency_name as Currency_name FROM list_currency ORDER BY currency_name");
    $dropdownData['currencies'] = $currencyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contractors
    $contractorStmt = $pdo->query("SELECT Contractors_id, Full_Company_name FROM Contractors ORDER BY Full_Company_name");
    $dropdownData['contractors'] = $contractorStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the data to help troubleshoot field display issues (can be removed in production)
    // error_log("Order data for verification: " . print_r($order, true));
    
    echo json_encode([
        'success' => true,
        'order' => $order,
        'route_points' => $routePoints,
        'extra_services' => $extraServices,
        'dropdown_data' => $dropdownData
    ]);
    
} catch (Exception $e) {
    error_log("Error getting order for verification: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 