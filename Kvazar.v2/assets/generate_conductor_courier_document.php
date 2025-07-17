<?php
session_start();
require_once '../config/db_connect.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

$orderId = intval($_GET['order_id'] ?? 0);
$action = $_GET['action'] ?? 'view';
$format = $_GET['format'] ?? 'html';
$contractId = intval($_GET['contract_id'] ?? 0);

if (!$orderId) {
    header('HTTP/1.1 400 Bad Request');
    exit('Order ID is required');
}

try {
    // Get comprehensive order data with courier and driver information
    $orderStmt = $pdo->prepare("
        SELECT 
            o.*,
            c.Full_Company_name as client_name,
            c.Contact_person as client_contact,
            c.Contact_person_phone as client_phone,
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
    
    // Get assigned driver information
    $driverStmt = $pdo->prepare("
        SELECT d.Driver_id, d.Name as driver_name, d.Phone_number as driver_phone, d.Passport as driver_passport
        FROM Drivers_list dl
        JOIN Drivers d ON dl.Driver_id = d.Driver_id
        WHERE dl.Order_id = ?
        LIMIT 1
    ");
    $driverStmt->execute([$orderId]);
    $driver = $driverStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$driver) {
        header('HTTP/1.1 400 Bad Request');
        exit('No driver assigned to this order');
    }
    
    // Get assigned vehicle information  
    $vehicleStmt = $pdo->prepare("
        SELECT v.Vehicle_id, v.Brand as vehicle_brand, v.Plate_number as vehicle_plate
        FROM Vehicle_list vl
        JOIN Vehicles v ON vl.Vehicle_id = v.Vehicle_id
        WHERE vl.Order_id = ?
        LIMIT 1
    ");
    $vehicleStmt->execute([$orderId]);
    $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get courier contracts
    $courierContracts = [];
    $selectedContract = null;
    
    if (!empty($order['Courier_id'])) {
        $contractsStmt = $pdo->prepare("
            SELECT 
                cc.Contract_id,
                cc.Contract_number,
                cc.Contract_date,
                cc.Contract_status,
                ct.Type_name as contract_type
            FROM Courier_contracts cc
            LEFT JOIN list_contract_type ct ON cc.Contract_type = ct.Type_id
            WHERE cc.Courier_id = ? AND cc.Contract_status = 'active'
            ORDER BY cc.Contract_date DESC
        ");
        $contractsStmt->execute([$order['Courier_id']]);
        $courierContracts = $contractsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If specific contract ID is provided, use it; otherwise use the first active contract
        if ($contractId && !empty($courierContracts)) {
            foreach ($courierContracts as $contract) {
                if ($contract['Contract_id'] == $contractId) {
                    $selectedContract = $contract;
                    break;
                }
            }
        }
        
        // If no specific contract selected, use the most recent active contract
        if (!$selectedContract && !empty($courierContracts)) {
            $selectedContract = $courierContracts[0];
        }
        
        // If we need to show contract selection (multiple contracts and no specific contract selected)
        if (count($courierContracts) > 1 && !$contractId && $action === 'view') {
            showContractSelectionPage($order, $courierContracts, $orderId);
            exit();
        }
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
            $filename = "conductor_courier_order_{$order['display_order_number']}_" . date('Y-m-d') . ".doc";
            header('Content-Type: application/vnd.ms-word');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        } elseif ($format === 'pdf') {
            // Generate PDF using browser's print functionality
            $filename = "conductor_courier_order_{$order['display_order_number']}_" . date('Y-m-d') . ".html";
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        } else {
            $filename = "conductor_courier_order_{$order['display_order_number']}_" . date('Y-m-d') . ".html";
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
    } else {
        header('Content-Type: text/html; charset=UTF-8');
    }
    
} catch (Exception $e) {
    error_log("Error generating conductor-courier document: " . $e->getMessage());
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
        <?php if ($selectedContract): ?>
            по Договору № <?= htmlspecialchars($selectedContract['Contract_number']) ?> от <?= date('d.m.Y', strtotime($selectedContract['Contract_date'])) ?> г.
        <?php else: ?>
            по Договору № б/н от ___ г.
        <?php endif; ?>
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
            <th>Продукция КОЛДОСТ</th>
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
                    echo htmlspecialchars($routePoints[0]['Company_name'] ?: 'ООО «БиоФАРМАХОЛДИНГ»');
                } else {
                    echo 'ООО «БиоФАРМАХОЛДИНГ»';
                }
                ?>
            </th>
        </tr>
        <tr>
            <td>Адрес отправителя</td>
            <td colspan="2">
                <?php 
                if (!empty($routePoints) && $routePoints[0]['Action_type'] === 'loading') {
                    echo htmlspecialchars($routePoints[0]['Address_Loading'] ?: 'Калужская область, г. Москальск, ул. Гамалей');
                } else {
                    echo 'Калужская область, г. Москальск, ул. Гамалей';
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
                    <?= date('d.m.Y', strtotime($order['Order_date'])) ?>, в 09:00
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
                        // Fallback to default values
                        echo 'Коротков Егор 8-910-690-10-22, Алексей +7 967-122-38-26, Мария +7-985-148-71-08';
                    }
                } else {
                    // No route points, use default
                    echo 'Коротков Егор 8-910-690-10-22, Алексей +7 967-122-38-26, Мария +7-985-148-71-08';
                }
                ?>
            </td>
        </tr>
    </table>

    <!-- Recipients Information -->
    <?php if (!empty($routePoints)): ?>
        <?php foreach ($routePoints as $index => $point): ?>
            <?php if ($point['Action_type'] !== 'loading' && $index > 0): ?>
            <table>
                <tr>
                    <th colspan="2">Получатель <?= $index ?> (наименование)</th>
                    <th><?= htmlspecialchars($point['Company_name'] ?: 'ООО «КНТП «КОРА»') ?></th>
                </tr>
                <tr>
                    <td>Адрес получателя</td>
                    <td colspan="2">
                        <?php if (!empty($point['Address_Loading'])): ?>
                            <?= htmlspecialchars($point['Address_Loading']) ?>
                        <?php else: ?>
                            Калужская обл., Боровский р-н, д. Староможайская, ул. Индустриальная, д. 1-а
                        <?php endif; ?>
                    </td>
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
                            echo 'Фетисова Юлия 8-910-590-04-27; +7 910-860-06-14; +7-484-386-30-40';
                        }
                        ?>
                    </td>
                </tr>
            </table>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
    <!-- Default recipient if no route points -->
    <table>
        <tr>
            <th colspan="2">Получатель 2 (наименование)</th>
            <th>ООО «БиоФАРМАХОЛДИНГ» (по умолчанию - нет данных маршрута)</th>
        </tr>
        <tr>
            <td>Адрес получателя</td>
            <td colspan="2">г.Москва, ул. Гамалей, д.18, стр. 33 (шлагб)</td>
        </tr>
        <tr>
            <td>Время и дата загрузки</td>
            <td colspan="2"><?= date('d.m.Y', strtotime($order['Order_date'])) ?></td>
        </tr>
        <tr>
            <td>Контактное лицо получателя</td>
            <td colspan="2">Романикин Сергей 8-915-353-12-29, Ремина Татьяна 8-916-397-67-73</td>
        </tr>
    </table>
    <?php endif; ?>

    <!-- Driver and Vehicle Assignment Section -->
    <div style="margin-top: 30px;">
        <h3 style="text-align: center;">Подтверждение Исполнителя</h3>
        
        <table>
            <tr>
                <th>Марка и номер ТС</th>
                <th>Водитель (ФИО), мобильный телефон</th>
                <th>Паспортные данные</th>
            </tr>
            <tr>
                <td>
                    <?php if ($vehicle): ?>
                        <?= htmlspecialchars($vehicle['vehicle_brand'] . ' - ' . $vehicle['vehicle_plate']) ?>
                    <?php else: ?>
                        Не назначен
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($driver): ?>
                        <?= htmlspecialchars($driver['driver_name']) ?>
                        <?php if (!empty($driver['driver_phone'])): ?>
                            <br><?= htmlspecialchars($driver['driver_phone']) ?>
                        <?php endif; ?>
                    <?php else: ?>
                        Не назначен
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($driver && !empty($driver['driver_passport'])): ?>
                        <?= htmlspecialchars($driver['driver_passport']) ?>
                    <?php else: ?>
                        Не указан
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <table class="no-border" style="margin-top: 20px;">
            <tr class="no-border">
                <td class="no-border"><strong>Заказчик:</strong></td>
                <td class="no-border">ООО «ТК КВАЗАР»</td>
            </tr>
            <tr class="no-border">
                <td class="no-border"><strong>Исполнитель:</strong></td>
                <td class="no-border"><?= htmlspecialchars($order['courier_name'] ?: 'Не назначен') ?></td>
            </tr>
        </table>
    </div>

    <!-- Signatures Section -->
    <div class="signature-section">
        <div class="signature-block">
            <p><strong>Подпись Стороны:</strong></p>
            <p><strong>Заказчик:</strong></p>
            <p>ООО «ТК КВАЗАР»</p>
            <p>____________________<br/>
            <small style="margin-left: 50px;">Давлетманов С.В.</small></p>
            
            <div class="signature-line"></div>
            <p style="text-align: center; font-size: 11px;">"____" МП _______ 2024 г.</p>
        </div>
        
        <div class="signature-block">
            <p><strong>Исполнитель:</strong></p>
            <p><?= htmlspecialchars($order['courier_name'] ?: 'Перевозчик') ?></p>
            <p>____________________<br/>
            <small style="margin-left: 50px;">С.Фабонов В.В.</small></p>
            
            <div class="signature-line"></div>
            <p style="text-align: center; font-size: 11px;">"____" МП _______ 2024 г.</p>
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

<?php
function showContractSelectionPage($order, $contracts, $orderId) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Выбор договора - Заказ #<?= htmlspecialchars($order['display_order_number']) ?></title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 20px;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .container {
                background: white;
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                max-width: 600px;
                width: 100%;
            }
            
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            
            .header h1 {
                color: #2c3e50;
                margin: 0 0 10px 0;
                font-size: 24px;
            }
            
            .header p {
                color: #7f8c8d;
                margin: 0;
                font-size: 14px;
            }
            
            .contracts-list {
                margin-bottom: 30px;
            }
            
            .contract-item {
                border: 2px solid #ecf0f1;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 15px;
                cursor: pointer;
                transition: all 0.3s ease;
                background: #f8f9fa;
            }
            
            .contract-item:hover {
                border-color: #3498db;
                background: #e3f2fd;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
            }
            
            .contract-item.selected {
                border-color: #27ae60;
                background: #e8f5e8;
            }
            
            .contract-number {
                font-size: 18px;
                font-weight: 600;
                color: #2c3e50;
                margin-bottom: 8px;
            }
            
            .contract-details {
                display: flex;
                gap: 20px;
                flex-wrap: wrap;
            }
            
            .contract-detail {
                color: #7f8c8d;
                font-size: 14px;
            }
            
            .contract-detail strong {
                color: #5a6c7d;
            }
            
            .actions {
                text-align: center;
            }
            
            .btn {
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-block;
                margin: 0 10px;
            }
            
            .btn:hover {
                background: linear-gradient(135deg, #2980b9, #1c5980);
                transform: translateY(-2px);
            }
            
            .btn:disabled {
                background: #bdc3c7;
                cursor: not-allowed;
                transform: none;
            }
            
            .btn-secondary {
                background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            }
            
            .btn-secondary:hover {
                background: linear-gradient(135deg, #7f8c8d, #5a6c7d);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Выбор договора с перевозчиком</h1>
                <p>Для заказа #<?= htmlspecialchars($order['display_order_number']) ?> - <?= htmlspecialchars($order['courier_name']) ?></p>
                <p>Выберите договор для использования в документе</p>
            </div>
            
            <div class="contracts-list">
                <?php foreach ($contracts as $contract): ?>
                <div class="contract-item" data-contract-id="<?= $contract['Contract_id'] ?>">
                    <div class="contract-number">Договор № <?= htmlspecialchars($contract['Contract_number']) ?></div>
                    <div class="contract-details">
                        <div class="contract-detail">
                            <strong>Дата:</strong> <?= date('d.m.Y', strtotime($contract['Contract_date'])) ?>
                        </div>
                        <div class="contract-detail">
                            <strong>Тип:</strong> <?= htmlspecialchars($contract['contract_type'] ?: 'Не указан') ?>
                        </div>
                        <div class="contract-detail">
                            <strong>Статус:</strong> <?= htmlspecialchars($contract['Contract_status']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="actions">
                <button id="generateDocBtn" class="btn" disabled>Создать документ</button>
                <button class="btn btn-secondary" onclick="window.close()">Отмена</button>
            </div>
        </div>
        
        <script>
            let selectedContractId = null;
            
            document.addEventListener('DOMContentLoaded', function() {
                const contractItems = document.querySelectorAll('.contract-item');
                const generateBtn = document.getElementById('generateDocBtn');
                
                contractItems.forEach(item => {
                    item.addEventListener('click', function() {
                        // Remove selected class from all items
                        contractItems.forEach(i => i.classList.remove('selected'));
                        
                        // Add selected class to clicked item
                        this.classList.add('selected');
                        
                        // Enable generate button
                        selectedContractId = this.dataset.contractId;
                        generateBtn.disabled = false;
                    });
                });
                
                generateBtn.addEventListener('click', function() {
                    if (selectedContractId) {
                        const url = window.location.href + '&contract_id=' + selectedContractId;
                        window.location.href = url;
                    }
                });
            });
        </script>
    </body>
    </html>
    <?php
}
?> 