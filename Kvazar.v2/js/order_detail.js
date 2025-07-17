// Order Detail Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeOrderDetail();
});

function initializeOrderDetail() {
    setupTabs();
    loadOrderData();
    setupEventListeners();
}

// Tab Management
function setupTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
            
            // Load content for the tab if needed
            loadTabContent(targetTab);
        });
    });
}

// Load dynamic content for tabs
function loadTabContent(tabName) {
    switch(tabName) {
        case 'route':
            loadRoutePoints();
            break;
        case 'services':
            loadExtraServices();
            break;
        case 'courier':
            loadCourierInfo();
            break;
        case 'files':
            loadAttachedFiles();
            break;
    }
}

// Load order data on page load
function loadOrderData() {
    // Load route points (since details tab is active by default)
    loadRoutePoints();
}

// Load route points
function loadRoutePoints() {
    const container = document.getElementById('route-points-container');
    if (!container || container.dataset.loaded === 'true') return;
    
    container.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div>Загрузка маршрута...</div>';
    
    fetch(`../assets/get_order_route.php?order_id=${window.orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRoutePoints(data.route_points);
                container.dataset.loaded = 'true';
            } else {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-route"></i><p>Маршрут не найден</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading route points:', error);
            container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Ошибка загрузки маршрута</p></div>';
        });
}

// Display route points
function displayRoutePoints(routePoints) {
    const container = document.getElementById('route-points-container');
    
    if (!routePoints || routePoints.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-route"></i><p>Маршрут не задан</p></div>';
        return;
    }
    
    const routeHTML = routePoints.map((point, index) => {
        const contacts = point.contact_list || [];
        const contactsHTML = contacts.length > 0 
            ? contacts.map(contact => `${escapeHtml(contact.name)} (${escapeHtml(contact.phone)})`).join(', ')
            : 'Не указаны';
        
        const actionClass = point.Action_type === 'Погрузка' ? 'loading' : 'unloading';
        
        return `
            <div class="route-point">
                <div class="route-point-header">
                    <h5 class="route-point-title">Пункт ${index + 1}</h5>
                    <span class="route-action-badge ${actionClass}">
                        ${escapeHtml(point.Action_type || 'Не указано')}
                    </span>
                </div>
                <div class="route-point-details">
                    <div class="route-detail">
                        <span class="route-detail-label">Компания</span>
                        <span class="route-detail-value">${escapeHtml(point.Company_name || 'Не указано')}</span>
                    </div>
                    <div class="route-detail">
                        <span class="route-detail-label">Адрес</span>
                        <span class="route-detail-value">${escapeHtml(point.Address || 'Не указан')}</span>
                    </div>
                    <div class="route-detail">
                        <span class="route-detail-label">Дата</span>
                        <span class="route-detail-value">${formatDate(point.Date) || 'Не указана'}</span>
                    </div>
                    <div class="route-detail">
                        <span class="route-detail-label">Время</span>
                        <span class="route-detail-value">${point.Time || 'Не указано'}</span>
                    </div>
                    <div class="route-detail">
                        <span class="route-detail-label">Контакты</span>
                        <span class="route-detail-value">${contactsHTML}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    container.innerHTML = routeHTML;
}

// Load extra services
function loadExtraServices() {
    const container = document.getElementById('extra-services-container');
    if (!container || container.dataset.loaded === 'true') return;
    
    container.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div>Загрузка услуг...</div>';
    
    fetch(`../assets/get_order_services.php?order_id=${window.orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayExtraServices(data.extra_services);
                container.dataset.loaded = 'true';
            } else {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-concierge-bell"></i><p>Дополнительные услуги не найдены</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading extra services:', error);
            container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Ошибка загрузки услуг</p></div>';
        });
}

// Display extra services
function displayExtraServices(extraServices) {
    const container = document.getElementById('extra-services-container');
    
    if (!extraServices || extraServices.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-concierge-bell"></i><p>Дополнительные услуги не указаны</p></div>';
        return;
    }
    
    let total = 0;
    const servicesHTML = extraServices.map((service, index) => {
        const serviceTotal = parseFloat(service.Total || 0);
        total += serviceTotal;
        
        return `
            <div class="service-item">
                <div class="service-detail">
                    <span class="service-label">Услуга</span>
                    <span class="service-value">${escapeHtml(service.Service_name || 'Не указано')}</span>
                </div>
                <div class="service-detail">
                    <span class="service-label">Цена</span>
                    <span class="service-value">${formatCurrency(service.Service_price, '')}</span>
                </div>
                <div class="service-detail">
                    <span class="service-label">Количество</span>
                    <span class="service-value">${service.Quantity || 0}</span>
                </div>
                <div class="service-detail">
                    <span class="service-label">Итого</span>
                    <span class="service-value">${formatCurrency(service.Total, '')}</span>
                </div>
            </div>
        `;
    }).join('');
    
    container.innerHTML = `
        ${servicesHTML}
        <div class="services-total">
            Общая стоимость услуг: ${formatCurrency(total, '')}
        </div>
    `;
}

// Load courier information
function loadCourierInfo() {
    const container = document.getElementById('courier-info-container');
    if (!container || container.dataset.loaded === 'true') return;
    
    container.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div>Загрузка информации о перевозчике...</div>';
    
    fetch(`../assets/get_order_courier_details.php?order_id=${window.orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCourierInfo(data);
                container.dataset.loaded = 'true';
            } else {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-truck"></i><p>Информация о перевозчике не найдена</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading courier info:', error);
            container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Ошибка загрузки информации о перевозчике</p></div>';
        });
}

// Display courier information
function displayCourierInfo(data) {
    const container = document.getElementById('courier-info-container');
    
    if (!data.courier) {
        container.innerHTML = `
            <div class="content-card">
                <div class="empty-state">
                    <i class="fas fa-truck"></i>
                    <p>Перевозчик не назначен</p>
                    <button class="btn-action btn-courier" onclick="showAddCourierModal()">
                        <i class="fas fa-plus"></i> Назначить перевозчика
                    </button>
                </div>
            </div>
        `;
        return;
    }
    
    const courier = data.courier;
    const drivers = data.drivers || [];
    const vehicles = data.vehicles || [];
    
    let courierHTML = `
        <div class="content-card">
            <h4>Информация о перевозчике</h4>
            <div class="courier-details">
                <div class="courier-main-info">
                    <div class="info-item">
                        <span class="info-label">Название компании:</span>
                        <span class="info-value">${escapeHtml(courier.name)}</span>
                    </div>
                    ${courier.contact_person ? `
                        <div class="info-item">
                            <span class="info-label">Контактное лицо:</span>
                            <span class="info-value">${escapeHtml(courier.contact_person)}</span>
                        </div>
                    ` : ''}
                    ${courier.phone ? `
                        <div class="info-item">
                            <span class="info-label">Телефон:</span>
                            <span class="info-value">
                                <a href="tel:${courier.phone}" class="phone-link">${escapeHtml(courier.phone)}</a>
                            </span>
                        </div>
                    ` : ''}
                    ${courier.email ? `
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value">
                                <a href="mailto:${courier.email}" class="email-link">${escapeHtml(courier.email)}</a>
                            </span>
                        </div>
                    ` : ''}
                    ${courier.address ? `
                        <div class="info-item">
                            <span class="info-label">Адрес:</span>
                            <span class="info-value">${escapeHtml(courier.address)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    
    // Add drivers section if there are assigned drivers
    if (drivers && drivers.length > 0) {
        courierHTML += `
            <div class="content-card">
                <h4>Назначенные водители</h4>
                <div class="drivers-list">
                    ${drivers.map(driver => `
                        <div class="driver-item">
                            <div class="driver-main">
                                <div class="driver-name">
                                    <i class="fas fa-user"></i>
                                    ${escapeHtml(driver.driver_name)}
                                </div>
                                <div class="driver-details">
                                    ${driver.driver_phone ? `
                                        <div class="driver-detail">
                                            <i class="fas fa-phone"></i>
                                            <a href="tel:${driver.driver_phone}" class="phone-link">${escapeHtml(driver.driver_phone)}</a>
                                        </div>
                                    ` : ''}
                                    ${driver.driver_passport ? `
                                        <div class="driver-detail">
                                            <i class="fas fa-id-card"></i>
                                            ${escapeHtml(driver.driver_passport)}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                            <div class="assignment-date">
                                Назначен: ${formatDate(driver.assigned_at)}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    // Add vehicles section if there are assigned vehicles
    if (vehicles && vehicles.length > 0) {
        courierHTML += `
            <div class="content-card">
                <h4>Назначенные транспортные средства</h4>
                <div class="vehicles-list">
                    ${vehicles.map(vehicle => `
                        <div class="vehicle-item">
                            <div class="vehicle-main">
                                <div class="vehicle-name">
                                    <i class="fas fa-truck"></i>
                                    ${escapeHtml(vehicle.vehicle_display)}
                                </div>
                                <div class="vehicle-details">
                                    <div class="vehicle-detail">
                                        <span class="label">Марка:</span>
                                        <span class="value">${escapeHtml(vehicle.vehicle_brand)}</span>
                                    </div>
                                    <div class="vehicle-detail">
                                        <span class="label">Номер:</span>
                                        <span class="value">${escapeHtml(vehicle.vehicle_plate)}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="assignment-date">
                                Назначен: ${formatDate(vehicle.assigned_at)}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    container.innerHTML = courierHTML;
}

// Load attached files
function loadAttachedFiles() {
    const container = document.getElementById('attached-files-container');
    if (!container || container.dataset.loaded === 'true') return;
    
    container.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div>Загрузка файлов...</div>';
    
    fetch(`../assets/get_attached_files.php?order_id=${window.orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAttachedFiles(data.files);
                container.dataset.loaded = 'true';
            } else {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-file"></i><p>Прикрепленные файлы не найдены</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading attached files:', error);
            container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Ошибка загрузки файлов</p></div>';
        });
}

// Display attached files
function displayAttachedFiles(files) {
    const container = document.getElementById('attached-files-container');
    
    if (!files || files.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-file"></i><p>Прикрепленные файлы отсутствуют</p></div>';
        return;
    }
    
    const filesHTML = files.map(file => `
        <div class="file-item">
            <div class="file-info">
                <i class="fas fa-file file-icon"></i>
                <div class="file-details">
                    <div class="file-name">${escapeHtml(file.original_filename)}</div>
                    <div class="file-size">${formatFileSize(file.file_size)}</div>
                </div>
            </div>
            <button class="file-download-btn" onclick="downloadFile('${file.file_path}', '${file.original_filename}')">
                <i class="fas fa-download"></i> Скачать
            </button>
        </div>
    `).join('');
    
    container.innerHTML = filesHTML;
}

// Action Handlers
function toggleEditMode() {
    loadOrderForEdit();
}

// Edit functionality
function loadOrderForEdit() {
    const modal = document.getElementById('editOrderModal');
    const formContent = document.getElementById('editOrderFormContent');
    
    if (!modal || !formContent) {
        console.error('Edit modal elements not found');
        return;
    }
    
    formContent.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner"></div>Загрузка данных заказа...</div>';
    modal.style.display = 'block';
    
    // Load order data, route points, and extra services data
    const promises = [
        fetch(`../assets/get_order_for_verification.php?order_id=${window.orderId}`).then(r => r.json()),
        fetch(`../assets/get_order_route.php?order_id=${window.orderId}`).then(r => r.json()),
        fetch(`../assets/get_order_services.php?order_id=${window.orderId}`).then(r => r.json())
    ];
    
    Promise.all(promises)
        .then(([orderResponse, routeResponse, servicesResponse]) => {
            if (orderResponse.success) {
                const routePoints = routeResponse.success ? routeResponse.route_points : [];
                const extraServices = servicesResponse.success ? servicesResponse.extra_services : [];
                
                // Combine data for form generation
                const combinedData = {
                    order: orderResponse.order,
                    route_points: routePoints,
                    extra_services: extraServices,
                    dropdown_data: orderResponse.dropdown_data || {}
                };
                
                generateEditForm(combinedData);
                setupEditCalculations();
            } else {
                formContent.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Ошибка загрузки данных: ' + (orderResponse.error || 'Неизвестная ошибка') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error loading edit data:', error);
            formContent.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Ошибка загрузки данных</div>';
        });
}

function closeEditModal() {
    const modal = document.getElementById('editOrderModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function generateEditForm(data) {
    const formContent = document.getElementById('editOrderFormContent');
    const order = data.order;
    const routePoints = data.route_points;
    const extraServices = data.extra_services;
    const dropdownData = data.dropdown_data;
    
    let formHTML = `
        <!-- Client Information Section -->
        <div class="form-section">
            <h4>Информация о клиенте</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_client_name">Клиент:</label>
                    <input type="text" id="edit_client_name" value="${escapeHtml(order.client_name || '')}" disabled>
                    <input type="hidden" name="client_id" value="${order.Client_id || ''}">
                </div>
                <div class="form-group">
                    <label for="edit_contract_number">Номер договора:</label>
                    <input type="text" id="edit_contract_number" value="${escapeHtml(order.Contract_number || '')}" disabled>
                </div>
                <div class="form-group">
                    <label for="edit_contract_date">Дата договора:</label>
                    <input type="text" id="edit_contract_date" value="${formatDate(order.Contract_date) || ''}" disabled>
                </div>
                <div class="form-group">
                    <label for="edit_contractor">Подрядчик:</label>
                    <select id="edit_contractor" name="contractor_id">
                        <option value="">Выберите подрядчика...</option>
                        ${dropdownData.contractors.map(contractor => 
                            `<option value="${contractor.Contractors_id}" ${order.Contractor == contractor.Contractors_id ? 'selected' : ''}>
                                ${escapeHtml(contractor.Full_Company_name)}
                            </option>`
                        ).join('')}
                    </select>
                </div>
            </div>
        </div>

        <!-- Order Information Section -->
        <div class="form-section">
            <h4>Информация о заказе</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_order_number" class="required">Номер заказа:</label>
                    <input type="text" id="edit_order_number" name="display_order_number" value="${escapeHtml(order.display_order_number || '')}" required>
                </div>
                <div class="form-group">
                    <label for="edit_order_date">Дата заказа:</label>
                    <input type="date" id="edit_order_date" name="order_date" value="${order.Order_date || ''}">
                </div>
                <div class="form-group">
                    <label for="edit_shipping_type">Тип перевозки:</label>
                    <select id="edit_shipping_type" name="shipping_type">
                        <option value="">Выберите тип перевозки...</option>
                        ${dropdownData.shipping_types.map(type => 
                            `<option value="${type.Type_id}" ${order.Shipping_type == type.Type_id ? 'selected' : ''}>
                                ${escapeHtml(type.Type_name)}
                            </option>`
                        ).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_transport_type">Тип транспорта:</label>
                    <select id="edit_transport_type" name="transport_type">
                        <option value="">Выберите тип транспорта...</option>
                        ${dropdownData.transport_types.map(type => 
                            `<option value="${type.Type_id}" ${order.Vehicle_type == type.Type_id ? 'selected' : ''}>
                                ${escapeHtml(type.Type_name)}
                            </option>`
                        ).join('')}
                    </select>
                </div>
            </div>
        </div>

        <!-- Cargo Information Section -->
        <div class="form-section">
            <h4>Информация о грузе</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_cargo_name">Наименование груза:</label>
                    <select id="edit_cargo_name" name="cargo_name">
                        <option value="">Выберите груз...</option>
                        ${dropdownData.cargo_names.map(cargo => 
                            `<option value="${cargo.id}" ${order.Cargo_type == cargo.id ? 'selected' : ''}>
                                ${escapeHtml(cargo.cargo_name)}
                            </option>`
                        ).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_cargo_weight">Вес груза:</label>
                    <input type="number" id="edit_cargo_weight" name="cargo_weight" value="${order.Weight || ''}" step="0.01">
                </div>
                <div class="form-group">
                    <label for="edit_weight_unit">Единица веса:</label>
                    <select id="edit_weight_unit" name="weight_unit">
                        <option value="тонн" ${order.Weight_unit == 'тонн' ? 'selected' : ''}>Тонн</option>
                        <option value="кг" ${order.Weight_unit == 'кг' ? 'selected' : ''}>Кг</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_cargo_volume">Объем груза (м³):</label>
                    <input type="number" id="edit_cargo_volume" name="cargo_volume" value="${order.Volume || ''}" step="0.01">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_cargo_quantity">Количество мест:</label>
                    <input type="number" id="edit_cargo_quantity" name="cargo_quantity" value="${order.Quantity || ''}" min="1">
                </div>
                <div class="form-group">
                    <label for="edit_length">Длина (м):</label>
                    <input type="number" id="edit_length" name="length" value="${order.Size ? order.Size.split(' x ')[0] : ''}" step="0.01">
                </div>
                <div class="form-group">
                    <label for="edit_width">Ширина (м):</label>
                    <input type="number" id="edit_width" name="width" value="${order.Size ? order.Size.split(' x ')[1] : ''}" step="0.01">
                </div>
                <div class="form-group">
                    <label for="edit_height">Высота (м):</label>
                    <input type="number" id="edit_height" name="height" value="${order.Size ? order.Size.split(' x ')[2] : ''}" step="0.01">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_loading_type">Тип погрузки:</label>
                    <select id="edit_loading_type" name="loading_type">
                        <option value="">Выберите тип погрузки...</option>
                        ${dropdownData.loading_types.map(type => 
                            `<option value="${type.id}" ${order.Loading_type == type.id ? 'selected' : ''}>
                                ${escapeHtml(type.loading_name)}
                            </option>`
                        ).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_packaging_type">Тип упаковки:</label>
                    <select id="edit_packaging_type" name="packaging_type">
                        <option value="">Выберите тип упаковки...</option>
                        ${dropdownData.packaging_types.map(type => 
                            `<option value="${type.id}" ${order.Packing_type == type.id ? 'selected' : ''}>
                                ${escapeHtml(type.packaging_name)}
                            </option>`
                        ).join('')}
                    </select>
                </div>
            </div>
        </div>

        <!-- Temperature Section -->
        <div class="form-section">
            <h4>Температурный режим</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_min_temperature">Мин. температура (°C):</label>
                    <input type="number" id="edit_min_temperature" name="min_temperature" value="${order.Min_temperature || ''}" step="0.1">
                </div>
                <div class="form-group">
                    <label for="edit_max_temperature">Макс. температура (°C):</label>
                    <input type="number" id="edit_max_temperature" name="max_temperature" value="${order.Max_temperature || ''}" step="0.1">
                </div>
                <div class="form-group">
                    <label for="edit_temp_print_list">Температурный лист:</label>
                    <select id="edit_temp_print_list" name="temp_print_list">
                        <option value="">Выберите...</option>
                        <option value="Да" ${order.Temperature_record == 'Да' ? 'selected' : ''}>Да</option>
                        <option value="Нет" ${order.Temperature_record == 'Нет' ? 'selected' : ''}>Нет</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Insurance & Cost Section -->
        <div class="form-section">
            <h4>Страхование и стоимость</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_cargo_price">Стоимость груза:</label>
                    <input type="number" id="edit_cargo_price" name="cargo_price" value="${order.Cargo_price || ''}" step="0.01">
                </div>
                <div class="form-group">
                    <label for="edit_currency">Валюта:</label>
                    <select id="edit_currency" name="currency_id">
                        <option value="">Выберите валюту...</option>
                        ${dropdownData.currencies.map(currency => 
                            `<option value="${currency.Currency_id}" ${order.Currency_id == currency.Currency_id ? 'selected' : ''}>
                                ${escapeHtml(currency.Currency_name)}
                            </option>`
                        ).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_rate">Коэффициент:</label>
                    <input type="number" id="edit_rate" name="rate" value="${order.Rate || ''}" step="0.01">
                </div>
                <div class="form-group">
                    <label for="edit_total_insurance">Страхование:</label>
                    <input type="number" id="edit_total_insurance" name="total_insurance" value="${order.Insurance_price || ''}" step="0.01">
                </div>
            </div>
        </div>

        <!-- Transport & Rates Section -->
        <div class="form-section">
            <h4>Стоимость перевозки и переработка</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_transport_rate">Ставка перевозки:</label>
                    <input type="number" id="edit_transport_rate" name="transport_rate" value="${order.Rate_2 || ''}" step="0.01">
                </div>
                <div class="form-group">
                    <label for="edit_transport_hours">Часы:</label>
                    <input type="number" id="edit_transport_hours" name="transport_hours" value="${order.Hours || ''}" min="1" step="1">
                </div>
                <div class="form-group">
                    <label for="edit_overwork_hours">Часы переработки:</label>
                    <input type="number" id="edit_overwork_hours" name="overwork_hours" value="${order.Extra_hours || ''}" min="0" step="1">
                </div>
                <div class="form-group">
                    <label for="edit_transport_total">Итого за перевозку:</label>
                    <input type="number" id="edit_transport_total" name="transport_total" value="${order.Total_price_vehicle || ''}" step="0.01">
                </div>
            </div>
        </div>
        
        <!-- Order Totals Section -->
        <div class="form-section">
            <h4>Итоговая стоимость</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_extra_services_total">Доп. услуги:</label>
                    <input type="number" id="edit_extra_services_total" name="extra_services_total" value="${order.Total_price_extra_service || ''}" step="0.01" readonly>
                </div>
                <div class="form-group">
                    <label for="edit_order_total">Общая сумма:</label>
                    <input type="number" id="edit_order_total" name="order_total" value="${order.Order_total || ''}" step="0.01">
                </div>
            </div>
        </div>

        <!-- Route Points Section -->
        <div class="form-section">
            <h4>Маршрут перевозки</h4>
            <div id="edit-route-points-container">
                ${generateRoutePointsEditHTML(routePoints)}
            </div>
            <div class="route-actions">
                <button type="button" class="btn-add-route" onclick="addRoutePoint()">
                    <i class="fas fa-plus"></i> Добавить точку маршрута
                </button>
            </div>
        </div>

        <!-- Notes Section -->
        <div class="form-section">
            <h4>Дополнительные примечания</h4>
            <div class="form-group">
                <label for="edit_order_notes">Примечания:</label>
                <textarea id="edit_order_notes" name="order_notes" rows="4">${escapeHtml(order.order_notes || '')}</textarea>
            </div>
        </div>
    `;
    
    formContent.innerHTML = formHTML;
    
    // Set up route point event listeners
    setupRoutePointEvents();
}

function generateRoutePointsEditHTML(routePoints) {
    if (!routePoints || routePoints.length === 0) {
        return '<div class="no-route-points">Маршрут не задан. Добавьте точки маршрута.</div>';
    }
    
    return routePoints.map((point, index) => `
        <div class="edit-route-point" data-point-index="${index}" data-point-id="${point.Point_id || ''}">
            <div class="route-point-header">
                <h5>Точка ${index + 1}</h5>
                <button type="button" class="btn-remove-point" onclick="removeRoutePoint(${index})" title="Удалить точку">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="route-point-fields">
                <div class="form-row">
                    <div class="form-group">
                        <label for="route_action_${index}">Тип действия:</label>
                        <select id="route_action_${index}" name="route_points[${index}][action_type]" required>
                            <option value="">Выберите действие...</option>
                            <option value="Погрузка" ${point.Action_type === 'Погрузка' ? 'selected' : ''}>Погрузка</option>
                            <option value="Выгрузка" ${point.Action_type === 'Выгрузка' ? 'selected' : ''}>Выгрузка</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="route_company_${index}">Компания:</label>
                        <input type="text" id="route_company_${index}" name="route_points[${index}][company_name]" 
                               value="${escapeHtml(point.Company_name || '')}" placeholder="Название компании">
                    </div>
                    <div class="form-group">
                        <label for="route_date_${index}" class="required">Дата:</label>
                        <input type="date" id="route_date_${index}" name="route_points[${index}][date]" 
                               value="${point.Date || ''}" required>
                    </div>
                    <div class="form-group">
                        <label for="route_time_${index}">Время:</label>
                        <input type="time" id="route_time_${index}" name="route_points[${index}][time]" 
                               value="${point.Time || ''}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="route_address_${index}" class="required">Адрес:</label>
                        <textarea id="route_address_${index}" name="route_points[${index}][address]" 
                                  rows="2" placeholder="Полный адрес точки маршрута" required>${escapeHtml(point.Address || '')}</textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="route_contact_name_${index}">Контактное лицо:</label>
                        <input type="text" id="route_contact_name_${index}" name="route_points[${index}][contact_name]" 
                               value="${point.contact_list && point.contact_list[0] ? escapeHtml(point.contact_list[0].name) : ''}" 
                               placeholder="Имя контактного лица">
                    </div>
                    <div class="form-group">
                        <label for="route_contact_phone_${index}">Телефон контакта:</label>
                        <input type="tel" id="route_contact_phone_${index}" name="route_points[${index}][contact_phone]" 
                               value="${point.contact_list && point.contact_list[0] ? escapeHtml(point.contact_list[0].phone) : ''}" 
                               placeholder="+7 (XXX) XXX-XX-XX">
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function setupRoutePointEvents() {
    // Add event listeners for dynamic route point management
    window.addRoutePoint = function() {
        const container = document.getElementById('edit-route-points-container');
        const existingPoints = container.querySelectorAll('.edit-route-point');
        const newIndex = existingPoints.length;
        
        const newPointHTML = `
            <div class="edit-route-point" data-point-index="${newIndex}" data-point-id="">
                <div class="route-point-header">
                    <h5>Точка ${newIndex + 1}</h5>
                    <button type="button" class="btn-remove-point" onclick="removeRoutePoint(${newIndex})" title="Удалить точку">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="route-point-fields">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="route_action_${newIndex}">Тип действия:</label>
                            <select id="route_action_${newIndex}" name="route_points[${newIndex}][action_type]" required>
                                <option value="">Выберите действие...</option>
                                <option value="Погрузка">Погрузка</option>
                                <option value="Выгрузка">Выгрузка</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="route_company_${newIndex}">Компания:</label>
                            <input type="text" id="route_company_${newIndex}" name="route_points[${newIndex}][company_name]" 
                                   placeholder="Название компании">
                        </div>
                        <div class="form-group">
                            <label for="route_date_${newIndex}" class="required">Дата:</label>
                            <input type="date" id="route_date_${newIndex}" name="route_points[${newIndex}][date]" required>
                        </div>
                        <div class="form-group">
                            <label for="route_time_${newIndex}">Время:</label>
                            <input type="time" id="route_time_${newIndex}" name="route_points[${newIndex}][time]">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="route_address_${newIndex}" class="required">Адрес:</label>
                            <textarea id="route_address_${newIndex}" name="route_points[${newIndex}][address]" 
                                      rows="2" placeholder="Полный адрес точки маршрута" required></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="route_contact_name_${newIndex}">Контактное лицо:</label>
                            <input type="text" id="route_contact_name_${newIndex}" name="route_points[${newIndex}][contact_name]" 
                                   placeholder="Имя контактного лица">
                        </div>
                        <div class="form-group">
                            <label for="route_contact_phone_${newIndex}">Телефон контакта:</label>
                            <input type="tel" id="route_contact_phone_${newIndex}" name="route_points[${newIndex}][contact_phone]" 
                                   placeholder="+7 (XXX) XXX-XX-XX">
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        if (container.querySelector('.no-route-points')) {
            container.innerHTML = newPointHTML;
        } else {
            container.insertAdjacentHTML('beforeend', newPointHTML);
        }
        
        // Update point numbers
        updateRoutePointNumbers();
    };
    
    window.removeRoutePoint = function(index) {
        const container = document.getElementById('edit-route-points-container');
        const pointElement = container.querySelector(`[data-point-index="${index}"]`);
        
        if (pointElement) {
            pointElement.remove();
            
            // Check if any points remain
            const remainingPoints = container.querySelectorAll('.edit-route-point');
            if (remainingPoints.length === 0) {
                container.innerHTML = '<div class="no-route-points">Маршрут не задан. Добавьте точки маршрута.</div>';
            } else {
                updateRoutePointNumbers();
            }
        }
    };
}

function updateRoutePointNumbers() {
    const container = document.getElementById('edit-route-points-container');
    const points = container.querySelectorAll('.edit-route-point');
    
    points.forEach((point, index) => {
        // Update visual number
        const header = point.querySelector('h5');
        if (header) {
            header.textContent = `Точка ${index + 1}`;
        }
        
        // Update data attributes and form field names
        point.setAttribute('data-point-index', index);
        
        // Update all form field names and IDs
        const fields = point.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            const name = field.getAttribute('name');
            const id = field.getAttribute('id');
            
            if (name) {
                field.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
            }
            if (id) {
                field.setAttribute('id', id.replace(/_\d+$/, `_${index}`));
            }
        });
        
        // Update labels for attributes
        const labels = point.querySelectorAll('label');
        labels.forEach(label => {
            const forAttr = label.getAttribute('for');
            if (forAttr) {
                label.setAttribute('for', forAttr.replace(/_\d+$/, `_${index}`));
            }
        });
        
        // Update remove button onclick
        const removeBtn = point.querySelector('.btn-remove-point');
        if (removeBtn) {
            removeBtn.setAttribute('onclick', `removeRoutePoint(${index})`);
        }
    });
}

function setupEditCalculations() {
    function calculateInsurance() {
        const cargoPrice = parseFloat(document.getElementById('edit_cargo_price')?.value || 0);
        const rate = parseFloat(document.getElementById('edit_rate')?.value || 0);
        const insurance = (cargoPrice * rate) / 100;
        
        const insuranceField = document.getElementById('edit_total_insurance');
        if (insuranceField) {
            insuranceField.value = insurance.toFixed(2);
        }
        
        updateGrandTotal();
    }
    
    function calculateTransportTotal() {
        const transportRate = parseFloat(document.getElementById('edit_transport_rate')?.value || 0);
        const hours = parseFloat(document.getElementById('edit_transport_hours')?.value || 0);
        const overworkHours = parseFloat(document.getElementById('edit_overwork_hours')?.value || 0);
        const total = transportRate * (hours + overworkHours);
        
        const totalField = document.getElementById('edit_transport_total');
        if (totalField) {
            totalField.value = total.toFixed(2);
        }
        
        updateGrandTotal();
    }
    
    function updateGrandTotal() {
        const insurance = parseFloat(document.getElementById('edit_total_insurance')?.value || 0);
        const transport = parseFloat(document.getElementById('edit_transport_total')?.value || 0);
        const extraServices = parseFloat(document.getElementById('edit_extra_services_total')?.value || 0);
        const grandTotal = insurance + transport + extraServices;
        
        const totalField = document.getElementById('edit_order_total');
        if (totalField) {
            totalField.value = grandTotal.toFixed(2);
        }
    }
    
    // Setup event listeners for calculations
    const priceField = document.getElementById('edit_cargo_price');
    const rateField = document.getElementById('edit_rate');
    const transportRateField = document.getElementById('edit_transport_rate');
    const hoursField = document.getElementById('edit_transport_hours');
    const overworkField = document.getElementById('edit_overwork_hours');
    
    if (priceField) priceField.addEventListener('input', calculateInsurance);
    if (rateField) rateField.addEventListener('input', calculateInsurance);
    if (transportRateField) transportRateField.addEventListener('input', calculateTransportTotal);
    if (hoursField) hoursField.addEventListener('input', calculateTransportTotal);
    if (overworkField) overworkField.addEventListener('input', calculateTransportTotal);
    
    // Setup form submission
    const editForm = document.getElementById('editOrderForm');
    if (editForm) {
        editForm.addEventListener('submit', submitOrderEdit);
    }
}

function submitOrderEdit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const orderId = document.getElementById('editOrderId').value;
    
    // Add order ID to form data
    formData.append('order_id', orderId);
    
    // Convert FormData to JSON for the order data (excluding route points)
    const orderData = {};
    for (let [key, value] of formData.entries()) {
        // Skip route points data - we'll handle it separately
        if (!key.startsWith('route_points[')) {
            orderData[key] = value;
        }
    }
    
    // Collect route points data separately
    const routePoints = collectRoutePointsData();
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<div class="loading-spinner"></div>Сохранение...';
    submitBtn.disabled = true;
    
    // Submit the order data first
    fetch('../assets/save_verified_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // If order data was saved successfully, save route points
            return saveRoutePoints(orderId, routePoints);
        } else {
            throw new Error(data.error || 'Failed to save order');
        }
    })
    .then(routeResponse => {
        if (routeResponse.success) {
            alert('Заказ и маршрут успешно обновлены!');
            closeEditModal();
            // Reload the page to show updated data
            window.location.reload();
        } else {
            alert('Заказ сохранен, но произошла ошибка при сохранении маршрута: ' + routeResponse.message);
        }
    })
    .catch(error => {
        console.error('Error saving order:', error);
        alert('Ошибка сохранения: ' + error.message);
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function collectRoutePointsData() {
    const routePoints = [];
    const container = document.getElementById('edit-route-points-container');
    const pointElements = container.querySelectorAll('.edit-route-point');
    
    pointElements.forEach((pointElement, index) => {
        const actionType = pointElement.querySelector(`[name="route_points[${index}][action_type]"]`)?.value;
        const companyName = pointElement.querySelector(`[name="route_points[${index}][company_name]"]`)?.value;
        const date = pointElement.querySelector(`[name="route_points[${index}][date]"]`)?.value;
        const time = pointElement.querySelector(`[name="route_points[${index}][time]"]`)?.value;
        const address = pointElement.querySelector(`[name="route_points[${index}][address]"]`)?.value;
        const contactName = pointElement.querySelector(`[name="route_points[${index}][contact_name]"]`)?.value;
        const contactPhone = pointElement.querySelector(`[name="route_points[${index}][contact_phone]"]`)?.value;
        
        if (actionType && date && address) {
            routePoints.push({
                action_type: actionType,
                company_name: companyName,
                date: date,
                time: time,
                address: address,
                contact_name: contactName,
                contact_phone: contactPhone
            });
        }
    });
    
    return routePoints;
}

function saveRoutePoints(orderId, routePoints) {
    return fetch('../assets/save_order_route.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            route_points: routePoints
        })
    })
    .then(response => response.json());
}

function changeOrderStatus() {
    // Load status timeline and show timeline modal
    fetch(`../assets/get_order_status_timeline.php?order_id=${window.orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStatusTimelineModal(data);
            } else {
                alert('Ошибка загрузки временной шкалы статусов: ' + (data.error || 'Неизвестная ошибка'));
            }
        })
        .catch(error => {
            console.error('Error loading status timeline:', error);
            alert('Ошибка загрузки временной шкалы статусов');
        });
}

function showStatusTimelineModal(data) {
    // Create status timeline modal
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    
    modal.innerHTML = `
        <div class="modal-content timeline-modal">
            <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
            <h3>Прогресс статуса заказа #${escapeHtml(data.order_number)}</h3>
            
            <div class="status-timeline-container">
                ${generateStatusTimelineHTML(data.timeline)}
            </div>
            
            <div class="status-change-form" style="display: none;">
                <div class="form-section">
                    <h4>Изменение статуса</h4>
                    <div class="form-group">
                        <label for="timeline-status-reason">Причина изменения (необязательно):</label>
                        <textarea id="timeline-status-reason" rows="3" placeholder="Укажите причину изменения статуса..."></textarea>
                    </div>
                    <div class="timeline-actions">
                        <button type="button" class="btn-secondary" id="cancel-timeline-change">Отмена</button>
                        <button type="button" class="btn-primary" id="confirm-timeline-change">Подтвердить изменение</button>
                    </div>
                </div>
            </div>
            
            <div class="modal-buttons">
                <button type="button" class="btn-secondary" onclick="this.closest('.modal').remove()">
                    Закрыть
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Setup timeline handlers
    setupTimelineHandlers();
}

function generateStatusTimelineHTML(timeline) {
    let timelineHTML = '<div class="status-timeline">';
    
    timeline.forEach((status, index) => {
        const isLast = index === timeline.length - 1;
        
        // Determine status classes
        let itemClass = 'timeline-item';
        if (status.is_completed) {
            itemClass += status.is_current ? ' current' : ' completed';
        } else if (status.is_next_available) {
            itemClass += ' next-available';
        } else {
            itemClass += ' pending';
        }
        
        timelineHTML += `
            <div class="${itemClass}" ${status.is_next_available ? `data-status-id="${status.status_id}"` : ''}>
                <div class="timeline-marker" style="background-color: ${status.is_completed ? status.status_color : '#e0e0e0'};">
                    ${status.is_completed ? '<i class="fas fa-check"></i>' : ''}
                    ${status.is_current && status.is_completed ? '<i class="fas fa-circle"></i>' : ''}
                    ${status.is_next_available ? '<i class="fas fa-play"></i>' : ''}
                </div>
                
                <div class="timeline-content">
                    <div class="timeline-header">
                        <h4 class="status-name">${escapeHtml(status.status_name)}</h4>
                        ${status.is_next_available ? `
                            <button class="btn-action btn-sm change-to-status" data-status-id="${status.status_id}" data-status-name="${escapeHtml(status.status_name)}">
                                <i class="fas fa-arrow-right"></i> Перейти
                            </button>
                        ` : ''}
                    </div>
                    
                    ${status.history ? `
                        <div class="status-history">
                            <div class="history-user">
                                <i class="fas fa-user"></i> ${escapeHtml(status.history.changed_by_name || status.history.changed_by_login)}
                            </div>
                            <div class="history-date">
                                <i class="fas fa-clock"></i> ${status.history.change_date}
                            </div>
                            ${status.history.change_reason ? `
                                <div class="history-reason">
                                    <i class="fas fa-comment"></i> ${escapeHtml(status.history.change_reason)}
                                </div>
                            ` : ''}
                        </div>
                    ` : status.is_completed ? `
                        <div class="status-history">
                            <div class="history-placeholder">
                                <i class="fas fa-info-circle"></i> Информация о изменении недоступна
                            </div>
                        </div>
                    ` : ''}
                </div>
                
                ${!isLast ? '<div class="timeline-connector"></div>' : ''}
            </div>
        `;
    });
    
    timelineHTML += '</div>';
    return timelineHTML;
}

let selectedTimelineStatusId = null;
let selectedTimelineStatusName = null;

function setupTimelineHandlers() {
    // Handle status change button clicks
    document.querySelectorAll('.change-to-status').forEach(button => {
        button.addEventListener('click', function() {
            selectedTimelineStatusId = this.dataset.statusId;
            selectedTimelineStatusName = this.dataset.statusName;
            
            // Show status change form
            document.querySelector('.status-change-form').style.display = 'block';
            
            // Update form title
            document.querySelector('.status-change-form h4').textContent = `Перейти к статусу: ${selectedTimelineStatusName}`;
            
            // Scroll to form
            document.querySelector('.status-change-form').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });
    
    // Handle cancel button
    document.getElementById('cancel-timeline-change').addEventListener('click', function() {
        selectedTimelineStatusId = null;
        selectedTimelineStatusName = null;
        document.querySelector('.status-change-form').style.display = 'none';
        document.getElementById('timeline-status-reason').value = '';
    });
    
    // Handle confirm button
    document.getElementById('confirm-timeline-change').addEventListener('click', function() {
        if (!selectedTimelineStatusId) return;
        
        const reason = document.getElementById('timeline-status-reason').value.trim();
        
        // Show loading state
        this.innerHTML = '<div class="loading-spinner"></div>Изменение...';
        this.disabled = true;
        
        // Submit status change
        fetch('../assets/update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: window.orderId,
                new_status_id: parseInt(selectedTimelineStatusId),
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Статус успешно изменен с "${data.previous_status}" на "${data.new_status}"`);
                // Close modal and reload page
                document.querySelector('.modal').remove();
                window.location.reload();
            } else {
                alert('Ошибка изменения статуса: ' + (data.error || 'Неизвестная ошибка'));
                // Restore button
                this.innerHTML = 'Подтвердить изменение';
                this.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            alert('Ошибка изменения статуса');
            // Restore button
            this.innerHTML = 'Подтвердить изменение';
            this.disabled = false;
        });
    });
}

function viewOrderHistory() {
    // Open order history modal
    fetch(`../assets/get_order_status_history.php?order_id=${window.orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showHistoryModal(data);
            } else {
                alert('Ошибка загрузки истории заказа');
            }
        })
        .catch(error => {
            console.error('Error loading order history:', error);
            alert('Ошибка загрузки истории заказа');
        });
}

function downloadOrderPDF() {
    // Create download link for PDF
    const link = document.createElement('a');
    link.href = `../assets/download_order_pdf.php?order_id=${window.orderId}`;
    link.download = `Order_${window.orderId}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function downloadOrderWord() {
    // First generate Word document if it doesn't exist, then download
    fetch(`../assets/generate_order_word_on_demand.php?order_id=${window.orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Now download the Word document
                const link = document.createElement('a');
                link.href = `../assets/download_order_word.php?order_id=${window.orderId}`;
                link.download = `Order_${window.orderId}.docx`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('Ошибка создания Word документа: ' + (data.error || 'Неизвестная ошибка'));
            }
        })
        .catch(error => {
            console.error('Error generating Word document:', error);
            alert('Ошибка создания Word документа');
        });
}

// Show history modal
function showHistoryModal(data) {
    // Create and show modal with order history
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    
    modal.innerHTML = `
        <div class="modal-content large-modal">
            <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
            <h3>История изменения статусов</h3>
            
            <div class="form-section">
                <h4>Информация о заказе</h4>
                <div class="order-summary">
                    <div><strong>Номер заказа:</strong> ${escapeHtml(data.order_info.display_order_number)}</div>
                    <div><strong>Дата заказа:</strong> ${formatDate(data.order_info.Order_date)}</div>
                    <div><strong>Клиент:</strong> ${escapeHtml(data.order_info.client_name)}</div>
                    <div><strong>Текущий статус:</strong> ${escapeHtml(data.order_info.current_status)}</div>
                </div>
            </div>

            <div class="form-section">
                <h4>История изменений</h4>
                <div class="status-history-timeline">
                    ${generateHistoryHTML(data.history)}
                </div>
            </div>

            <div class="modal-buttons">
                <button type="button" class="btn-secondary" onclick="this.closest('.modal').remove()">
                    Закрыть
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Generate history HTML
function generateHistoryHTML(history) {
    if (!history || history.length === 0) {
        return '<div class="no-history">История изменений пуста</div>';
    }
    
    return history.map((record, index) => `
        <div class="history-item ${index === 0 ? 'latest' : ''}">
            <div class="history-date">${record.Change_date}</div>
            <div class="history-content">
                <div class="status-change">
                    <span class="status-badge initial" style="background-color: ${record.previous_status_color || '#6c757d'}">
                        ${escapeHtml(record.previous_status_name || 'Начальный')}
                    </span>
                    <span class="change-arrow">→</span>
                    <span class="status-badge" style="background-color: ${record.new_status_color}">
                        ${escapeHtml(record.new_status_name)}
                    </span>
                </div>
                <div class="history-user">
                    Изменено: ${escapeHtml(record.changed_by_name)} (${escapeHtml(record.changed_by_login)})
                </div>
                ${record.Change_reason ? `<div class="history-reason">${escapeHtml(record.Change_reason)}</div>` : ''}
            </div>
        </div>
    `).join('');
}

// Event Listeners
function setupEventListeners() {
    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.remove();
        }
    });
}

// Utility Functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU');
}

function formatCurrency(amount, currency) {
    if (!amount || amount === '0.00') return '—';
    return `${parseFloat(amount).toLocaleString('ru-RU')} ${currency || ''}`;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Download file function
window.downloadFile = function(filePath, originalName) {
    const link = document.createElement('a');
    link.href = `../assets/download_file.php?file=${encodeURIComponent(filePath)}&name=${encodeURIComponent(originalName)}`;
    link.download = originalName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Courier Assignment Functions
function showAddCourierModal() {
    const modal = document.getElementById('addCourierModal');
    modal.style.display = 'block';
    loadCouriersForAssignment();
}

function closeAddCourierModal() {
    const modal = document.getElementById('addCourierModal');
    modal.style.display = 'none';
    resetCourierForm();
}

function resetCourierForm() {
    const form = document.getElementById('addCourierForm');
    form.reset();
    
    // Reset dropdowns and fields
    document.getElementById('courierSelect').innerHTML = '<option value="">Выберите перевозчика...</option>';
    document.getElementById('driverSelect').innerHTML = '<option value="">Сначала выберите перевозчика</option>';
    document.getElementById('vehicleSelect').innerHTML = '<option value="">Сначала выберите перевозчика</option>';
    
    // Disable dependent fields
    document.getElementById('driverSelect').disabled = true;
    document.getElementById('vehicleSelect').disabled = true;
    
    // Clear auto-filled fields
    document.getElementById('driverPhone').value = '';
    document.getElementById('driverPassport').value = '';
}

function loadCouriersForAssignment() {
    const courierSelect = document.getElementById('courierSelect');
    
    courierSelect.innerHTML = '<option value="">Загрузка...</option>';
    
    fetch('../assets/get_couriers_for_assignment.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                courierSelect.innerHTML = '<option value="">Выберите перевозчика...</option>';
                
                data.couriers.forEach(courier => {
                    const option = document.createElement('option');
                    option.value = courier.Courier_id;
                    option.textContent = courier.Full_Company_name;
                    option.dataset.contact = courier.Contact_person || '';
                    option.dataset.phone = courier.Phone_number || '';
                    courierSelect.appendChild(option);
                });
                
                // Setup courier change handler
                courierSelect.addEventListener('change', handleCourierChange);
            } else {
                courierSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                showNotification('Ошибка загрузки перевозчиков: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading couriers:', error);
            courierSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
            showNotification('Ошибка загрузки перевозчиков', 'error');
        });
}

function handleCourierChange() {
    const courierId = this.value;
    const driverSelect = document.getElementById('driverSelect');
    const vehicleSelect = document.getElementById('vehicleSelect');
    
    if (!courierId) {
        driverSelect.innerHTML = '<option value="">Сначала выберите перевозчика</option>';
        vehicleSelect.innerHTML = '<option value="">Сначала выберите перевозчика</option>';
        driverSelect.disabled = true;
        vehicleSelect.disabled = true;
        
        // Clear auto-filled fields
        document.getElementById('driverPhone').value = '';
        document.getElementById('driverPassport').value = '';
        return;
    }
    
    // Show loading state
    driverSelect.innerHTML = '<option value="">Загрузка водителей...</option>';
    vehicleSelect.innerHTML = '<option value="">Загрузка транспорта...</option>';
    driverSelect.disabled = true;
    vehicleSelect.disabled = true;
    
    // Load drivers and vehicles for the selected courier
    fetch(`../assets/get_courier_drivers_vehicles.php?courier_id=${courierId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate drivers
                driverSelect.innerHTML = '<option value="">Выберите водителя (необязательно)</option>';
                data.drivers.forEach(driver => {
                    const option = document.createElement('option');
                    option.value = driver.Driver_id;
                    option.textContent = driver.driver_name;
                    option.dataset.phone = driver.driver_phone || '';
                    option.dataset.passport = driver.driver_passport || '';
                    driverSelect.appendChild(option);
                });
                driverSelect.disabled = false;
                
                // Populate vehicles
                vehicleSelect.innerHTML = '<option value="">Выберите транспорт (необязательно)</option>';
                data.vehicles.forEach(vehicle => {
                    const option = document.createElement('option');
                    option.value = vehicle.Vehicle_id;
                    option.textContent = vehicle.vehicle_display;
                    vehicleSelect.appendChild(option);
                });
                vehicleSelect.disabled = false;
                
                // Setup change handlers
                driverSelect.addEventListener('change', handleDriverChange);
            } else {
                driverSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                vehicleSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                showNotification('Ошибка загрузки ресурсов перевозчика: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading courier resources:', error);
            driverSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
            vehicleSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
            showNotification('Ошибка загрузки ресурсов перевозчика', 'error');
        });
}

function handleDriverChange() {
    const selectedOption = this.options[this.selectedIndex];
    const phoneField = document.getElementById('driverPhone');
    const passportField = document.getElementById('driverPassport');
    
    if (selectedOption.value) {
        phoneField.value = selectedOption.dataset.phone || '';
        passportField.value = selectedOption.dataset.passport || '';
    } else {
        phoneField.value = '';
        passportField.value = '';
    }
}



function submitCourierAssignment(e) {
    e.preventDefault();
    
    const form = document.getElementById('addCourierForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Назначение...';
    
    // Collect form data
    const formData = new FormData(form);
    const assignmentData = {
        order_id: parseInt(formData.get('order_id')),
        courier_id: parseInt(formData.get('courier_id')),
        driver_id: formData.get('driver_id') ? parseInt(formData.get('driver_id')) : null,
        vehicle_id: formData.get('vehicle_id') ? parseInt(formData.get('vehicle_id')) : null
    };
    
    // Validate courier selection
    if (!assignmentData.courier_id) {
        showNotification('Пожалуйста, выберите перевозчика', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        return;
    }
    
    // Submit assignment
    fetch('../assets/assign_courier_to_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(assignmentData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`Перевозчик "${data.courier_name}" успешно назначен на заказ ${data.order_number}`, 'success');
            closeAddCourierModal();
            // Reload the page to show updated courier information
            window.location.reload();
        } else {
            showNotification('Ошибка назначения перевозчика: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error assigning courier:', error);
        showNotification('Ошибка назначения перевозчика', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Setup courier form submission
document.addEventListener('DOMContentLoaded', function() {
    const courierForm = document.getElementById('addCourierForm');
    if (courierForm) {
        courierForm.addEventListener('submit', submitCourierAssignment);
    }
});

// Old showNotification function removed - using enhanced version below

// Send Form to Courier Functions
function showSendFormModal() {
    const modal = document.getElementById('sendFormModal');
    modal.style.display = 'block';
    loadCouriersForSendForm();
}

function closeSendFormModal() {
    const modal = document.getElementById('sendFormModal');
    modal.style.display = 'none';
    resetSendFormModal();
}

function resetSendFormModal() {
    const form = document.getElementById('sendFormForm');
    form.reset();
    
    document.getElementById('sendFormCourierSelect').innerHTML = '<option value="">Выберите перевозчика...</option>';
    document.getElementById('linkGenerationResult').style.display = 'none';
    document.getElementById('generateLinkBtn').style.display = 'inline-flex';
    document.getElementById('generatedLink').value = '';
    document.getElementById('courierEmail').value = '';
}

function loadCouriersForSendForm() {
    const courierSelect = document.getElementById('sendFormCourierSelect');
    
    courierSelect.innerHTML = '<option value="">Загрузка...</option>';
    
    fetch('../assets/get_couriers_for_assignment.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                courierSelect.innerHTML = '<option value="">Выберите перевозчика...</option>';
                
                data.couriers.forEach(courier => {
                    const option = document.createElement('option');
                    option.value = courier.Courier_id;
                    option.textContent = courier.Full_Company_name;
                    option.dataset.phone = courier.Phone_number || '';
                    courierSelect.appendChild(option);
                });
            } else {
                courierSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                showNotification('Ошибка загрузки перевозчиков: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading couriers for send form:', error);
            courierSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
            showNotification('Ошибка загрузки перевозчиков', 'error');
        });
}

function generateCourierLink(e) {
    e.preventDefault();
    
    const form = document.getElementById('sendFormForm');
    const submitBtn = document.getElementById('generateLinkBtn');
    const courierSelect = document.getElementById('sendFormCourierSelect');
    const originalText = submitBtn.innerHTML;
    
    const courierId = courierSelect.value;
    if (!courierId) {
        showNotification('Пожалуйста, выберите перевозчика', 'error');
        return;
    }
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Генерация ссылки...';
    
    // Get courier email from the API response (will be set later)
    const selectedOption = courierSelect.options[courierSelect.selectedIndex];
    const courierPhone = selectedOption.dataset.phone || '';
    
    const requestData = {
        order_id: window.orderId,
        courier_id: parseInt(courierId)
    };
    
    fetch('../assets/generate_courier_link.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show the generated link section
            document.getElementById('linkGenerationResult').style.display = 'block';
            document.getElementById('generatedLink').value = data.link_url;
            document.getElementById('courierEmail').value = data.courier_email || '';
            
            // Update expiry display
            const expiryDate = new Date(data.expires_at);
            document.getElementById('linkExpiry').textContent = expiryDate.toLocaleString('ru-RU');
            
            // Hide generate button, show result
            submitBtn.style.display = 'none';
            
            // Set up copy and email handlers
            setupLinkHandlers(data.link_token, data.link_url);
            
            showNotification(
                data.is_existing ? 
                'Использована существующая ссылка для этого перевозчика' : 
                'Ссылка успешно сгенерирована', 
                'success'
            );
        } else {
            showNotification('Ошибка генерации ссылки: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error generating courier link:', error);
        showNotification('Ошибка генерации ссылки', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function setupLinkHandlers(linkToken, linkUrl) {
    // Copy link handler
    document.getElementById('copyLinkBtn').onclick = function() {
        const linkInput = document.getElementById('generatedLink');
        linkInput.select();
        linkInput.setSelectionRange(0, 99999); // For mobile devices
        
        try {
            document.execCommand('copy');
            showNotification('Ссылка скопирована в буфер обмена', 'success');
            
            // Visual feedback
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Скопировано';
            btn.style.background = '#28a745';
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '';
            }, 2000);
        } catch (err) {
            showNotification('Не удалось скопировать ссылку', 'error');
            console.error('Copy failed:', err);
        }
    };
    
    // Send email handler
    document.getElementById('sendEmailBtn').onclick = function() {
        const courierEmail = document.getElementById('courierEmail').value;
        
        if (!courierEmail) {
            showNotification('Email перевозчика не указан', 'error');
            return;
        }
        
        const btn = this;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
        
        fetch('../assets/send_courier_link_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                link_token: linkToken,
                courier_email: courierEmail
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Email успешно отправлен на ' + courierEmail, 'success');
                
                // Visual feedback
                btn.innerHTML = '<i class="fas fa-check"></i> Отправлено';
                btn.style.background = '#28a745';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = '';
                    btn.disabled = false;
                }, 3000);
            } else {
                showNotification('Ошибка отправки email: ' + data.error, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error sending email:', error);
            showNotification('Ошибка отправки email', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };
}

// Setup send form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const sendFormForm = document.getElementById('sendFormForm');
    if (sendFormForm) {
        sendFormForm.addEventListener('submit', generateCourierLink);
    }
});

// Make functions available globally
window.closeEditModal = closeEditModal;
window.showAddCourierModal = showAddCourierModal;
window.closeAddCourierModal = closeAddCourierModal;
window.showSendFormModal = showSendFormModal;
window.closeSendFormModal = closeSendFormModal;

// Document generation functions
function generateClientConductorDocument(action = 'view') {
    const orderId = window.orderId;
    
    if (!orderId) {
        showNotification('Ошибка: ID заказа не найден', 'error');
        return;
    }
    
    if (action === 'view') {
        showDocumentPreview('client_conductor', orderId);
    } else if (action === 'download') {
        downloadDocument('client_conductor', orderId, 'pdf');
    }
}

function generateConductorCourierDocument(action = 'view') {
    const orderId = window.orderId;
    
    if (!orderId) {
        showNotification('Ошибка: ID заказа не найден', 'error');
        return;
    }
    
    if (action === 'view') {
        showDocumentPreview('conductor_courier', orderId);
    } else if (action === 'download') {
        downloadDocument('conductor_courier', orderId, 'pdf');
    }
}

function showDocumentPreview(documentType, orderId) {
    const modal = document.getElementById('documentPreviewModal');
    const title = document.getElementById('documentPreviewTitle');
    const loading = document.getElementById('documentPreviewLoading');
    const frame = document.getElementById('documentPreviewFrame');
    const downloadPdfBtn = document.getElementById('downloadPdfBtn');
    const downloadWordBtn = document.getElementById('downloadWordBtn');
    
    // Set title based on document type
    const documentTitles = {
        'client_conductor': 'Договор Клиент-Экспедитор',
        'conductor_courier': 'Договор Экспедитор-Перевозчик'
    };
    
    const icons = {
        'client_conductor': 'fas fa-file-contract',
        'conductor_courier': 'fas fa-file-invoice'
    };
    
    title.innerHTML = `<i class="${icons[documentType]}"></i> ${documentTitles[documentType]}`;
    
    // Set up download buttons
    const baseUrl = documentType === 'client_conductor' 
        ? '../assets/generate_client_conductor_document.php'
        : '../assets/generate_conductor_courier_document.php';
    
    downloadPdfBtn.href = `${baseUrl}?order_id=${orderId}&action=download&format=pdf`;
    downloadWordBtn.href = `${baseUrl}?order_id=${orderId}&action=download&format=word`;
    
    // Set up download button click handlers
    downloadPdfBtn.onclick = function(e) {
        e.preventDefault();
        
        if (documentType === 'conductor_courier') {
            // Check if iframe contains contract selection page
            try {
                const frameDoc = frame.contentDocument || frame.contentWindow.document;
                const contractSelection = frameDoc.querySelector('.contract-item.selected');
                
                let contractParam = '';
                if (contractSelection) {
                    contractParam = '&contract_id=' + contractSelection.dataset.contractId;
                }
                
                const downloadUrl = `${baseUrl}?order_id=${orderId}&action=download&format=pdf${contractParam}`;
                downloadDocument(documentType, orderId, 'pdf', downloadUrl);
            } catch (e) {
                // If can't access iframe content (different origin or not loaded), use default
                downloadDocument(documentType, orderId, 'pdf');
            }
        } else {
            downloadDocument(documentType, orderId, 'pdf');
        }
    };
    
    downloadWordBtn.onclick = function(e) {
        e.preventDefault();
        
        if (documentType === 'conductor_courier') {
            // Check if iframe contains contract selection page
            try {
                const frameDoc = frame.contentDocument || frame.contentWindow.document;
                const contractSelection = frameDoc.querySelector('.contract-item.selected');
                
                let contractParam = '';
                if (contractSelection) {
                    contractParam = '&contract_id=' + contractSelection.dataset.contractId;
                }
                
                const downloadUrl = `${baseUrl}?order_id=${orderId}&action=download&format=word${contractParam}`;
                downloadDocument(documentType, orderId, 'word', downloadUrl);
            } catch (e) {
                // If can't access iframe content (different origin or not loaded), use default
                downloadDocument(documentType, orderId, 'word');
            }
        } else {
            downloadDocument(documentType, orderId, 'word');
        }
    };
    
    // Show modal and loading
    modal.style.display = 'block';
    loading.style.display = 'flex';
    frame.style.display = 'none';
    
    // Load document in iframe
    const viewUrl = `${baseUrl}?order_id=${orderId}&action=view`;
    frame.src = viewUrl;
    
    // Handle iframe load
    frame.onload = function() {
        loading.style.display = 'none';
        frame.style.display = 'block';
    };
    
    // Handle iframe error
    frame.onerror = function() {
        loading.innerHTML = `
            <i class="fas fa-exclamation-triangle" style="color: #e74c3c; font-size: 24px;"></i>
            <div class="document-preview-loading-text" style="color: #e74c3c;">
                Ошибка загрузки документа
            </div>
        `;
    };
}

function downloadDocument(documentType, orderId, format, downloadUrl) {
    // Show loading notification that auto-closes
    const loadingNotification = showNotification('Подготовка документа к скачиванию...', 'info', 2000);
    
    // Use provided URL or construct default URL
    let finalDownloadUrl;
    if (downloadUrl) {
        finalDownloadUrl = downloadUrl;
    } else {
        const baseUrl = documentType === 'client_conductor' 
            ? '../assets/generate_client_conductor_document.php'
            : '../assets/generate_conductor_courier_document.php';
        finalDownloadUrl = `${baseUrl}?order_id=${orderId}&action=download&format=${format}`;
    }
    
    if (format === 'pdf') {
        // For PDF, open in new window to trigger browser's print dialog
        const pdfWindow = window.open(finalDownloadUrl, '_blank');
        showNotification('Документ открыт в новом окне для печати в PDF', 'success', 3000);
    } else {
        // For Word, direct download
        const link = document.createElement('a');
        link.href = finalDownloadUrl;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Show success notification after a short delay
        setTimeout(() => {
            showNotification('Документ готов к скачиванию', 'success', 3000);
        }, 1000);
    }
}

function closeDocumentPreview() {
    const modal = document.getElementById('documentPreviewModal');
    const frame = document.getElementById('documentPreviewFrame');
    const loading = document.getElementById('documentPreviewLoading');
    
    modal.style.display = 'none';
    frame.src = '';
    loading.style.display = 'flex';
    frame.style.display = 'none';
}

// Enhanced notification function with auto-close
function showNotification(message, type = 'info', duration = 5000) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Style the notification
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '15px 20px',
        borderRadius: '8px',
        color: 'white',
        fontSize: '14px',
        fontWeight: '500',
        zIndex: '10001',
        maxWidth: '400px',
        boxShadow: '0 4px 12px rgba(0, 0, 0, 0.3)',
        animation: 'slideInRight 0.3s ease',
        backgroundColor: getNotificationColor(type),
        cursor: 'pointer'
    });
    
    // Add close button styles
    const closeBtn = notification.querySelector('.notification-close');
    if (closeBtn) {
        Object.assign(closeBtn.style, {
            background: 'none',
            border: 'none',
            color: 'white',
            cursor: 'pointer',
            marginLeft: '10px',
            padding: '0',
            fontSize: '12px',
            opacity: '0.8'
        });
        
        closeBtn.addEventListener('mouseenter', () => closeBtn.style.opacity = '1');
        closeBtn.addEventListener('mouseleave', () => closeBtn.style.opacity = '0.8');
    }
    
    // Add to body
    document.body.appendChild(notification);
    
    // Auto remove after specified duration
    const timeoutId = setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, duration);
    
    // Allow manual close to cancel auto-close
    notification.addEventListener('click', () => {
        clearTimeout(timeoutId);
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    });
    
    // Add CSS animations if not already present
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .notification-content {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .notification-close {
                margin-left: auto;
            }
        `;
        document.head.appendChild(style);
    }
    
    return notification;
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('documentPreviewModal');
    if (event.target === modal) {
        closeDocumentPreview();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('documentPreviewModal');
        if (modal && modal.style.display === 'block') {
            closeDocumentPreview();
        }
    }
});

// Make document functions globally available
window.generateClientConductorDocument = generateClientConductorDocument;
window.generateConductorCourierDocument = generateConductorCourierDocument;
window.closeDocumentPreview = closeDocumentPreview;

function getNotificationIcon(type) {
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    return icons[type] || icons['info'];
}

function getNotificationColor(type) {
    const colors = {
        'success': '#27ae60',
        'error': '#e74c3c',
        'warning': '#f39c12',
        'info': '#3498db'
    };
    return colors[type] || colors['info'];
}