<?php
session_start();
require_once '../config/db_connect.php';
require_once 'order_logging.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

$orderId = intval($_GET['order_id'] ?? 0);
$action = $_GET['action'] ?? 'view';
$format = $_GET['format'] ?? 'html';

if (!$orderId) {
    header('HTTP/1.1 400 Bad Request');
    exit('Order ID is required');
}

// Log document generation
logDocumentGenerate($pdo, $orderId, 'client_conductor');

try {
    // Get comprehensive order data with all related information
    $orderStmt = $pdo->prepare("
        SELECT 
            o.*,
            c.Full_Company_name as client_name,
            c.Contact_person as client_contact,
            c.Contact_person_phone as client_phone,
            c.Contact_person_email as client_email,
            c.Physical_address as client_address,
            cr.Full_Company_name as courier_name,
            cr.Contact_person as courier_contact,
            cr.Contact_person_phone as courier_phone,
            cr.Contact_person_email as courier_email,
            cr.Physical_address as courier_address,
            curr.currency_name,
            cc.Contract_number,
            cc.Contract_date,
            st.Type_name as shipping_type_name,
            tt.Type_name as transport_type_name,
            cn.cargo_name as cargo_name_display,
            lt.loading_name as loading_type_name,
            pt.packaging_name as packaging_type_name
        FROM Orders o
        LEFT JOIN Clients c ON o.Client_id = c.Client_id
        LEFT JOIN Couriers cr ON o.Courier_id = cr.Courier_id
        LEFT JOIN list_currency curr ON o.Currency_id = curr.id
        LEFT JOIN Client_contracts cc ON o.Contract_id = cc.Contract_id
        LEFT JOIN list_shipping_type st ON o.Shipping_type = st.Type_id
        LEFT JOIN list_transport_type tt ON o.Vehicle_type = tt.Type_id
        LEFT JOIN list_cargo_name cn ON o.Cargo_type = cn.id
        LEFT JOIN list_loading_type lt ON o.Loading_type = lt.id
        LEFT JOIN list_packaging_type pt ON o.Packing_type = pt.id
        WHERE o.Order_id = ?
    ");
    
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('HTTP/1.1 404 Not Found');
        exit('Order not found');
    }
    
    // Check if courier and driver are assigned
    if (empty($order['Courier_id'])) {
        header('HTTP/1.1 400 Bad Request');
        exit('No courier assigned to this order');
    }
    
    $driverCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM Drivers_list WHERE Order_id = ?");
    $driverCheckStmt->execute([$orderId]);
    if ($driverCheckStmt->fetchColumn() == 0) {
        header('HTTP/1.1 400 Bad Request');
        exit('No driver assigned to this order');
    }
    
    // Get route points
    $routeStmt = $pdo->prepare("
        SELECT Point_id, Position, Company_name, Address_Loading, Action_type, Date, Time
        FROM Points 
        WHERE Order_id = ? 
        ORDER BY Position
    ");
    $routeStmt->execute([$orderId]);
    $routePoints = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get extra services
    $servicesStmt = $pdo->prepare("
        SELECT Service_name, Service_price, Quantity, Total
        FROM Extra_service 
        WHERE Order_id = ?
    ");
    $servicesStmt->execute([$orderId]);
    $extraServices = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set appropriate headers
    if ($action === 'download') {
        if ($format === 'word') {
            $filename = "client_conductor_order_{$order['display_order_number']}_" . date('Y-m-d') . ".doc";
            header('Content-Type: application/vnd.ms-word');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        } elseif ($format === 'pdf') {
            // Generate PDF using browser's print functionality
            $filename = "client_conductor_order_{$order['display_order_number']}_" . date('Y-m-d') . ".html";
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        } else {
            $filename = "client_conductor_order_{$order['display_order_number']}_" . date('Y-m-d') . ".html";
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
    } else {
        header('Content-Type: text/html; charset=UTF-8');
    }
    
} catch (Exception $e) {
    error_log("Error generating client-conductor document: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error generating document');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявка № <?= htmlspecialchars($order['display_order_number']) ?> от <?= date('d.m.Y', strtotime($order['Order_date'])) ?> г.</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 14px;
            line-height: 1.4;
            margin: 20px;
            color: #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .subheader {
            text-align: center;
            margin-bottom: 30px;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table, th, td {
            border: 1px solid #000;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .no-border {
            border: none;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-block {
            width: 45%;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            height: 20px;
            margin: 10px 0;
        }
        
        .company-info {
            margin-bottom: 20px;
            font-size: 12px;
        }
        
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        
        <?php if ($format === 'word'): ?>
        /* Word-specific styles */
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.15;
        }
        table {
            border-collapse: collapse;
            border: 1px solid black;
        }
        th, td {
            border: 1px solid black;
            padding: 4pt;
        }
        <?php endif; ?>
    </style>
    <?php if ($format === 'pdf' && $action === 'download'): ?>
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
    <?php endif; ?>
</head>
<body>
    <div class="header">
        ЗАЯВКА № <?= htmlspecialchars($order['display_order_number']) ?> от <?= date('d.m.Y', strtotime($order['Order_date'])) ?> г.
    </div>
    
    <div class="subheader">
        по Договору № <?= htmlspecialchars($order['Contract_number'] ?: 'б/н') ?> от <?= $order['Contract_date'] ? date('d.m.Y', strtotime($order['Contract_date'])) : '___' ?> г.
    </div>

    <!-- Service Details Table -->
    <table>
        <tr>
            <th style="width: 40%;">Услуги</th>
            <th style="width: 60%;">Автоперевозка по РФ</th>
        </tr>
        <tr>
            <td>Дата загрузки/разгрузки</td>
            <td><?= date('d.m.Y', strtotime($order['Order_date'])) ?></td>
        </tr>
        <tr>
            <td>Маршрут перевозки</td>
            <td>
                <?php if (!empty($routePoints)): ?>
                    <?php 
                    $routeText = [];
                    foreach ($routePoints as $point) {
                        $address = !empty($point['Address_Loading']) ? $point['Address_Loading'] : $point['Company_name'];
                        if (!empty($address)) {
                            $routeText[] = $address;
                        }
                    }
                    echo htmlspecialchars(implode(' - ', $routeText));
                    ?>
                <?php else: ?>
                    Не указан
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Вид транспорта и условия перевозки</td>
            <td><?= htmlspecialchars($order['transport_type_name'] ?: 'Не указано') ?></td>
        </tr>
        <tr>
            <td>Стоимость перевозки</td>
            <td><?= number_format($order['Total_price_vehicle'], 2, ',', ' ') ?> <?= htmlspecialchars($order['currency_name']) ?></td>
        </tr>
        <tr>
            <td>Дополнительные условия</td>
            <td>
                <?php if (!empty($order['Min_temperature']) || !empty($order['Max_temperature'])): ?>
                    Температурный режим: <?= $order['Min_temperature'] ?>°C - <?= $order['Max_temperature'] ?>°C<br>
                <?php endif; ?>
                Загрузка: <?= htmlspecialchars($order['loading_type_name'] ?: 'Не указано') ?><br>
                Упаковка: <?= htmlspecialchars($order['packaging_type_name'] ?: 'Не указано') ?>
            </td>
        </tr>
    </table>

    <!-- Cargo Information Table -->
    <table>
        <tr>
            <th colspan="2">Груз (характер груза, наименование)</th>
            <th>Автоперевозка КОЛДОСТ</th>
        </tr>
        <tr>
            <td>Вес груза, габариты, вид упаковки, количество мест</td>
            <td><?= htmlspecialchars($order['Weight'] . ' ' . $order['Weight_unit']) ?></td>
            <td><?= htmlspecialchars($order['cargo_name_display'] ?: 'Продукция КОЛДОСТ') ?></td>
        </tr>
        <tr>
            <td>Стоимость груза</td>
            <td colspan="2"><?= number_format($order['Cargo_price'], 2, ',', ' ') ?> <?= htmlspecialchars($order['currency_name']) ?></td>
        </tr>
    </table>

    <!-- Sender Information -->
    <table>
        <tr>
            <th colspan="2">Отправитель 1 (наименование)</th>
            <th>
                <?php 
                if (!empty($routePoints) && $routePoints[0]['Action_type'] === 'loading') {
                    echo htmlspecialchars($routePoints[0]['Company_name'] ?: $order['client_name']);
                } else {
                    echo htmlspecialchars($order['client_name']);
                }
                ?>
            </th>
        </tr>
        <tr>
            <td>Адрес отправителя</td>
            <td colspan="2">
                <?php 
                if (!empty($routePoints) && $routePoints[0]['Action_type'] === 'loading') {
                    echo htmlspecialchars($routePoints[0]['Address_Loading'] ?: $order['client_address'] ?: 'Не указан');
                } else {
                    echo htmlspecialchars($order['client_address'] ?: 'Не указан');
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>Время и дата разгрузки</td>
            <td colspan="2">
                <?php if (!empty($routePoints)): ?>
                    <?php 
                    $firstPoint = $routePoints[0];
                    echo date('d.m.Y', strtotime($firstPoint['Date'] ?: $order['Order_date']));
                    if (!empty($firstPoint['Time'])) {
                        echo ', в ' . htmlspecialchars($firstPoint['Time']);
                    }
                    ?>
                <?php else: ?>
                    <?= date('d.m.Y', strtotime($order['Order_date'])) ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Контактное лицо отправителя</td>
            <td colspan="2">
                <?php
                // Get contact info for the first point (sender)
                if (!empty($routePoints)) {
                    $contactStmt = $pdo->prepare("SELECT Name, Phone_number FROM Contact_list WHERE Point_id = ?");
                    $contactStmt->execute([$routePoints[0]['Point_id']]);
                    $contacts = $contactStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($contacts)) {
                        $contactTexts = [];
                        foreach ($contacts as $contact) {
                            $contactText = htmlspecialchars($contact['Name']);
                            if (!empty($contact['Phone_number'])) {
                                $contactText .= ' ' . htmlspecialchars($contact['Phone_number']);
                            }
                            $contactTexts[] = $contactText;
                        }
                        echo implode(', ', $contactTexts);
                    } else {
                        // Fallback to client contact info
                        $contactInfo = [];
                        if (!empty($order['client_contact'])) {
                            $contactInfo[] = htmlspecialchars($order['client_contact']);
                        }
                        if (!empty($order['client_phone'])) {
                            $contactInfo[] = htmlspecialchars($order['client_phone']);
                        }
                        echo implode(', ', $contactInfo);
                    }
                } else {
                    // No route points, use client info
                    $contactInfo = [];
                    if (!empty($order['client_contact'])) {
                        $contactInfo[] = htmlspecialchars($order['client_contact']);
                    }
                    if (!empty($order['client_phone'])) {
                        $contactInfo[] = htmlspecialchars($order['client_phone']);
                    }
                    echo implode(', ', $contactInfo);
                }
                ?>
            </td>
        </tr>
    </table>

    <!-- Recipients Information -->
    <?php if (count($routePoints) > 1): ?>
        <?php foreach ($routePoints as $index => $point): ?>
            <?php if ($point['Action_type'] !== 'loading' && $index > 0): ?>
            <table>
                <tr>
                    <th colspan="2">Получатель <?= $index ?> (наименование)</th>
                    <th><?= htmlspecialchars($point['Company_name'] ?: 'Не указано') ?></th>
                </tr>
                <tr>
                    <td>Адрес получателя</td>
                    <td colspan="2"><?= htmlspecialchars($point['Address_Loading'] ?: 'Не указан') ?></td>
                </tr>
                <tr>
                    <td>Время и дата загрузки</td>
                    <td colspan="2">
                        <?= $point['Date'] ? date('d.m.Y', strtotime($point['Date'])) : date('d.m.Y', strtotime($order['Order_date'])) ?>
                        <?php if (!empty($point['Time'])): ?>
                            , в <?= htmlspecialchars($point['Time']) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Контактное лицо получателя</td>
                    <td colspan="2">
                        <?php
                        // Get contact info for this point
                        $contactStmt = $pdo->prepare("SELECT Name, Phone_number FROM Contact_list WHERE Point_id = ?");
                        $contactStmt->execute([$point['Point_id']]);
                        $contacts = $contactStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $contactTexts = [];
                        foreach ($contacts as $contact) {
                            $contactText = htmlspecialchars($contact['Name']);
                            if (!empty($contact['Phone_number'])) {
                                $contactText .= ' ' . htmlspecialchars($contact['Phone_number']);
                            }
                            $contactTexts[] = $contactText;
                        }
                        echo implode(', ', $contactTexts);
                        ?>
                    </td>
                </tr>
            </table>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Driver and Vehicle Assignment Section -->
    <div style="margin-top: 30px;">
        <h3 style="text-align: center;">Подтверждение Исполнителя</h3>
        
        <table class="no-border" style="margin-bottom: 20px;">
            <tr class="no-border">
                <td class="no-border"><strong>Заказчик:</strong></td>
                <td class="no-border"><?= htmlspecialchars($order['client_name']) ?></td>
            </tr>
            <tr class="no-border">
                <td class="no-border"><strong>Исполнитель:</strong></td>
                <td class="no-border">ООО «ТК КВАЗАР»</td>
            </tr>
        </table>
    </div>

    <!-- Signatures Section -->
    <div class="signature-section">
        <div class="signature-block">
            <p><strong>Подпись Стороны:</strong></p>
            <p><strong>Заказчик:</strong></p>
            <p>____________________<br/>
            <small style="margin-left: 50px;">С.Добронов</small></p>
            
            <div class="signature-line"></div>
            <p style="text-align: center; font-size: 11px;">«___»_____20___ г.</p>
        </div>
        
        <div class="signature-block">
            <p><strong>Исполнитель:</strong></p>
            <p>ООО «ТК КВАЗАР»</p>
            <p>____________________<br/>
            <small style="margin-left: 50px;">С.Добронов В.В.</small></p>
            
            <div class="signature-line"></div>
            <p style="text-align: center; font-size: 11px;">«___»_____20___ г.</p>
        </div>
    </div>

    <?php if ($action === 'view'): ?>
    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Печать документа
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">
            Закрыть
        </button>
    </div>
    <?php endif; ?>
</body>
</html> 