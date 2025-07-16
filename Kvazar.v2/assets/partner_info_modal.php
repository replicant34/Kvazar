<?php
// Partner Information Modal Component
?>
<div id="partnerInfoModal" class="mc-modal">
    <div class="mc-modal-content partner-info-modal">
        <span class="mc-close" id="partnerInfoModalClose">&times;</span>
        <div class="partner-info-header">
            <h2 id="partnerInfoTitle">Информация о партнере</h2>
            <div class="partner-type-badge" id="partnerTypeBadge"></div>
        </div>
        
        <div class="partner-info-content">
            <!-- Company Information Section -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-building"></i>
                    Информация о компании
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Тип компании:</label>
                        <span id="companyType"></span>
                    </div>
                    <div class="info-item">
                        <label>Полное название:</label>
                        <span id="fullCompanyName"></span>
                    </div>
                    <div class="info-item">
                        <label>Краткое название:</label>
                        <span id="shortCompanyName"></span>
                    </div>
                    <div class="info-item">
                        <label>ИНН:</label>
                        <span id="inn"></span>
                    </div>
                    <div class="info-item">
                        <label>КПП:</label>
                        <span id="kpp"></span>
                    </div>
                    <div class="info-item">
                        <label>ОГРН:</label>
                        <span id="ogrn"></span>
                    </div>
                </div>
            </div>

            <!-- Addresses Section -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Адреса
                </h3>
                <div class="info-grid">
                    <div class="info-item full-width">
                        <label>Физический адрес:</label>
                        <span id="physicalAddress"></span>
                    </div>
                    <div class="info-item full-width">
                        <label>Юридический адрес:</label>
                        <span id="legalAddress"></span>
                    </div>
                </div>
            </div>

            <!-- Bank Information Section -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-university"></i>
                    Банковская информация
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Название банка:</label>
                        <span id="bankName"></span>
                    </div>
                    <div class="info-item">
                        <label>БИК:</label>
                        <span id="bik"></span>
                    </div>
                    <div class="info-item">
                        <label>Расчетный счет:</label>
                        <span id="settlementAccount"></span>
                    </div>
                    <div class="info-item">
                        <label>Корр. счет:</label>
                        <span id="correspondentAccount"></span>
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-user"></i>
                    Контактная информация
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Контактное лицо:</label>
                        <span id="contactPerson"></span>
                    </div>
                    <div class="info-item">
                        <label>Должность:</label>
                        <span id="contactPosition"></span>
                    </div>
                    <div class="info-item">
                        <label>Телефон:</label>
                        <span id="contactPhone"></span>
                    </div>
                    <div class="info-item">
                        <label>Email:</label>
                        <span id="contactEmail"></span>
                    </div>
                </div>
            </div>

            <!-- Head Information Section -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-user-tie"></i>
                    Руководитель
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Должность:</label>
                        <span id="headPosition"></span>
                    </div>
                    <div class="info-item">
                        <label>ФИО:</label>
                        <span id="headName"></span>
                    </div>
                </div>
            </div>

            <!-- Dates Section -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Даты
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Дата создания:</label>
                        <span id="createdDate"></span>
                    </div>
                    <div class="info-item">
                        <label>Последнее обновление:</label>
                        <span id="updatedDate"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="partner-info-footer">
            <button class="mc-btn-download" id="downloadPartnerPdf">
                <i class="fas fa-download"></i>
                Скачать PDF
            </button>
            <button class="mc-btn-cancel" id="closePartnerInfo">Закрыть</button>
        </div>
    </div>
</div> 