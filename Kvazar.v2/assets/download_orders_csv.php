<?php
session_start();
require_once '../config/db_connect.php';
require_once 'order_logging.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    header('Location: ../index.php');
    exit();
}

try {
    // Get filter parameters
    $filters = [];
    $params = [];
    
    // Search filter
    if (!empty($_GET['search'])) {
        $searchTerm = '%' . $_GET['search'] . '%';
        $filters[] = "(
            o.display_order_number LIKE ? OR
            c.Full_Company_name LIKE ? OR
            cr.Full_Company_name LIKE ?
        )";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Status filter
    if (!empty($_GET['status'])) {
        $filters[] = "o.Status = ?";
        $params[] = $_GET['status'];
    }
    
    // Client filter
    if (!empty($_GET['client'])) {
        $filters[] = "o.Client_id = ?";
        $params[] = $_GET['client'];
    }
    
    // Courier filter
    if (!empty($_GET['courier'])) {
        $filters[] = "o.Courier_id = ?";
        $params[] = $_GET['courier'];
    }
    
    // Date range filter
    if (!empty($_GET['dateFrom'])) {
        $filters[] = "o.Order_date >= ?";
        $params[] = $_GET['dateFrom'];
    }
    
    if (!empty($_GET['dateTo'])) {
        $filters[] = "o.Order_date <= ?";
        $params[] = $_GET['dateTo'];
    }
    
    // Build WHERE clause
    $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
    
    // Map sort columns
    $sortMapping = [
        'status' => 'los.Status_name',
        'order_number' => 'o.display_order_number',
        'client_name' => 'c.Full_Company_name',
        'order_date' => 'o.Order_date',
        'courier_name' => 'cr.Full_Company_name',
        'order_total' => 'o.Order_total'
    ];
    
    $sortColumn = $sortMapping[$_GET['sort_column'] ?? 'order_date'] ?? 'o.Order_date';
    $sortDirection = strtoupper($_GET['sort_direction'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
    
    // Query for CSV export
    $query = "
        SELECT 
            o.display_order_number as 'Номер заказа',
            DATE_FORMAT(o.Order_date, '%d.%m.%Y') as 'Дата заказа',
            c.Full_Company_name as 'Клиент',
            cr.Full_Company_name as 'Перевозчик',
            los.Status_name as 'Статус',
            o.Shipping_type as 'Тип перевозки',
            o.Vehicle_type as 'Тип транспорта',
            o.Cargo_type as 'Тип груза',
            CONCAT(COALESCE(o.Weight, ''), ' ', COALESCE(o.Weight_unit, '')) as 'Вес',
            o.Volume as 'Объем (м³)',
            o.Quantity as 'Количество мест',
            o.Loading_type as 'Тип погрузки',
            o.Packing_type as 'Тип упаковки',
            CASE 
                WHEN o.Min_temperature IS NOT NULL AND o.Max_temperature IS NOT NULL 
                THEN CONCAT(o.Min_temperature, ' - ', o.Max_temperature, '°C')
                WHEN o.Min_temperature IS NOT NULL 
                THEN CONCAT('от ', o.Min_temperature, '°C')
                WHEN o.Max_temperature IS NOT NULL 
                THEN CONCAT('до ', o.Max_temperature, '°C')
                ELSE NULL
            END as 'Температурный режим',
            o.Temperature_record as 'Температурный лист',
            CONCAT(COALESCE(o.Cargo_price, ''), ' ', COALESCE(curr.currency_name, '')) as 'Стоимость груза',
            o.Rate as 'Коэффициент',
            CONCAT(COALESCE(o.Insurance_price, ''), ' ', COALESCE(curr.currency_name, '')) as 'Страхование',
            CONCAT(COALESCE(o.Total_price_vehicle, ''), ' ', COALESCE(curr.currency_name, '')) as 'Транспортировка',
            CONCAT(COALESCE(o.Order_total, ''), ' ', COALESCE(curr.currency_name, '')) as 'Общая сумма',
            DATE_FORMAT(o.Created_at, '%d.%m.%Y %H:%i') as 'Дата создания',
            DATE_FORMAT(o.Updated_at, '%d.%m.%Y %H:%i') as 'Дата обновления'
        FROM Orders o
        LEFT JOIN Clients c ON o.Client_id = c.Client_id
        LEFT JOIN Couriers cr ON o.Courier_id = cr.Courier_id
        LEFT JOIN list_order_status los ON o.Status = los.Status_id
        LEFT JOIN list_currency curr ON o.Currency_id = curr.id
        " . $whereClause . "
        ORDER BY " . $sortColumn . " " . $sortDirection;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate filename with timestamp
    $filename = 'orders_export_' . date('Y-m-d_H-i-s') . '.csv';
    
    // Log export operation
    logExportOperation($pdo, 'csv', count($orders), $_GET);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper UTF-8 encoding in Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers if we have data
    if (!empty($orders)) {
        fputcsv($output, array_keys($orders[0]), ';');
        
        // Add data rows
        foreach ($orders as $order) {
            fputcsv($output, $order, ';');
        }
    } else {
        // If no data, just add headers
        fputcsv($output, [
            'Номер заказа', 'Дата заказа', 'Клиент', 'Перевозчик', 'Статус',
            'Тип перевозки', 'Тип транспорта', 'Тип груза', 'Вес', 'Объем (м³)',
            'Количество мест', 'Тип погрузки', 'Тип упаковки', 'Температурный режим',
            'Температурный лист', 'Стоимость груза', 'Коэффициент', 'Страхование',
            'Транспортировка', 'Общая сумма', 'Дата создания', 'Дата обновления'
        ], ';');
    }
    
    fclose($output);
    exit();
    
} catch (Exception $e) {
    error_log("Error exporting orders CSV: " . $e->getMessage());
    http_response_code(500);
    echo "Ошибка при экспорте данных";
    exit();
}
?> 