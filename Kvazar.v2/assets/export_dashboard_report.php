<?php
session_start();
require_once '../config/db_connect.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized');
}

try {
    $exportType = $_GET['type'] ?? 'csv'; // csv or excel
    $reportType = $_GET['report'] ?? 'overview'; // overview, clients, couriers, orders
    $dateRange = $_GET['date_range'] ?? '30';
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    // Determine date range
    if ($startDate && $endDate) {
        $dateCondition = "DATE(o.Order_date) BETWEEN ? AND ?";
        $dateParams = [$startDate, $endDate];
        $dateLabel = "from_{$startDate}_to_{$endDate}";
    } else {
        $dateCondition = "o.Order_date >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $dateParams = [intval($dateRange)];
        $dateLabel = "last_{$dateRange}_days";
    }
    
    $filename = "kvazar_dashboard_{$reportType}_{$dateLabel}_" . date('Y-m-d');
    
    if ($exportType === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$filename}.csv");
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8 Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        switch ($reportType) {
            case 'overview':
                exportOverviewReport($pdo, $output, $dateCondition, $dateParams);
                break;
            case 'clients':
                exportClientsReport($pdo, $output, $dateCondition, $dateParams);
                break;
            case 'couriers':
                exportCouriersReport($pdo, $output, $dateCondition, $dateParams);
                break;
            case 'orders':
                exportOrdersReport($pdo, $output, $dateCondition, $dateParams);
                break;
            default:
                exportOverviewReport($pdo, $output, $dateCondition, $dateParams);
        }
        
        fclose($output);
    } else {
        // For now, redirect to CSV. Excel export would require PHPSpreadsheet
        header("Location: export_dashboard_report.php?type=csv&report={$reportType}&date_range={$dateRange}" . 
               ($startDate ? "&start_date={$startDate}&end_date={$endDate}" : ""));
    }
    
} catch (Exception $e) {
    error_log("Error exporting dashboard report: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Export error: ' . $e->getMessage());
}

function exportOverviewReport($pdo, $output, $dateCondition, $dateParams) {
    // Header
    fputcsv($output, ['KVAZAR LOGISTICS - Dashboard Overview Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Overall statistics
    $overallStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN o.Status = 1 THEN 1 END) as new_orders,
            COUNT(CASE WHEN o.Status = 2 THEN 1 END) as in_progress_orders,
            COUNT(CASE WHEN o.Status = 4 THEN 1 END) as completed_orders,
            COUNT(CASE WHEN o.Status = 6 THEN 1 END) as cancelled_orders,
            COALESCE(SUM(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as completed_revenue
        FROM Orders o 
        WHERE $dateCondition
    ");
    $overallStmt->execute($dateParams);
    $overall = $overallStmt->fetch(PDO::FETCH_ASSOC);
    
    fputcsv($output, ['Overall Statistics']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Orders', $overall['total_orders']]);
    fputcsv($output, ['New Orders', $overall['new_orders']]);
    fputcsv($output, ['In Progress Orders', $overall['in_progress_orders']]);
    fputcsv($output, ['Completed Orders', $overall['completed_orders']]);
    fputcsv($output, ['Cancelled Orders', $overall['cancelled_orders']]);
    fputcsv($output, ['Total Revenue', number_format($overall['completed_revenue'], 2)]);
    fputcsv($output, []);
    
    // Status distribution
    $statusStmt = $pdo->prepare("
        SELECT 
            los.Status_name,
            COUNT(o.Order_id) as count,
            ROUND((COUNT(o.Order_id) * 100.0 / (SELECT COUNT(*) FROM Orders WHERE $dateCondition)), 2) as percentage
        FROM list_order_status los
        LEFT JOIN Orders o ON los.Status_id = o.Status AND $dateCondition
        GROUP BY los.Status_id, los.Status_name
        ORDER BY count DESC
    ");
    $statusStmt->execute(array_merge($dateParams, $dateParams));
    
    fputcsv($output, ['Status Distribution']);
    fputcsv($output, ['Status', 'Count', 'Percentage']);
    while ($row = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [$row['Status_name'], $row['count'], $row['percentage'] . '%']);
    }
}

function exportClientsReport($pdo, $output, $dateCondition, $dateParams) {
    fputcsv($output, ['KVAZAR LOGISTICS - Clients Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    $clientsStmt = $pdo->prepare("
        SELECT 
            c.Full_Company_name as client_name,
            c.Contact_person,
            c.Phone_number,
            c.Email,
            COUNT(o.Order_id) as order_count,
            COUNT(CASE WHEN o.Status = 4 THEN 1 END) as completed_orders,
            COUNT(CASE WHEN o.Status = 6 THEN 1 END) as cancelled_orders,
            COALESCE(SUM(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as total_revenue,
            COALESCE(AVG(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as avg_order_value
        FROM Clients c
        LEFT JOIN Orders o ON c.Client_id = o.Client_id AND $dateCondition
        GROUP BY c.Client_id, c.Full_Company_name, c.Contact_person, c.Phone_number, c.Email
        ORDER BY order_count DESC
    ");
    $clientsStmt->execute($dateParams);
    
    fputcsv($output, ['Client Name', 'Contact Person', 'Phone', 'Email', 'Total Orders', 'Completed', 'Cancelled', 'Total Revenue', 'Avg Order Value']);
    
    while ($row = $clientsStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['client_name'],
            $row['Contact_person'],
            $row['Phone_number'],
            $row['Email'],
            $row['order_count'],
            $row['completed_orders'],
            $row['cancelled_orders'],
            number_format($row['total_revenue'], 2),
            number_format($row['avg_order_value'], 2)
        ]);
    }
}

function exportCouriersReport($pdo, $output, $dateCondition, $dateParams) {
    fputcsv($output, ['KVAZAR LOGISTICS - Couriers Performance Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    $couriersStmt = $pdo->prepare("
        SELECT 
            co.Full_Company_name as courier_name,
            co.Contact_person,
            co.Phone_number,
            COUNT(o.Order_id) as assigned_orders,
            COUNT(CASE WHEN o.Status = 4 THEN 1 END) as completed_orders,
            COUNT(CASE WHEN o.Status = 6 THEN 1 END) as cancelled_orders,
            CASE 
                WHEN COUNT(o.Order_id) > 0 
                THEN ROUND((COUNT(CASE WHEN o.Status = 4 THEN 1 END) * 100.0 / COUNT(o.Order_id)), 2)
                ELSE 0 
            END as completion_rate,
            COALESCE(SUM(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as total_revenue
        FROM Couriers co
        LEFT JOIN Orders o ON co.Courier_id = o.Courier_id AND $dateCondition
        GROUP BY co.Courier_id, co.Full_Company_name, co.Contact_person, co.Phone_number
        ORDER BY completion_rate DESC, assigned_orders DESC
    ");
    $couriersStmt->execute($dateParams);
    
    fputcsv($output, ['Courier Name', 'Contact Person', 'Phone', 'Assigned Orders', 'Completed', 'Cancelled', 'Completion Rate', 'Total Revenue']);
    
    while ($row = $couriersStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['courier_name'],
            $row['Contact_person'],
            $row['Phone_number'],
            $row['assigned_orders'],
            $row['completed_orders'],
            $row['cancelled_orders'],
            $row['completion_rate'] . '%',
            number_format($row['total_revenue'], 2)
        ]);
    }
}

function exportOrdersReport($pdo, $output, $dateCondition, $dateParams) {
    fputcsv($output, ['KVAZAR LOGISTICS - Orders Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    $ordersStmt = $pdo->prepare("
        SELECT 
            o.display_order_number,
            DATE(o.Order_date) as order_date,
            c.Full_Company_name as client_name,
            co.Full_Company_name as courier_name,
            los.Status_name as status,
            o.Cargo_name,
            o.Total_price,
            lst.Shipping_type_name as shipping_type
        FROM Orders o
        LEFT JOIN Clients c ON o.Client_id = c.Client_id
        LEFT JOIN Couriers co ON o.Courier_id = co.Courier_id
        LEFT JOIN list_order_status los ON o.Status = los.Status_id
        LEFT JOIN list_shipping_types lst ON o.Shipping_type = lst.Shipping_type_id
        WHERE $dateCondition
        ORDER BY o.Order_date DESC
    ");
    $ordersStmt->execute($dateParams);
    
    fputcsv($output, ['Order Number', 'Date', 'Client', 'Courier', 'Status', 'Cargo', 'Total Price', 'Shipping Type']);
    
    while ($row = $ordersStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['display_order_number'],
            $row['order_date'],
            $row['client_name'],
            $row['courier_name'] ?: 'Not assigned',
            $row['status'],
            $row['Cargo_name'],
            number_format($row['Total_price'], 2),
            $row['shipping_type']
        ]);
    }
}
?> 