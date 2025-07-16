<?php
session_start();
require_once '../config/db_connect.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    header('Location: ../index.php');
    exit();
}

$userRole = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами - Kvazar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/manage_orders.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../elements/admin_sidebar.php'; ?>
        
        <div id="content">
            <?php 
            $page_title = 'Управление заказами';
            include '../elements/admin_navbar.php'; 
            ?>
            
            <div class="content-wrapper">
                <div class="mc-content-wrapper">
                    <div class="page-header">
                        <h2>Управление заказами</h2>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="filters-section">
                        <div class="filters-left">
                            <div class="search-box">
                                <input type="text" id="searchInput" placeholder="Поиск заказов...">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="filters">
                                <select id="statusFilter">
                                    <option value="">Все статусы</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT Status_id, Status_name FROM list_order_status ORDER BY Status_name");
                                    while ($row = $stmt->fetch()) {
                                        echo "<option value='" . htmlspecialchars($row['Status_id']) . "'>" . 
                                             htmlspecialchars($row['Status_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                                <select id="clientFilter">
                                    <option value="">Все клиенты</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT Client_id, Full_Company_name FROM Clients ORDER BY Full_Company_name");
                                    while ($row = $stmt->fetch()) {
                                        echo "<option value='" . htmlspecialchars($row['Client_id']) . "'>" . 
                                             htmlspecialchars($row['Full_Company_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                                <select id="courierFilter">
                                    <option value="">Все перевозчики</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT Courier_id, Full_Company_name FROM Couriers ORDER BY Full_Company_name");
                                    while ($row = $stmt->fetch()) {
                                        echo "<option value='" . htmlspecialchars($row['Courier_id']) . "'>" . 
                                             htmlspecialchars($row['Full_Company_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                                <div class="date-range">
                                    <input type="date" id="dateFrom" placeholder="От">
                                    <input type="date" id="dateTo" placeholder="До">
                                </div>
                            </div>
                        </div>
                        <div class="filters-right">
                            <button id="downloadOrders" class="btn-secondary" title="Скачать данные">
                                <i class="fas fa-download"></i>
                            </button>
                            <button id="refreshOrders" class="btn-secondary" title="Обновить">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="table-container" id="tableContainer">
                        <div class="scroll-indicator" id="scrollIndicator">Прокрутка...</div>
                        <table id="ordersTable">
                            <thead>
                                <tr>
                                    <th class="sortable" data-column="status">
                                        <span>Статус</span>
                                        <i class="fas fa-sort"></i>
                                    </th>
                                    <th class="sortable" data-column="order_number">
                                        <span>Номер заказа</span>
                                        <i class="fas fa-sort"></i>
                                    </th>
                                    <th class="sortable" data-column="client_name">
                                        <span>Клиент</span>
                                        <i class="fas fa-sort"></i>
                                    </th>
                                    <th class="sortable" data-column="order_date">
                                        <span>Дата заказа</span>
                                        <i class="fas fa-sort"></i>
                                    </th>
                                    <th class="sortable" data-column="courier_name">
                                        <span>Перевозчик</span>
                                        <i class="fas fa-sort"></i>
                                    </th>
                                    <th class="sortable" data-column="order_total">
                                        <span>Сумма заказа</span>
                                        <i class="fas fa-sort"></i>
                                    </th>
                                    <th class="no-sort">Действия</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTableBody">
                                <!-- Data will be loaded here via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            <span id="paginationInfo">Показано 0 из 0 заказов</span>
                        </div>
                        <div class="pagination" id="pagination">
                            <!-- Pagination buttons will be generated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div id="viewOrderModal" class="modal">
        <div class="modal-content large-modal">
            <span class="close">&times;</span>
            <h3>Просмотр заказа</h3>
            <div class="pdf-viewer-container">
                <iframe id="pdfViewer" src="" width="100%" height="600px"></iframe>
            </div>
        </div>
    </div>

    <!-- Attached Files Modal -->
    <div id="attachedFilesModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Прикрепленные файлы</h3>
            <div id="attachedFilesList">
                <!-- Files list will be loaded here -->
            </div>
        </div>
    </div>



    <!-- Download Preorder Modal -->
    <div id="downloadPreorderModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Скачать предзаказ</h3>
            <form id="downloadPreorderForm">
                <input type="hidden" id="downloadOrderId" name="order_id">
                <div class="download-options">
                    <h4>Выберите данные для включения:</h4>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="sections[]" value="basic_info" checked> Основная информация</label>
                        <label><input type="checkbox" name="sections[]" value="cargo_info" checked> Информация о грузе</label>
                        <label><input type="checkbox" name="sections[]" value="temperature"> Температурный режим</label>
                        <label><input type="checkbox" name="sections[]" value="route_points" checked> Маршрут</label>
                        <label><input type="checkbox" name="sections[]" value="cost_info" checked> Стоимость</label>
                        <label><input type="checkbox" name="sections[]" value="extra_services"> Дополнительные услуги</label>
                        <label><input type="checkbox" name="sections[]" value="notes"> Примечания</label>
                    </div>
                    <h4>Формат файла:</h4>
                    <div class="radio-group">
                        <label><input type="radio" name="export_format" value="pdf" checked> PDF</label>
                        <label><input type="radio" name="export_format" value="word"> Word</label>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-download"></i> Скачать
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeModal('downloadPreorderModal')">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Generate Official Order Modal -->
    <div id="generateOfficialOrderModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Создать официальный заказ</h3>
            <form id="generateOfficialOrderForm">
                <input type="hidden" id="generateOfficialOrderId" name="order_id">
                <div class="download-options">
                    <h4>Тип документа:</h4>
                    <div class="radio-group">
                        <label><input type="radio" name="document_type" value="official_order" checked> Официальный заказ</label>
                        <label><input type="radio" name="document_type" value="transport_order"> Заявка на транспортировку</label>
                        <label><input type="radio" name="document_type" value="service_agreement"> Соглашение об оказании услуг</label>
                    </div>
                    <h4>Формат файла:</h4>
                    <div class="radio-group">
                        <label><input type="radio" name="export_format" value="pdf" checked> PDF</label>
                        <label><input type="radio" name="export_format" value="word"> Word</label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="include_signatures" value="1" checked> 
                            Включить поля для подписей
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="include_stamps" value="1" checked> 
                            Включить поля для печатей
                        </label>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-file-contract"></i> Создать документ
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeModal('generateOfficialOrderModal')">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Set Courier Modal -->
    <div id="setCourierModal" class="modal">
        <div class="modal-content large-modal">
            <span class="close">&times;</span>
            <h3>Назначить перевозчика</h3>
            <form id="setCourierForm">
                <input type="hidden" id="setCourierOrderId" name="order_id">
                <!-- Dynamic form content will be populated by JavaScript -->
                <div id="setCourierFormContent">
                    <!-- Content populated dynamically -->
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Назначить
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeModal('setCourierModal')">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Status Modal -->
    <div id="changeStatusModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Изменить статус заказа</h3>
            <form id="changeStatusForm">
                <input type="hidden" id="changeStatusOrderId" name="order_id">
                
                <div class="form-section">
                    <h4>Информация о заказе</h4>
                    <div id="statusOrderInfo">
                        <!-- Order info will be populated -->
                    </div>
                </div>

                <div class="form-section">
                    <h4>Изменение статуса</h4>
                    <div class="form-group">
                        <label for="currentStatus">Текущий статус:</label>
                        <div id="currentStatusDisplay" class="current-status-display">
                            <!-- Current status will be shown here -->
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="newStatus">Новый статус:</label>
                        <select id="newStatus" name="new_status_id" required>
                            <option value="">Выберите новый статус...</option>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="statusChangeReason">Причина изменения:</label>
                        <textarea id="statusChangeReason" name="reason" rows="3" placeholder="Укажите причину изменения статуса..."></textarea>
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-check"></i> Изменить статус
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeModal('changeStatusModal')">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status History Modal -->
    <div id="statusHistoryModal" class="modal">
        <div class="modal-content large-modal">
            <span class="close">&times;</span>
            <h3>История изменения статусов</h3>
            
            <div class="form-section">
                <h4>Информация о заказе</h4>
                <div id="historyOrderInfo">
                    <!-- Order info will be populated -->
                </div>
            </div>

            <div class="form-section">
                <h4>История изменений</h4>
                <div id="statusHistoryContent">
                    <!-- History will be populated -->
                </div>
            </div>

            <div class="modal-buttons">
                <button type="button" class="btn-secondary" onclick="closeModal('statusHistoryModal')">
                    Закрыть
                </button>
            </div>
        </div>
    </div>

    <script src="../js/manage_orders.js"></script>
    <script src="../js/sidebar.js"></script>
</body>
</html>
