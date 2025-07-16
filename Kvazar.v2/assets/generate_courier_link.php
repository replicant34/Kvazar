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
    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderId = intval($input['order_id'] ?? 0);
    $courierId = intval($input['courier_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    
    if (!$orderId || !$courierId) {
        echo json_encode(['success' => false, 'error' => 'Order ID and Courier ID are required']);
        exit();
    }
    
    // Validate that order exists and doesn't have courier assigned
    $orderStmt = $pdo->prepare("
        SELECT Order_id, display_order_number, Courier_id 
        FROM Orders 
        WHERE Order_id = ?
    ");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    if (!empty($order['Courier_id'])) {
        echo json_encode(['success' => false, 'error' => 'Order already has a courier assigned']);
        exit();
    }
    
    // Validate courier exists and get email
    $courierStmt = $pdo->prepare("
        SELECT Courier_id, Full_Company_name, Contact_person_email 
        FROM Couriers 
        WHERE Courier_id = ?
    ");
    $courierStmt->execute([$courierId]);
    $courier = $courierStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$courier) {
        echo json_encode(['success' => false, 'error' => 'Courier not found']);
        exit();
    }
    
    // Check if there's already an active link for this order-courier combination
    $existingLinkStmt = $pdo->prepare("
        SELECT Link_token, Expires_at 
        FROM courier_assignment_links 
        WHERE Order_id = ? AND Courier_id = ? AND Expires_at > NOW() AND Is_used = FALSE
    ");
    $existingLinkStmt->execute([$orderId, $courierId]);
    $existingLink = $existingLinkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingLink) {
        // Return existing link instead of creating new one
        $linkUrl = generateLinkUrl($existingLink['Link_token']);
        
        echo json_encode([
            'success' => true,
            'link_token' => $existingLink['Link_token'],
            'link_url' => $linkUrl,
            'expires_at' => $existingLink['Expires_at'],
            'courier_name' => $courier['Full_Company_name'],
            'courier_email' => $courier['Contact_person_email'],
            'order_number' => $order['display_order_number'],
            'is_existing' => true
        ]);
        exit();
    }
    
    // Generate unique, complex token
    $linkToken = generateUniqueToken();
    
    // Set expiry to 24 hours from now
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store in database
    $insertStmt = $pdo->prepare("
        INSERT INTO courier_assignment_links 
        (Order_id, Courier_id, Link_token, Expires_at, Created_by) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $insertStmt->execute([$orderId, $courierId, $linkToken, $expiresAt, $userId]);
    
    // Generate full URL
    $linkUrl = generateLinkUrl($linkToken);
    
    // Log the action
    $logStmt = $pdo->prepare("
        INSERT INTO action_logs (user_id, action_type, table_name, description, ip_address) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $logStmt->execute([
        $userId, 
        'Генерация ссылки для перевозчика',
        'courier_assignment_links',
        "Создана ссылка для перевозчика {$courier['Full_Company_name']} на заказ {$order['display_order_number']}",
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'link_token' => $linkToken,
        'link_url' => $linkUrl,
        'expires_at' => $expiresAt,
        'courier_name' => $courier['Full_Company_name'],
        'courier_email' => $courier['Contact_person_email'],
        'order_number' => $order['display_order_number'],
        'is_existing' => false
    ]);
    
} catch (Exception $e) {
    error_log("Error generating courier link: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

function generateUniqueToken() {
    // Generate a complex, unique token with multiple random components
    $timestamp = time();
    $random1 = bin2hex(random_bytes(16));
    $random2 = bin2hex(random_bytes(12));
    $hash = hash('sha256', $timestamp . $random1 . uniqid() . $random2);
    
    // Combine and shuffle for extra complexity
    $token = substr($hash, 0, 32) . substr($random1, 0, 16) . substr($random2, 0, 12);
    
    return $token;
}

function generateLinkUrl($token) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host . '/Kvazar.v2';
    
    return $baseUrl . '/courier_form.php?token=' . $token;
}
?> 