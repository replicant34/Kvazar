<?php
// Order Logging System
// Centralized logging for all order-related operations

require_once '../config/db_connect.php';

/**
 * Log order-related actions to action_logs table
 * @param PDO $pdo - Database connection
 * @param string $actionType - Type of action (English key)
 * @param mixed $orderId - Order ID (can be null for general actions)
 * @param string $description - Action description in Russian
 * @param array $additionalData - Additional data for context
 */
function logOrderAction($pdo, $actionType, $orderId = null, $description = '', $additionalData = []) {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // Handle IPv6 localhost
        if ($ipAddress === '::1') {
            $ipAddress = '127.0.0.1';
        }
        
        // Translate action types to Russian
        $russianActionTypes = [
            // Page access
            'page_access' => 'доступ_к_странице',
            
            // Order management
            'order_create' => 'создание_заказа',
            'order_view' => 'просмотр_заказа',
            'order_update' => 'изменение_заказа',
            'order_delete' => 'удаление_заказа',
            'order_list_view' => 'просмотр_списка_заказов',
            
            // Order status management
            'status_change' => 'изменение_статуса',
            'status_view_history' => 'просмотр_истории_статуса',
            
            // Courier assignment
            'courier_assign' => 'назначение_курьера',
            'courier_unassign' => 'отмена_назначения_курьера',
            'courier_view' => 'просмотр_курьера',
            
            // Document operations
            'document_generate' => 'генерация_документа',
            'document_download' => 'скачивание_документа',
            'document_view' => 'просмотр_документа',
            
            // Route management
            'route_view' => 'просмотр_маршрута',
            'route_update' => 'изменение_маршрута',
            
            // Services management
            'services_view' => 'просмотр_услуг',
            'services_update' => 'изменение_услуг',
            
            // File operations
            'file_upload' => 'загрузка_файла',
            'file_download' => 'скачивание_файла',
            'file_delete' => 'удаление_файла',
            'file_view' => 'просмотр_файла',
            
            // Export operations
            'export_csv' => 'экспорт_csv',
            'export_pdf' => 'экспорт_pdf',
            'export_word' => 'экспорт_word',
            
            // Order verification
            'order_verify' => 'проверка_заказа',
            'order_approve' => 'утверждение_заказа',
            'order_reject' => 'отклонение_заказа',
            
            // Search and filter
            'search' => 'поиск',
            'filter' => 'фильтрация',
            
            // Authentication for order access
            'access_granted' => 'доступ_разрешен',
            'access_denied' => 'доступ_запрещен'
        ];
        
        $russianActionType = $russianActionTypes[$actionType] ?? $actionType;
        
        // Determine table name based on action type
        $tableName = 'Orders';
        if (in_array($actionType, ['page_access', 'access_granted', 'access_denied'])) {
            $tableName = 'order_pages';
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO action_logs (user_id, action_type, table_name, record_id, description, ip_address, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$userId, $russianActionType, $tableName, $orderId, $description, $ipAddress]);
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Failed to log order action: " . $e->getMessage());
    }
}

/**
 * Get order description for logging
 * @param PDO $pdo - Database connection
 * @param mixed $orderId - Order ID
 * @return string - Order description
 */
function getOrderDescription($pdo, $orderId) {
    try {
        if (!$orderId) return 'Неизвестный заказ';
        
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(display_order_number, CONCAT('Заказ ID: ', Order_id)) as order_display,
                Cargo_type,
                CONCAT(
                    COALESCE(c.Full_company_name, c.Short_company_name, 'Неизвестный клиент'),
                    CASE 
                        WHEN Cargo_type IS NOT NULL AND Cargo_type != '' 
                        THEN CONCAT(' - ', Cargo_type)
                        ELSE ''
                    END
                ) as full_description
            FROM Orders o
            LEFT JOIN Clients c ON o.Client_id = c.Client_id
            WHERE o.Order_id = ?
        ");
        
        $stmt->execute([$orderId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['order_display'] . ' (' . $result['full_description'] . ')';
        }
        
        return "Заказ ID: {$orderId}";
    } catch (Exception $e) {
        return "Заказ ID: {$orderId}";
    }
}

/**
 * Get client name for logging
 * @param PDO $pdo - Database connection
 * @param int $clientId - Client ID
 * @return string - Client name
 */
function getClientName($pdo, $clientId) {
    try {
        if (!$clientId) return 'Неизвестный клиент';
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(Full_company_name, Short_company_name, 'Неизвестный клиент') as client_name
            FROM Clients 
            WHERE Client_id = ?
        ");
        
        $stmt->execute([$clientId]);
        return $stmt->fetchColumn() ?: "Клиент ID: {$clientId}";
    } catch (Exception $e) {
        return "Клиент ID: {$clientId}";
    }
}

/**
 * Get courier name for logging
 * @param PDO $pdo - Database connection
 * @param int $courierId - Courier ID
 * @return string - Courier name
 */
function getCourierName($pdo, $courierId) {
    try {
        if (!$courierId) return 'Неизвестный курьер';
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(Full_company_name, Short_company_name, 'Неизвестный курьер') as courier_name
            FROM Couriers 
            WHERE Courier_id = ?
        ");
        
        $stmt->execute([$courierId]);
        return $stmt->fetchColumn() ?: "Курьер ID: {$courierId}";
    } catch (Exception $e) {
        return "Курьер ID: {$courierId}";
    }
}

/**
 * Get status name for logging
 * @param PDO $pdo - Database connection
 * @param int $statusId - Status ID
 * @return string - Status name
 */
function getStatusName($pdo, $statusId) {
    try {
        if (!$statusId) return 'Неизвестный статус';
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(Status_name, 'Неизвестный статус') as status_name
            FROM list_order_status 
            WHERE Status_id = ?
        ");
        
        $stmt->execute([$statusId]);
        return $stmt->fetchColumn() ?: "Статус ID: {$statusId}";
    } catch (Exception $e) {
        return "Статус ID: {$statusId}";
    }
}

/**
 * Log page access for order-related pages
 * @param PDO $pdo - Database connection
 * @param string $pageName - Name of the page
 * @param array $params - Additional parameters
 */
function logOrderPageAccess($pdo, $pageName, $params = []) {
    $pageDescriptions = [
        'add_order' => 'Страница создания заказа',
        'manage_orders' => 'Страница управления заказами',
        'order_detail' => 'Страница деталей заказа',
        'analytics_dashboard' => 'Аналитическая панель заказов'
    ];
    
    $description = $pageDescriptions[$pageName] ?? "Страница: {$pageName}";
    
    if (isset($params['order_id'])) {
        $orderDesc = getOrderDescription($pdo, $params['order_id']);
        $description .= " - {$orderDesc}";
    }
    
    logOrderAction($pdo, 'page_access', $params['order_id'] ?? null, $description);
}

/**
 * Quick logging functions for common operations
 */

function logOrderCreate($pdo, $orderId, $orderData = []) {
    $orderDesc = getOrderDescription($pdo, $orderId);
    $clientName = isset($orderData['client_id']) ? getClientName($pdo, $orderData['client_id']) : '';
    $description = "Создание заказа: {$orderDesc}";
    if ($clientName) $description .= " для клиента: {$clientName}";
    
    logOrderAction($pdo, 'order_create', $orderId, $description);
}

function logOrderView($pdo, $orderId) {
    $orderDesc = getOrderDescription($pdo, $orderId);
    logOrderAction($pdo, 'order_view', $orderId, "Просмотр заказа: {$orderDesc}");
}

function logOrderUpdate($pdo, $orderId, $changes = '') {
    $orderDesc = getOrderDescription($pdo, $orderId);
    $description = "Изменение заказа: {$orderDesc}";
    if ($changes) $description .= " - {$changes}";
    
    logOrderAction($pdo, 'order_update', $orderId, $description);
}

function logStatusChange($pdo, $orderId, $oldStatus, $newStatus, $reason = '') {
    $orderDesc = getOrderDescription($pdo, $orderId);
    $oldStatusName = getStatusName($pdo, $oldStatus);
    $newStatusName = getStatusName($pdo, $newStatus);
    
    $description = "Изменение статуса заказа: {$orderDesc} с '{$oldStatusName}' на '{$newStatusName}'";
    if ($reason) $description .= " - Причина: {$reason}";
    
    logOrderAction($pdo, 'status_change', $orderId, $description);
}

function logCourierAssign($pdo, $orderId, $courierId) {
    $orderDesc = getOrderDescription($pdo, $orderId);
    $courierName = getCourierName($pdo, $courierId);
    
    logOrderAction($pdo, 'courier_assign', $orderId, "Назначение курьера: {$courierName} на заказ: {$orderDesc}");
}

function logDocumentGenerate($pdo, $orderId, $documentType) {
    $orderDesc = getOrderDescription($pdo, $orderId);
    $docTypes = [
        'official_order' => 'Официальный заказ',
        'client_conductor' => 'Документ клиент-проводник',
        'conductor_courier' => 'Документ проводник-курьер',
        'pdf' => 'PDF документ',
        'word' => 'Word документ'
    ];
    
    $docTypeName = $docTypes[$documentType] ?? $documentType;
    logOrderAction($pdo, 'document_generate', $orderId, "Генерация документа '{$docTypeName}' для заказа: {$orderDesc}");
}

function logFileOperation($pdo, $orderId, $operation, $fileName = '') {
    $orderDesc = getOrderDescription($pdo, $orderId);
    $operations = [
        'upload' => 'Загрузка файла',
        'download' => 'Скачивание файла', 
        'delete' => 'Удаление файла',
        'view' => 'Просмотр файла'
    ];
    
    $opName = $operations[$operation] ?? $operation;
    $description = "{$opName}";
    if ($fileName) $description .= " '{$fileName}'";
    $description .= " для заказа: {$orderDesc}";
    
    logOrderAction($pdo, "file_{$operation}", $orderId, $description);
}

function logExportOperation($pdo, $format, $recordCount = null, $filters = []) {
    $formats = [
        'csv' => 'CSV',
        'pdf' => 'PDF', 
        'word' => 'Word'
    ];
    
    $formatName = $formats[$format] ?? $format;
    $description = "Экспорт заказов в формате {$formatName}";
    
    if ($recordCount !== null) {
        $description .= " - {$recordCount} записей";
    }
    
    if (!empty($filters)) {
        $filterDesc = [];
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $filterDesc[] = "{$key}: {$value}";
            }
        }
        if (!empty($filterDesc)) {
            $description .= " с фильтрами: " . implode(', ', $filterDesc);
        }
    }
    
    logOrderAction($pdo, "export_{$format}", null, $description);
}

?> 