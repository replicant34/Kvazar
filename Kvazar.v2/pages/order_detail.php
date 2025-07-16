<?php
session_start();
require_once '../config/db_connect.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    header('Location: ../index.php');
    exit();
}

$userRole = $_SESSION['role'];
$orderId = intval($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: manage_orders.php');
    exit();
}

// Get order data
try {
    $orderStmt = $pdo->prepare("
        SELECT 
            o.*,
            c.Full_Company_name as client_name,
            c.Contact_person_phone as client_phone,
            c.Contact_person_email as client_email,
            cr.Full_Company_name as courier_name,
            cont.Full_Company_name as contractor_name,
            curr.currency_name as currency_name,
            curr.id as currency_id,
            cc.Contract_number,
            cc.Contract_date,
            ord_notes.Note_content as order_notes,
            st.Type_name as shipping_type_name,
            tt.Type_name as transport_type_name,
            cn.cargo_name as cargo_name_display,
            lt.loading_name as loading_type_name,
            pt.packaging_name as packaging_type_name,
            los.Status_name as status_name,
            los.Status_color as status_color
        FROM Orders o
        LEFT JOIN Clients c ON o.Client_id = c.Client_id
        LEFT JOIN Couriers cr ON o.Courier_id = cr.Courier_id
        LEFT JOIN Contractors cont ON o.Contractor = cont.Contractors_id
        LEFT JOIN list_currency curr ON o.Currency_id = curr.id
        LEFT JOIN Client_contracts cc ON o.Contract_id = cc.Contract_id
        LEFT JOIN Order_notes ord_notes ON o.Note_id = ord_notes.Note_id
        LEFT JOIN list_shipping_type st ON o.Shipping_type = st.Type_id
        LEFT JOIN list_transport_type tt ON o.Vehicle_type = tt.Type_id
        LEFT JOIN list_cargo_name cn ON o.Cargo_type = cn.id
        LEFT JOIN list_loading_type lt ON o.Loading_type = lt.id
        LEFT JOIN list_packaging_type pt ON o.Packing_type = pt.id
        LEFT JOIN list_order_status los ON o.Status = los.Status_id
        WHERE o.Order_id = ?
    ");
    
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: manage_orders.php');
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error loading order: " . $e->getMessage());
    header('Location: manage_orders.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ #<?= htmlspecialchars($order['display_order_number']) ?> - Kvazar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/order_detail.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../elements/admin_sidebar.php'; ?>
        
        <div id="content">
            <?php 
            $page_title = 'Детали заказа';
            include '../elements/admin_navbar.php'; 
            ?>
            
            <div class="order-detail-wrapper">
                <!-- Order Header -->
                <div class="order-header">
                    <div class="order-header-left">
                        <h1>Заказ #<?= htmlspecialchars($order['display_order_number']) ?></h1>
                        <div class="order-meta">
                            <span class="order-date">
                                <i class="fas fa-calendar"></i>
                                <?= date('d.m.Y', strtotime($order['Order_date'])) ?>
                            </span>
                            <span class="status-badge" style="background-color: <?= htmlspecialchars($order['status_color']) ?>">
                                <?= htmlspecialchars($order['status_name']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="order-header-right">
                        <div class="order-actions">
                            <button class="btn-action btn-edit" onclick="toggleEditMode()">
                                <i class="fas fa-edit"></i> Редактировать
                            </button>
                            <button class="btn-action btn-status" onclick="changeOrderStatus()">
                                <i class="fas fa-exchange-alt"></i> Статус
                            </button>
                            <?php if (empty($order['Courier_id'])): ?>
                            <button class="btn-action btn-courier" onclick="showAddCourierModal()">
                                <i class="fas fa-truck"></i> Назначить перевозчика
                            </button>
                            <button class="btn-action btn-send-form" onclick="showSendFormModal()">
                                <i class="fas fa-paper-plane"></i> Отправить форму перевозчику
                            </button>
                            <?php endif; ?>
                            <button class="btn-action btn-history" onclick="viewOrderHistory()">
                                <i class="fas fa-history"></i> История
                            </button>
                            <div class="download-group">
                                <button class="btn-action btn-download" onclick="downloadOrderPDF()">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="btn-action btn-download-word" onclick="downloadOrderWord()">
                                    <i class="fas fa-file-word"></i> Word
                                </button>
                            </div>
                        </div>
                        <a href="manage_orders.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Назад
                        </a>
                    </div>
                </div>

                <!-- Order Content -->
                <div class="order-content">
                    <!-- Order Overview Card -->
                    <div class="content-card overview-card">
                        <h3>Обзор заказа</h3>
                        <div class="overview-grid">
                            <div class="overview-item">
                                <span class="label">Клиент:</span>
                                <span class="value"><?= htmlspecialchars($order['client_name']) ?></span>
                            </div>
                            <div class="overview-item">
                                <span class="label">Подрядчик:</span>
                                <span class="value"><?= htmlspecialchars($order['contractor_name'] ?: 'Не назначен') ?></span>
                            </div>
                            <div class="overview-item">
                                <span class="label">Перевозчик:</span>
                                <span class="value"><?= htmlspecialchars($order['courier_name'] ?: 'Не назначен') ?></span>
                            </div>
                            <div class="overview-item">
                                <span class="label">Общая сумма:</span>
                                <span class="value total-amount">
                                    <?= number_format($order['Order_total'], 2) ?> <?= htmlspecialchars($order['currency_name']) ?>
                                </span>
                            </div>
                            <div class="overview-item">
                                <span class="label">Договор:</span>
                                <span class="value"><?= htmlspecialchars($order['Contract_number'] ?: 'Не указан') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Tabs -->
                    <div class="tabs-container">
                        <div class="tabs-nav">
                            <button class="tab-btn active" data-tab="details">Детали заказа</button>
                            <button class="tab-btn" data-tab="route">Маршрут</button>
                            <button class="tab-btn" data-tab="costs">Стоимость</button>
                            <button class="tab-btn" data-tab="services">Услуги</button>
                            <button class="tab-btn" data-tab="courier">Перевозчик</button>
                            <button class="tab-btn" data-tab="files">Файлы</button>
                        </div>

                        <!-- Details Tab -->
                        <div class="tab-content active" id="details-tab">
                            <div class="details-grid">
                                <!-- Client Information -->
                                <div class="content-card">
                                    <h4>Информация о клиенте</h4>
                                    <div class="info-list">
                                        <div class="info-item">
                                            <span class="info-label">Название:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['client_name']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Телефон:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['client_phone'] ?: 'Не указан') ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Email:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['client_email'] ?: 'Не указан') ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Договор:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['Contract_number'] ?: 'Не указан') ?></span>
                                        </div>
                                        <?php if ($order['Contract_date']): ?>
                                        <div class="info-item">
                                            <span class="info-label">Дата договора:</span>
                                            <span class="info-value"><?= date('d.m.Y', strtotime($order['Contract_date'])) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Cargo Information -->
                                <div class="content-card">
                                    <h4>Информация о грузе</h4>
                                    <div class="info-list">
                                        <div class="info-item">
                                            <span class="info-label">Наименование:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['cargo_name_display'] ?: 'Не указано') ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Вес:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['Weight'] . ' ' . $order['Weight_unit']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Объем:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['Volume']) ?> м³</span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Количество:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['Quantity']) ?> мест</span>
                                        </div>
                                        <?php if ($order['Size']): ?>
                                        <div class="info-item">
                                            <span class="info-label">Размеры:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['Size']) ?> м</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Transport Information -->
                                <div class="content-card">
                                    <h4>Информация о перевозке</h4>
                                    <div class="info-list">
                                        <div class="info-item">
                                            <span class="info-label">Тип перевозки:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['shipping_type_name'] ?: 'Не указан') ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Тип транспорта:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['transport_type_name'] ?: 'Не указан') ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Тип погрузки:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['loading_type_name'] ?: 'Не указан') ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Тип упаковки:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['packaging_type_name'] ?: 'Не указан') ?></span>
                                        </div>
                                        <?php if ($order['Min_temperature'] || $order['Max_temperature']): ?>
                                        <div class="info-item">
                                            <span class="info-label">Температурный режим:</span>
                                            <span class="info-value">
                                                <?= htmlspecialchars($order['Min_temperature']) ?>°C - <?= htmlspecialchars($order['Max_temperature']) ?>°C
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <?php if ($order['order_notes']): ?>
                                <div class="content-card full-width">
                                    <h4>Примечания</h4>
                                    <div class="notes-content">
                                        <?= nl2br(htmlspecialchars($order['order_notes'])) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Route Tab -->
                        <div class="tab-content" id="route-tab">
                            <div class="content-card">
                                <h4>Маршрут перевозки</h4>
                                <div id="route-points-container">
                                    <!-- Route points will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <!-- Costs Tab -->
                        <div class="tab-content" id="costs-tab">
                            <div class="costs-grid">
                                <div class="content-card">
                                    <h4>Стоимость груза и страхование</h4>
                                    <div class="cost-breakdown">
                                        <div class="cost-item">
                                            <span class="cost-label">Стоимость груза:</span>
                                            <span class="cost-value"><?= number_format($order['Cargo_price'], 2) ?> <?= htmlspecialchars($order['currency_name']) ?></span>
                                        </div>
                                        <div class="cost-item">
                                            <span class="cost-label">Коэффициент:</span>
                                            <span class="cost-value"><?= htmlspecialchars($order['Rate']) ?>%</span>
                                        </div>
                                        <div class="cost-item">
                                            <span class="cost-label">Страхование:</span>
                                            <span class="cost-value"><?= number_format($order['Insurance_price'], 2) ?> <?= htmlspecialchars($order['currency_name']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="content-card">
                                    <h4>Стоимость перевозки</h4>
                                    <div class="cost-breakdown">
                                        <div class="cost-item">
                                            <span class="cost-label">Ставка:</span>
                                            <span class="cost-value"><?= number_format($order['Rate_2'], 2) ?> <?= htmlspecialchars($order['currency_name']) ?></span>
                                        </div>
                                        <div class="cost-item">
                                            <span class="cost-label">Часы:</span>
                                            <span class="cost-value"><?= htmlspecialchars($order['Hours']) ?> ч</span>
                                        </div>
                                        <div class="cost-item">
                                            <span class="cost-label">Переработка:</span>
                                            <span class="cost-value"><?= htmlspecialchars($order['Extra_hours']) ?> ч</span>
                                        </div>
                                        <div class="cost-item total">
                                            <span class="cost-label">Итого за перевозку:</span>
                                            <span class="cost-value"><?= number_format($order['Total_price_vehicle'], 2) ?> <?= htmlspecialchars($order['currency_name']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="content-card full-width">
                                    <h4>Итоговая стоимость</h4>
                                    <div class="total-breakdown">
                                        <div class="total-item">
                                            <span class="total-label">Страхование:</span>
                                            <span class="total-value"><?= number_format($order['Insurance_price'], 2) ?> <?= htmlspecialchars($order['currency_name']) ?></span>
                                        </div>
                                        <div class="total-item">
                                            <span class="total-label">Перевозка:</span>
                                            <span class="total-value"><?= number_format($order['Total_price_vehicle'], 2) ?> <?= htmlspecialchars($order['currency_name']) ?></span>
                                        </div>
                                        <div class="total-item">
                                            <span class="total-label">Доп. услуги:</span>
                                            <span class="total-value"><?= number_format($order['Total_price_extra_service'], 2) ?> <?= htmlspecialchars($order['currency_name']) ?></span>
                                        </div>
                                        <div class="total-item grand-total">
                                            <span class="total-label">Общая сумма:</span>
                                            <span class="total-value"><?= number_format($order['Order_total'], 2) ?> <?= htmlspecialchars($order['currency_name']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Services Tab -->
                        <div class="tab-content" id="services-tab">
                            <div class="content-card">
                                <h4>Дополнительные услуги</h4>
                                <div id="extra-services-container">
                                    <!-- Extra services will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <!-- Courier Tab -->
                        <div class="tab-content" id="courier-tab">
                            <div id="courier-info-container">
                                <!-- Courier information will be loaded here -->
                            </div>
                        </div>

                        <!-- Files Tab -->
                        <div class="tab-content" id="files-tab">
                            <div class="content-card">
                                <h4>Прикрепленные файлы</h4>
                                <div id="attached-files-container">
                                    <!-- Attached files will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Order Modal -->
    <div id="editOrderModal" class="modal">
        <div class="modal-content large-modal">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Редактировать заказ</h3>
            <form id="editOrderForm" class="edit-form">
                <input type="hidden" id="editOrderId" value="<?= $orderId ?>">
                <!-- Form fields will be populated by JavaScript -->
                <div id="editOrderFormContent">
                    <!-- Dynamic form content -->
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Courier Modal -->
    <div id="addCourierModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddCourierModal()">&times;</span>
            <h3>Назначить перевозчика для заказа #<?= htmlspecialchars($order['display_order_number']) ?></h3>
            <form id="addCourierForm" class="edit-form">
                <input type="hidden" id="addCourierOrderId" name="order_id" value="<?= $orderId ?>">
                
                <!-- Courier Selection -->
                <div class="form-section">
                    <h4>Выбор перевозчика</h4>
                    <div class="form-group">
                        <label for="courierSelect" class="required">Перевозчик:</label>
                        <select id="courierSelect" name="courier_id" required>
                            <option value="">Выберите перевозчика...</option>
                        </select>
                    </div>
                </div>

                <!-- Driver Selection -->
                <div class="form-section">
                    <h4>Выбор водителя</h4>
                    <div class="form-group">
                        <label for="driverSelect">Водитель:</label>
                        <select id="driverSelect" name="driver_id" disabled>
                            <option value="">Сначала выберите перевозчика</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="driverPhone">Телефон водителя:</label>
                            <input type="text" id="driverPhone" name="driver_phone" readonly>
                        </div>
                        <div class="form-group">
                            <label for="driverPassport">Паспорт водителя:</label>
                            <input type="text" id="driverPassport" name="driver_passport" readonly>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Selection -->
                <div class="form-section">
                    <h4>Выбор транспорта</h4>
                    <div class="form-group">
                        <label for="vehicleSelect">Номер транспорта:</label>
                        <select id="vehicleSelect" name="vehicle_id" disabled>
                            <option value="">Сначала выберите перевозчика</option>
                        </select>
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-truck"></i> Назначить перевозчика
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeAddCourierModal()">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Send Form to Courier Modal -->
    <div id="sendFormModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSendFormModal()">&times;</span>
            <h3>Отправить форму перевозчику для заказа #<?= htmlspecialchars($order['display_order_number']) ?></h3>
            <form id="sendFormForm" class="edit-form">
                <input type="hidden" id="sendFormOrderId" name="order_id" value="<?= $orderId ?>">
                
                <!-- Courier Selection -->
                <div class="form-section">
                    <h4>Выбор перевозчика</h4>
                    <div class="form-group">
                        <label for="sendFormCourierSelect" class="required">Перевозчик:</label>
                        <select id="sendFormCourierSelect" name="courier_id" required>
                            <option value="">Выберите перевозчика...</option>
                        </select>
                    </div>
                </div>

                <!-- Link Generation Result -->
                <div id="linkGenerationResult" style="display: none;">
                    <div class="form-section">
                        <h4>Сгенерированная ссылка</h4>
                        <div class="form-group">
                            <label>Ссылка для перевозчика:</label>
                            <div class="link-display">
                                <input type="text" id="generatedLink" readonly>
                                <button type="button" id="copyLinkBtn" class="btn-secondary">
                                    <i class="fas fa-copy"></i> Копировать
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Срок действия:</label>
                            <div class="expiry-info">
                                <i class="fas fa-clock"></i>
                                <span id="linkExpiry">24 часа с момента создания</span>
                            </div>
                        </div>
                    </div>

                    <!-- Email Options -->
                    <div class="form-section">
                        <h4>Отправка по email</h4>
                        <div class="form-group">
                            <label>Email перевозчика:</label>
                            <input type="email" id="courierEmail" readonly>
                        </div>
                        <div class="email-actions">
                            <button type="button" id="sendEmailBtn" class="btn-primary">
                                <i class="fas fa-envelope"></i> Отправить по email
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="submit" class="btn-primary" id="generateLinkBtn">
                        <i class="fas fa-link"></i> Сгенерировать ссылку
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeSendFormModal()">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden order data for JavaScript -->
    <script>
        window.orderData = <?= json_encode($order) ?>;
        window.orderId = <?= $orderId ?>;
    </script>

    <script src="../js/order_detail.js"></script>
    <script src="../js/sidebar.js"></script>
</body>
</html> 