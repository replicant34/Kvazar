// Order Preview Handler
class OrderPreviewHandler {
    constructor() {
        this.modal = null;
        this.originalContent = null;
        this.init();
    }

    init() {
        // Get the modal that's already in the page
        this.modal = document.getElementById('orderPreviewModal');
        
        // Store the original content for restoration
        const content = document.querySelector('.order-preview-content');
        if (content) {
            this.originalContent = content.innerHTML;
        }
        
        // Add modal event listeners
        this.addModalEventListeners();
    }

    addModalEventListeners() {
        if (!this.modal) {
            console.error('Order preview modal not found');
            return;
        }
        
        // Close button handlers
        const closeBtn = document.getElementById('orderPreviewModalClose');
        const backBtn = document.getElementById('backToEdit');
        const submitBtn = document.getElementById('confirmSubmitOrder');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hideModal());
        }
        
        if (backBtn) {
            backBtn.addEventListener('click', () => this.hideModal());
        }
        
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.handleSubmit());
        }
        
        // Click outside modal to close
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.hideModal();
            }
        });
        
        // ESC key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.style.display === 'block') {
                this.hideModal();
            }
        });
    }

    showOrderPreview() {
        if (!this.modal) {
            console.error('Modal not found');
            return;
        }

        // Collect and display order data
        this.displayOrderPreview();
        this.showModal();
    }

    showModal() {
        if (this.modal) {
            this.modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    hideModal() {
        if (this.modal) {
            this.modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    displayOrderPreview() {
        // Basic order information
        this.setElementText('previewOrderNumber', this.getFieldValue('order_number'));
        this.setElementText('previewOrderDate', this.formatDate(this.getFieldValue('order_date')));
        this.setElementText('previewClient', this.getSelectedText('clientSearch'));
        this.setElementText('previewContractor', this.getSelectedText('contractor'));
        this.setElementText('previewContract', this.getContractValue());
        this.setElementText('previewShippingType', this.getSelectedText('shipping_type'));

        // Cargo information
        this.setElementText('previewCargoName', this.getSelectedText('cargo_name'));
        this.setElementText('previewTransportType', this.getSelectedText('transport_type'));
        this.setElementText('previewCargoWeight', this.getWeightDisplay());
        this.setElementText('previewCargoVolume', this.getVolumeDisplay());
        this.setElementText('previewDimensions', this.getDimensionsDisplay());
        this.setElementText('previewQuantity', this.getFieldValue('cargo_quantity'));
        this.setElementText('previewLoadingType', this.getSelectedText('loading_type'));
        this.setElementText('previewPackagingType', this.getSelectedText('packaging_type'));

        // Temperature information
        this.displayTemperatureInfo();

        // Route information
        this.displayRoutePoints();

        // Cost information
        this.displayCostInfo();

        // Extra services
        this.displayExtraServices();

        // Supporting documents
        this.displayDocuments();

        // Additional notes
        this.setElementText('previewNotes', this.getFieldValue('order_notes'));
    }

    displayTemperatureInfo() {
        const minTemp = this.getFieldValue('min_temperature');
        const maxTemp = this.getFieldValue('max_temperature');
        const tempPrint = this.getSelectedText('temp_print_list');
        
        if (minTemp || maxTemp || tempPrint) {
            this.setElementText('previewMinTemp', minTemp ? `${minTemp}°C` : '—');
            this.setElementText('previewMaxTemp', maxTemp ? `${maxTemp}°C` : '—');
            this.setElementText('previewTempPrint', tempPrint || '—');
            this.showSection('temperatureSection');
        } else {
            this.hideSection('temperatureSection');
        }
    }

    displayRoutePoints() {
        const routeContainer = document.getElementById('previewRoutePoints');
        if (!routeContainer) return;

        const routePoints = document.querySelectorAll('.route-point');
        routeContainer.innerHTML = '';

        if (routePoints.length === 0) {
            routeContainer.innerHTML = '<div class="empty-state">Маршрут не задан</div>';
            return;
        }

        routePoints.forEach((point, index) => {
            const position = point.dataset.position || (index + 1);
            const pointData = this.collectRoutePointData(point, position);
            
            const pointElement = this.createRoutePointElement(pointData, index);
            routeContainer.appendChild(pointElement);
        });
    }

    collectRoutePointData(pointElement, position) {
        return {
            actionType: this.getElementValue(`action_type_${position}`),
            date: this.getElementValue(`point_date_${position}`),
            time: this.getElementValue(`point_time_${position}`),
            address: this.getElementValue(`address_loading_${position}`),
            companyName: this.getElementValue(`company_name_${position}`),
            contactPerson: this.getElementValue(`contact_person_${position}`),
            phoneNumber: this.getElementValue(`phone_number_${position}`)
        };
    }

    createRoutePointElement(pointData, index) {
        const pointDiv = document.createElement('div');
        pointDiv.className = 'route-point-item';
        
        const dateTime = this.formatDateTime(pointData.date, pointData.time);
        
        pointDiv.innerHTML = `
            <div class="route-point-header">
                <div class="route-point-type">${pointData.actionType || 'Не указано'}</div>
                <div class="route-point-datetime">${dateTime}</div>
            </div>
            <div class="route-point-details">
                <div class="route-point-detail">
                    <label>Адрес:</label>
                    <span>${pointData.address || '—'}</span>
                </div>
                <div class="route-point-detail">
                    <label>Компания:</label>
                    <span>${pointData.companyName || '—'}</span>
                </div>
                <div class="route-point-detail">
                    <label>Контактное лицо:</label>
                    <span>${pointData.contactPerson || '—'}</span>
                </div>
                <div class="route-point-detail">
                    <label>Телефон:</label>
                    <span>${pointData.phoneNumber || '—'}</span>
                </div>
            </div>
        `;
        
        return pointDiv;
    }

    displayCostInfo() {
        const cargoPrice = this.getFieldValue('cargo_price');
        const currency = this.getSelectedText('currency');
        const rate = this.getFieldValue('rate');
        const insurance = this.getFieldValue('total_insurance');
        const transportCost = this.getFieldValue('transport_total');
        const totalCost = this.getElementText('summary-grand-total');

        this.setElementText('previewCargoPrice', cargoPrice ? `${cargoPrice} ${currency}` : '—');
        this.setElementText('previewCurrency', currency || '—');
        this.setElementText('previewRate', rate || '—');
        this.setElementText('previewInsurance', insurance ? `${insurance} ${currency}` : '—');
        this.setElementText('previewTransportCost', transportCost ? `${transportCost} ${currency}` : '—');
        this.setElementText('previewTotalCost', totalCost || '—');
    }

    displayExtraServices() {
        const extraServicesContainer = document.getElementById('previewExtraServicesList');
        if (!extraServicesContainer) return;

        const serviceRows = document.querySelectorAll('.extra-service-row');
        extraServicesContainer.innerHTML = '';

        if (serviceRows.length === 0) {
            extraServicesContainer.innerHTML = '<div class="empty-state">Дополнительные услуги не указаны</div>';
            this.hideSection('extraServicesSection');
            return;
        }

        let hasServices = false;
        serviceRows.forEach(row => {
            const serviceName = this.getSelectValue(row, '.extra-service-select');
            const quantity = this.getInputValue(row, '.extra-service-quantity-input');
            const price = this.getInputValue(row, '.extra-service-price-input');
            const total = this.getInputValue(row, '.extra-service-total-input');

            if (serviceName && quantity && price) {
                hasServices = true;
                const serviceElement = this.createExtraServiceElement(serviceName, quantity, price, total);
                extraServicesContainer.appendChild(serviceElement);
            }
        });

        if (!hasServices) {
            extraServicesContainer.innerHTML = '<div class="empty-state">Дополнительные услуги не указаны</div>';
            this.hideSection('extraServicesSection');
        } else {
            this.showSection('extraServicesSection');
        }
    }

    createExtraServiceElement(serviceName, quantity, price, total) {
        const serviceDiv = document.createElement('div');
        serviceDiv.className = 'extra-service-item';
        
        serviceDiv.innerHTML = `
            <div class="extra-service-name">${serviceName}</div>
            <div class="extra-service-quantity">${quantity}</div>
            <div class="extra-service-price">${price}</div>
            <div class="extra-service-total">${total}</div>
        `;
        
        return serviceDiv;
    }

    displayDocuments() {
        const documentsContainer = document.getElementById('previewDocumentsList');
        if (!documentsContainer) return;

        const uploadedFiles = window.getUploadedFiles ? window.getUploadedFiles() : [];
        documentsContainer.innerHTML = '';

        if (uploadedFiles.length === 0) {
            documentsContainer.innerHTML = '<div class="empty-state">Сопроводительные документы не загружены</div>';
            this.hideSection('documentsSection');
            return;
        }

        uploadedFiles.forEach(file => {
            const documentElement = this.createDocumentElement(file);
            documentsContainer.appendChild(documentElement);
        });

        this.showSection('documentsSection');
    }

    createDocumentElement(file) {
        const docDiv = document.createElement('div');
        docDiv.className = 'document-item';
        
        const icon = this.getFileIcon(file.type);
        const size = this.formatFileSize(file.size);
        
        docDiv.innerHTML = `
            <div class="document-icon">
                <i class="fas ${icon}"></i>
            </div>
            <div class="document-name">${file.original_name}</div>
            <div class="document-size">${size}</div>
        `;
        
        return docDiv;
    }

    // Helper methods
    getFieldValue(fieldId) {
        const element = document.getElementById(fieldId);
        return element ? element.value.trim() : '';
    }

    getSelectedText(fieldId) {
        const element = document.getElementById(fieldId);
        if (!element) return '';
        
        if (element.tagName === 'SELECT') {
            const option = element.options[element.selectedIndex];
            return option ? option.text : '';
        }
        return element.value.trim();
    }

    getElementValue(elementId) {
        const element = document.getElementById(elementId);
        return element ? element.value.trim() : '';
    }

    getElementText(elementId) {
        const element = document.getElementById(elementId);
        return element ? element.textContent.trim() : '';
    }

    getSelectValue(parentElement, selector) {
        const select = parentElement.querySelector(selector);
        if (!select) return '';
        const option = select.options[select.selectedIndex];
        return option ? option.text : '';
    }

    getInputValue(parentElement, selector) {
        const input = parentElement.querySelector(selector);
        return input ? input.value.trim() : '';
    }

    setElementText(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value || '—';
        }
    }

    showSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.style.display = 'block';
        }
    }

    hideSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.style.display = 'none';
        }
    }

    getContractValue() {
        const contractSelect = document.getElementById('contract_select');
        const contractStatic = document.getElementById('contract_static');
        const contractDate = this.getFieldValue('contract_date');
        
        let contractNumber = '';
        
        if (contractStatic && contractStatic.style.display !== 'none') {
            contractNumber = contractStatic.value || '';
        } else if (contractSelect && contractSelect.style.display !== 'none') {
            const option = contractSelect.options[contractSelect.selectedIndex];
            contractNumber = option ? option.text : '';
        }
        
        // Format with date if available: "774697 от 01.12.2020"
        if (contractNumber && contractDate) {
            const formattedDate = this.formatDate(contractDate);
            return `${contractNumber} от ${formattedDate}`;
        }
        
        return contractNumber || '';
    }

    getWeightDisplay() {
        const weight = this.getFieldValue('cargo_weight');
        const activeBtn = document.querySelector('.unit-btn.active');
        const unit = activeBtn ? (activeBtn.dataset.unit === 'ton' ? 'тонн' : 'кг') : 'тонн';
        return weight ? `${weight} ${unit}` : '—';
    }

    getVolumeDisplay() {
        const volume = this.getFieldValue('cargo_volume');
        return volume ? `${volume} м³` : '—';
    }

    getDimensionsDisplay() {
        const length = this.getFieldValue('length');
        const width = this.getFieldValue('width');
        const height = this.getFieldValue('height');
        
        if (length && width && height) {
            return `${length} × ${width} × ${height} м`;
        } else if (length || width || height) {
            return `${length || '—'} × ${width || '—'} × ${height || '—'} м`;
        }
        return '—';
    }

    formatDate(dateString) {
        if (!dateString || dateString.trim() === '') return '—';
        
        try {
            let date;
            
            // Check if date is in DD.MM.YYYY format (Russian format)
            if (dateString.includes('.')) {
                const parts = dateString.split('.');
                if (parts.length === 3) {
                    const day = parseInt(parts[0], 10);
                    const month = parseInt(parts[1], 10) - 1; // Month is 0-indexed
                    const year = parseInt(parts[2], 10);
                    date = new Date(year, month, day);
                } else {
                    date = new Date(dateString);
                }
            } else {
                // Standard date format
                date = new Date(dateString);
            }
            
            // Check if the date is valid
            if (isNaN(date.getTime())) {
                console.warn('Invalid date:', dateString);
                return '—';
            }
            
            return date.toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        } catch (error) {
            console.warn('Date formatting error:', error, 'for date:', dateString);
            return '—';
        }
    }

    formatDateTime(dateString, timeString) {
        if (!dateString && !timeString) return '—';
        
        const date = dateString ? this.formatDate(dateString) : '—';
        const time = timeString || '—';
        
        return `${date} ${time}`;
    }

    getFileIcon(mimeType) {
        if (!mimeType) return 'fa-file';
        
        if (mimeType.includes('pdf')) {
            return 'fa-file-pdf';
        } else if (mimeType.includes('word') || mimeType.includes('document')) {
            return 'fa-file-word';
        } else if (mimeType.includes('excel') || mimeType.includes('sheet')) {
            return 'fa-file-excel';
        } else if (mimeType.includes('image')) {
            return 'fa-file-image';
        } else if (mimeType.includes('text')) {
            return 'fa-file-alt';
        } else {
            return 'fa-file';
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    handleSubmit() {
        // Hide the preview modal
        this.hideModal();
        
        // Call the actual submit function
        if (window.submitOrder) {
            window.submitOrder();
        } else {
            console.error('submitOrder function not found');
        }
    }
}

// Initialize order preview handler when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.orderPreviewHandler = new OrderPreviewHandler();
});

// Global function to show order preview
window.showOrderPreview = function() {
    if (window.orderPreviewHandler) {
        window.orderPreviewHandler.showOrderPreview();
    }
};
