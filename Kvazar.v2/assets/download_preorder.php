<?php
session_start();
require_once '../config/db_connect.php';
require_once '../vendor/autoload.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderId = intval($input['order_id'] ?? 0);
    $exportFormat = $input['export_format'] ?? 'pdf';
    $sections = $input['sections'] ?? [];
    
    if (!$orderId) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        exit();
    }
    
    // Get order data
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            c.Full_Company_name as client_name,
            c.Main_address as client_address,
            c.Phone_number as client_phone,
            cr.Full_Company_name as courier_name,
            cr.Main_address as courier_address,
            cr.Phone_number as courier_phone,
            curr.currency_name as currency_name,
            cc.Contract_number,
            cc.Contract_date,
            on.Note_content as order_notes
        FROM Orders o
        LEFT JOIN Clients c ON o.Client_id = c.Client_id
        LEFT JOIN Couriers cr ON o.Courier_id = cr.Courier_id
        LEFT JOIN list_currency curr ON o.Currency_id = curr.id
        LEFT JOIN Client_contracts cc ON o.Contract_id = cc.Contract_id
        LEFT JOIN Order_notes on ON o.Note_id = on.Note_id
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
            p.*,
            GROUP_CONCAT(
                CONCAT(cl.Name, ' - ', cl.Phone_number) 
                SEPARATOR '; '
            ) as contacts
        FROM Points p
        LEFT JOIN Contact_list cl ON p.Point_id = cl.Point_id
        WHERE p.Order_id = ?
        GROUP BY p.Point_id
        ORDER BY p.Position
    ");
    
    $routeStmt->execute([$orderId]);
    $routePoints = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get extra services
    $servicesStmt = $pdo->prepare("
        SELECT Service_name, Service_price, Quantity, Total
        FROM Extra_service
        WHERE Order_id = ?
        ORDER BY Service_name
    ");
    
    $servicesStmt->execute([$orderId]);
    $extraServices = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($exportFormat === 'pdf') {
        generatePDF($order, $routePoints, $extraServices, $sections);
    } else {
        generateWord($order, $routePoints, $extraServices, $sections);
    }
    
} catch (Exception $e) {
    error_log("Error downloading preorder: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Export error: ' . $e->getMessage()
    ]);
}

function generatePDF($order, $routePoints, $extraServices, $sections) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('KVAZAR LOGISTICS');
    $pdf->SetAuthor('KVAZAR LOGISTICS');
    $pdf->SetTitle('Предзаказ #' . $order['display_order_number']);
    $pdf->SetSubject('Предварительный заказ');
    
    // Set margins
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    
    // Set header and footer fonts
    $pdf->setHeaderFont(['dejavusans', '', 12]);
    $pdf->setFooterFont(['dejavusans', '', 8]);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('dejavusans', '', 10);
    
    // Header
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, 'KVAZAR LOGISTICS', 0, 1, 'C');
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->Cell(0, 8, 'ПРЕДВАРИТЕЛЬНЫЙ ЗАКАЗ', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Order header
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->Cell(0, 8, 'Заказ № ' . htmlspecialchars($order['display_order_number']), 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 6, 'Дата заказа: ' . formatDate($order['Order_date']), 0, 1, 'L');
    $pdf->Ln(3);
    
    // Basic Information Section
    if (in_array('basic_info', $sections)) {
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 8, 'ОСНОВНАЯ ИНФОРМАЦИЯ', 1, 1, 'L', 1);
        $pdf->SetFont('dejavusans', '', 9);
        
        $pdf->Cell(50, 6, 'Клиент:', 1, 0, 'L');
        $pdf->Cell(0, 6, htmlspecialchars($order['client_name'] ?? '-'), 1, 1, 'L');
        
        $pdf->Cell(50, 6, 'Подрядчик:', 1, 0, 'L');
        $pdf->Cell(0, 6, htmlspecialchars($order['courier_name'] ?? '-'), 1, 1, 'L');
        
        $pdf->Cell(50, 6, 'Тип перевозки:', 1, 0, 'L');
        $pdf->Cell(0, 6, htmlspecialchars($order['Shipping_type'] ?? '-'), 1, 1, 'L');
        
        $pdf->Cell(50, 6, 'Тип транспорта:', 1, 0, 'L');
        $pdf->Cell(0, 6, htmlspecialchars($order['Vehicle_type'] ?? '-'), 1, 1, 'L');
        
        $pdf->Ln(3);
    }
    
    // Cargo Information Section
    if (in_array('cargo_info', $sections)) {
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 8, 'ИНФОРМАЦИЯ О ГРУЗЕ', 1, 1, 'L', 1);
        $pdf->SetFont('dejavusans', '', 9);
        
        $pdf->Cell(50, 6, 'Наименование груза:', 1, 0, 'L');
        $pdf->Cell(0, 6, htmlspecialchars($order['Cargo_type'] ?? '-'), 1, 1, 'L');
        
        $pdf->Cell(50, 6, 'Вес груза:', 1, 0, 'L');
        $weight = $order['Weight'] ? $order['Weight'] . ' ' . ($order['Weight_unit'] ?? 'тонн') : '-';
        $pdf->Cell(0, 6, $weight, 1, 1, 'L');
        
        $pdf->Cell(50, 6, 'Объем груза (м³):', 1, 0, 'L');
        $pdf->Cell(0, 6, $order['Volume'] ?? '-', 1, 1, 'L');
        
        $pdf->Cell(50, 6, 'Количество мест:', 1, 0, 'L');
        $pdf->Cell(0, 6, $order['Quantity'] ?? '-', 1, 1, 'L');
        
        if ($order['Size']) {
            $pdf->Cell(50, 6, 'Размеры (м):', 1, 0, 'L');
            $pdf->Cell(0, 6, htmlspecialchars($order['Size']), 1, 1, 'L');
        }
        
        $pdf->Cell(50, 6, 'Тип погрузки:', 1, 0, 'L');
        $pdf->Cell(0, 6, htmlspecialchars($order['Loading_type'] ?? '-'), 1, 1, 'L');
        
        $pdf->Cell(50, 6, 'Тип упаковки:', 1, 0, 'L');
        $pdf->Cell(0, 6, htmlspecialchars($order['Packing_type'] ?? '-'), 1, 1, 'L');
        
        $pdf->Ln(3);
    }
    
    // Temperature Section
    if (in_array('temperature', $sections) && ($order['Min_temperature'] || $order['Max_temperature'])) {
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 8, 'ТЕМПЕРАТУРНЫЙ РЕЖИМ', 1, 1, 'L', 1);
        $pdf->SetFont('dejavusans', '', 9);
        
        if ($order['Min_temperature']) {
            $pdf->Cell(50, 6, 'Мин. температура (°C):', 1, 0, 'L');
            $pdf->Cell(0, 6, $order['Min_temperature'], 1, 1, 'L');
        }
        
        if ($order['Max_temperature']) {
            $pdf->Cell(50, 6, 'Макс. температура (°C):', 1, 0, 'L');
            $pdf->Cell(0, 6, $order['Max_temperature'], 1, 1, 'L');
        }
        
        $pdf->Cell(50, 6, 'Температурный лист:', 1, 0, 'L');
        $pdf->Cell(0, 6, $order['Temperature_record'] ?? '-', 1, 1, 'L');
        
        $pdf->Ln(3);
    }
    
    // Route Points Section
    if (in_array('route_points', $sections) && !empty($routePoints)) {
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 8, 'МАРШРУТ', 1, 1, 'L', 1);
        $pdf->SetFont('dejavusans', '', 8);
        
        foreach ($routePoints as $index => $point) {
            $pdf->SetFont('dejavusans', 'B', 9);
            $pdf->Cell(0, 6, 'Точка ' . ($index + 1) . ' - ' . htmlspecialchars($point['Action_type']), 1, 1, 'L');
            $pdf->SetFont('dejavusans', '', 8);
            
            $pdf->Cell(30, 5, 'Дата:', 1, 0, 'L');
            $pdf->Cell(40, 5, formatDate($point['Date']) ?? '-', 1, 0, 'L');
            $pdf->Cell(20, 5, 'Время:', 1, 0, 'L');
            $pdf->Cell(0, 5, $point['Time'] ?? '-', 1, 1, 'L');
            
            $pdf->Cell(30, 5, 'Адрес:', 1, 0, 'L');
            $pdf->Cell(0, 5, htmlspecialchars($point['Address_Loading'] ?? '-'), 1, 1, 'L');
            
            $pdf->Cell(30, 5, 'Компания:', 1, 0, 'L');
            $pdf->Cell(0, 5, htmlspecialchars($point['Company_name'] ?? '-'), 1, 1, 'L');
            
            if ($point['contacts']) {
                $pdf->Cell(30, 5, 'Контакты:', 1, 0, 'L');
                $pdf->Cell(0, 5, htmlspecialchars($point['contacts']), 1, 1, 'L');
            }
            
            $pdf->Ln(2);
        }
        
        $pdf->Ln(1);
    }
    
    // Cost Information Section
    if (in_array('cost_info', $sections)) {
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 8, 'СТОИМОСТЬ', 1, 1, 'L', 1);
        $pdf->SetFont('dejavusans', '', 9);
        
        $currency = $order['currency_name'] ?? '';
        
        if ($order['Cargo_price']) {
            $pdf->Cell(50, 6, 'Стоимость груза:', 1, 0, 'L');
            $pdf->Cell(0, 6, formatCurrency($order['Cargo_price']) . ' ' . $currency, 1, 1, 'L');
        }
        
        if ($order['Rate']) {
            $pdf->Cell(50, 6, 'Коэффициент:', 1, 0, 'L');
            $pdf->Cell(0, 6, $order['Rate'], 1, 1, 'L');
        }
        
        if ($order['Insurance_price']) {
            $pdf->Cell(50, 6, 'Страхование:', 1, 0, 'L');
            $pdf->Cell(0, 6, formatCurrency($order['Insurance_price']) . ' ' . $currency, 1, 1, 'L');
        }
        
        if ($order['Total_price_vehicle']) {
            $pdf->Cell(50, 6, 'Транспортировка:', 1, 0, 'L');
            $pdf->Cell(0, 6, formatCurrency($order['Total_price_vehicle']) . ' ' . $currency, 1, 1, 'L');
        }
        
        if ($order['Order_total']) {
            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->Cell(50, 8, 'ОБЩАЯ СУММА:', 1, 0, 'L');
            $pdf->Cell(0, 8, formatCurrency($order['Order_total']) . ' ' . $currency, 1, 1, 'L');
        }
        
        $pdf->Ln(3);
    }
    
    // Extra Services Section
    if (in_array('extra_services', $sections) && !empty($extraServices)) {
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 8, 'ДОПОЛНИТЕЛЬНЫЕ УСЛУГИ', 1, 1, 'L', 1);
        $pdf->SetFont('dejavusans', '', 9);
        
        $pdf->Cell(60, 6, 'Услуга', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Цена', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Количество', 1, 0, 'C');
        $pdf->Cell(0, 6, 'Общая сумма', 1, 1, 'C');
        
        foreach ($extraServices as $service) {
            $pdf->Cell(60, 5, htmlspecialchars($service['Service_name']), 1, 0, 'L');
            $pdf->Cell(30, 5, formatCurrency($service['Service_price']), 1, 0, 'R');
            $pdf->Cell(30, 5, $service['Quantity'], 1, 0, 'C');
            $pdf->Cell(0, 5, formatCurrency($service['Total']), 1, 1, 'R');
        }
        
        $pdf->Ln(3);
    }
    
    // Notes Section
    if (in_array('notes', $sections) && !empty($order['order_notes'])) {
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 8, 'ДОПОЛНИТЕЛЬНЫЕ ПРИМЕЧАНИЯ', 1, 1, 'L', 1);
        $pdf->SetFont('dejavusans', '', 9);
        
        $pdf->MultiCell(0, 6, htmlspecialchars($order['order_notes']), 1, 'L');
        $pdf->Ln(3);
    }
    
    // Generate filename
    $filename = 'preorder_' . $order['display_order_number'] . '_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Output PDF
    ob_clean();
    $pdf->Output($filename, 'D');
    exit();
}

function generateWord($order, $routePoints, $extraServices, $sections) {
    // For Word generation, we'll create a simple HTML that can be downloaded as .doc
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Предзаказ #' . htmlspecialchars($order['display_order_number']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .section { margin-bottom: 20px; }
            .section-title { background-color: #f0f0f0; padding: 8px; font-weight: bold; border: 1px solid #ccc; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            td, th { border: 1px solid #ccc; padding: 6px; text-align: left; }
            .total-row { font-weight: bold; background-color: #f9f9f9; }
        </style>
    </head>
    <body>';
    
    // Header
    $html .= '<div class="header">
        <h1>KVAZAR LOGISTICS</h1>
        <h2>ПРЕДВАРИТЕЛЬНЫЙ ЗАКАЗ</h2>
        <h3>Заказ № ' . htmlspecialchars($order['display_order_number']) . '</h3>
        <p>Дата заказа: ' . formatDate($order['Order_date']) . '</p>
    </div>';
    
    // Add sections based on selection
    if (in_array('basic_info', $sections)) {
        $html .= '<div class="section">
            <div class="section-title">ОСНОВНАЯ ИНФОРМАЦИЯ</div>
            <table>
                <tr><td width="30%">Клиент:</td><td>' . htmlspecialchars($order['client_name'] ?? '-') . '</td></tr>
                <tr><td>Подрядчик:</td><td>' . htmlspecialchars($order['courier_name'] ?? '-') . '</td></tr>
                <tr><td>Тип перевозки:</td><td>' . htmlspecialchars($order['Shipping_type'] ?? '-') . '</td></tr>
                <tr><td>Тип транспорта:</td><td>' . htmlspecialchars($order['Vehicle_type'] ?? '-') . '</td></tr>
            </table>
        </div>';
    }
    
    // Add other sections similarly...
    // (Implement the remaining sections for Word format)
    
    $html .= '</body></html>';
    
    // Generate filename
    $filename = 'preorder_' . $order['display_order_number'] . '_' . date('Y-m-d_H-i-s') . '.doc';
    
    // Output as Word document
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    echo $html;
    exit();
}

function formatDate($dateString) {
    if (!$dateString) return null;
    try {
        $date = new DateTime($dateString);
        return $date->format('d.m.Y');
    } catch (Exception $e) {
        return $dateString;
    }
}

function formatCurrency($amount) {
    return number_format((float)$amount, 2, '.', ' ');
}
?> 