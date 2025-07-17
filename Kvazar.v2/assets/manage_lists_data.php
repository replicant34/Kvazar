<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if lists access is granted
if (!isset($_SESSION['lists_access_granted']) || !$_SESSION['lists_access_granted']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Password required.']);
    exit();
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'load':
            loadTableData($pdo, $input);
            break;
        case 'load_relationship':
            loadRelationshipData($pdo, $input);
            break;
        case 'create':
            createItem($pdo, $input);
            break;
        case 'update':
            updateItem($pdo, $input);
            break;
        case 'delete':
            deleteItem($pdo, $input);
            break;
        case 'check_usage':
            checkItemUsage($pdo, $input);
            break;
        case 'download_csv':
            downloadCSV($pdo, $input);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Logging function
function logAction($pdo, $actionType, $tableName, $recordId, $description) {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // Handle IPv6 localhost
        if ($ipAddress === '::1') {
            $ipAddress = '127.0.0.1';
        }
        
        // Translate action types to Russian
        $russianActionTypes = [
            'access_granted' => 'доступ_разрешен',
            'access_denied' => 'доступ_запрещен',
            'page_access' => 'доступ_к_странице',
            'view' => 'просмотр',
            'create' => 'создание',
            'update' => 'изменение',
            'delete' => 'удаление',
            'delete_failed' => 'удаление_заблокировано',
            'export' => 'экспорт'
        ];
        
        $russianActionType = $russianActionTypes[$actionType] ?? $actionType;
        
        $stmt = $pdo->prepare("
            INSERT INTO action_logs (user_id, action_type, table_name, record_id, description, ip_address, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$userId, $russianActionType, $tableName, $recordId, $description, $ipAddress]);
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Failed to log action: " . $e->getMessage());
    }
}

// Get Russian table name for logging
function getRussianTableName($tableName) {
    $tableNames = [
        'Contractors' => 'Подрядчики',
        'list_extra_service' => 'Дополнительные услуги',
        'list_actions_passwords' => 'Пароли действий',
        'list_cargo_name' => 'Названия грузов',
        'list_contract_status' => 'Статусы договоров',
        'list_contract_type' => 'Типы договоров',
        'list_currency' => 'Валюты',
        'list_loading_type' => 'Типы загрузки',
        'list_order_status' => 'Статусы заказов',
        'list_packaging_type' => 'Типы упаковки',
        'list_partners_status' => 'Статусы партнеров',
        'list_shipping_type' => 'Типы доставки',
        'list_transport_type' => 'Типы транспорта',
        'list_rates' => 'Тарифы',
        'Vehicles' => 'Транспорт',
        'Drivers' => 'Водители'
    ];
    
    return $tableNames[$tableName] ?? $tableName;
}

// Get item description for logging
function getItemDescription($pdo, $tableName, $recordId, $data = null) {
    try {
        if ($data && is_array($data)) {
            // For new items, use provided data
            switch ($tableName) {
                case 'Contractors':
                    return $data['Full_Company_name'] ?? 'Новый подрядчик';
                case 'list_extra_service':
                    return $data['service_name'] ?? 'Новая услуга';
                case 'list_cargo_name':
                    return $data['cargo_name'] ?? 'Новый тип груза';
                case 'list_contract_status':
                case 'list_order_status':
                case 'list_partners_status':
                    return $data['Status_name'] ?? 'Новый статус';
                case 'list_contract_type':
                case 'list_shipping_type':
                case 'list_transport_type':
                    return $data['Type_name'] ?? 'Новый тип';
                case 'list_currency':
                    return $data['currency_name'] ?? $data['currency_code'] ?? 'Новая валюта';
                case 'list_loading_type':
                    return $data['loading_name'] ?? 'Новый тип загрузки';
                case 'list_packaging_type':
                    return $data['packaging_name'] ?? 'Новый тип упаковки';
                case 'list_rates':
                    return 'Договор №' . ($data['contract_number'] ?? 'Новый тариф');
                case 'Vehicles':
                    return ($data['Brand'] ?? '') . ' ' . ($data['Plate_number'] ?? 'Новый автомобиль');
                case 'Drivers':
                    return $data['Name'] ?? 'Новый водитель';
                default:
                    return 'Новая запись';
            }
        }
        
        // For existing items, get from database
        if (!$recordId) return 'Неизвестная запись';
        
        $primaryKey = getPrimaryKey($tableName);
        
        switch ($tableName) {
            case 'Contractors':
                $stmt = $pdo->prepare("SELECT Full_Company_name FROM {$tableName} WHERE {$primaryKey} = ?");
                $stmt->execute([$recordId]);
                return $stmt->fetchColumn() ?: "Подрядчик ID: {$recordId}";
                
            case 'list_extra_service':
                $stmt = $pdo->prepare("SELECT service_name FROM {$tableName} WHERE {$primaryKey} = ?");
                $stmt->execute([$recordId]);
                return $stmt->fetchColumn() ?: "Услуга ID: {$recordId}";
                
            case 'list_cargo_name':
                $stmt = $pdo->prepare("SELECT cargo_name FROM {$tableName} WHERE {$primaryKey} = ?");
                $stmt->execute([$recordId]);
                return $stmt->fetchColumn() ?: "Груз ID: {$recordId}";
                
            case 'list_contract_status':
            case 'list_order_status':
            case 'list_partners_status':
                $stmt = $pdo->prepare("SELECT Status_name FROM {$tableName} WHERE {$primaryKey} = ?");
                $stmt->execute([$recordId]);
                return $stmt->fetchColumn() ?: "Статус ID: {$recordId}";
                
            case 'list_contract_type':
            case 'list_shipping_type':
            case 'list_transport_type':
                $stmt = $pdo->prepare("SELECT Type_name FROM {$tableName} WHERE {$primaryKey} = ?");
                $stmt->execute([$recordId]);
                return $stmt->fetchColumn() ?: "Тип ID: {$recordId}";
                
            case 'list_currency':
                $stmt = $pdo->prepare("SELECT currency_name FROM {$tableName} WHERE {$primaryKey} = ?");
                $stmt->execute([$recordId]);
                return $stmt->fetchColumn() ?: "Валюта ID: {$recordId}";
                
            case 'list_loading_type':
                $stmt = $pdo->prepare("SELECT loading_name FROM {$tableName} WHERE {$primaryKey} = ?");
                $stmt->execute([$recordId]);
                return $stmt->fetchColumn() ?: "Тип загрузки ID: {$recordId}";
                
            case 'list_packaging_type':
                $stmt = $pdo->prepare("SELECT packaging_name FROM {$tableName} WHERE {$primaryKey} = ?");
                $stmt->execute([$recordId]);
                return $stmt->fetchColumn() ?: "Упаковка ID: {$recordId}";
                
            case 'list_rates':
                $stmt = $pdo->prepare("SELECT contract_number FROM {$tableName} WHERE {$primaryKey} = ?");
                $stmt->execute([$recordId]);
                $contractNumber = $stmt->fetchColumn();
                return $contractNumber ? "Договор №{$contractNumber}" : "Тариф ID: {$recordId}";
                
            case 'Vehicles':
                $stmt = $pdo->prepare("SELECT CONCAT(COALESCE(Brand, ''), ' ', COALESCE(Plate_number, '')) FROM {$tableName} WHERE {$primaryKey} = ?");
                $stmt->execute([$recordId]);
                return trim($stmt->fetchColumn()) ?: "Автомобиль ID: {$recordId}";
                
            case 'Drivers':
                $stmt = $pdo->prepare("SELECT Name FROM {$tableName} WHERE {$primaryKey} = ?");
                $stmt->execute([$recordId]);
                return $stmt->fetchColumn() ?: "Водитель ID: {$recordId}";
                
            default:
                return "Запись ID: {$recordId}";
        }
    } catch (Exception $e) {
        return "Запись ID: {$recordId}";
    }
}

// Get primary key for table
function getPrimaryKey($tableName) {
    $primaryKeys = [
        'Contractors' => 'Contractors_id',
        'list_extra_service' => 'id',
        'list_actions_passwords' => 'Action_id',
        'list_cargo_name' => 'id',
        'list_contract_status' => 'Status_id',
        'list_contract_type' => 'Type_id',
        'list_currency' => 'id',
        'list_loading_type' => 'id',
        'list_order_status' => 'Status_id',
        'list_packaging_type' => 'id',
        'list_partners_status' => 'Status_id',
        'list_shipping_type' => 'Type_id',
        'list_transport_type' => 'Type_id',
        'list_rates' => 'id',
        'Vehicles' => 'Vehicle_id',
        'Drivers' => 'Driver_id'
    ];
    
    return $primaryKeys[$tableName] ?? 'id';
}

function loadTableData($pdo, $input) {
    $table = sanitizeTableName($input['table']);
    $columns = $input['columns'] ?? ['*'];
    
    // Build column list
    $columnList = '*';
    if (is_array($columns) && !in_array('*', $columns)) {
        $columnList = implode(', ', array_map('sanitizeColumnName', $columns));
    }
    
    $sql = "SELECT {$columnList} FROM {$table} ORDER BY 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log view action
    $russianTableName = getRussianTableName($table);
    logAction($pdo, 'view', $table, null, "Просмотр списка: {$russianTableName}");
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function loadRelationshipData($pdo, $input) {
    $table = sanitizeTableName($input['table']);
    
    // Get first two columns for relationships
    $sql = "SHOW COLUMNS FROM {$table}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($columns) >= 2) {
        $col1 = sanitizeColumnName($columns[0]);
        $col2 = sanitizeColumnName($columns[1]);
        $sql = "SELECT {$col1}, {$col2} FROM {$table} ORDER BY {$col2}";
    } else {
        $sql = "SELECT * FROM {$table} ORDER BY 1";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function createItem($pdo, $input) {
    $table = sanitizeTableName($input['table']);
    $data = $input['data'] ?? [];
    
    if (empty($data)) {
        throw new Exception('No data provided');
    }
    
    // Remove empty values and auto-increment fields
    $data = array_filter($data, function($value, $key) {
        return $value !== '' && $value !== null && !preg_match('/_id$/', $key);
    }, ARRAY_FILTER_USE_BOTH);
    
    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '?');
    
    $sql = "INSERT INTO {$table} (" . implode(', ', array_map('sanitizeColumnName', $columns)) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    
    $insertId = $pdo->lastInsertId();
    
    // Log creation
    $russianTableName = getRussianTableName($table);
    $itemDescription = getItemDescription($pdo, $table, $insertId, $data);
    logAction($pdo, 'create', $table, $insertId, "Создание записи в '{$russianTableName}': {$itemDescription}");
    
    echo json_encode(['success' => true, 'id' => $insertId, 'message' => 'Элемент добавлен']);
}

function updateItem($pdo, $input) {
    $table = sanitizeTableName($input['table']);
    $id = $input['id'];
    $column = sanitizeColumnName($input['column']);
    $value = $input['value'];
    $primaryKey = sanitizeColumnName($input['primaryKey']);
    
    // Get old value for logging
    $oldValueStmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE {$primaryKey} = ?");
    $oldValueStmt->execute([$id]);
    $oldValue = $oldValueStmt->fetchColumn();
    
    // Special handling for boolean fields
    if (in_array($column, ['request_password']) && is_string($value)) {
        $value = ($value === '1' || $value === 'true' || $value === 'on') ? 1 : 0;
    }
    
    $sql = "UPDATE {$table} SET {$column} = ? WHERE {$primaryKey} = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$value, $id]);
    
    if ($stmt->rowCount() > 0) {
        // Log update
        $russianTableName = getRussianTableName($table);
        $itemDescription = getItemDescription($pdo, $table, $id);
        $columnNames = [
            'Full_Company_name' => 'Полное название',
            'Short_Company_name' => 'Короткое название',
            'Company_type' => 'Тип компании',
            'INN' => 'ИНН',
            'KPP' => 'КПП',
            'service_name' => 'Название услуги',
            'price' => 'Цена',
            'cargo_name' => 'Название груза',
            'Status_name' => 'Название статуса',
            'Status_color' => 'Цвет статуса',
            'Type_name' => 'Название типа',
            'currency_name' => 'Название валюты',
            'currency_code' => 'Код валюты',
            'loading_name' => 'Тип загрузки',
            'packaging_name' => 'Тип упаковки',
            'contract_number' => 'Номер договора',
            'rate' => 'Тариф',
            'Brand' => 'Марка',
            'Plate_number' => 'Номер',
            'Name' => 'Имя',
            'Phone_number' => 'Телефон',
            'request_password' => 'Требуется пароль',
            'Action_password' => 'Пароль действия'
        ];
        
        $columnDisplayName = $columnNames[$column] ?? $column;
        $logDescription = "Изменение в '{$russianTableName}' - {$itemDescription}: {$columnDisplayName} изменено с '{$oldValue}' на '{$value}'";
        
        logAction($pdo, 'update', $table, $id, $logDescription);
        
        echo json_encode(['success' => true, 'message' => 'Изменения сохранены']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Не удалось сохранить изменения']);
    }
}

function deleteItem($pdo, $input) {
    $table = sanitizeTableName($input['table']);
    $id = $input['id'];
    $primaryKey = sanitizeColumnName($input['primaryKey']);
    
    // Get item description before deletion
    $itemDescription = getItemDescription($pdo, $table, $id);
    
    // Check if item is used in orders or other relations
    $usageInfo = checkItemUsageInternal($pdo, $table, $id, $primaryKey);
    
    if (!empty($usageInfo['orders'])) {
        $orderNumbers = implode(', ', $usageInfo['orders']);
        
        // Log failed deletion attempt
        $russianTableName = getRussianTableName($table);
        logAction($pdo, 'delete_failed', $table, $id, "Попытка удаления из '{$russianTableName}' - {$itemDescription}: Заблокировано, используется в заказах: {$orderNumbers}");
        
        throw new Exception("Невозможно удалить. Элемент используется в заказах: {$orderNumbers}");
    }
    
    if (!empty($usageInfo['relations'])) {
        $relations = implode(', ', $usageInfo['relations']);
        
        // Log failed deletion attempt
        $russianTableName = getRussianTableName($table);
        logAction($pdo, 'delete_failed', $table, $id, "Попытка удаления из '{$russianTableName}' - {$itemDescription}: Заблокировано, используется в связанных таблицах: {$relations}");
        
        throw new Exception("Невозможно удалить. Элемент используется в связанных таблицах: {$relations}");
    }
    
    $sql = "DELETE FROM {$table} WHERE {$primaryKey} = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        // Log successful deletion
        $russianTableName = getRussianTableName($table);
        logAction($pdo, 'delete', $table, $id, "Удаление из '{$russianTableName}': {$itemDescription}");
        
        echo json_encode(['success' => true, 'message' => 'Элемент удален']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Элемент не найден']);
    }
}

function checkItemUsage($pdo, $input) {
    $table = sanitizeTableName($input['table']);
    $id = $input['id'];
    $primaryKey = sanitizeColumnName($input['primaryKey']);
    
    $usage = checkItemUsageInternal($pdo, $table, $id, $primaryKey);
    
    echo json_encode(['success' => true, 'usage' => $usage]);
}

function checkItemUsageInternal($pdo, $table, $id, $primaryKey) {
    $usage = ['orders' => [], 'relations' => []];
    
    // Table relationships mapping
    $relationships = [
        'Contractors' => [
            'orders' => ['Orders' => 'Contractor_id'],
            'relations' => []
        ],
        'list_cargo_names' => [
            'orders' => ['Order_cargo' => 'Cargo_name_id'],
            'relations' => []
        ],
        'list_contract_status' => [
            'orders' => [],
            'relations' => ['Contracts' => 'Contract_status_id']
        ],
        'list_contract_types' => [
            'orders' => [],
            'relations' => ['Contracts' => 'Contract_type_id']
        ],
        'list_currencies' => [
            'orders' => ['Orders' => 'Currency_id'],
            'relations' => ['Rates' => 'Rate_currency']
        ],
        'list_loading_types' => [
            'orders' => ['Orders' => 'Loading_type_id'],
            'relations' => []
        ],
        'list_order_status' => [
            'orders' => ['Orders' => 'Order_status_id'],
            'relations' => []
        ],
        'list_packaging_types' => [
            'orders' => ['Order_cargo' => 'Packaging_type_id'],
            'relations' => []
        ],
        'list_partner_status' => [
            'orders' => [],
            'relations' => ['Partners' => 'Partner_status_id']
        ],
        'list_shipping_types' => [
            'orders' => ['Orders' => 'Shipping_type_id'],
            'relations' => []
        ],
        'list_transport_types' => [
            'orders' => [],
            'relations' => ['Vehicle_list' => 'Transport_type_id', 'Rates' => 'Transport_type_id']
        ],
        'Couriers' => [
            'orders' => ['Orders' => 'Courier_id'],
            'relations' => ['Drivers_list' => 'Courier_id', 'Vehicle_list' => 'Courier_id']
        ]
    ];
    
    if (!isset($relationships[$table])) {
        return $usage;
    }
    
    $tableRelations = $relationships[$table];
    
    // Check orders usage
    foreach ($tableRelations['orders'] as $orderTable => $column) {
        try {
            if ($orderTable === 'Orders') {
                $sql = "SELECT Order_number FROM Orders WHERE {$column} = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $orders = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $usage['orders'] = array_merge($usage['orders'], $orders);
            } else {
                // For cargo and other related tables, get order numbers through joins
                $sql = "SELECT DISTINCT o.Order_number 
                        FROM Orders o 
                        JOIN {$orderTable} ot ON o.Order_id = ot.Order_id 
                        WHERE ot.{$column} = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $orders = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $usage['orders'] = array_merge($usage['orders'], $orders);
            }
        } catch (Exception $e) {
            // Table might not exist or column might not exist
            continue;
        }
    }
    
    // Check relations usage
    foreach ($tableRelations['relations'] as $relTable => $column) {
        try {
            $sql = "SELECT COUNT(*) FROM {$relTable} WHERE {$column} = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $usage['relations'][] = "{$relTable} ({$count} записей)";
            }
        } catch (Exception $e) {
            // Table might not exist or column might not exist
            continue;
        }
    }
    
    return $usage;
}

function downloadCSV($pdo, $input) {
    $table = sanitizeTableName($input['table']);
    
    // Get all data
    $sql = "SELECT * FROM {$table} ORDER BY 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        throw new Exception('No data to export');
    }
    
    // Log CSV download
    $russianTableName = getRussianTableName($table);
    $recordCount = count($data);
    logAction($pdo, 'export', $table, null, "Экспорт CSV файла '{$russianTableName}': {$recordCount} записей");
    
    // Generate CSV
    $csv = [];
    
    // Headers
    $headers = array_keys($data[0]);
    $csv[] = implode(',', array_map(function($header) {
        return '"' . str_replace('"', '""', $header) . '"';
    }, $headers));
    
    // Data rows
    foreach ($data as $row) {
        $csvRow = [];
        foreach ($row as $value) {
            $csvRow[] = '"' . str_replace('"', '""', $value ?? '') . '"';
        }
        $csv[] = implode(',', $csvRow);
    }
    
    $csvContent = implode("\n", $csv);
    
    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Content-Length: ' . strlen($csvContent));
    
    echo $csvContent;
    exit();
}

function sanitizeTableName($table) {
    // Whitelist allowed table names - Updated to match actual database
    $allowedTables = [
        'Contractors', 'list_extra_service', 'list_actions_passwords',
        'list_cargo_name', 'list_contract_status', 'list_contract_type',
        'list_currency', 'list_loading_type', 'list_order_status',
        'list_packaging_type', 'list_partners_status', 'list_shipping_type',
        'list_transport_type', 'list_rates', 'Vehicles', 'Drivers',
        'Couriers', 'Partners', 'Contracts', 'Orders', 'Order_cargo'
    ];
    
    if (!in_array($table, $allowedTables)) {
        throw new Exception('Invalid table name');
    }
    
    return $table;
}

function sanitizeColumnName($column) {
    // Basic column name validation
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
        throw new Exception('Invalid column name');
    }
    
    return $column;
}

// Additional helper functions for complex operations
function getTableSchema($pdo, $table) {
    $sql = "SHOW COLUMNS FROM " . sanitizeTableName($table);
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function validateData($data, $table) {
    // Basic validation - can be extended
    $errors = [];
    
    // Required field validations
    switch ($table) {
        case 'Contractors':
            if (empty($data['Full_name'])) {
                $errors[] = 'Полное название обязательно';
            }
            if (empty($data['INN'])) {
                $errors[] = 'ИНН обязателен';
            }
            break;
        case 'list_currencies':
            if (empty($data['Currency_code'])) {
                $errors[] = 'Код валюты обязателен';
            }
            if (empty($data['Currency_name'])) {
                $errors[] = 'Название валюты обязательно';
            }
            break;
        // Add more validations as needed
    }
    
    return $errors;
}
?> 