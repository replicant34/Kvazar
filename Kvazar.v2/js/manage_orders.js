/**
 * Manage Orders JavaScript
 * Handles order management interface functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let currentPage = 1;
    const itemsPerPage = 15;
    let totalOrders = 0;
    let currentSort = { column: 'Order_date', direction: 'desc' };
    let currentFilters = {
        search: '',
        status: '',
        client: '',
        courier: '',
        dateFrom: '',
        dateTo: ''
    };

    // Initialize the page
    initializePage();

    function initializePage() {
        setupEventListeners();
        setupTableEventDelegation(); // Setup event delegation early
        loadOrders();
    }

    function setupEventListeners() {
        // Search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentFilters.search = this.value;
                    currentPage = 1;
                    loadOrders();
                }, 500);
            });
        }

        // Filter dropdowns
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                currentFilters.status = this.value;
                currentPage = 1;
                loadOrders();
            });
        }

        const clientFilter = document.getElementById('clientFilter');
        if (clientFilter) {
            clientFilter.addEventListener('change', function() {
                currentFilters.client = this.value;
                currentPage = 1;
                loadOrders();
            });
        }

        const courierFilter = document.getElementById('courierFilter');
        if (courierFilter) {
            courierFilter.addEventListener('change', function() {
                currentFilters.courier = this.value;
                currentPage = 1;
                loadOrders();
            });
        }

        // Date filters
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        
        if (dateFrom) {
            dateFrom.addEventListener('change', function() {
                currentFilters.dateFrom = this.value;
                currentPage = 1;
                loadOrders();
            });
        }

        if (dateTo) {
            dateTo.addEventListener('change', function() {
                currentFilters.dateTo = this.value;
                currentPage = 1;
                loadOrders();
            });
        }

        // Table sorting
        document.querySelectorAll('#ordersTable th.sortable').forEach(header => {
            header.addEventListener('click', function() {
                const column = this.dataset.column;
                if (currentSort.column === column) {
                    currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort.column = column;
                    currentSort.direction = 'asc';
                }
                currentPage = 1;
                loadOrders();
            });
        });

        // Refresh button
        const refreshBtn = document.getElementById('refreshOrders');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                loadOrders();
            });
        }

        // Download button
        const downloadBtn = document.getElementById('downloadOrders');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function() {
                downloadOrdersCSV();
            });
        }

        // Modal close buttons
        document.querySelectorAll('.modal .close').forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });

        // Form submissions
        setupFormSubmissions();
    }

    function loadOrders() {
        const tableBody = document.getElementById('ordersTableBody');
        if (!tableBody) return;

        // Show loading state
        tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">Загрузка заказов...</td></tr>';

        // Prepare request data
        const requestData = {
            page: currentPage,
            itemsPerPage: itemsPerPage,
            sort: currentSort,
            filters: currentFilters
        };

        // Fetch orders from server
        fetch('../assets/manage_orders_fetch_orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrders(data.orders);
                updatePagination(data.totalCount);
                updateSortIndicators();
            } else {
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: red;">Ошибка загрузки данных: ' + (data.error || 'Неизвестная ошибка') + '</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: red;">Ошибка загрузки данных</td></tr>';
        });
    }

    function displayOrders(orders) {
        const tableBody = document.getElementById('ordersTableBody');
        if (!tableBody) return;

        if (orders.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">Заказы не найдены</td></tr>';
            return;
        }

        tableBody.innerHTML = orders.map(order => {
            const statusBadge = createStatusBadge(order.status_name, order.status_color);
            const actionButtons = createActionButtons(order);
            
            return `
                <tr class="order-row" data-order-id="${order.Order_id}">
                    <td>${statusBadge}</td>
                    <td><span class="order-number">${escapeHtml(order.display_order_number || '')}</span></td>
                    <td><span class="company-name" title="${escapeHtml(order.client_name || '')}">${escapeHtml(order.client_name || '—')}</span></td>
                    <td>${formatDate(order.Order_date)}</td>
                    <td><span class="company-name" title="${escapeHtml(order.courier_name || '')}">${escapeHtml(order.courier_name || '—')}</span></td>
                    <td><span class="order-total">${formatCurrency(order.Order_total, order.currency_name)}</span></td>
                    <td class="actions-cell-wrapper"><div class="actions-cell">${actionButtons}</div></td>
                </tr>
            `;
        }).join('');
        
        // Add click event listeners to order rows
        setupOrderRowClickHandlers();
        
        // Also setup event delegation on the table body
        setupTableEventDelegation();
    }

    function createStatusBadge(statusName, statusColor) {
        return `
            <span class="status-badge" style="border-color: ${statusColor}; color: ${statusColor};">
                <span class="status-dot" style="background-color: ${statusColor};"></span>
                ${escapeHtml(statusName || 'Неизвестно')}
            </span>
        `;
    }

    function createActionButtons(order) {
        const buttons = [];
        const statusId = parseInt(order.Status);

        // Actions based on status
        if (statusId === 1) {
            buttons.push(`<button class="action-btn btn-download" onclick="downloadPreorder(${order.Order_id})" title="Скачать предзаказ">
                <i class="fas fa-download"></i> Предзаказ
            </button>`);
            
            buttons.push(`<button class="action-btn btn-courier" onclick="setCourier(${order.Order_id})" title="Назначить перевозчика">
                <i class="fas fa-truck"></i> Перевозчик
            </button>`);
        }

        // Official order generation (available for orders with assigned couriers)
        if (statusId > 1 && order.courier_name && order.courier_name !== '—') {
            buttons.push(`<button class="action-btn btn-official" onclick="generateOfficialOrder(${order.Order_id})" title="Создать официальный заказ">
                <i class="fas fa-file-contract"></i> Официальный заказ
            </button>`);
        }

        // Always available actions
        buttons.push(`<button class="action-btn btn-view" onclick="viewOrder(${order.Order_id})" title="Просмотр заказа">
            <i class="fas fa-eye"></i> Просмотр
        </button>`);

        // Check if there are attached files
        if (order.has_attached_files) {
            buttons.push(`<button class="action-btn btn-files" onclick="viewAttachedFiles(${order.Order_id})" title="Прикрепленные файлы">
                <i class="fas fa-paperclip"></i> Файлы
            </button>`);
        }

        return buttons.join('');
    }

    function updatePagination(totalCount) {
        totalOrders = totalCount;
        const totalPages = Math.ceil(totalCount / itemsPerPage);
        const paginationContainer = document.getElementById('pagination');
        const paginationInfo = document.getElementById('paginationInfo');

        // Update info
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalCount);
        
        if (paginationInfo) {
            paginationInfo.textContent = `Показано ${startItem}-${endItem} из ${totalCount} заказов`;
        }

        if (!paginationContainer) return;

        // Generate pagination buttons
        let paginationHTML = '';

        // Previous button
        if (currentPage > 1) {
            paginationHTML += `<button onclick="changePage(${currentPage - 1})" title="Предыдущая страница">
                <i class="fas fa-chevron-left"></i>
            </button>`;
        } else {
            paginationHTML += `<button disabled><i class="fas fa-chevron-left"></i></button>`;
        }

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            paginationHTML += `<button onclick="changePage(1)">1</button>`;
            if (startPage > 2) {
                paginationHTML += `<span>...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                paginationHTML += `<button class="active">${i}</button>`;
            } else {
                paginationHTML += `<button onclick="changePage(${i})">${i}</button>`;
            }
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<span>...</span>`;
            }
            paginationHTML += `<button onclick="changePage(${totalPages})">${totalPages}</button>`;
        }

        // Next button
        if (currentPage < totalPages) {
            paginationHTML += `<button onclick="changePage(${currentPage + 1})" title="Следующая страница">
                <i class="fas fa-chevron-right"></i>
            </button>`;
        } else {
            paginationHTML += `<button disabled><i class="fas fa-chevron-right"></i></button>`;
        }

        paginationContainer.innerHTML = paginationHTML;
    }

    function updateSortIndicators() {
        document.querySelectorAll('#ordersTable th.sortable i').forEach(icon => {
            icon.className = 'fas fa-sort';
        });

        const currentHeader = document.querySelector(`#ordersTable th[data-column="${currentSort.column}"] i`);
        if (currentHeader) {
            currentHeader.className = currentSort.direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
        }
    }

    // Pagination function (global scope)
    window.changePage = function(page) {
        currentPage = page;
        loadOrders();
    };

    // Modal action functions (global scope)
    window.downloadPreorder = function(orderId) {
        showDownloadPreorderModal(orderId);
    };

    window.viewOrder = function(orderId) {
        showViewOrderModal(orderId);
    };

    window.viewAttachedFiles = function(orderId) {
        showAttachedFilesModal(orderId);
    };

    window.setCourier = function(orderId) {
        showSetCourierModal(orderId);
    };

    window.generateOfficialOrder = function(orderId) {
        showGenerateOfficialOrderModal(orderId);
    };

    // Setup click handlers for order rows
    function setupOrderRowClickHandlers() {
        const orderRows = document.querySelectorAll('.order-row');
        
        orderRows.forEach(row => {
            row.style.cursor = 'pointer';
        });
    }

    // Setup event delegation for table clicks (more reliable for dynamic content)
    function setupTableEventDelegation() {
        const tableBody = document.getElementById('ordersTableBody');
        if (!tableBody) {
            return;
        }
        
        // Remove any existing listeners to avoid duplicates
        tableBody.removeEventListener('click', handleTableClick);
        tableBody.addEventListener('click', handleTableClick);
    }
    
    function handleTableClick(e) {
        // Find the closest order row
        const orderRow = e.target.closest('.order-row');
        if (!orderRow) {
            return;
        }
        
        // Check if the click was on an action button or its parent
        if (e.target.closest('.actions-cell') || 
            e.target.closest('.action-btn') || 
            e.target.closest('.actions-cell-wrapper') ||
            e.target.classList.contains('action-btn')) {
            return; // Don't navigate if clicking on action buttons
        }
        
        // Check if we're inside a form
        const parentForm = e.target.closest('form');
        if (parentForm) {
            e.preventDefault(); // Prevent form submission
        }
        
        // Prevent any default behavior and stop propagation immediately
        e.preventDefault(); 
        e.stopPropagation(); 
        e.stopImmediatePropagation(); // Stop all other event handlers
        
        const orderId = orderRow.getAttribute('data-order-id');
        
        if (orderId) {
            // Additional safety check - make sure we're not in the middle of a page load
            if (document.readyState === 'loading') {
                setTimeout(() => navigateToOrderDetail(orderId), 500);
            } else {
                navigateToOrderDetail(orderId);
            }
        }
    }
    
    // Separate navigation function
    function navigateToOrderDetail(orderId) {
        try {
            const url = `order_detail.php?id=${orderId}`;
            
            // Use setTimeout to ensure any other events complete first
            setTimeout(() => {
                try {
                    window.location.href = url;
                } catch (navError) {
                    // Fallback method
                    window.open(url, '_self');
                }
            }, 100); // Small delay to prevent conflicts
            
        } catch (error) {
            console.error('Error navigating:', error);
            alert('Ошибка навигации: ' + error.message);
        }
    }

    window.openOrderDetail = function(orderId) {
        navigateToOrderDetail(orderId);
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    };

    // Utility functions
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU');
    }

    function formatCurrency(amount, currency) {
        if (!amount || amount === '0.00') return '—';
        return `${parseFloat(amount).toLocaleString('ru-RU')} ${currency || ''}`;
    }

    // Modal functions
    function loadOrderForVerification(orderId) {
        // Show loading state
        const modal = document.getElementById('verifyOrderModal');
        const formContent = document.getElementById('verifyOrderFormContent');
        const orderIdInput = document.getElementById('verifyOrderId');
        
        if (!modal || !formContent) return;
        
        orderIdInput.value = orderId;
        formContent.innerHTML = '<div style="text-align: center; padding: 40px;">Загрузка данных заказа...</div>';
        modal.style.display = 'block';
        
        // Load order data
        fetch(`../assets/get_order_for_verification.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                generateVerificationForm(data);
            } else {
                formContent.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Ошибка загрузки данных: ' + (data.error || 'Неизвестная ошибка') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error loading order for verification:', error);
            formContent.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Ошибка загрузки данных</div>';
        });
    }

    function generateVerificationForm(data) {
        const formContent = document.getElementById('verifyOrderFormContent');
        const order = data.order;
        const routePoints = data.route_points;
        const extraServices = data.extra_services;
        const dropdownData = data.dropdown_data;
        
        // Debug: Log the data to console for troubleshooting (can be removed in production)
        // console.log('Order data:', order);
        
        let formHTML = `
            <!-- Client Information Section -->
            <div class="form-section">
                <h4>Информация о клиенте</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="verify_client_name">Клиент:</label>
                        <input type="text" id="verify_client_name" value="${escapeHtml(order.client_name || '')}" disabled>
                        <input type="hidden" name="client_id" value="${order.Client_id || ''}">
                    </div>
                    <div class="form-group">
                        <label for="verify_contract_number">Номер договора:</label>
                        <input type="text" id="verify_contract_number" value="${escapeHtml(order.Contract_number || '')}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="verify_contract_date">Дата договора:</label>
                        <input type="text" id="verify_contract_date" value="${formatDate(order.Contract_date) || ''}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="verify_contractor">Подрядчик:</label>
                        <select id="verify_contractor" name="contractor_id">
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
                        <label for="verify_order_number">Номер заказа:</label>
                        <input type="text" id="verify_order_number" name="display_order_number" value="${escapeHtml(order.display_order_number || '')}" required>
                    </div>
                    <div class="form-group">
                        <label for="verify_order_date">Дата заказа:</label>
                        <input type="date" id="verify_order_date" name="order_date" value="${order.Order_date || ''}">
                    </div>
                    <div class="form-group">
                        <label for="verify_shipping_type">Тип перевозки:</label>
                        <select id="verify_shipping_type" name="shipping_type">
                            <option value="">Выберите тип перевозки...</option>
                            ${dropdownData.shipping_types.map(type => 
                                `<option value="${type.Type_id}" ${order.Shipping_type == type.Type_id ? 'selected' : ''}>
                                    ${escapeHtml(type.Type_name)}
                                </option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="verify_transport_type">Тип транспорта:</label>
                        <select id="verify_transport_type" name="transport_type">
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
                        <label for="verify_cargo_name">Наименование груза:</label>
                        <select id="verify_cargo_name" name="cargo_name">
                            <option value="">Выберите груз...</option>
                            ${dropdownData.cargo_names.map(cargo => 
                                `<option value="${cargo.id}" ${order.Cargo_type == cargo.id ? 'selected' : ''}>
                                    ${escapeHtml(cargo.cargo_name)}
                                </option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="verify_cargo_weight">Вес груза:</label>
                        <input type="number" id="verify_cargo_weight" name="cargo_weight" value="${order.Weight || ''}" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="verify_weight_unit">Единица веса:</label>
                        <select id="verify_weight_unit" name="weight_unit">
                            <option value="тонн" ${order.Weight_unit == 'тонн' ? 'selected' : ''}>Тонн</option>
                            <option value="кг" ${order.Weight_unit == 'кг' ? 'selected' : ''}>Кг</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="verify_cargo_volume">Объем груза (м³):</label>
                        <input type="number" id="verify_cargo_volume" name="cargo_volume" value="${order.Volume || ''}" step="0.01">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="verify_cargo_quantity">Количество мест:</label>
                        <input type="number" id="verify_cargo_quantity" name="cargo_quantity" value="${order.Quantity || ''}" min="1">
                    </div>
                    <div class="form-group">
                        <label for="verify_length">Длина (м):</label>
                        <input type="number" id="verify_length" name="length" value="${order.Size ? order.Size.split(' x ')[0] : ''}" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="verify_width">Ширина (м):</label>
                        <input type="number" id="verify_width" name="width" value="${order.Size ? order.Size.split(' x ')[1] : ''}" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="verify_height">Высота (м):</label>
                        <input type="number" id="verify_height" name="height" value="${order.Size ? order.Size.split(' x ')[2] : ''}" step="0.01">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="verify_loading_type">Тип погрузки:</label>
                        <select id="verify_loading_type" name="loading_type">
                            <option value="">Выберите тип погрузки...</option>
                            ${dropdownData.loading_types.map(type => 
                                `<option value="${type.id}" ${order.Loading_type == type.id ? 'selected' : ''}>
                                    ${escapeHtml(type.loading_name)}
                                </option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="verify_packaging_type">Тип упаковки:</label>
                        <select id="verify_packaging_type" name="packaging_type">
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
                        <label for="verify_min_temperature">Мин. температура (°C):</label>
                        <input type="number" id="verify_min_temperature" name="min_temperature" value="${order.Min_temperature || ''}" step="0.1">
                    </div>
                    <div class="form-group">
                        <label for="verify_max_temperature">Макс. температура (°C):</label>
                        <input type="number" id="verify_max_temperature" name="max_temperature" value="${order.Max_temperature || ''}" step="0.1">
                    </div>
                    <div class="form-group">
                        <label for="verify_temp_print_list">Температурный лист:</label>
                        <select id="verify_temp_print_list" name="temp_print_list">
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
                        <label for="verify_cargo_price">Стоимость груза:</label>
                        <input type="number" id="verify_cargo_price" name="cargo_price" value="${order.Cargo_price || ''}" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="verify_currency">Валюта:</label>
                        <select id="verify_currency" name="currency_id">
                            <option value="">Выберите валюту...</option>
                            ${dropdownData.currencies.map(currency => 
                                `<option value="${currency.Currency_id}" ${order.Currency_id == currency.Currency_id ? 'selected' : ''}>
                                    ${escapeHtml(currency.Currency_name)}
                                </option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="verify_rate">Коэффициент:</label>
                        <input type="number" id="verify_rate" name="rate" value="${order.Rate || ''}" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="verify_total_insurance">Страхование:</label>
                        <input type="number" id="verify_total_insurance" name="total_insurance" value="${order.Insurance_price || ''}" step="0.01">
                    </div>
                </div>
            </div>

            <!-- Transport & Rates Section -->
            <div class="form-section">
                <h4>Стоимость перевозки и переработка</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="verify_transport_rate">Ставка перевозки:</label>
                        <input type="number" id="verify_transport_rate" name="transport_rate" value="${order.Rate_2 || ''}" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="verify_transport_hours">Часы:</label>
                        <input type="number" id="verify_transport_hours" name="transport_hours" value="${order.Hours || ''}" min="1" step="1">
                    </div>
                    <div class="form-group">
                        <label for="verify_overwork_hours">Часы переработки:</label>
                        <input type="number" id="verify_overwork_hours" name="overwork_hours" value="${order.Extra_hours || ''}" min="0" step="1">
                    </div>
                    <div class="form-group">
                        <label for="verify_transport_total">Итого за перевозку:</label>
                        <input type="number" id="verify_transport_total" name="transport_total" value="${order.Total_price_vehicle || ''}" step="0.01">
                    </div>
                </div>
            </div>
            
            <!-- Order Totals Section -->
            <div class="form-section">
                <h4>Итоговая стоимость</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="verify_extra_services_total">Доп. услуги:</label>
                        <input type="number" id="verify_extra_services_total" name="extra_services_total" value="${order.Total_price_extra_service || ''}" step="0.01" readonly>
                    </div>
                    <div class="form-group">
                        <label for="verify_order_total">Общая сумма:</label>
                        <input type="number" id="verify_order_total" name="order_total" value="${order.Order_total || ''}" step="0.01">
                    </div>
                </div>
            </div>

            <!-- Route Points Section -->
            <div class="form-section">
                <h4>Маршрут</h4>
                <div class="route-points-container">
                    ${generateRoutePointsHTML(routePoints)}
                </div>
            </div>

            <!-- Extra Services Section -->
            <div class="form-section">
                <h4>Дополнительные услуги</h4>
                <div class="extra-services-container">
                    ${generateExtraServicesHTML(extraServices)}
                </div>
            </div>

            <!-- Notes Section -->
            <div class="form-section">
                <h4>Дополнительные примечания</h4>
                <div class="form-group">
                    <label for="verify_order_notes">Примечания:</label>
                    <textarea id="verify_order_notes" name="order_notes" rows="4">${escapeHtml(order.order_notes || '')}</textarea>
                </div>
            </div>
        `;
        
        formContent.innerHTML = formHTML;
        
        // Add dynamic calculation functionality
        setupVerificationCalculations();
    }

    function generateRoutePointsHTML(routePoints) {
        if (!routePoints || routePoints.length === 0) {
            return '<p style="color: #666; font-style: italic;">Маршрут не задан</p>';
        }

        return routePoints.map((point, index) => {
            const contacts = point.contact_list || [];
            const contactsHTML = contacts.length > 0 
                ? contacts.map(contact => `${escapeHtml(contact.name)} (${escapeHtml(contact.phone)})`).join(', ')
                : 'Не указаны';

            return `
                <div class="route-point-item" style="border: 1px solid #ddd; border-radius: 6px; padding: 15px; margin-bottom: 15px; background: #f8f9fa;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h5 style="margin: 0; color: #2c3e50;">Пункт ${index + 1}</h5>
                        <span style="background: ${point.Action_type === 'Погрузка' ? '#28a745' : '#17a2b8'}; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8em;">
                            ${escapeHtml(point.Action_type || 'Не указано')}
                        </span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <strong>Компания:</strong><br>
                            ${escapeHtml(point.Company_name || 'Не указано')}
                        </div>
                        <div>
                            <strong>Адрес:</strong><br>
                            ${escapeHtml(point.Address || 'Не указан')}
                        </div>
                        <div>
                            <strong>Дата и время:</strong><br>
                            ${formatDate(point.Date)} ${point.Time || ''}
                        </div>
                        <div>
                            <strong>Контакты:</strong><br>
                            ${contactsHTML}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function generateExtraServicesHTML(extraServices) {
        if (!extraServices || extraServices.length === 0) {
            return '<p style="color: #666; font-style: italic;">Дополнительные услуги не указаны</p>';
        }

        let total = 0;
        const servicesHTML = extraServices.map((service, index) => {
            const serviceTotal = parseFloat(service.Total || 0);
            total += serviceTotal;
            
            return `
                <div class="extra-service-item" style="border: 1px solid #ddd; border-radius: 6px; padding: 10px; margin-bottom: 10px; background: #f8f9fa;">
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 15px; align-items: center;">
                        <div>
                            <strong>Услуга:</strong><br>
                            ${escapeHtml(service.Service_name || 'Не указано')}
                        </div>
                        <div>
                            <strong>Цена:</strong><br>
                            ${formatCurrency(service.Service_price, '')}
                        </div>
                        <div>
                            <strong>Количество:</strong><br>
                            ${service.Quantity || 0}
                        </div>
                        <div>
                            <strong>Итого:</strong><br>
                            <span style="font-weight: bold;">${formatCurrency(service.Total, '')}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        return `
            ${servicesHTML}
            <div style="text-align: right; margin-top: 10px; padding: 10px; border-top: 2px solid #3498db;">
                <strong>Общая стоимость доп. услуг: ${formatCurrency(total, '')}</strong>
            </div>
        `;
    }

    function setupVerificationCalculations() {
        // Get all calculation input fields
        const cargoPrice = document.getElementById('verify_cargo_price');
        const rate = document.getElementById('verify_rate');
        const totalInsurance = document.getElementById('verify_total_insurance');
        const transportRate = document.getElementById('verify_transport_rate');
        const transportHours = document.getElementById('verify_transport_hours');
        const overworkHours = document.getElementById('verify_overwork_hours');
        const transportTotal = document.getElementById('verify_transport_total');
        const extraServicesTotal = document.getElementById('verify_extra_services_total');
        const orderTotal = document.getElementById('verify_order_total');

        // Function to calculate insurance
        function calculateInsurance() {
            const price = parseFloat(cargoPrice?.value || 0);
            const rateValue = parseFloat(rate?.value || 0);
            
            if (price > 0 && rateValue > 0) {
                const insurance = (price * rateValue) / 100;
                if (totalInsurance) {
                    totalInsurance.value = insurance.toFixed(2);
                }
            }
            updateGrandTotal();
        }

        // Function to calculate transport total
        function calculateTransportTotal() {
            const rateValue = parseFloat(transportRate?.value || 0);
            const hours = parseFloat(transportHours?.value || 0);
            const overwork = parseFloat(overworkHours?.value || 0);
            
            // Basic transport cost
            const basicCost = rateValue * hours;
            
            // Overwork cost (usually at higher rate, but for simplicity using same rate)
            const overworkCost = rateValue * overwork;
            
            const total = basicCost + overworkCost;
            
            if (transportTotal) {
                transportTotal.value = total.toFixed(2);
            }
            updateGrandTotal();
        }

        // Function to update grand total
        function updateGrandTotal() {
            const insurance = parseFloat(totalInsurance?.value || 0);
            const transport = parseFloat(transportTotal?.value || 0);
            const extraServices = parseFloat(extraServicesTotal?.value || 0);
            
            const grandTotal = insurance + transport + extraServices;
            
            if (orderTotal) {
                orderTotal.value = grandTotal.toFixed(2);
            }
        }

        // Add event listeners
        if (cargoPrice) cargoPrice.addEventListener('input', calculateInsurance);
        if (rate) rate.addEventListener('input', calculateInsurance);
        if (transportRate) {
            transportRate.addEventListener('input', calculateTransportTotal);
        }
        if (transportHours) {
            transportHours.addEventListener('input', calculateTransportTotal);
        }
        if (overworkHours) {
            overworkHours.addEventListener('input', calculateTransportTotal);
        }
        if (extraServicesTotal) {
            extraServicesTotal.addEventListener('input', updateGrandTotal);
        }

        // Initial calculation
        calculateInsurance();
        calculateTransportTotal();
        updateGrandTotal();
    }

    function showDownloadPreorderModal(orderId) {
        const modal = document.getElementById('downloadPreorderModal');
        const orderIdInput = document.getElementById('downloadOrderId');
        
        if (modal && orderIdInput) {
            orderIdInput.value = orderId;
            modal.style.display = 'block';
        }
    }

    function showGenerateOfficialOrderModal(orderId) {
        const modal = document.getElementById('generateOfficialOrderModal');
        const orderIdInput = document.getElementById('generateOfficialOrderId');
        
        if (modal && orderIdInput) {
            orderIdInput.value = orderId;
            modal.style.display = 'block';
        }
    }

    function showViewOrderModal(orderId) {
        // Load order PDF and show in modal
        fetch(`../assets/get_order_pdf.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.pdf_path) {
                const modal = document.getElementById('viewOrderModal');
                const pdfViewer = document.getElementById('pdfViewer');
                
                if (modal && pdfViewer) {
                    pdfViewer.src = `../${data.pdf_path}`;
                    modal.style.display = 'block';
                }
            } else {
                alert('PDF файл не найден для этого заказа');
            }
        })
        .catch(error => {
            console.error('Error loading order PDF:', error);
            alert('Ошибка загрузки PDF файла');
        });
    }

    function showAttachedFilesModal(orderId) {
        // Load attached files list
        fetch(`../assets/get_attached_files.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('attachedFilesModal');
                const filesList = document.getElementById('attachedFilesList');
                
                if (modal && filesList) {
                    filesList.innerHTML = data.files.map(file => `
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
                    
                    modal.style.display = 'block';
                }
            } else {
                alert('Ошибка загрузки списка файлов');
            }
        })
        .catch(error => {
            console.error('Error loading attached files:', error);
            alert('Ошибка загрузки списка файлов');
        });
    }

    function showSetCourierModal(orderId) {
        const modal = document.getElementById('setCourierModal');
        const orderIdInput = document.getElementById('setCourierOrderId');
        const formContent = document.getElementById('setCourierFormContent');
        
        if (modal && orderIdInput && formContent) {
            orderIdInput.value = orderId;
            formContent.innerHTML = '<div style="text-align: center; padding: 40px;">Загрузка данных...</div>';
            modal.style.display = 'block';
            
            // Load courier assignment data
            fetch(`../assets/get_courier_assignment_data.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setupCourierForm(data);
                } else {
                    formContent.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Ошибка загрузки данных: ' + (data.error || 'Неизвестная ошибка') + '</div>';
                }
            })
            .catch(error => {
                console.error('Error loading courier assignment data:', error);
                formContent.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Ошибка загрузки данных</div>';
            });
        }
    }

    function setupCourierForm(data) {
        const formContent = document.getElementById('setCourierFormContent');
        const orderInfo = data.order_info;
        const couriers = data.couriers;
        const currentAssignments = data.current_assignments;
        
        let formHTML = `
            <div class="courier-assignment-form">
                <!-- Order Information -->
                <div class="form-section">
                    <h4>Информация о заказе</h4>
                    <div class="order-info">
                        <div><strong>Номер заказа:</strong> ${escapeHtml(orderInfo.display_order_number || '')}</div>
                        <div><strong>Клиент:</strong> ${escapeHtml(orderInfo.client_name || '')}</div>
                        <div><strong>Текущий подрядчик:</strong> ${escapeHtml(orderInfo.current_courier_name || 'Не назначен')}</div>
                    </div>
                </div>

                <!-- Courier Selection -->
                <div class="form-section">
                    <h4>Выбор подрядчика</h4>
                    <div class="form-group">
                        <label for="setCourier_courier">Подрядчик:</label>
                        <select id="setCourier_courier" name="courier_id" required>
                            <option value="">Выберите подрядчика...</option>
                            ${couriers.map(courier => 
                                `<option value="${courier.Courier_id}" ${orderInfo.current_courier_id == courier.Courier_id ? 'selected' : ''}>
                                    ${escapeHtml(courier.Full_Company_name)}
                                </option>`
                            ).join('')}
                        </select>
                    </div>
                </div>

                <!-- Drivers Section -->
                <div class="form-section">
                    <h4>Назначение водителей</h4>
                    <div id="driversSection" class="resources-section">
                        <div class="loading-message">Выберите подрядчика для загрузки водителей</div>
                    </div>
                </div>

                <!-- Vehicles Section -->
                <div class="form-section">
                    <h4>Назначение транспорта</h4>
                    <div id="vehiclesSection" class="resources-section">
                        <div class="loading-message">Выберите подрядчика для загрузки транспорта</div>
                    </div>
                </div>
            </div>
        `;
        
        formContent.innerHTML = formHTML;
        
        // Setup courier change handler
        const courierSelect = document.getElementById('setCourier_courier');
        if (courierSelect) {
            courierSelect.addEventListener('change', function() {
                const selectedCourierId = this.value;
                if (selectedCourierId) {
                    loadCourierResources(selectedCourierId, currentAssignments);
                } else {
                    clearResourcesSections();
                }
            });
            
            // If there's a current courier, load its resources
            if (orderInfo.current_courier_id) {
                loadCourierResources(orderInfo.current_courier_id, currentAssignments);
            }
        }
    }

    function loadCourierResources(courierId, currentAssignments) {
        const driversSection = document.getElementById('driversSection');
        const vehiclesSection = document.getElementById('vehiclesSection');
        
        if (!driversSection || !vehiclesSection) return;
        
        // Show loading state
        driversSection.innerHTML = '<div class="loading-message">Загрузка водителей...</div>';
        vehiclesSection.innerHTML = '<div class="loading-message">Загрузка транспорта...</div>';
        
        // Load courier resources
        fetch(`../assets/get_courier_resources.php?courier_id=${courierId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateDriversSection(data.drivers, currentAssignments.drivers);
                populateVehiclesSection(data.vehicles, currentAssignments.vehicles);
            } else {
                driversSection.innerHTML = '<div class="error-message">Ошибка загрузки водителей: ' + (data.error || 'Неизвестная ошибка') + '</div>';
                vehiclesSection.innerHTML = '<div class="error-message">Ошибка загрузки транспорта: ' + (data.error || 'Неизвестная ошибка') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error loading courier resources:', error);
            driversSection.innerHTML = '<div class="error-message">Ошибка загрузки водителей</div>';
            vehiclesSection.innerHTML = '<div class="error-message">Ошибка загрузки транспорта</div>';
        });
    }

    function populateDriversSection(drivers, currentDrivers) {
        const driversSection = document.getElementById('driversSection');
        const currentDriverIds = currentDrivers.map(d => d.Driver_id);
        
        if (drivers.length === 0) {
            driversSection.innerHTML = '<div class="no-data-message">У данного подрядчика нет водителей</div>';
            return;
        }
        
        let driversHTML = '<div class="resources-list">';
        drivers.forEach(driver => {
            const isSelected = currentDriverIds.includes(driver.Driver_id);
            driversHTML += `
                <div class="resource-item">
                    <label class="resource-checkbox">
                        <input type="checkbox" name="driver_ids[]" value="${driver.Driver_id}" ${isSelected ? 'checked' : ''}>
                        <div class="resource-info">
                            <div class="resource-name">${escapeHtml(driver.driver_name)}</div>
                            <div class="resource-details">
                                <span>Телефон: ${escapeHtml(driver.driver_phone || 'Не указан')}</span>
                                <span>Паспорт: ${escapeHtml(driver.driver_passport || 'Не указан')}</span>
                            </div>
                        </div>
                    </label>
                </div>
            `;
        });
        driversHTML += '</div>';
        
        driversSection.innerHTML = driversHTML;
    }

    function populateVehiclesSection(vehicles, currentVehicles) {
        const vehiclesSection = document.getElementById('vehiclesSection');
        const currentVehicleIds = currentVehicles.map(v => v.Vehicle_id);
        
        if (vehicles.length === 0) {
            vehiclesSection.innerHTML = '<div class="no-data-message">У данного подрядчика нет транспорта</div>';
            return;
        }
        
        let vehiclesHTML = '<div class="resources-list">';
        vehicles.forEach(vehicle => {
            const isSelected = currentVehicleIds.includes(vehicle.Vehicle_id);
            vehiclesHTML += `
                <div class="resource-item">
                    <label class="resource-checkbox">
                        <input type="checkbox" name="vehicle_ids[]" value="${vehicle.Vehicle_id}" ${isSelected ? 'checked' : ''}>
                        <div class="resource-info">
                            <div class="resource-name">${escapeHtml(vehicle.vehicle_brand)}</div>
                            <div class="resource-details">
                                <span>Номер: ${escapeHtml(vehicle.vehicle_plate)}</span>
                            </div>
                        </div>
                    </label>
                </div>
            `;
        });
        vehiclesHTML += '</div>';
        
        vehiclesSection.innerHTML = vehiclesHTML;
    }

    function clearResourcesSections() {
        const driversSection = document.getElementById('driversSection');
        const vehiclesSection = document.getElementById('vehiclesSection');
        
        if (driversSection) {
            driversSection.innerHTML = '<div class="loading-message">Выберите подрядчика для загрузки водителей</div>';
        }
        
        if (vehiclesSection) {
            vehiclesSection.innerHTML = '<div class="loading-message">Выберите подрядчика для загрузки транспорта</div>';
        }
    }

    function setupFormSubmissions() {
        // Download preorder form
        const downloadForm = document.getElementById('downloadPreorderForm');
        if (downloadForm) {
            downloadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitDownloadPreorder();
            });
        }

        // Generate official order form
        const generateOfficialOrderForm = document.getElementById('generateOfficialOrderForm');
        if (generateOfficialOrderForm) {
            generateOfficialOrderForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitGenerateOfficialOrder();
            });
        }

        // Set courier form
        const courierForm = document.getElementById('setCourierForm');
        if (courierForm) {
            courierForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitCourierAssignment();
            });
        }

        // Verify order form
        const verifyForm = document.getElementById('verifyOrderForm');
        if (verifyForm) {
            verifyForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitVerifiedOrder();
            });
        }

        // Change status form
        const changeStatusForm = document.getElementById('changeStatusForm');
        if (changeStatusForm) {
            changeStatusForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitStatusChange();
            });
        }
    }

    function downloadOrdersCSV() {
        // Create download parameters
        const params = new URLSearchParams({
            ...currentFilters,
            sort_column: currentSort.column,
            sort_direction: currentSort.direction
        });

        // Download CSV
        window.location.href = `../assets/download_orders_csv.php?${params.toString()}`;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    window.downloadFile = function(filePath, originalName) {
        const link = document.createElement('a');
        link.href = `../assets/download_file.php?file=${encodeURIComponent(filePath)}&name=${encodeURIComponent(originalName)}`;
        link.download = originalName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    function submitVerifiedOrder() {
        const form = document.getElementById('verifyOrderForm');
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
        
        // Collect form data
        const formData = new FormData(form);
        const orderData = {};
        
        // Convert FormData to object
        for (let [key, value] of formData.entries()) {
            orderData[key] = value;
        }
        
        // Add order ID
        orderData.order_id = document.getElementById('verifyOrderId').value;
        
        // Submit to server
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
                alert('Заказ успешно обновлен!');
                document.getElementById('verifyOrderModal').style.display = 'none';
                loadOrders(); // Refresh the orders table
            } else {
                alert('Ошибка при сохранении заказа: ' + (data.error || 'Неизвестная ошибка'));
            }
        })
        .catch(error => {
            console.error('Error saving verified order:', error);
            alert('Ошибка при сохранении заказа');
        })
        .finally(() => {
            // Restore button state
            submitBtn.disabled = false;
                         submitBtn.innerHTML = originalText;
         });
     }

     function submitDownloadPreorder() {
         const form = document.getElementById('downloadPreorderForm');
         const formData = new FormData(form);
         
         // Get selected sections
         const sections = [];
         const checkboxes = form.querySelectorAll('input[name="sections[]"]:checked');
         checkboxes.forEach(checkbox => {
             sections.push(checkbox.value);
         });
         
         if (sections.length === 0) {
             alert('Пожалуйста, выберите хотя бы один раздел для экспорта');
             return;
         }
         
         const downloadData = {
             order_id: formData.get('order_id'),
             export_format: formData.get('export_format') || 'pdf',
             sections: sections
         };
         
         // Show loading state
         const submitBtn = form.querySelector('button[type="submit"]');
         const originalText = submitBtn.innerHTML;
         submitBtn.disabled = true;
         submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Подготовка...';
         
         // Submit download request
         fetch('../assets/download_preorder.php', {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/json'
             },
             body: JSON.stringify(downloadData)
         })
         .then(response => {
             if (response.ok) {
                 // If successful, trigger download
                 return response.blob();
             } else {
                 throw new Error('Download failed');
             }
         })
         .then(blob => {
             // Create download link
             const url = window.URL.createObjectURL(blob);
             const a = document.createElement('a');
             a.style.display = 'none';
             a.href = url;
             
             const extension = downloadData.export_format === 'pdf' ? 'pdf' : 'doc';
             a.download = `preorder_${downloadData.order_id}_${new Date().toISOString().slice(0, 10)}.${extension}`;
             
             document.body.appendChild(a);
             a.click();
             window.URL.revokeObjectURL(url);
             document.body.removeChild(a);
             
             // Close modal
             document.getElementById('downloadPreorderModal').style.display = 'none';
         })
         .catch(error => {
             console.error('Error downloading preorder:', error);
             alert('Ошибка при скачивании предзаказа');
         })
         .finally(() => {
             // Restore button state
             submitBtn.disabled = false;
             submitBtn.innerHTML = originalText;
         });
     }

     function submitGenerateOfficialOrder() {
         const form = document.getElementById('generateOfficialOrderForm');
         const formData = new FormData(form);
         
         const generateData = {
             order_id: formData.get('order_id'),
             document_type: formData.get('document_type') || 'official_order',
             export_format: formData.get('export_format') || 'pdf',
             include_signatures: formData.get('include_signatures') === '1',
             include_stamps: formData.get('include_stamps') === '1'
         };
         
         // Show loading state
         const submitBtn = form.querySelector('button[type="submit"]');
         const originalText = submitBtn.innerHTML;
         submitBtn.disabled = true;
         submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Создание документа...';
         
         // Submit generation request
         fetch('../assets/generate_official_order.php', {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/json'
             },
             body: JSON.stringify(generateData)
         })
         .then(response => response.json())
         .then(data => {
             if (data.success) {
                 // Trigger download
                 const link = document.createElement('a');
                 link.href = `../assets/download_official_order.php?order_id=${generateData.order_id}&format=${generateData.export_format}&type=${generateData.document_type}`;
                 link.download = data.filename || `official_order_${generateData.order_id}.${generateData.export_format}`;
                 document.body.appendChild(link);
                 link.click();
                 document.body.removeChild(link);
                 
                 // Close modal
                 document.getElementById('generateOfficialOrderModal').style.display = 'none';
                 
                 // Show success message
                 alert('Официальный заказ успешно создан!');
             } else {
                 alert('Ошибка при создании документа: ' + (data.error || 'Неизвестная ошибка'));
             }
         })
         .catch(error => {
             console.error('Error generating official order:', error);
             alert('Ошибка при создании официального заказа');
         })
         .finally(() => {
             // Restore button state
             submitBtn.disabled = false;
             submitBtn.innerHTML = originalText;
         });
     }

     function submitCourierAssignment() {
         const form = document.getElementById('setCourierForm');
         const submitBtn = form.querySelector('button[type="submit"]');
         const originalText = submitBtn.innerHTML;
         
         // Show loading state
         submitBtn.disabled = true;
         submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
         
         // Collect form data
         const formData = new FormData(form);
         const assignmentData = {
             order_id: formData.get('order_id'),
             courier_id: formData.get('courier_id')
         };
         
         // Get selected drivers
         const driverCheckboxes = form.querySelectorAll('input[name="driver_ids[]"]:checked');
         assignmentData.driver_ids = Array.from(driverCheckboxes).map(cb => parseInt(cb.value));
         
         // Get selected vehicles
         const vehicleCheckboxes = form.querySelectorAll('input[name="vehicle_ids[]"]:checked');
         assignmentData.vehicle_ids = Array.from(vehicleCheckboxes).map(cb => parseInt(cb.value));
         
         // Validate courier selection
         if (!assignmentData.courier_id) {
             alert('Пожалуйста, выберите подрядчика');
             submitBtn.disabled = false;
             submitBtn.innerHTML = originalText;
             return;
         }
         
         // Submit assignment
         fetch('../assets/save_courier_assignment.php', {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/json'
             },
             body: JSON.stringify(assignmentData)
         })
         .then(response => response.json())
         .then(data => {
             if (data.success) {
                 alert('Подрядчик назначен успешно!');
                 document.getElementById('setCourierModal').style.display = 'none';
                 loadOrders(); // Refresh the orders table
             } else {
                 alert('Ошибка при назначении подрядчика: ' + (data.error || 'Неизвестная ошибка'));
             }
         })
         .catch(error => {
             console.error('Error saving courier assignment:', error);
             alert('Ошибка при назначении подрядчика');
         })
         .finally(() => {
             // Restore button state
             submitBtn.disabled = false;
             submitBtn.innerHTML = originalText;
         });
     }

     function showChangeStatusModal(orderId) {
         const modal = document.getElementById('changeStatusModal');
         const orderIdInput = document.getElementById('changeStatusOrderId');
         const orderInfo = document.getElementById('statusOrderInfo');
         const currentStatusDisplay = document.getElementById('currentStatusDisplay');
         const newStatusSelect = document.getElementById('newStatus');
         
         if (modal && orderIdInput) {
             orderIdInput.value = orderId;
             orderInfo.innerHTML = 'Загрузка информации о заказе...';
             currentStatusDisplay.innerHTML = '';
             newStatusSelect.innerHTML = '<option value="">Загрузка статусов...</option>';
             modal.style.display = 'block';
             
             // Load current order status and available transitions
             loadOrderStatusData(orderId);
         }
     }

     function loadOrderStatusData(orderId) {
         // Get order data for status change
         fetch(`../assets/manage_orders_fetch_orders.php`, {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/json'
             },
             body: JSON.stringify({
                 page: 1,
                 itemsPerPage: 1,
                 filters: { order_id: orderId }
             })
         })
         .then(response => response.json())
         .then(data => {
             if (data.success && data.orders.length > 0) {
                 const order = data.orders[0];
                 displayOrderStatusInfo(order);
                 loadAvailableStatuses(order.Status);
             } else {
                 document.getElementById('statusOrderInfo').innerHTML = 'Ошибка загрузки данных заказа';
             }
         })
         .catch(error => {
             console.error('Error loading order status data:', error);
             document.getElementById('statusOrderInfo').innerHTML = 'Ошибка загрузки данных';
         });
     }

     function displayOrderStatusInfo(order) {
         const orderInfo = document.getElementById('statusOrderInfo');
         const currentStatusDisplay = document.getElementById('currentStatusDisplay');
         
         orderInfo.innerHTML = `
             <div class="order-summary">
                 <div><strong>Номер заказа:</strong> ${escapeHtml(order.display_order_number)}</div>
                 <div><strong>Клиент:</strong> ${escapeHtml(order.client_name)}</div>
                 <div><strong>Дата заказа:</strong> ${formatDate(order.order_date)}</div>
                 <div><strong>Перевозчик:</strong> ${escapeHtml(order.courier_name || 'Не назначен')}</div>
             </div>
         `;
         
         currentStatusDisplay.innerHTML = createStatusBadge(order.status_name, order.status_color);
     }

     function loadAvailableStatuses(currentStatusId) {
         // Define valid status transitions (same as backend)
         const validTransitions = {
             1: [2, 3, 6], // "Новый" can go to "В работе", "Ожидает подтверждения", "Отменен"
             2: [4, 5, 6], // "В работе" can go to "Выполнен", "Приостановлен", "Отменен"
             3: [1, 2, 6], // "Ожидает подтверждения" can go to "Новый", "В работе", "Отменен"
             4: [7],       // "Выполнен" can go to "Архив"
             5: [2, 6],    // "Приостановлен" can go to "В работе", "Отменен"
             6: [7],       // "Отменен" can go to "Архив"
             7: []         // "Архив" - final status, no transitions
         };
         
         const newStatusSelect = document.getElementById('newStatus');
         const availableTransitions = validTransitions[parseInt(currentStatusId)] || [];
         
         if (availableTransitions.length === 0) {
             newStatusSelect.innerHTML = '<option value="">Нет доступных переходов</option>';
             newStatusSelect.disabled = true;
             return;
         }
         
         // Load available statuses
         fetch('../assets/manage_orders_fetch_orders.php', {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/json'
             },
             body: JSON.stringify({ page: 1, itemsPerPage: 1 })
         })
         .then(response => response.json())
         .then(data => {
             // Get all statuses from the status filter data
             const statusOptions = availableTransitions.map(statusId => {
                 // This is a simplified approach - in a real app you'd fetch the status list
                 const statusNames = {
                     1: 'Новый',
                     2: 'В работе', 
                     3: 'Ожидает подтверждения',
                     4: 'Выполнен',
                     5: 'Приостановлен',
                     6: 'Отменен',
                     7: 'Архив'
                 };
                 
                 return `<option value="${statusId}">${statusNames[statusId]}</option>`;
             }).join('');
             
             newStatusSelect.innerHTML = '<option value="">Выберите новый статус...</option>' + statusOptions;
             newStatusSelect.disabled = false;
         })
         .catch(error => {
             console.error('Error loading statuses:', error);
             newStatusSelect.innerHTML = '<option value="">Ошибка загрузки статусов</option>';
         });
     }

     function submitStatusChange() {
         const form = document.getElementById('changeStatusForm');
         const submitBtn = form.querySelector('button[type="submit"]');
         const originalText = submitBtn.innerHTML;
         
         // Show loading state
         submitBtn.disabled = true;
         submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обновление...';
         
         // Collect form data
         const formData = new FormData(form);
         const statusData = {
             order_id: parseInt(formData.get('order_id')),
             new_status_id: parseInt(formData.get('new_status_id')),
             reason: formData.get('reason') || ''
         };
         
         // Validate
         if (!statusData.new_status_id) {
             alert('Пожалуйста, выберите новый статус');
             submitBtn.disabled = false;
             submitBtn.innerHTML = originalText;
             return;
         }
         
         // Submit status change
         fetch('../assets/update_order_status.php', {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/json'
             },
             body: JSON.stringify(statusData)
         })
         .then(response => response.json())
         .then(data => {
             if (data.success) {
                 alert(`Статус успешно изменен с "${data.previous_status}" на "${data.new_status}"`);
                 document.getElementById('changeStatusModal').style.display = 'none';
                 loadOrders(); // Refresh the orders table
             } else {
                 alert('Ошибка при изменении статуса: ' + (data.error || 'Неизвестная ошибка'));
             }
         })
         .catch(error => {
             console.error('Error changing status:', error);
             alert('Ошибка при изменении статуса');
         })
         .finally(() => {
             // Restore button state
             submitBtn.disabled = false;
             submitBtn.innerHTML = originalText;
         });
     }

     function showStatusHistoryModal(orderId) {
         const modal = document.getElementById('statusHistoryModal');
         const orderInfo = document.getElementById('historyOrderInfo');
         const historyContent = document.getElementById('statusHistoryContent');
         
         if (modal) {
             orderInfo.innerHTML = 'Загрузка информации о заказе...';
             historyContent.innerHTML = 'Загрузка истории...';
             modal.style.display = 'block';
             
             // Load status history
             fetch(`../assets/get_order_status_history.php?order_id=${orderId}`)
             .then(response => response.json())
             .then(data => {
                 if (data.success) {
                     displayStatusHistory(data);
                 } else {
                     orderInfo.innerHTML = 'Ошибка загрузки данных';
                     historyContent.innerHTML = 'Ошибка загрузки истории: ' + (data.error || 'Неизвестная ошибка');
                 }
             })
             .catch(error => {
                 console.error('Error loading status history:', error);
                 orderInfo.innerHTML = 'Ошибка загрузки данных';
                 historyContent.innerHTML = 'Ошибка загрузки истории';
             });
         }
     }

     function displayStatusHistory(data) {
         const orderInfo = document.getElementById('historyOrderInfo');
         const historyContent = document.getElementById('statusHistoryContent');
         const order = data.order_info;
         const history = data.history;
         
         orderInfo.innerHTML = `
             <div class="order-summary">
                 <div><strong>Номер заказа:</strong> ${escapeHtml(order.display_order_number)}</div>
                 <div><strong>Клиент:</strong> ${escapeHtml(order.client_name)}</div>
                 <div><strong>Дата заказа:</strong> ${formatDate(order.Order_date)}</div>
                 <div><strong>Текущий статус:</strong> ${escapeHtml(order.current_status)}</div>
             </div>
         `;
         
         if (history.length === 0) {
             historyContent.innerHTML = '<div class="no-history">История изменений пуста</div>';
             return;
         }
         
         let historyHTML = '<div class="status-history-timeline">';
         
         history.forEach((record, index) => {
             const isLatest = index === 0;
             historyHTML += `
                 <div class="history-item ${isLatest ? 'latest' : ''}">
                     <div class="history-date">${record.Change_date}</div>
                     <div class="history-content">
                         <div class="status-change">
                             ${record.previous_status_name ? 
                                 `<span class="status-badge" style="background-color: ${record.previous_status_color || '#ccc'}">${record.previous_status_name}</span>` : 
                                 '<span class="status-badge initial">Начальный статус</span>'
                             }
                             <i class="fas fa-arrow-right change-arrow"></i>
                             <span class="status-badge" style="background-color: ${record.new_status_color || '#ccc'}">${record.new_status_name}</span>
                         </div>
                         <div class="history-user">Изменено: ${record.changed_by_name || record.changed_by_login}</div>
                         ${record.Change_reason ? `<div class="history-reason">${escapeHtml(record.Change_reason)}</div>` : ''}
                     </div>
                 </div>
             `;
         });
         
         historyHTML += '</div>';
         historyContent.innerHTML = historyHTML;
     }
 }); 