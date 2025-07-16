// Global dropdown toggle function
function toggleDropdown(button) {
    // Prevent event bubbling
    event.stopPropagation();
    
    const dropdown = button.closest('.dropdown');
    const content = dropdown.querySelector('.dropdown-content');
    const icon = button.querySelector('i');
    
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-content').forEach(dd => {
        if (dd !== content) {
            dd.classList.remove('show');
            const otherIcon = dd.previousElementSibling.querySelector('i');
            if (otherIcon) otherIcon.classList.remove('rotated');
        }
    });
    
    // Toggle current dropdown
    content.classList.toggle('show');
    icon.classList.toggle('rotated');
    
    // Position the dropdown if it's being shown
    if (content.classList.contains('show')) {
        const buttonRect = button.getBoundingClientRect();
        const contentWidth = 280; // min-width from CSS
        
        // Always position below the button for consistency
        let top = buttonRect.bottom + 5;
        let left = buttonRect.left + (buttonRect.width / 2) - (contentWidth / 2);
        
        // If dropdown would go below viewport, position it at the bottom with some margin
        if (top + 350 > window.innerHeight) {
            top = window.innerHeight - 360; // 350px height + 10px margin
        }
        
        // Ensure dropdown doesn't go outside viewport horizontally
        if (left < 10) {
            left = 10;
        } else if (left + contentWidth > window.innerWidth - 10) {
            left = window.innerWidth - contentWidth - 10;
        }
        
        console.log('Final position:', { top, left });
        
        // Apply the calculated position
        content.style.top = top + 'px';
        content.style.left = left + 'px';
        
        // Close dropdown when clicking outside
        setTimeout(() => {
            const closeDropdown = function(e) {
                if (!dropdown.contains(e.target)) {
                    content.classList.remove('show');
                    icon.classList.remove('rotated');
                    document.removeEventListener('click', closeDropdown);
                }
            };
            document.addEventListener('click', closeDropdown);
        }, 0);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Constants
    window.currentPage = 1;
    let currentTab = 'client';
    let currentSort = { field: 'created_at', direction: 'desc' };
    let visibleColumns = new Set([
        'status', 'company_type', 'full_name', 'short_name', 'contracts',
        'inn', 'kpp', 'ogrn', 'physical_address', 'legal_address',
        'bank_name', 'bik', 'settlement_account', 'correspondent_account',
        'contact_person', 'contact_position', 'contact_phone', 'contact_email',
        'head_position', 'head_name'
    ]); // Default visible columns
    let idColumn = 'Client_id'; // Default for client tab

    // Responsive pagination based on screen size
    function getItemsPerPage() {
        const screenWidth = window.innerWidth;
        
        if (screenWidth <= 768) {
            return 10; // Mobile: 10 rows
        } else if (screenWidth <= 1440) {
            return 14; // 13-inch screens: 14 rows (increased breakpoint)
        } else {
            return 20; // Bigger screens: 20 rows
        }
    }

    let ITEMS_PER_PAGE = getItemsPerPage();

    // Render pagination controls
    function renderPagination(totalPages, currentPage, totalCount) {
        const paginationContainer = document.getElementById('paginationContainer');
        if (!paginationContainer) {
            // Create pagination container if it doesn't exist
            const tableContainer = document.getElementById('tableContainer');
            const paginationDiv = document.createElement('div');
            paginationDiv.id = 'paginationContainer';
            paginationDiv.className = 'pagination-container';
            tableContainer.parentNode.insertBefore(paginationDiv, tableContainer.nextSibling);
        }

        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';

        // Always show pagination, even if only one page
        const paginationDiv = document.createElement('div');
        paginationDiv.className = 'pagination';

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i> Предыдущая';
        prevBtn.disabled = currentPage === 1 || totalPages <= 1;
        prevBtn.onclick = function() {
            if (currentPage > 1 && totalPages > 1) {
                window.currentPage = currentPage - 1;
                loadPartners();
            }
        };
        paginationDiv.appendChild(prevBtn);

        // Page numbers
        const pageNumbers = document.createElement('div');
        pageNumbers.className = 'page-numbers';

        if (totalPages <= 1) {
            // Show single page button when only one page
            const singlePageBtn = document.createElement('button');
            singlePageBtn.className = 'pagination-btn active';
            singlePageBtn.textContent = '1';
            singlePageBtn.disabled = true;
            pageNumbers.appendChild(singlePageBtn);
        } else {
            // Calculate range of pages to show
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);

            // Always show first page if not in range
            if (startPage > 1) {
                const firstPageBtn = document.createElement('button');
                firstPageBtn.className = 'pagination-btn';
                firstPageBtn.textContent = '1';
                firstPageBtn.onclick = function() {
                    window.currentPage = 1;
                    loadPartners();
                };
                pageNumbers.appendChild(firstPageBtn);

                if (startPage > 2) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'pagination-ellipsis';
                    ellipsis.textContent = '...';
                    pageNumbers.appendChild(ellipsis);
                }
            }

            // Show page numbers in range
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = 'pagination-btn';
                if (i === currentPage) {
                    pageBtn.classList.add('active');
                }
                pageBtn.textContent = i;
                pageBtn.onclick = function() {
                    window.currentPage = i;
                    loadPartners();
                };
                pageNumbers.appendChild(pageBtn);
            }

            // Always show last page if not in range
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'pagination-ellipsis';
                    ellipsis.textContent = '...';
                    pageNumbers.appendChild(ellipsis);
                }

                const lastPageBtn = document.createElement('button');
                lastPageBtn.className = 'pagination-btn';
                lastPageBtn.textContent = totalPages;
                lastPageBtn.onclick = function() {
                    window.currentPage = totalPages;
                    loadPartners();
                };
                pageNumbers.appendChild(lastPageBtn);
            }
        }

        paginationDiv.appendChild(pageNumbers);

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.innerHTML = 'Следующая <i class="fas fa-chevron-right"></i>';
        nextBtn.disabled = currentPage === totalPages || totalPages <= 1;
        nextBtn.onclick = function() {
            if (currentPage < totalPages && totalPages > 1) {
                window.currentPage = currentPage + 1;
                loadPartners();
            }
        };
        paginationDiv.appendChild(nextBtn);

        // Page info
        const pageInfo = document.createElement('div');
        pageInfo.className = 'page-info';
        const startItem = (currentPage - 1) * ITEMS_PER_PAGE + 1;
        const endItem = Math.min(currentPage * ITEMS_PER_PAGE, totalCount);
        pageInfo.textContent = `Показано ${startItem}-${endItem} из ${totalCount} записей`;
        paginationDiv.appendChild(pageInfo);

        container.appendChild(paginationDiv);
    }

    // Column definitions
    const columnDefs = {
        common: [
            { id: 'status', label: 'Статус', sortable: true },
            { id: 'company_type', label: 'Тип компании', sortable: true, field: 'Company_type' },
            { id: 'full_name', label: 'Полное название', sortable: true, field: 'Full_Company_name' },
            { id: 'short_name', label: 'Сокращенное название', sortable: true, field: 'Short_Company_name' },
            { id: 'contracts', label: 'Контракты', sortable: false },
            { id: 'inn', label: 'ИНН', sortable: true, field: 'INN' },
            { id: 'kpp', label: 'КПП', sortable: true, field: 'KPP' },
            { id: 'ogrn', label: 'ОГРН', sortable: true, field: 'OGRN' },
            { id: 'physical_address', label: 'Фактический адрес', sortable: true, field: 'Physical_address' },
            { id: 'legal_address', label: 'Юридический адрес', sortable: true, field: 'Legal_address' },
            { id: 'bank_name', label: 'Наименование банка', sortable: true, field: 'Bank_name' },
            { id: 'bik', label: 'БИК', sortable: true, field: 'BIK' },
            { id: 'settlement_account', label: 'Расчетный счет', sortable: true, field: 'Settlement_account' },
            { id: 'correspondent_account', label: 'Корреспондентский счет', sortable: true, field: 'Correspondent_account' },
            { id: 'contact_person', label: 'Контактное лицо', sortable: true, field: 'Contact_person' },
            { id: 'contact_position', label: 'Должность контакта', sortable: true, field: 'Contact_person_position' },
            { id: 'contact_phone', label: 'Телефон контакта', sortable: true, field: 'Contact_person_phone' },
            { id: 'contact_email', label: 'Email контакта', sortable: true, field: 'Contact_person_email' },
            { id: 'head_position', label: 'Должность руководителя', sortable: true, field: 'Head_position' },
            { id: 'head_name', label: 'Имя руководителя', sortable: true, field: 'Head_name' },
            { id: 'created_at', label: 'Дата создания', sortable: true },
            { id: 'updated_at', label: 'Дата обновления', sortable: true }
        ],
        courier: [
            { id: 'drivers', label: 'Водители', sortable: false },
            { id: 'vehicles', label: 'Транспорт', sortable: false }
        ],
        client: [
            { id: 'orders', label: 'Заказы', sortable: false }
        ]
    };

    // DOM Elements
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const bankFilter = document.getElementById('bankFilter');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const columnSettingsBtn = document.getElementById('columnSettingsBtn');
    const columnSettingsModal = document.getElementById('columnSettingsModal');
    const columnCheckboxes = document.getElementById('columnCheckboxes');
    const downloadButton = document.getElementById('downloadPartners');
    const passwordModal = document.getElementById('passwordModal');
    const actionPassword = document.getElementById('actionPassword');
    const statusModal = document.getElementById('statusModal');
    const cancelStatusChange = document.getElementById('cancelStatusChange');

    // Initialize column settings
    function initializeColumnSettings() {
        columnCheckboxes.innerHTML = '';
        const columns = [...columnDefs.common];
        
        if (currentTab === 'courier') {
            columns.push(...columnDefs.courier);
        } else if (currentTab === 'client') {
            columns.push(...columnDefs.client);
        }

        columns.forEach(col => {
            const div = document.createElement('div');
            div.className = 'checkbox-item';
            div.innerHTML = `
                <input type="checkbox" id="col_${col.id}" 
                       ${visibleColumns.has(col.id) ? 'checked' : ''}>
                <label for="col_${col.id}">${col.label}</label>
            `;
            columnCheckboxes.appendChild(div);
        });
    }

    // Load partners data
    function loadPartners() {
        ITEMS_PER_PAGE = getItemsPerPage();
        
        const filters = {
            tab: currentTab,
            page: window.currentPage,
            perPage: ITEMS_PER_PAGE,
            search: searchInput.value,
            status: statusFilter.value,
            bank: bankFilter.value,
            dateFrom: dateFrom.value,
            dateTo: dateTo.value,
            sort: JSON.stringify(currentSort)
        };

        fetch('../assets/manage_partners_fetch_partners.php?' + new URLSearchParams(filters))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderTable(data.partners);
                    renderPagination(data.totalPages, data.currentPage, data.totalCount);
                }
            })
            .catch(error => {
                // Handle fetch error silently
            });
    }

    // Render table with data
    function renderTable(partners) {
        const tbody = document.querySelector('#partnersTable tbody');
        tbody.innerHTML = '';

        partners.forEach(partner => {
            const tr = document.createElement('tr');
            tr.innerHTML = generateTableRow(partner);
            tbody.appendChild(tr);
        });
    }

    // Generate table row HTML
    function generateTableRow(partner) {
        let html = '';
        
        // Debug: Check which ID column is being used
        console.log('Generating row for partner:', { 
            partnerId: partner[idColumn], 
            idColumn: idColumn, 
            currentTab: currentTab,
            partner: partner 
        });
        
        columnDefs.common.forEach(col => {
            if (visibleColumns.has(col.id)) {
                if (col.id === 'status') {
                    html += `<td>
                        <button class="status-btn" data-id="${partner[idColumn]}" data-current-status="${partner.Status}" 
                                style="border-color: ${partner.status_color}; color: ${partner.status_color};">
                            <span class="status-dot" style="background-color: ${partner.status_color}"></span>
                            ${partner.status_name}
                        </button>
                    </td>`;
                } else if (col.id === 'contracts') {
                    html += generateContractsCell(partner.contracts);
                } else {
                    const value = col.field ? partner[col.field] : partner[col.id];
                    html += `<td title="${value || '-'}">${value || '-'}</td>`;
                }
            }
        });

        // Add tab-specific columns
        if (currentTab === 'courier' && partner.drivers) {
            if (visibleColumns.has('drivers')) {
                html += generateDriversCell(partner.drivers);
            }
            if (visibleColumns.has('vehicles')) {
                html += generateVehiclesCell(partner.vehicles);
            }
        } else if (currentTab === 'client' && partner.orders) {
            if (visibleColumns.has('orders')) {
                html += generateOrdersCell(partner.orders);
            }
        }

        // Add actions column at the end with both edit and save card buttons
        html += `
            <td class="actions-column">
                <button class="edit-btn" data-id="${partner[idColumn]}" title="Редактировать">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="save-card-btn" data-id="${partner[idColumn]}" data-type="${currentTab}" title="Сохранить карточку партнера">
                    <i class="fas fa-file-pdf"></i>
                </button>
            </td>
        `;

        return html;
    }

    // Generate cells for special columns
    function generateContractsCell(contracts) {
        if (!contracts || !contracts.length) {
            return '<td>-</td>';
        }
        
        // Always show dropdown for consistent UI
        const contractsList = contracts.map(c => {
            const downloadButton = c.file_path ? 
                `<button class="download-btn" onclick="downloadContractFile(${c.id}, '${currentTab}')" title="Скачать файл контракта">
                    <i class="fas fa-download"></i>
                    <span>Скачать</span>
                </button>` : '';
            
            return `<div class="contract-item">
                <div class="contract-info">
                    <span class="contract-number">#${c.number || 'N/A'}</span>
                    <span class="contract-date">${c.date || 'N/A'}</span>
                    <span class="contract-type">${c.type || 'N/A'}</span>
                    <span class="status-dot" style="background-color: ${c.status_color || '#6c757d'}"></span>
                </div>
                ${downloadButton}
            </div>`;
        }).join('');

        return `
            <td>
                <div class="dropdown">
                    <button class="dropdown-btn" onclick="toggleDropdown(this)">
                        <span>Контракты (${contracts.length})</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-content">
                        ${contractsList}
                    </div>
                </div>
            </td>
        `;
    }

    // Generate drivers cell
    function generateDriversCell(drivers) {
        if (!drivers || !drivers.length) return '<td>-</td>';
        
        const driversList = drivers.map(d => 
            `<div>${d.name}</div>`
        ).join('');

        return `
            <td>
                <div class="dropdown">
                    <span>Drivers (${drivers.length})</span>
                    <div class="dropdown-content">${driversList}</div>
                </div>
            </td>
        `;
    }

    // Generate vehicles cell
    function generateVehiclesCell(vehicles) {
        if (!vehicles || !vehicles.length) return '<td>-</td>';
        
        const vehiclesList = vehicles.map(v => 
            `<div>${v.brand} (${v.plate_number})</div>`
        ).join('');

        return `
            <td>
                <div class="dropdown">
                    <span>Vehicles (${vehicles.length})</span>
                    <div class="dropdown-content">${vehiclesList}</div>
                </div>
            </td>
        `;
    }

    // Generate orders cell
    function generateOrdersCell(orders) {
        if (!orders || !orders.length) return '<td>-</td>';
        
        const ordersList = orders.map(o => 
            `<div>#${o.id} (${o.status})</div>`
        ).join('');

        return `
            <td>
                <div class="dropdown">
                    <span>Orders (${orders.length})</span>
                    <div class="dropdown-content">${ordersList}</div>
                </div>
            </td>
        `;
    }

    // Password protection for actions
    let pendingAction = null;

    function checkActionPassword(action, callback) {
        // First check if password is required for this action
        let actionType;
        if (action === 'download') {
            actionType = 'download_csv'; // This will map to Action_id 3
        } else if (action === 'edit') {
            actionType = 'edit'; // This will map to Action_id 2
        } else {
            actionType = action;
        }
        
        fetch('../assets/manage_partners_check_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action_type: actionType
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.request_password === 0) {
                // No password required, execute callback directly
                callback();
            } else if (data.request_password === 1) {
                // Password required, show password modal
                showPasswordModal(action, callback);
            } else {
                // Error or invalid action
                alert('Ошибка: действие не найдено');
            }
        })
        .catch(error => {
            alert('Ошибка проверки действия');
        });
    }

    function showPasswordModal(action, callback) {
        const passwordModal = document.getElementById('passwordModal');
        const actionPassword = document.getElementById('actionPassword');
        const passwordForm = document.getElementById('passwordForm');
        const passwordError = document.getElementById('passwordError');
        
        // Reset password input and error
        actionPassword.value = '';
        passwordError.style.display = 'none';
        
        // Set action type
        document.getElementById('actionType').value = action;
        
        // Show password modal
        passwordModal.style.display = 'block';
        actionPassword.focus();

        // Handle form submission
        passwordForm.onsubmit = function(e) {
            e.preventDefault();
            const password = actionPassword.value;
            
            // Send password to server for verification
            fetch('../assets/manage_partners_verify_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action_type: action === 'download' ? 'download_csv' : action,
                    action_password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    passwordModal.style.display = 'none';
                    callback();
                } else {
                    passwordError.textContent = data.error || 'Неверный пароль';
                    passwordError.style.display = 'block';
                    actionPassword.value = '';
                    actionPassword.focus();
                }
            })
            .catch(error => {
                passwordError.textContent = 'Ошибка проверки пароля';
                passwordError.style.display = 'block';
            });
        };

        // Handle close button
        document.getElementById('passwordModalClose').onclick = function() {
            passwordModal.style.display = 'none';
        };

        // Handle clicking outside modal
        passwordModal.onclick = function(e) {
            if (e.target === passwordModal) {
                passwordModal.style.display = 'none';
            }
        };
    }

    // Column visibility handlers
    columnSettingsBtn.addEventListener('click', () => {
        initializeColumnSettings();
        columnSettingsModal.style.display = 'block';
    });

    document.getElementById('applyColumnSettings').addEventListener('click', () => {
        visibleColumns.clear();
        document.querySelectorAll('#columnCheckboxes input:checked').forEach(checkbox => {
            visibleColumns.add(checkbox.id.replace('col_', ''));
        });
        columnSettingsModal.style.display = 'none';
        generateTableHeaders();
        loadPartners();
    });

    // Add cancel button handler for column settings
    document.getElementById('cancelColumnSettings').addEventListener('click', () => {
        columnSettingsModal.style.display = 'none';
    });

    // Tab switching
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Update current tab
            currentTab = this.dataset.tab;
            
            // Update ID column based on tab
            switch(currentTab) {
                case 'client':
                    idColumn = 'Client_id';
                    break;
                case 'courier':
                    idColumn = 'Courier_id';
                    break;
                case 'agent':
                    idColumn = 'Agent_id';
                    break;
            }
            
            // Reset to first page when switching tabs
            window.currentPage = 1;
            
            // Reload partners for new tab
            loadPartners();
            
            // Update column settings for new tab
            initializeColumnSettings();
            
            // Regenerate headers for new tab
            generateTableHeaders();
        });
    });

    // Sort handlers
    document.querySelectorAll('th[data-sort]').forEach(th => {
        th.addEventListener('click', () => {
            const field = th.dataset.sort;
            if (currentSort.field === field) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.field = field;
                currentSort.direction = 'asc';
            }
            loadPartners();
        });
    });

    // Filter functionality
    statusFilter.addEventListener('change', () => {
        window.currentPage = 1;
        loadPartners();
    });

    bankFilter.addEventListener('change', () => {
        window.currentPage = 1;
        loadPartners();
    });

    dateFrom.addEventListener('change', () => {
        window.currentPage = 1;
        loadPartners();
    });

    dateTo.addEventListener('change', () => {
        window.currentPage = 1;
        loadPartners();
    });

    // Search functionality with debouncing
    const debouncedSearch = debounce(() => {
        window.currentPage = 1;
        loadPartners();
    }, 300);

    searchInput.addEventListener('input', debouncedSearch);

    // Download functionality
    downloadButton.addEventListener('click', () => {
        checkActionPassword('download', () => {
            const filters = {
                tab: currentTab,
                search: searchInput.value,
                status: statusFilter.value,
                bank: bankFilter.value,
                dateFrom: dateFrom.value,
                dateTo: dateTo.value
            };
            
            const downloadUrl = '../assets/manage_partners_download_csv.php?' + new URLSearchParams(filters);
            window.open(downloadUrl, '_blank');
        });
    });

    // Edit/Delete handlers
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-btn')) {
            const partnerId = e.target.dataset.id;
            checkActionPassword('edit', () => {
                // Fetch partner data and show edit modal
                fetch(`../assets/manage_partners_fetch_partner.php?id=${partnerId}&type=${currentTab}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Basic Information
                            document.getElementById('editId').value = partnerId;
                            document.getElementById('editType').value = currentTab;
                            document.getElementById('editCompanyType').value = data.Company_type;
                            document.getElementById('editFullName').value = data.Full_Company_name;
                            document.getElementById('editShortName').value = data.Short_Company_name;
                            document.getElementById('editStatus').value = data.Status;
                            document.getElementById('editINN').value = data.INN;
                            document.getElementById('editKPP').value = data.KPP;
                            document.getElementById('editOGRN').value = data.OGRN;
                            
                            // Address Information
                            document.getElementById('editPhysicalAddress').value = data.Physical_address;
                            document.getElementById('editLegalAddress').value = data.Legal_address;
                            
                            // Bank Information
                            document.getElementById('editBankName').value = data.Bank_name;
                            document.getElementById('editBIK').value = data.BIK;
                            document.getElementById('editSettlementAccount').value = data.Settlement_account;
                            document.getElementById('editCorrespondentAccount').value = data.Correspondent_account;
                            
                            // Contact Information
                            document.getElementById('editContactPerson').value = data.Contact_person;
                            document.getElementById('editContactPersonPosition').value = data.Contact_person_position;
                            document.getElementById('editContactPersonPhone').value = data.Contact_person_phone;
                            document.getElementById('editContactPersonEmail').value = data.Contact_person_email;
                            document.getElementById('editHeadPosition').value = data.Head_position;
                            document.getElementById('editHeadName').value = data.Head_name;

                            // Update status color
                            const statusSelect = document.getElementById('editStatus');
                            const selectedOption = statusSelect.options[statusSelect.selectedIndex];
                            if (selectedOption.dataset.color) {
                                statusSelect.style.borderColor = selectedOption.dataset.color;
                            }

                            document.getElementById('editModal').style.display = 'block';
                        } else {
                            alert('Error loading partner data');
                        }
                    })
                    .catch(error => {
                        alert('Error loading partner data');
                    });
            });
        } else if (e.target.classList.contains('save-card-btn') || e.target.closest('.save-card-btn')) {
            // Handle both direct clicks and clicks on child elements (like the icon)
            const saveCardBtn = e.target.classList.contains('save-card-btn') ? 
                e.target : e.target.closest('.save-card-btn');
            
            const partnerId = saveCardBtn.dataset.id;
            const partnerType = saveCardBtn.dataset.type;
            
            console.log('Save card button clicked:', { partnerId, partnerType, currentTab });
            
            if (!partnerId || !partnerType) {
                console.error('Missing partner data:', { partnerId, partnerType });
                alert('Ошибка: отсутствуют данные партнера');
                return;
            }
            
            // Download PDF directly without password check for now
            const downloadUrl = `../assets/generate_partner_pdf.php?id=${partnerId}&type=${partnerType}`;
            console.log('Opening URL:', downloadUrl);
            window.open(downloadUrl, '_blank');
        }
    });

    // Add form submit handler
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            id: document.getElementById('editId').value,
            type: document.getElementById('editType').value
        };

        // Convert form data to object
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        fetch('../assets/manage_partners_edit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('editModal').style.display = 'none';
                loadPartners(); // Reload the table
            } else {
                alert('Error updating partner');
            }
        })
        .catch(error => {
            alert('Error updating partner');
        });
    });

    // Add status select color change handler
    document.getElementById('editStatus').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const color = selectedOption.dataset.color;
        this.closest('.status-select-wrapper').style.setProperty('--status-color', color);
    });

    // Utility function for debouncing
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Add this function to generate table headers
    function generateTableHeaders() {
        const headerRow = document.getElementById('tableHeaders');
        let html = '';

        // Add visible columns
        columnDefs.common.forEach(col => {
            if (visibleColumns.has(col.id)) {
                const sortable = col.sortable ? ` data-sort="${col.id}"` : '';
                const sortIcon = col.sortable ? ' <i class="fas fa-sort"></i>' : '';
                html += `<th${sortable}>${col.label}${sortIcon}</th>`;
            }
        });

        // Add tab-specific columns
        if (currentTab === 'courier') {
            columnDefs.courier.forEach(col => {
                if (visibleColumns.has(col.id)) {
                    html += `<th>${col.label}</th>`;
                }
            });
        } else if (currentTab === 'client') {
            columnDefs.client.forEach(col => {
                if (visibleColumns.has(col.id)) {
                    html += `<th>${col.label}</th>`;
                }
            });
        }

        // Add actions column
        html += '<th>Действия</th>';
        
        headerRow.innerHTML = html;

        // Reattach sort handlers
        attachSortHandlers();
    }

    // Add this function to reattach sort handlers
    function attachSortHandlers() {
        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                const field = th.dataset.sort;
                if (currentSort.field === field) {
                    currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort.field = field;
                    currentSort.direction = 'asc';
                }
                loadPartners();
            });
        });
    }

    // Add these event listeners after other initialization code
    let currentStatusButton = null;

    // Update the status button click handler
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('status-btn') || e.target.closest('.status-btn')) {
            const statusBtn = e.target.classList.contains('status-btn') ? 
                e.target : e.target.closest('.status-btn');
            
            currentStatusButton = statusBtn;
            statusModal.style.display = 'block';
        } else if (e.target.classList.contains('status-option')) {
            const newStatusId = e.target.dataset.statusId;
            const partnerId = currentStatusButton.dataset.id;
            
            fetch('../assets/manage_partners_update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: partnerId,
                    type: currentTab,
                    status: newStatusId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusModal.style.display = 'none';
                    loadPartners(); // Reload the table
                } else {
                    alert('Error updating status');
                }
            })
            .catch(error => {
                alert('Error updating status');
            });
        }
    });

    // Close status modal
    cancelStatusChange.addEventListener('click', () => {
        statusModal.style.display = 'none';
    });

    // Initial load
    loadPartners();
    generateTableHeaders(); // Initial header generation

    // Tab scroll functionality
    const tabScrollLeft = document.getElementById('tabScrollLeft');
    const tabScrollRight = document.getElementById('tabScrollRight');
    const tableContainer = document.getElementById('tableContainer');

    if (tabScrollLeft && tabScrollRight && tableContainer) {
        // Scroll left
        tabScrollLeft.addEventListener('click', function() {
            tableContainer.scrollBy({
                left: -300,
                behavior: 'smooth'
            });
        });

        // Scroll right
        tabScrollRight.addEventListener('click', function() {
            tableContainer.scrollBy({
                left: 300,
                behavior: 'smooth'
            });
        });

        // Update scroll button states based on scroll position
        function updateScrollButtons() {
            const isAtStart = tableContainer.scrollLeft <= 0;
            const isAtEnd = tableContainer.scrollLeft >= (tableContainer.scrollWidth - tableContainer.clientWidth);
            
            tabScrollLeft.disabled = isAtStart;
            tabScrollRight.disabled = isAtEnd;
        }

        // Listen for scroll events to update button states
        tableContainer.addEventListener('scroll', updateScrollButtons);
        
        // Initial button state update
        updateScrollButtons();
    }

    // Handle window resize for responsive pagination
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            const newItemsPerPage = getItemsPerPage();
            if (newItemsPerPage !== ITEMS_PER_PAGE) {
                ITEMS_PER_PAGE = newItemsPerPage;
                currentPage = 1; // Reset to first page when changing items per page
                loadPartners();
            }
        }, 300); // Debounce resize events
    });

    // Download contract file function
    function downloadContractFile(contractId, contractType) {
        const url = `../assets/download_contract_file.php?contract_id=${contractId}&contract_type=${contractType}`;
        window.open(url, '_blank');
    }
}); 