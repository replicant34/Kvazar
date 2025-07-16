<?php
// Order Preview Modal Component
?>
<div id="orderPreviewModal" class="mc-modal">
    <div class="mc-modal-content order-preview-modal">
        <span class="mc-close" id="orderPreviewModalClose">&times;</span>
        <div class="order-preview-header">
            <h2 id="orderPreviewTitle">Предварительный просмотр заказа</h2>
            <div class="order-status-badge" id="orderStatusBadge">Проверьте данные</div>
        </div>
        
        <div class="order-preview-content">
            <!-- Order Information Section -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-clipboard-list"></i>
                    Основная информация
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Номер заказа:</label>
                        <span id="previewOrderNumber"></span>
                    </div>
                    <div class="info-item">
                        <label>Дата заказа:</label>
                        <span id="previewOrderDate"></span>
                    </div>
                    <div class="info-item">
                        <label>Клиент:</label>
                        <span id="previewClient"></span>
                    </div>
                    <div class="info-item">
                        <label>Подрядчик:</label>
                        <span id="previewContractor"></span>
                    </div>
                    <div class="info-item">
                        <label>Договор:</label>
                        <span id="previewContract"></span>
                    </div>
                    <div class="info-item">
                        <label>Тип перевозки:</label>
                        <span id="previewShippingType"></span>
                    </div>
                </div>
            </div>

            <!-- Cargo Information Section -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-boxes"></i>
                    Информация о грузе
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Наименование груза:</label>
                        <span id="previewCargoName"></span>
                    </div>
                    <div class="info-item">
                        <label>Тип транспорта:</label>
                        <span id="previewTransportType"></span>
                    </div>
                    <div class="info-item">
                        <label>Вес груза:</label>
                        <span id="previewCargoWeight"></span>
                    </div>
                    <div class="info-item">
                        <label>Объем груза:</label>
                        <span id="previewCargoVolume"></span>
                    </div>
                    <div class="info-item">
                        <label>Габариты (Д×Ш×В):</label>
                        <span id="previewDimensions"></span>
                    </div>
                    <div class="info-item">
                        <label>Количество мест:</label>
                        <span id="previewQuantity"></span>
                    </div>
                    <div class="info-item">
                        <label>Тип погрузки:</label>
                        <span id="previewLoadingType"></span>
                    </div>
                    <div class="info-item">
                        <label>Тип упаковки:</label>
                        <span id="previewPackagingType"></span>
                    </div>
                </div>
            </div>

            <!-- Temperature Section -->
            <div class="info-section" id="temperatureSection">
                <h3 class="section-title">
                    <i class="fas fa-thermometer-half"></i>
                    Температурный режим
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Мин. температура:</label>
                        <span id="previewMinTemp"></span>
                    </div>
                    <div class="info-item">
                        <label>Макс. температура:</label>
                        <span id="previewMaxTemp"></span>
                    </div>
                    <div class="info-item">
                        <label>Температурный лист:</label>
                        <span id="previewTempPrint"></span>
                    </div>
                </div>
            </div>

            <!-- Route Information Section -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-route"></i>
                    Маршрут
                </h3>
                <div id="previewRoutePoints" class="route-points-list">
                    <!-- Route points will be populated dynamically -->
                </div>
            </div>

            <!-- Cost Information Section -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-calculator"></i>
                    Стоимость
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Стоимость груза:</label>
                        <span id="previewCargoPrice"></span>
                    </div>
                    <div class="info-item">
                        <label>Валюта:</label>
                        <span id="previewCurrency"></span>
                    </div>
                    <div class="info-item">
                        <label>Коэффициент:</label>
                        <span id="previewRate"></span>
                    </div>
                    <div class="info-item">
                        <label>Страхование:</label>
                        <span id="previewInsurance"></span>
                    </div>
                    <div class="info-item">
                        <label>Транспортировка:</label>
                        <span id="previewTransportCost"></span>
                    </div>
                    <div class="info-item">
                        <label>Дополнительные услуги:</label>
                        <span id="previewExtraServices"></span>
                    </div>
                </div>
                <div class="total-summary">
                    <div class="total-item">
                        <label>Общая стоимость услуг:</label>
                        <span id="previewTotalCost" class="total-amount"></span>
                    </div>
                </div>
            </div>

            <!-- Extra Services Section -->
            <div class="info-section" id="extraServicesSection">
                <h3 class="section-title">
                    <i class="fas fa-plus-circle"></i>
                    Дополнительные услуги
                </h3>
                <div id="previewExtraServicesList" class="extra-services-list">
                    <!-- Extra services will be populated dynamically -->
                </div>
            </div>

            <!-- Supporting Documents Section -->
            <div class="info-section" id="documentsSection">
                <h3 class="section-title">
                    <i class="fas fa-paperclip"></i>
                    Сопроводительные документы
                </h3>
                <div id="previewDocumentsList" class="documents-list">
                    <!-- Documents will be populated dynamically -->
                </div>
            </div>

            <!-- Additional Notes Section -->
            <div class="info-section" id="notesSection">
                <h3 class="section-title">
                    <i class="fas fa-sticky-note"></i>
                    Дополнительные примечания
                </h3>
                <div class="info-item full-width">
                    <span id="previewNotes" class="notes-text"></span>
                </div>
            </div>
        </div>

        <div class="order-preview-footer">
            <button class="mc-btn-back" id="backToEdit">
                <i class="fas fa-arrow-left"></i>
                Вернуться к редактированию
            </button>
            <button class="mc-btn-submit" id="confirmSubmitOrder">
                <i class="fas fa-check"></i>
                Подтвердить и создать заказ
            </button>
        </div>
    </div>
</div> 