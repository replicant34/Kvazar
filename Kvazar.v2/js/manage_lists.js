// Lists Management JavaScript

class ListsManager {
    constructor() {
        this.currentTab = 'contractors';
        this.tables = {};
        this.tableConfigs = this.getTableConfigurations();
        this.init();
    }

    init() {
        this.initTabs();
        this.loadTableData();
        this.setupEventListeners();
    }

    // Tab switching functionality
    initTabs() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tabId = e.target.getAttribute('data-tab');
                this.switchTab(tabId);
            });
        });
    }

    switchTab(tabId) {
        // Remove active class from all tabs and contents
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        // Add active class to selected tab and content
        document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
        document.getElementById(`${tabId}-tab`).classList.add('active');

        this.currentTab = tabId;
        
        // Load table data if not already loaded
        if (!this.tables[tabId]) {
            this.loadTableData(tabId);
        }
    }

    // Table configurations for all lists
    getTableConfigurations() {
        return {
            'contractors': {
                table: 'Contractors',
                columns: ['Contractors_id', 'Company_type', 'Full_Company_name', 'Short_Company_name', 'INN', 'KPP', 'OGRN', 'Physical_address', 'Legal_address', 'Bank_name', 'BIK', 'Settlement_account', 'Correspondent_account', 'Contact_person', 'Contact_person_position', 'Contact_person_phone', 'Contact_person_email', 'Head_position', 'Head_name'],
                displayColumns: ['Contractors_id', 'Company_type', 'Full_Company_name', 'Short_Company_name', 'INN'],
                editableColumns: ['Company_type', 'Full_Company_name', 'Short_Company_name', 'INN', 'KPP', 'OGRN', 'Physical_address', 'Legal_address', 'Bank_name', 'BIK', 'Settlement_account', 'Correspondent_account', 'Contact_person', 'Contact_person_position', 'Contact_person_phone', 'Contact_person_email', 'Head_position', 'Head_name'],
                primaryKey: 'Contractors_id',
                complex: true,
                groups: {
                    basic: ['Company_type', 'Full_Company_name', 'Short_Company_name', 'INN', 'KPP', 'OGRN'],
                    contact: ['Physical_address', 'Legal_address', 'Contact_person', 'Contact_person_position', 'Contact_person_phone', 'Contact_person_email', 'Head_position', 'Head_name'],
                    banking: ['Bank_name', 'BIK', 'Settlement_account', 'Correspondent_account']
                }
            },
            'extra-services': {
                table: 'list_extra_service',
                columns: ['id', 'service_name', 'price', 'created_at', 'updated_at'],
                displayColumns: ['id', 'service_name', 'price', 'created_at'],
                editableColumns: ['service_name', 'price'],
                primaryKey: 'id'
            },
            'action-passwords': {
                table: 'list_actions_passwords',
                columns: ['Action_id', 'Action_name', 'Action_password', 'request_password', 'Created_at', 'Created_by'],
                displayColumns: ['Action_id', 'Action_name', 'request_password', 'Action_password'],
                editableColumns: ['Action_name', 'request_password', 'Action_password'],
                primaryKey: 'Action_id'
            },
            'cargo-names': {
                table: 'list_cargo_name',
                columns: ['id', 'cargo_name'],
                displayColumns: ['id', 'cargo_name'],
                editableColumns: ['cargo_name'],
                primaryKey: 'id'
            },
            'contract-status': {
                table: 'list_contract_status',
                columns: ['Status_id', 'Status_name', 'Status_color', 'Created_at', 'Created_by'],
                displayColumns: ['Status_id', 'Status_name', 'Status_color'],
                editableColumns: ['Status_name', 'Status_color'],
                primaryKey: 'Status_id'
            },
            'contract-types': {
                table: 'list_contract_type',
                columns: ['Type_id', 'Type_name', 'Created_at', 'Created_by'],
                displayColumns: ['Type_id', 'Type_name'],
                editableColumns: ['Type_name'],
                primaryKey: 'Type_id'
            },
            'currencies': {
                table: 'list_currency',
                columns: ['id', 'currency_code', 'currency_name', 'currency_symbol'],
                displayColumns: ['id', 'currency_code', 'currency_name', 'currency_symbol'],
                editableColumns: ['currency_code', 'currency_name', 'currency_symbol'],
                primaryKey: 'id'
            },
            'loading-types': {
                table: 'list_loading_type',
                columns: ['id', 'loading_name'],
                displayColumns: ['id', 'loading_name'],
                editableColumns: ['loading_name'],
                primaryKey: 'id'
            },
            'order-status': {
                table: 'list_order_status',
                columns: ['Status_id', 'Status_name', 'Status_color', 'Created_at', 'Created_by'],
                displayColumns: ['Status_id', 'Status_name', 'Status_color'],
                editableColumns: ['Status_name', 'Status_color'],
                primaryKey: 'Status_id'
            },
            'packaging-types': {
                table: 'list_packaging_type',
                columns: ['id', 'packaging_name'],
                displayColumns: ['id', 'packaging_name'],
                editableColumns: ['packaging_name'],
                primaryKey: 'id'
            },
            'partner-status': {
                table: 'list_partners_status',
                columns: ['Status_id', 'Status_name', 'Status_color', 'Created_at'],
                displayColumns: ['Status_id', 'Status_name', 'Status_color'],
                editableColumns: ['Status_name', 'Status_color'],
                primaryKey: 'Status_id'
            },
            'rates': {
                table: 'list_rates',
                columns: ['id', 'contract_number', 'rate', 'currency_id', 'created_at', 'updated_at', 'coef_transport_rate', 'coef_overwork_rate'],
                displayColumns: ['id', 'contract_number', 'rate', 'currency_id', 'coef_transport_rate', 'coef_overwork_rate'],
                editableColumns: ['contract_number', 'rate', 'currency_id', 'coef_transport_rate', 'coef_overwork_rate'],
                primaryKey: 'id',
                hasFilters: true,
                relationships: {'currency_id': 'list_currency'}
            },
            'shipping-types': {
                table: 'list_shipping_type',
                columns: ['Type_id', 'Type_name', 'Created_at', 'Updated_at'],
                displayColumns: ['Type_id', 'Type_name'],
                editableColumns: ['Type_name'],
                primaryKey: 'Type_id'
            },
            'transport-types': {
                table: 'list_transport_type',
                columns: ['Type_id', 'Type_name', 'Max_load_ton', 'Max_load_kg', 'Max_capacity_m3', 'Refrigerator', 'Min_temp', 'Max_temp', 'transport_rate', 'hours', 'overwork_rate'],
                displayColumns: ['Type_id', 'Type_name', 'Max_load_ton', 'Max_capacity_m3', 'Refrigerator'],
                editableColumns: ['Type_name', 'Max_load_ton', 'Max_load_kg', 'Max_capacity_m3', 'Refrigerator', 'Min_temp', 'Max_temp', 'transport_rate', 'hours', 'overwork_rate'],
                primaryKey: 'Type_id'
            },
            'vehicles': {
                table: 'Vehicles',
                columns: ['Vehicle_id', 'Courier_id', 'Brand', 'Plate_number'],
                displayColumns: ['Vehicle_id', 'Brand', 'Plate_number', 'Courier_id'],
                editableColumns: ['Brand', 'Plate_number', 'Courier_id'],
                primaryKey: 'Vehicle_id',
                hasFilters: true,
                relationships: {'Courier_id': 'Couriers'}
            },
            'drivers': {
                table: 'Drivers',
                columns: ['Driver_id', 'Courier_id', 'Name', 'Phone_number', 'Passport'],
                displayColumns: ['Driver_id', 'Name', 'Phone_number', 'Courier_id'],
                editableColumns: ['Name', 'Phone_number', 'Passport', 'Courier_id'],
                primaryKey: 'Driver_id',
                hasFilters: true,
                relationships: {'Courier_id': 'Couriers'}
            }
        };
    }

    // Load table data
    async loadTableData(tabId = null) {
        const targetTab = tabId || this.currentTab;
        const config = this.tableConfigs[targetTab];
        
        if (!config) return;

        try {
            this.showLoading(targetTab);
            
            const response = await fetch('../assets/manage_lists_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'load',
                    table: config.table,
                    columns: config.columns
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.tables[targetTab] = data.data;
                this.renderTable(targetTab, data.data);
                
                // Load relationships data if needed
                if (config.relationships) {
                    await this.loadRelationshipData(targetTab);
                }
            } else {
                throw new Error(data.message || 'Failed to load data');
            }
        } catch (error) {
            console.error('Error loading table data:', error);
            this.showError(targetTab, 'Ошибка загрузки данных: ' + error.message);
        }
    }

    // Load relationship data for dropdowns
    async loadRelationshipData(tabId) {
        const config = this.tableConfigs[tabId];
        
        for (const [column, relTable] of Object.entries(config.relationships || {})) {
            try {
                const response = await fetch('../assets/manage_lists_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'load_relationship',
                        table: relTable
                    })
                });

                const data = await response.json();
                if (data.success) {
                    // Store relationship data for use in dropdowns
                    if (!this.relationshipData) this.relationshipData = {};
                    this.relationshipData[relTable] = data.data;
                }
            } catch (error) {
                console.error(`Error loading relationship data for ${relTable}:`, error);
            }
        }
    }

    // Render table
    renderTable(tabId, data) {
        const config = this.tableConfigs[tabId];
        const table = document.getElementById(`${tabId}-table`);
        const thead = table.querySelector('thead tr');
        const tbody = table.querySelector('tbody');

        // Clear existing content
        thead.innerHTML = '';
        tbody.innerHTML = '';

        // Render header
        config.displayColumns.forEach(column => {
            const th = document.createElement('th');
            th.textContent = this.getColumnDisplayName(column);
            thead.appendChild(th);
        });
        
        // Add actions column
        const actionsHeader = document.createElement('th');
        actionsHeader.textContent = 'Действия';
        actionsHeader.style.width = '120px';
        thead.appendChild(actionsHeader);

        // Render data rows
        data.forEach(row => {
            const tr = document.createElement('tr');
            
            config.displayColumns.forEach(column => {
                const td = document.createElement('td');
                const value = row[column] || '';
                
                if (config.editableColumns.includes(column)) {
                    td.className = 'editable';
                    td.setAttribute('data-column', column);
                    td.setAttribute('data-id', row[config.primaryKey]);
                    td.setAttribute('data-table', tabId);
                    td.textContent = value;
                    
                    // Add inline editing
                    td.addEventListener('click', () => this.startInlineEdit(td));
                } else {
                    td.textContent = value;
                }
                
                tr.appendChild(td);
            });

            // Add action buttons
            const actionsTd = document.createElement('td');
            actionsTd.className = 'action-buttons';
            actionsTd.innerHTML = `
                <button class="btn btn-warning btn-sm" onclick="editItem('${tabId}', ${row[config.primaryKey]})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteItem('${tabId}', ${row[config.primaryKey]})">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            tr.appendChild(actionsTd);
            
            tbody.appendChild(tr);
        });

        // Add filters if needed
        if (config.hasFilters) {
            this.addTableFilters(tabId);
        }
    }

    // Add table filters
    addTableFilters(tabId) {
        const tabContent = document.getElementById(`${tabId}-tab`);
        const tableContainer = tabContent.querySelector('.table-container');
        
        // Remove existing filters
        const existingFilters = tabContent.querySelector('.table-filters');
        if (existingFilters) {
            existingFilters.remove();
        }

        const filtersDiv = document.createElement('div');
        filtersDiv.className = 'table-filters';
        
        const config = this.tableConfigs[tabId];
        
        // Add relationship filters
        if (config.relationships) {
            Object.entries(config.relationships).forEach(([column, relTable]) => {
                const filterGroup = document.createElement('div');
                filterGroup.className = 'filter-group';
                
                const label = document.createElement('label');
                label.textContent = this.getColumnDisplayName(column);
                
                const select = document.createElement('select');
                select.className = 'filter-select';
                select.setAttribute('data-column', column);
                
                // Add empty option
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = 'Все';
                select.appendChild(emptyOption);
                
                // Add options from relationship data
                if (this.relationshipData && this.relationshipData[relTable]) {
                    this.relationshipData[relTable].forEach(item => {
                        const option = document.createElement('option');
                        const keys = Object.keys(item);
                        option.value = item[keys[0]]; // First column as value
                        option.textContent = item[keys[1]] || item[keys[0]]; // Second column as text, fallback to first
                        select.appendChild(option);
                    });
                }
                
                select.addEventListener('change', () => this.applyFilters(tabId));
                
                filterGroup.appendChild(label);
                filterGroup.appendChild(select);
                filtersDiv.appendChild(filterGroup);
            });
        }

        // Add search filter
        const searchGroup = document.createElement('div');
        searchGroup.className = 'filter-group';
        
        const searchLabel = document.createElement('label');
        searchLabel.textContent = 'Поиск';
        
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'filter-search';
        searchInput.placeholder = 'Введите для поиска...';
        searchInput.addEventListener('input', () => this.applyFilters(tabId));
        
        searchGroup.appendChild(searchLabel);
        searchGroup.appendChild(searchInput);
        filtersDiv.appendChild(searchGroup);

        tableContainer.parentNode.insertBefore(filtersDiv, tableContainer);
    }

    // Apply filters
    applyFilters(tabId) {
        const config = this.tableConfigs[tabId];
        const table = document.getElementById(`${tabId}-table`);
        const rows = table.querySelectorAll('tbody tr');
        const filters = document.querySelectorAll(`#${tabId}-tab .filter-select`);
        const search = document.querySelector(`#${tabId}-tab .filter-search`);
        
        const filterValues = {};
        filters.forEach(filter => {
            const column = filter.getAttribute('data-column');
            const value = filter.value;
            if (value) filterValues[column] = value;
        });
        
        const searchTerm = search ? search.value.toLowerCase() : '';
        
        rows.forEach(row => {
            let visible = true;
            
            // Apply relationship filters
            Object.entries(filterValues).forEach(([column, value]) => {
                const columnIndex = config.displayColumns.indexOf(column);
                if (columnIndex >= 0) {
                    const cellValue = row.cells[columnIndex].textContent;
                    if (cellValue !== value) {
                        visible = false;
                    }
                }
            });
            
            // Apply search filter
            if (searchTerm && visible) {
                const rowText = Array.from(row.cells).map(cell => cell.textContent.toLowerCase()).join(' ');
                if (!rowText.includes(searchTerm)) {
                    visible = false;
                }
            }
            
            row.style.display = visible ? '' : 'none';
        });
    }

    // Inline editing
    startInlineEdit(cell) {
        if (cell.classList.contains('editing')) return;
        
        const originalValue = cell.textContent;
        const column = cell.getAttribute('data-column');
        const tabId = cell.getAttribute('data-table');
        const config = this.tableConfigs[tabId];
        
        cell.classList.add('editing');
        
        // Check if this column has a relationship
        if (config.relationships && config.relationships[column]) {
            const select = document.createElement('select');
            const relTable = config.relationships[column];
            
            if (this.relationshipData && this.relationshipData[relTable]) {
                this.relationshipData[relTable].forEach(item => {
                    const option = document.createElement('option');
                    const keys = Object.keys(item);
                    option.value = item[keys[0]];
                    option.textContent = item[keys[1]] || item[keys[0]];
                    if (option.value == originalValue) option.selected = true;
                    select.appendChild(option);
                });
            }
            
            cell.innerHTML = '';
            cell.appendChild(select);
            select.focus();
            
            const finishEdit = () => {
                const newValue = select.value;
                cell.classList.remove('editing');
                cell.textContent = newValue;
                
                if (newValue !== originalValue) {
                    this.saveInlineEdit(cell, newValue);
                }
            };
            
            select.addEventListener('blur', finishEdit);
            select.addEventListener('change', finishEdit);
        } else {
            const input = document.createElement('input');
            input.value = originalValue;
            input.type = 'text';
            
            cell.innerHTML = '';
            cell.appendChild(input);
            input.focus();
            input.select();
            
            const finishEdit = () => {
                const newValue = input.value;
                cell.classList.remove('editing');
                cell.textContent = newValue;
                
                if (newValue !== originalValue) {
                    this.saveInlineEdit(cell, newValue);
                }
            };
            
            input.addEventListener('blur', finishEdit);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') finishEdit();
                if (e.key === 'Escape') {
                    cell.classList.remove('editing');
                    cell.textContent = originalValue;
                }
            });
        }
    }

    // Save inline edit
    async saveInlineEdit(cell, newValue) {
        const column = cell.getAttribute('data-column');
        const id = cell.getAttribute('data-id');
        const tabId = cell.getAttribute('data-table');
        const config = this.tableConfigs[tabId];
        
        try {
            const response = await fetch('../assets/manage_lists_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update',
                    table: config.table,
                    id: id,
                    column: column,
                    value: newValue,
                    primaryKey: config.primaryKey
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to save changes');
            } else {
                this.showNotification('Изменения сохранены', 'success');
            }
        } catch (error) {
            console.error('Error saving inline edit:', error);
            this.showNotification('Ошибка сохранения: ' + error.message, 'error');
            this.loadTableData(tabId); // Reload to revert changes
        }
    }

    // Helper functions
    getColumnDisplayName(column) {
        const displayNames = {
            'Contractors_id': 'ID',
            'Company_type': 'Тип',
            'Full_Company_name': 'Полное название',
            'Short_Company_name': 'Короткое название',
            'INN': 'ИНН',
            'KPP': 'КПП',
            'OGRN': 'ОГРН',
            'Physical_address': 'Адрес',
            'Legal_address': 'Юридический адрес',
            'Bank_name': 'Банк',
            'BIK': 'БИК',
            'Settlement_account': 'Расчетный счет',
            'Correspondent_account': 'Корреспондентский счет',
            'Contact_person': 'Контактное лицо',
            'Contact_person_position': 'Должность',
            'Contact_person_phone': 'Телефон',
            'Contact_person_email': 'Email',
            'Head_position': 'Должность руководителя',
            'Head_name': 'ФИО руководителя',
            'id': 'ID',
            'service_name': 'Название услуги',
            'price': 'Цена',
            'created_at': 'Создано',
            'updated_at': 'Обновлено',
            'Action_id': 'ID действия',
            'Action_name': 'Название действия',
            'Action_password': 'Пароль действия',
            'request_password': 'Требуется пароль',
            'Created_at': 'Создано',
            'Created_by': 'Создано пользователем',
            'cargo_name': 'Название груза',
            'Status_id': 'ID статуса',
            'Status_name': 'Название статуса',
            'Status_color': 'Цвет статуса',
            'Type_id': 'ID типа',
            'Type_name': 'Название типа',
            'currency_code': 'Код валюты',
            'currency_name': 'Название валюты',
            'currency_symbol': 'Символ валюты',
            'loading_name': 'Тип загрузки',
            'contract_number': 'Номер договора',
            'rate': 'Тариф',
            'coef_transport_rate': 'Коэффициент транспортного тарифа',
            'coef_overwork_rate': 'Коэффициент переработки',
            'Max_load_ton': 'Грузоподъемность (тонны)',
            'Max_load_kg': 'Грузоподъемность (кг)',
            'Max_capacity_m3': 'Объем (м³)',
            'Refrigerator': 'Холодильник',
            'Min_temp': 'Минимальная температура',
            'Max_temp': 'Максимальная температура',
            'transport_rate': 'Тариф за час',
            'hours': 'Часы',
            'overwork_rate': 'Тариф за переработку',
            'Vehicle_id': 'ID',
            'Courier_id': 'Курьер',
            'Brand': 'Марка',
            'Plate_number': 'Номер',
            'Driver_id': 'ID',
            'Name': 'Имя',
            'Phone_number': 'Телефон',
            'Passport': 'Паспорт'
        };
        
        return displayNames[column] || column;
    }

    showLoading(tabId) {
        const tbody = document.querySelector(`#${tabId}-table tbody`);
        tbody.innerHTML = '<tr><td colspan="100%" class="loading"><i class="fas fa-spinner"></i><br>Загрузка данных...</td></tr>';
    }

    showError(tabId, message) {
        const tbody = document.querySelector(`#${tabId}-table tbody`);
        tbody.innerHTML = `<tr><td colspan="100%" class="loading" style="color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i><br>${message}</td></tr>`;
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'}"></i>
            ${message}
            <button class="notification-close">&times;</button>
        `;
        
        // Add to body
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
        
        // Manual close
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
    }

    setupEventListeners() {
        // Modal close
        window.closeModal = () => {
            document.getElementById('itemModal').style.display = 'none';
        };

        // Form submission
        document.getElementById('itemForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveItem();
        });
    }

    // Modal operations
    openModal(mode, tabId, id = null) {
        const config = this.tableConfigs[tabId];
        const modal = document.getElementById('itemModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalFormContent = document.getElementById('modalFormContent');
        
        modalTitle.textContent = mode === 'add' ? 'Добавить элемент' : 'Редактировать элемент';
        
        // Clear form content
        modalFormContent.innerHTML = '';
        
        // Generate form fields
        if (config.complex && tabId === 'contractors') {
            this.generateContractorForm(modalFormContent, mode, id);
        } else {
            this.generateSimpleForm(modalFormContent, config, mode, id);
        }
        
        modal.style.display = 'block';
        
        // Store modal state
        modal.setAttribute('data-mode', mode);
        modal.setAttribute('data-tab', tabId);
        modal.setAttribute('data-id', id || '');
    }

    generateSimpleForm(container, config, mode, id) {
        config.editableColumns.forEach(column => {
            const formGroup = document.createElement('div');
            formGroup.className = 'form-group';
            
            const label = document.createElement('label');
            label.textContent = this.getColumnDisplayName(column);
            label.setAttribute('for', column);
            
            let input;
            
            // Check if this column has a relationship
            if (config.relationships && config.relationships[column]) {
                input = document.createElement('select');
                input.className = 'form-control';
                
                // Add empty option
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = 'Выберите...';
                input.appendChild(emptyOption);
                
                // Add relationship options
                const relTable = config.relationships[column];
                if (this.relationshipData && this.relationshipData[relTable]) {
                    this.relationshipData[relTable].forEach(item => {
                        const option = document.createElement('option');
                        const keys = Object.keys(item);
                        option.value = item[keys[0]];
                        option.textContent = item[keys[1]] || item[keys[0]];
                        input.appendChild(option);
                    });
                }
            } else {
                // Regular input field
                input = document.createElement('input');
                input.type = this.getInputType(column);
                input.className = 'form-control';
                
                // Special handling for boolean fields
                if (column === 'request_password') {
                    input.type = 'checkbox';
                    input.className = 'form-check-input';
                    input.value = '1';
                }
            }
            
            input.id = column;
            input.name = column;
            
            // Set value for edit mode
            if (mode === 'edit' && id) {
                const tableData = this.tables[container.getAttribute('data-tab')] || [];
                const item = tableData.find(row => row[config.primaryKey] == id);
                if (item && item[column] !== undefined) {
                    if (input.type === 'checkbox') {
                        input.checked = item[column] == '1';
                    } else {
                        input.value = item[column];
                    }
                }
            }
            
            formGroup.appendChild(label);
            formGroup.appendChild(input);
            container.appendChild(formGroup);
        });
    }

    generateContractorForm(container, mode, id) {
        const config = this.tableConfigs['contractors'];
        
        // Group fields logically
        Object.entries(config.groups).forEach(([groupName, fields]) => {
            const groupHeader = document.createElement('div');
            groupHeader.className = 'form-group-header';
            groupHeader.textContent = this.getGroupDisplayName(groupName);
            container.appendChild(groupHeader);
            
            fields.forEach(column => {
                const formGroup = document.createElement('div');
                formGroup.className = 'form-group';
                
                const label = document.createElement('label');
                label.textContent = this.getColumnDisplayName(column);
                label.setAttribute('for', column);
                
                const input = document.createElement('input');
                input.type = this.getInputType(column);
                input.className = 'form-control';
                input.id = column;
                input.name = column;
                
                // Set required fields
                if (['Full_Company_name', 'INN'].includes(column)) {
                    input.required = true;
                }
                
                // Set value for edit mode
                if (mode === 'edit' && id) {
                    const tableData = this.tables['contractors'] || [];
                    const item = tableData.find(row => row[config.primaryKey] == id);
                    if (item && item[column] !== undefined) {
                        input.value = item[column];
                    }
                }
                
                formGroup.appendChild(label);
                formGroup.appendChild(input);
                container.appendChild(formGroup);
            });
        });
    }

    getGroupDisplayName(groupName) {
        const groupNames = {
            'basic': 'Основная информация',
            'contact': 'Контактная информация',
            'banking': 'Банковские реквизиты'
        };
        return groupNames[groupName] || groupName;
    }

    getInputType(column) {
        const typeMap = {
            'Contact_person_email': 'email',
            'Contact_person_phone': 'tel',
            'Phone_number': 'tel',
            'INN': 'text',
            'KPP': 'text',
            'OGRN': 'text',
            'BIK': 'text',
            'Settlement_account': 'text',
            'Correspondent_account': 'text',
            'price': 'number',
            'rate': 'number',
            'coef_transport_rate': 'number',
            'coef_overwork_rate': 'number',
            'Max_load_ton': 'number',
            'Max_load_kg': 'number',
            'Max_capacity_m3': 'number',
            'Min_temp': 'number',
            'Max_temp': 'number',
            'transport_rate': 'number',
            'hours': 'number',
            'overwork_rate': 'number',
            'Status_color': 'color'
        };
        return typeMap[column] || 'text';
    }

    async saveItem() {
        const modal = document.getElementById('itemModal');
        const mode = modal.getAttribute('data-mode');
        const tabId = modal.getAttribute('data-tab');
        const id = modal.getAttribute('data-id');
        const config = this.tableConfigs[tabId];
        
        // Collect form data
        const formData = {};
        const form = document.getElementById('itemForm');
        
        config.editableColumns.forEach(column => {
            const field = form.querySelector(`[name="${column}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    formData[column] = field.checked ? '1' : '0';
                } else {
                    formData[column] = field.value;
                }
            }
        });
        
        try {
            let response;
            
            if (mode === 'add') {
                response = await fetch('../assets/manage_lists_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'create',
                        table: config.table,
                        data: formData
                    })
                });
            } else {
                // For edit mode, we need to update each field individually
                // This is a simplified approach - could be optimized
                const updates = [];
                
                for (const [column, value] of Object.entries(formData)) {
                    updates.push(fetch('../assets/manage_lists_data.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update',
                            table: config.table,
                            id: id,
                            column: column,
                            value: value,
                            primaryKey: config.primaryKey
                        })
                    }));
                }
                
                await Promise.all(updates);
                response = { ok: true };
            }
            
            if (response.ok || response) {
                this.showNotification(mode === 'add' ? 'Элемент добавлен' : 'Изменения сохранены', 'success');
                this.closeModal();
                this.loadTableData(tabId);
            } else {
                throw new Error('Failed to save item');
            }
        } catch (error) {
            console.error('Error saving item:', error);
            this.showNotification('Ошибка сохранения: ' + error.message, 'error');
        }
    }

    async deleteItem(tabId, id) {
        const config = this.tableConfigs[tabId];
        
        try {
            const response = await fetch('../assets/manage_lists_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    table: config.table,
                    id: id,
                    primaryKey: config.primaryKey
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Элемент удален', 'success');
                this.loadTableData(tabId);
            } else {
                throw new Error(data.message || 'Failed to delete item');
            }
        } catch (error) {
            console.error('Error deleting item:', error);
            this.showNotification('Ошибка удаления: ' + error.message, 'error');
        }
    }

    async downloadCSV(tabId) {
        const config = this.tableConfigs[tabId];
        
        try {
            const response = await fetch('../assets/manage_lists_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'download_csv',
                    table: config.table
                })
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${config.table}_${new Date().toISOString().slice(0,10)}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.showNotification('CSV файл скачан', 'success');
            } else {
                throw new Error('Failed to download CSV');
            }
        } catch (error) {
            console.error('Error downloading CSV:', error);
            this.showNotification('Ошибка скачивания CSV: ' + error.message, 'error');
        }
    }

    openContractorModal(mode, id = null) {
        this.openModal(mode, 'contractors', id);
    }

    closeModal() {
        document.getElementById('itemModal').style.display = 'none';
    }
}

// Global functions for button clicks
window.addItem = function(tabId) {
    const manager = window.listsManager;
    const config = manager.tableConfigs[tabId];
    
    if (config.complex && tabId === 'contractors') {
        window.addContractor();
    } else {
        manager.openModal('add', tabId);
    }
};

window.editItem = function(tabId, id) {
    const manager = window.listsManager;
    manager.openModal('edit', tabId, id);
};

window.deleteItem = function(tabId, id) {
    if (confirm('Вы уверены, что хотите удалить этот элемент?')) {
        window.listsManager.deleteItem(tabId, id);
    }
};

window.downloadCSV = function(tabId) {
    window.listsManager.downloadCSV(tabId);
};

window.addContractor = function() {
    window.listsManager.openContractorModal('add');
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.listsManager = new ListsManager();
});

// Add CSS for notifications
const style = document.createElement('style');
style.textContent = `
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    padding: 15px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 10001;
    min-width: 300px;
    border-left: 4px solid #007bff;
    animation: slideIn 0.3s ease;
}

.notification-success {
    border-left-color: #28a745;
    background: #d4edda;
    color: #155724;
}

.notification-error {
    border-left-color: #dc3545;
    background: #f8d7da;
    color: #721c24;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    margin-left: auto;
    opacity: 0.7;
}

.notification-close:hover {
    opacity: 1;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
`;
document.head.appendChild(style); 