<?php
require_once 'config/db_connect.php';

// Get and validate token
$token = $_GET['token'] ?? '';

if (!$token) {
    showErrorPage('Неверная ссылка', 'Ссылка не содержит необходимых параметров.');
    exit();
}

try {
    // Validate token and get order information
    $linkStmt = $pdo->prepare("
        SELECT 
            cal.Link_id,
            cal.Order_id,
            cal.Courier_id,
            cal.Expires_at,
            cal.Is_used,
            cal.Access_count,
            o.display_order_number,
            o.Order_date,
            o.Cargo_type,
            o.Weight,
            o.Weight_unit,
            o.Volume,
            o.Quantity,
            c.Full_Company_name as courier_name,
            cl.Full_Company_name as client_name,
            cl.Contact_person as client_contact,
            cl.Contact_person_phone as client_phone
        FROM courier_assignment_links cal
        JOIN Orders o ON cal.Order_id = o.Order_id
        JOIN Couriers c ON cal.Courier_id = c.Courier_id
        LEFT JOIN Clients cl ON o.Client_id = cl.Client_id
        WHERE cal.Link_token = ?
    ");
    
    $linkStmt->execute([$token]);
    $linkData = $linkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$linkData) {
        showErrorPage('Ссылка не найдена', 'Указанная ссылка не существует или была удалена.');
        exit();
    }
    
    // Check if link has expired
    if (strtotime($linkData['Expires_at']) < time()) {
        showExpiredPage();
        exit();
    }
    
    // Check if link has already been used
    if ($linkData['Is_used']) {
        showUsedPage();
        exit();
    }
    
    // Update access count
    $updateAccessStmt = $pdo->prepare("
        UPDATE courier_assignment_links 
        SET Access_count = Access_count + 1, Last_accessed_at = NOW() 
        WHERE Link_token = ?
    ");
    $updateAccessStmt->execute([$token]);
    
    // Check if order already has courier assigned
    $courierCheckStmt = $pdo->prepare("SELECT Courier_id FROM Orders WHERE Order_id = ?");
    $courierCheckStmt->execute([$linkData['Order_id']]);
    $currentCourier = $courierCheckStmt->fetchColumn();
    
    if (!empty($currentCourier)) {
        showAlreadyAssignedPage();
        exit();
    }
    
    // Get existing drivers and vehicles for this courier
    $driversStmt = $pdo->prepare("
        SELECT Driver_id, Name, Phone_number, Passport 
        FROM Drivers 
        WHERE Courier_id = ? 
        ORDER BY Name
    ");
    $driversStmt->execute([$linkData['Courier_id']]);
    $existingDrivers = $driversStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $vehiclesStmt = $pdo->prepare("
        SELECT Vehicle_id, Brand, Plate_number, CONCAT(Brand, ' - ', Plate_number) as display_name
        FROM Vehicles 
        WHERE Courier_id = ? 
        ORDER BY Brand, Plate_number
    ");
    $vehiclesStmt->execute([$linkData['Courier_id']]);
    $existingVehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order details for display
    $orderDetailsStmt = $pdo->prepare("
        SELECT 
            cn.cargo_name,
            lst.Type_name as shipping_type,
            ltt.Type_name as transport_type
        FROM Orders o
        LEFT JOIN list_cargo_name cn ON o.Cargo_type = cn.id
        LEFT JOIN list_shipping_type lst ON o.Shipping_type = lst.Type_id
        LEFT JOIN list_transport_type ltt ON o.Vehicle_type = ltt.Type_id
        WHERE o.Order_id = ?
    ");
    $orderDetailsStmt->execute([$linkData['Order_id']]);
    $orderDetails = $orderDetailsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get route points
    $routeStmt = $pdo->prepare("
        SELECT Point_id, Position, Company_name, Address_Loading as Address, Action_type, Date, Time
        FROM Points 
        WHERE Order_id = ? 
        ORDER BY Position
    ");
    $routeStmt->execute([$linkData['Order_id']]);
    $routePoints = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get extra services
    $servicesStmt = $pdo->prepare("
        SELECT es.Service_name, es.Service_price, es.Quantity, es.Total
        FROM Extra_service es
        WHERE es.Order_id = ?
    ");
    $servicesStmt->execute([$linkData['Order_id']]);
    $extraServices = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get attached files
    $filesStmt = $pdo->prepare("
        SELECT original_filename, file_size
        FROM Attached_order_files
        WHERE Order_id = ?
    ");
    $filesStmt->execute([$linkData['Order_id']]);
    $attachedFiles = $filesStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error in courier form: " . $e->getMessage());
    showErrorPage('Ошибка сервера', 'Произошла ошибка при загрузке данных. Попробуйте позже.');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'assets/process_courier_form.php';
    exit();
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Назначение водителя и транспорта - Заказ #<?= htmlspecialchars($linkData['display_order_number']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/courier_form.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <h1><i class="fas fa-truck"></i> Kvazar Logistics</h1>
            </div>
            <div class="order-info">
                <h2>Заказ #<?= htmlspecialchars($linkData['display_order_number']) ?></h2>
                <p>Перевозчик: <strong><?= htmlspecialchars($linkData['courier_name']) ?></strong></p>
            </div>
        </div>

        <!-- Order Information -->
        <div class="content-section">
            <h3><i class="fas fa-info-circle"></i> Информация о заказе</h3>
            <div class="order-details">
                <div class="detail-item">
                    <span class="label">Дата заказа:</span>
                    <span class="value"><?= date('d.m.Y', strtotime($linkData['Order_date'])) ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Клиент:</span>
                    <span class="value"><?= htmlspecialchars($linkData['client_name']) ?></span>
                </div>
                <?php if ($linkData['client_contact']): ?>
                <div class="detail-item">
                    <span class="label">Контактное лицо:</span>
                    <span class="value"><?= htmlspecialchars($linkData['client_contact']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($linkData['client_phone']): ?>
                <div class="detail-item">
                    <span class="label">Телефон клиента:</span>
                    <span class="value">
                        <a href="tel:<?= htmlspecialchars($linkData['client_phone']) ?>">
                            <?= htmlspecialchars($linkData['client_phone']) ?>
                        </a>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cargo Information -->
        <div class="content-section">
            <h3><i class="fas fa-boxes"></i> Информация о грузе</h3>
            <div class="order-details">
                <?php if ($orderDetails['cargo_name']): ?>
                <div class="detail-item">
                    <span class="label">Тип груза:</span>
                    <span class="value"><?= htmlspecialchars($orderDetails['cargo_name']) ?></span>
                </div>
                <?php endif; ?>
                <div class="detail-item">
                    <span class="label">Вес:</span>
                    <span class="value"><?= htmlspecialchars($linkData['Weight'] . ' ' . $linkData['Weight_unit']) ?></span>
                </div>
                <?php if ($linkData['Volume']): ?>
                <div class="detail-item">
                    <span class="label">Объем:</span>
                    <span class="value"><?= htmlspecialchars($linkData['Volume']) ?> м³</span>
                </div>
                <?php endif; ?>
                <?php if ($linkData['Quantity']): ?>
                <div class="detail-item">
                    <span class="label">Количество мест:</span>
                    <span class="value"><?= htmlspecialchars($linkData['Quantity']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($orderDetails['shipping_type']): ?>
                <div class="detail-item">
                    <span class="label">Тип перевозки:</span>
                    <span class="value"><?= htmlspecialchars($orderDetails['shipping_type']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($orderDetails['transport_type']): ?>
                <div class="detail-item">
                    <span class="label">Тип транспорта:</span>
                    <span class="value"><?= htmlspecialchars($orderDetails['transport_type']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($routePoints)): ?>
        <!-- Route Information -->
        <div class="content-section">
            <h3><i class="fas fa-route"></i> Маршрут</h3>
            <div class="route-list">
                <?php foreach ($routePoints as $index => $point): ?>
                <div class="route-point">
                    <div class="point-number"><?= $index + 1 ?></div>
                    <div class="point-details">
                        <div class="point-header">
                            <span class="company"><?= htmlspecialchars($point['Company_name']) ?></span>
                            <span class="action <?= strtolower($point['Action_type']) ?>">
                                <?= htmlspecialchars($point['Action_type']) ?>
                            </span>
                        </div>
                        <div class="point-address"><?= htmlspecialchars($point['Address']) ?></div>
                        <?php if ($point['Date'] || $point['Time']): ?>
                        <div class="point-schedule">
                            <?php if ($point['Date']): ?>
                                <span><i class="fas fa-calendar"></i> <?= date('d.m.Y', strtotime($point['Date'])) ?></span>
                            <?php endif; ?>
                            <?php if ($point['Time']): ?>
                                <span><i class="fas fa-clock"></i> <?= htmlspecialchars($point['Time']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($extraServices)): ?>
        <!-- Extra Services -->
        <div class="content-section">
            <h3><i class="fas fa-concierge-bell"></i> Дополнительные услуги</h3>
            <div class="services-list">
                <?php foreach ($extraServices as $service): ?>
                <div class="service-item">
                    <span class="service-name"><?= htmlspecialchars($service['Service_name']) ?></span>
                    <span class="service-details">
                        <?= htmlspecialchars($service['Quantity']) ?> × <?= number_format($service['Service_price'], 2) ?> = 
                        <?= number_format($service['Total'], 2) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($attachedFiles)): ?>
        <!-- Attached Files -->
        <div class="content-section">
            <h3><i class="fas fa-paperclip"></i> Прикрепленные файлы</h3>
            <div class="files-list">
                <?php foreach ($attachedFiles as $file): ?>
                <div class="file-item">
                    <i class="fas fa-file"></i>
                    <span class="file-name"><?= htmlspecialchars($file['original_filename']) ?></span>
                    <span class="file-size"><?= formatFileSize($file['file_size']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Driver and Vehicle Assignment Form -->
        <div class="content-section form-section">
            <h3><i class="fas fa-user-plus"></i> Назначение водителя и транспорта</h3>
            
            <form id="assignmentForm" method="POST" action="">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <!-- Driver Section -->
                <div class="form-group">
                    <h4>Водитель</h4>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="existing_driver" name="driver_type" value="existing" <?= !empty($existingDrivers) ? 'checked' : 'disabled' ?>>
                            <label for="existing_driver">Выбрать из существующих</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="new_driver" name="driver_type" value="new" <?= empty($existingDrivers) ? 'checked' : '' ?>>
                            <label for="new_driver">Добавить нового водителя</label>
                        </div>
                    </div>
                    
                    <?php if (!empty($existingDrivers)): ?>
                    <div id="existing_driver_section" class="conditional-section">
                        <select name="existing_driver_id" id="existingDriverSelect">
                            <option value="">Выберите водителя...</option>
                            <?php foreach ($existingDrivers as $driver): ?>
                            <option value="<?= $driver['Driver_id'] ?>" 
                                    data-phone="<?= htmlspecialchars($driver['Phone_number']) ?>" 
                                    data-passport="<?= htmlspecialchars($driver['Passport']) ?>">
                                <?= htmlspecialchars($driver['Name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="driver-info" id="driverInfo" style="display: none;">
                            <p><strong>Телефон:</strong> <span id="driverPhone"></span></p>
                            <p><strong>Паспорт:</strong> <span id="driverPassport"></span></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div id="new_driver_section" class="conditional-section">
                        <div class="form-row">
                            <div class="form-field">
                                <label for="new_driver_name">Имя водителя <span class="required">*</span></label>
                                <input type="text" id="new_driver_name" name="new_driver_name" required>
                            </div>
                            <div class="form-field">
                                <label for="new_driver_phone">Телефон водителя <span class="required">*</span></label>
                                <input type="tel" id="new_driver_phone" name="new_driver_phone" required>
                            </div>
                        </div>
                        <div class="form-field">
                            <label for="new_driver_passport">Паспорт водителя <span class="required">*</span></label>
                            <input type="text" id="new_driver_passport" name="new_driver_passport" required>
                        </div>
                    </div>
                </div>
                
                <!-- Vehicle Section -->
                <div class="form-group">
                    <h4>Транспортное средство</h4>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="existing_vehicle" name="vehicle_type" value="existing" <?= !empty($existingVehicles) ? 'checked' : 'disabled' ?>>
                            <label for="existing_vehicle">Выбрать из существующих</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="new_vehicle" name="vehicle_type" value="new" <?= empty($existingVehicles) ? 'checked' : '' ?>>
                            <label for="new_vehicle">Добавить новое ТС</label>
                        </div>
                    </div>
                    
                    <?php if (!empty($existingVehicles)): ?>
                    <div id="existing_vehicle_section" class="conditional-section">
                        <select name="existing_vehicle_id" id="existingVehicleSelect">
                            <option value="">Выберите транспортное средство...</option>
                            <?php foreach ($existingVehicles as $vehicle): ?>
                            <option value="<?= $vehicle['Vehicle_id'] ?>">
                                <?= htmlspecialchars($vehicle['display_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div id="new_vehicle_section" class="conditional-section">
                        <div class="form-row">
                            <div class="form-field">
                                <label for="new_vehicle_brand">Марка ТС <span class="required">*</span></label>
                                <input type="text" id="new_vehicle_brand" name="new_vehicle_brand" required>
                            </div>
                            <div class="form-field">
                                <label for="new_vehicle_plate">Номер ТС <span class="required">*</span></label>
                                <input type="text" id="new_vehicle_plate" name="new_vehicle_plate" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-check"></i> Назначить водителя и транспорт
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© <?= date('Y') ?> Kvazar Logistics. Все права защищены.</p>
            <p><small>Ссылка действительна до: <strong><?= date('d.m.Y H:i', strtotime($linkData['Expires_at'])) ?></strong></small></p>
        </div>
    </div>

    <script src="js/courier_form.js"></script>
</body>
</html>

<?php
function showErrorPage($title, $message) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> - Kvazar Logistics</title>
        <link rel="stylesheet" href="css/courier_form.css">
    </head>
    <body>
        <div class="container error-page">
            <div class="error-content">
                <i class="fas fa-exclamation-triangle"></i>
                <h1><?= htmlspecialchars($title) ?></h1>
                <p><?= htmlspecialchars($message) ?></p>
                <p><small>Если вы считаете, что это ошибка, обратитесь к оператору.</small></p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function showExpiredPage() {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ссылка истекла - Kvazar Logistics</title>
        <link rel="stylesheet" href="css/courier_form.css">
    </head>
    <body>
        <div class="container error-page">
            <div class="error-content">
                <i class="fas fa-clock"></i>
                <h1>Срок действия ссылки истек</h1>
                <p>Ссылка для назначения водителя и транспорта больше не действительна.</p>
                <p>Ссылка действует только 24 часа с момента создания.</p>
                <p><small>Обратитесь к оператору для получения новой ссылки.</small></p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function showUsedPage() {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ссылка уже использована - Kvazar Logistics</title>
        <link rel="stylesheet" href="css/courier_form.css">
    </head>
    <body>
        <div class="container error-page">
            <div class="error-content">
                <i class="fas fa-check-circle"></i>
                <h1>Ссылка уже использована</h1>
                <p>Назначение водителя и транспорта уже было выполнено.</p>
                <p>Каждая ссылка может быть использована только один раз.</p>
                <p><small>Если требуются изменения, обратитесь к оператору.</small></p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function showAlreadyAssignedPage() {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Перевозчик уже назначен - Kvazar Logistics</title>
        <link rel="stylesheet" href="css/courier_form.css">
    </head>
    <body>
        <div class="container error-page">
            <div class="error-content">
                <i class="fas fa-info-circle"></i>
                <h1>Перевозчик уже назначен</h1>
                <p>К данному заказу уже назначен перевозчик, водитель и транспорт.</p>
                <p>Если что-то изменилось, пожалуйста, свяжитесь с нашим оператором.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?> 