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
    $dateRange = $_GET['date_range'] ?? '30'; // Default to last 30 days
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    // Determine date range
    if ($startDate && $endDate) {
        $dateCondition = "DATE(o.Order_date) BETWEEN ? AND ?";
        $dateParams = [$startDate, $endDate];
    } else {
        $dateCondition = "o.Order_date >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $dateParams = [intval($dateRange)];
    }
    
    $statistics = [];
    
    // 1. Overall Order Statistics
    $overallStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN o.Status = 1 THEN 1 END) as new_orders,
            COUNT(CASE WHEN o.Status = 2 THEN 1 END) as in_progress_orders,
            COUNT(CASE WHEN o.Status = 4 THEN 1 END) as completed_orders,
            COUNT(CASE WHEN o.Status = 6 THEN 1 END) as cancelled_orders,
            COALESCE(SUM(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as completed_revenue,
            COALESCE(AVG(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as avg_order_value
        FROM Orders o 
        WHERE $dateCondition
    ");
    $overallStmt->execute($dateParams);
    $statistics['overall'] = $overallStmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Status Distribution
    $statusStmt = $pdo->prepare("
        SELECT 
            los.Status_name,
            los.Status_color,
            COUNT(o.Order_id) as count,
            ROUND((COUNT(o.Order_id) * 100.0 / (SELECT COUNT(*) FROM Orders WHERE $dateCondition)), 2) as percentage
        FROM list_order_status los
        LEFT JOIN Orders o ON los.Status_id = o.Status AND $dateCondition
        GROUP BY los.Status_id, los.Status_name, los.Status_color
        ORDER BY count DESC
    ");
    $statusStmt->execute(array_merge($dateParams, $dateParams));
    $statistics['status_distribution'] = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Daily Order Trends (Last 30 days)
    $trendsStmt = $pdo->prepare("
        SELECT 
            DATE(o.Order_date) as order_date,
            COUNT(*) as total_orders,
            COUNT(CASE WHEN o.Status = 4 THEN 1 END) as completed_orders,
            COALESCE(SUM(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as daily_revenue
        FROM Orders o
        WHERE o.Order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(o.Order_date)
        ORDER BY order_date ASC
    ");
    $trendsStmt->execute();
    $statistics['daily_trends'] = $trendsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Top Clients by Order Count
    $topClientsStmt = $pdo->prepare("
        SELECT 
            c.Full_Company_name as client_name,
            COUNT(o.Order_id) as order_count,
            COALESCE(SUM(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as total_revenue,
            COALESCE(AVG(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as avg_order_value
        FROM Clients c
        LEFT JOIN Orders o ON c.Client_id = o.Client_id AND $dateCondition
        GROUP BY c.Client_id, c.Full_Company_name
        HAVING order_count > 0
        ORDER BY order_count DESC
        LIMIT 10
    ");
    $topClientsStmt->execute($dateParams);
    $statistics['top_clients'] = $topClientsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Courier Performance
    $courierPerformanceStmt = $pdo->prepare("
        SELECT 
            co.Full_Company_name as courier_name,
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
        GROUP BY co.Courier_id, co.Full_Company_name
        HAVING assigned_orders > 0
        ORDER BY completion_rate DESC, assigned_orders DESC
        LIMIT 10
    ");
    $courierPerformanceStmt->execute($dateParams);
    $statistics['courier_performance'] = $courierPerformanceStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Revenue by Shipping Type
    $shippingRevenueStmt = $pdo->prepare("
        SELECT 
            st.Shipping_type_name,
            COUNT(o.Order_id) as order_count,
            COALESCE(SUM(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as total_revenue,
            COALESCE(AVG(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as avg_revenue
        FROM list_shipping_types st
        LEFT JOIN Orders o ON st.Shipping_type_id = o.Shipping_type AND $dateCondition
        GROUP BY st.Shipping_type_id, st.Shipping_type_name
        HAVING order_count > 0
        ORDER BY total_revenue DESC
    ");
    $shippingRevenueStmt->execute($dateParams);
    $statistics['shipping_revenue'] = $shippingRevenueStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. Recent Activity (Last 10 status changes)
    $recentActivityStmt = $pdo->prepare("
        SELECT 
            o.display_order_number,
            c.Full_Company_name as client_name,
            prev_status.Status_name as previous_status,
            new_status.Status_name as new_status,
            new_status.Status_color as new_status_color,
            u.Name as changed_by,
            osh.Change_date,
            osh.Change_reason
        FROM Order_status_history osh
        JOIN Orders o ON osh.Order_id = o.Order_id
        LEFT JOIN Clients c ON o.Client_id = c.Client_id
        LEFT JOIN list_order_status prev_status ON osh.Previous_status = prev_status.Status_id
        JOIN list_order_status new_status ON osh.New_status = new_status.Status_id
        LEFT JOIN Users u ON osh.Changed_by = u.User_id
        ORDER BY osh.Change_date DESC
        LIMIT 10
    ");
    $recentActivityStmt->execute();
    $recentActivity = $recentActivityStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates for recent activity
    foreach ($recentActivity as &$activity) {
        $activity['Change_date'] = date('d.m.Y H:i', strtotime($activity['Change_date']));
    }
    $statistics['recent_activity'] = $recentActivity;
    
    // 8. Monthly Revenue Comparison (Current vs Previous Month)
    $monthlyComparisonStmt = $pdo->prepare("
        SELECT 
            YEAR(o.Order_date) as year,
            MONTH(o.Order_date) as month,
            COUNT(*) as order_count,
            COALESCE(SUM(CASE WHEN o.Status = 4 THEN o.Total_price END), 0) as revenue
        FROM Orders o
        WHERE o.Order_date >= DATE_SUB(DATE_SUB(CURDATE(), INTERVAL DAY(CURDATE())-1 DAY), INTERVAL 1 MONTH)
        GROUP BY YEAR(o.Order_date), MONTH(o.Order_date)
        ORDER BY year DESC, month DESC
        LIMIT 2
    ");
    $monthlyComparisonStmt->execute();
    $monthlyData = $monthlyComparisonStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $statistics['monthly_comparison'] = [
        'current_month' => $monthlyData[0] ?? ['order_count' => 0, 'revenue' => 0],
        'previous_month' => $monthlyData[1] ?? ['order_count' => 0, 'revenue' => 0]
    ];
    
    // Calculate growth percentages
    if (isset($monthlyData[1]) && $monthlyData[1]['revenue'] > 0) {
        $statistics['monthly_comparison']['revenue_growth'] = round(
            (($monthlyData[0]['revenue'] - $monthlyData[1]['revenue']) / $monthlyData[1]['revenue']) * 100, 2
        );
    } else {
        $statistics['monthly_comparison']['revenue_growth'] = 0;
    }
    
    if (isset($monthlyData[1]) && $monthlyData[1]['order_count'] > 0) {
        $statistics['monthly_comparison']['order_growth'] = round(
            (($monthlyData[0]['order_count'] - $monthlyData[1]['order_count']) / $monthlyData[1]['order_count']) * 100, 2
        );
    } else {
        $statistics['monthly_comparison']['order_growth'] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'statistics' => $statistics,
        'date_range' => $dateRange,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Error getting dashboard statistics: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 