<?php
// Process courier assignment form submission
require_once '../config/db_connect.php';

$token = $_POST['token'] ?? '';

if (!$token) {
    showErrorPage('Неверная ссылка', 'Отсутствует токен аутентификации.');
    exit();
}

try {
    // Validate token and get link information
    $linkStmt = $pdo->prepare("
        SELECT 
            cal.Link_id,
            cal.Order_id,
            cal.Courier_id,
            cal.Expires_at,
            cal.Is_used,
            cal.Created_by,
            o.display_order_number,
            c.Full_Company_name as courier_name
        FROM courier_assignment_links cal
        JOIN Orders o ON cal.Order_id = o.Order_id
        JOIN Couriers c ON cal.Courier_id = c.Courier_id
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
    
    // Check if order already has courier assigned
    $courierCheckStmt = $pdo->prepare("SELECT Courier_id FROM Orders WHERE Order_id = ?");
    $courierCheckStmt->execute([$linkData['Order_id']]);
    $currentCourier = $courierCheckStmt->fetchColumn();
    
    if (!empty($currentCourier)) {
        showAlreadyAssignedPage();
        exit();
    }
    
    // Validate form data
    $driverType = $_POST['driver_type'] ?? '';
    $vehicleType = $_POST['vehicle_type'] ?? '';
    
    if (!in_array($driverType, ['existing', 'new']) || !in_array($vehicleType, ['existing', 'new'])) {
        showErrorPage('Неверные данные', 'Выберите корректный тип водителя и транспортного средства.');
        exit();
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    $driverId = null;
    $vehicleId = null;
    $submissionData = [];
    
    // Process driver assignment
    if ($driverType === 'existing') {
        $driverId = intval($_POST['existing_driver_id'] ?? 0);
        
        if (!$driverId) {
            throw new Exception('Не выбран водитель из существующих');
        }
        
        // Validate that driver belongs to this courier
        $driverValidationStmt = $pdo->prepare("
            SELECT Driver_id, Name FROM Drivers 
            WHERE Driver_id = ? AND Courier_id = ?
        ");
        $driverValidationStmt->execute([$driverId, $linkData['Courier_id']]);
        $driverInfo = $driverValidationStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$driverInfo) {
            throw new Exception('Водитель не найден или не принадлежит данному перевозчику');
        }
        
        $submissionData['driver'] = [
            'type' => 'existing',
            'driver_id' => $driverId,
            'driver_name' => $driverInfo['Name']
        ];
        
    } else if ($driverType === 'new') {
        $newDriverName = trim($_POST['new_driver_name'] ?? '');
        $newDriverPhone = trim($_POST['new_driver_phone'] ?? '');
        $newDriverPassport = trim($_POST['new_driver_passport'] ?? '');
        
        if (!$newDriverName || !$newDriverPhone || !$newDriverPassport) {
            throw new Exception('Заполните все поля для нового водителя');
        }
        
        // Insert new driver
        $insertDriverStmt = $pdo->prepare("
            INSERT INTO Drivers (Courier_id, Name, Phone_number, Passport) 
            VALUES (?, ?, ?, ?)
        ");
        $insertDriverStmt->execute([$linkData['Courier_id'], $newDriverName, $newDriverPhone, $newDriverPassport]);
        $driverId = $pdo->lastInsertId();
        
        $submissionData['driver'] = [
            'type' => 'new',
            'driver_id' => $driverId,
            'driver_name' => $newDriverName,
            'driver_phone' => $newDriverPhone,
            'driver_passport' => $newDriverPassport
        ];
    }
    
    // Process vehicle assignment
    if ($vehicleType === 'existing') {
        $vehicleId = intval($_POST['existing_vehicle_id'] ?? 0);
        
        if (!$vehicleId) {
            throw new Exception('Не выбрано транспортное средство из существующих');
        }
        
        // Validate that vehicle belongs to this courier
        $vehicleValidationStmt = $pdo->prepare("
            SELECT Vehicle_id, Brand, Plate_number FROM Vehicles 
            WHERE Vehicle_id = ? AND Courier_id = ?
        ");
        $vehicleValidationStmt->execute([$vehicleId, $linkData['Courier_id']]);
        $vehicleInfo = $vehicleValidationStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$vehicleInfo) {
            throw new Exception('Транспортное средство не найдено или не принадлежит данному перевозчику');
        }
        
        $submissionData['vehicle'] = [
            'type' => 'existing',
            'vehicle_id' => $vehicleId,
            'vehicle_brand' => $vehicleInfo['Brand'],
            'vehicle_plate' => $vehicleInfo['Plate_number']
        ];
        
    } else if ($vehicleType === 'new') {
        $newVehicleBrand = trim($_POST['new_vehicle_brand'] ?? '');
        $newVehiclePlate = trim($_POST['new_vehicle_plate'] ?? '');
        
        if (!$newVehicleBrand || !$newVehiclePlate) {
            throw new Exception('Заполните все поля для нового транспортного средства');
        }
        
        // Insert new vehicle
        $insertVehicleStmt = $pdo->prepare("
            INSERT INTO Vehicles (Courier_id, Brand, Plate_number) 
            VALUES (?, ?, ?)
        ");
        $insertVehicleStmt->execute([$linkData['Courier_id'], $newVehicleBrand, $newVehiclePlate]);
        $vehicleId = $pdo->lastInsertId();
        
        $submissionData['vehicle'] = [
            'type' => 'new',
            'vehicle_id' => $vehicleId,
            'vehicle_brand' => $newVehicleBrand,
            'vehicle_plate' => $newVehiclePlate
        ];
    }
    
    // Assign courier to order
    $updateOrderStmt = $pdo->prepare("
        UPDATE Orders 
        SET Courier_id = ?, Updated_at = NOW() 
        WHERE Order_id = ?
    ");
    $updateOrderStmt->execute([$linkData['Courier_id'], $linkData['Order_id']]);
    
    // Clear any existing driver assignments
    $clearDriversStmt = $pdo->prepare("DELETE FROM Drivers_list WHERE Order_id = ?");
    $clearDriversStmt->execute([$linkData['Order_id']]);
    
    // Clear any existing vehicle assignments
    $clearVehiclesStmt = $pdo->prepare("DELETE FROM Vehicle_list WHERE Order_id = ?");
    $clearVehiclesStmt->execute([$linkData['Order_id']]);
    
    // Assign driver to order
    if ($driverId) {
        $assignDriverStmt = $pdo->prepare("
            INSERT INTO Drivers_list (Order_id, Driver_id, Created_at) 
            VALUES (?, ?, NOW())
        ");
        $assignDriverStmt->execute([$linkData['Order_id'], $driverId]);
    }
    
    // Assign vehicle to order
    if ($vehicleId) {
        $assignVehicleStmt = $pdo->prepare("
            INSERT INTO Vehicle_list (Order_id, Vehicle_id, Created_at) 
            VALUES (?, ?, NOW())
        ");
        $assignVehicleStmt->execute([$linkData['Order_id'], $vehicleId]);
    }
    
    // Mark link as used
    $updateLinkStmt = $pdo->prepare("
        UPDATE courier_assignment_links 
        SET Is_used = TRUE, Used_at = NOW(), Submitted_data = ? 
        WHERE Link_token = ?
    ");
    $updateLinkStmt->execute([json_encode($submissionData), $token]);
    
    // Log the action
    $logStmt = $pdo->prepare("
        INSERT INTO action_logs (user_id, action_type, table_name, description, ip_address) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $logStmt->execute([
        $linkData['Created_by'], // User who created the link
        'Назначение перевозчика через форму',
        'Orders',
        "Перевозчик {$linkData['courier_name']} назначен на заказ {$linkData['display_order_number']} через внешнюю форму",
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Show success page
    showSuccessPage($linkData, $submissionData);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error processing courier form: " . $e->getMessage());
    // Temporary debugging - show full error details
    showErrorPage('Ошибка обработки формы', 'Детали ошибки: ' . $e->getMessage() . ' | Файл: ' . $e->getFile() . ' | Строка: ' . $e->getLine());
}

function showSuccessPage($linkData, $submissionData) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Назначение выполнено - Kvazar Logistics</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="../css/courier_form.css">
    </head>
    <body>
        <div class="container success-page">
            <div class="success-content">
                <i class="fas fa-check-circle"></i>
                <h1>Назначение выполнено успешно!</h1>
                
                <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;">
                    <h3>Детали назначения:</h3>
                    <p><strong>Заказ:</strong> #<?= htmlspecialchars($linkData['display_order_number']) ?></p>
                    <p><strong>Перевозчик:</strong> <?= htmlspecialchars($linkData['courier_name']) ?></p>
                    
                    <?php if (isset($submissionData['driver'])): ?>
                    <p><strong>Водитель:</strong> 
                        <?= htmlspecialchars($submissionData['driver']['driver_name']) ?>
                        <?php if ($submissionData['driver']['type'] === 'new'): ?>
                            <span style="color: #f39c12;">(новый)</span>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if (isset($submissionData['vehicle'])): ?>
                    <p><strong>Транспорт:</strong> 
                        <?= htmlspecialchars($submissionData['vehicle']['vehicle_brand']) ?> 
                        (<?= htmlspecialchars($submissionData['vehicle']['vehicle_plate']) ?>)
                        <?php if ($submissionData['vehicle']['type'] === 'new'): ?>
                            <span style="color: #f39c12;">(новый)</span>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>
                
                <p>Информация о назначении передана в систему Kvazar Logistics.</p>
                <p>При необходимости внесения изменений обратитесь к оператору.</p>
                
                <p><small>Данная ссылка больше не действительна.</small></p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?> 