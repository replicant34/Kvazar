document.addEventListener('DOMContentLoaded', function() {
    // Constants
    const ITEMS_PER_PAGE = 10;
    let currentPage = 1;
    let currentTab = 'client';
    let currentSort = { field: 'created_at', direction: 'desc' };
    let contractData = [];

    // Global currentPage for pagination
    window.currentPage = 1;

    // DOM Elements
    const tabButtons = document.querySelectorAll('.tab-btn');
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const table = document.getElementById('contractsTable');
    const tableBody = table.querySelector('tbody');
    const modal = document.getElementById('passwordModal');
    const actionPassword = document.getElementById('actionPassword');
    
    // Debug log for download button
    console.log('Looking for download button...');
    const downloadButton = document.getElementById('downloadContracts');
    console.log('Download button found:', downloadButton);

    if (downloadButton) {
        console.log('Adding click event to download button');
        downloadButton.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent immediate download
            console.log('Download button clicked');
            checkActionPassword('download_csv', () => {
                const filters = {
                    tab: currentTab,
                    search: searchInput.value,
                    type: typeFilter.value,
                    status: statusFilter.value,
                    dateFrom: dateFrom.value,
                    dateTo: dateTo.value
                };

                console.log('Download filters:', filters);
                const queryString = new URLSearchParams(filters).toString();
                window.location.href = '../assets/manage_contracts_download_csv.php?' + queryString;
            });
        });
    } else {
        console.error('Download button not found in the DOM');
    }

    // Action state
    let pendingAction = null;

    // Function Declarations
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
                loadContracts();
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
                    loadContracts();
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
                    loadContracts();
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
                    loadContracts();
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
                loadContracts();
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

    // Initialize
    loadContracts();
    generateTableHeaders();

    // Add specific event listener for cancel button
    const cancelButton = document.getElementById('cancelStatusChange');
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            document.getElementById('statusModal').style.display = 'none';
            window.currentContractForStatusChange = null;
        });
    }

    // Tab Switching
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            currentTab = button.dataset.tab;
            window.currentPage = 1;
            loadContracts();
        });
    });

    // Filtering
    const filterInputs = [searchInput, typeFilter, statusFilter, dateFrom, dateTo];
    filterInputs.forEach(input => {
        input.addEventListener('change', () => {
            window.currentPage = 1;
            loadContracts();
        });
    });

    searchInput.addEventListener('input', debounce(() => {
        window.currentPage = 1;
        loadContracts();
    }, 300));

    // Load Contracts
    function loadContracts() {
        const filters = {
            search: searchInput.value,
            type: typeFilter.value,
            status: statusFilter.value,
            dateFrom: dateFrom.value,
            dateTo: dateTo.value,
            page: window.currentPage || currentPage,
            perPage: ITEMS_PER_PAGE,
            sort: JSON.stringify(currentSort),
            tab: currentTab
        };

        const queryString = new URLSearchParams(filters).toString();
        console.log('Fetching with params:', queryString);

        fetch('../assets/manage_contracts_fetch_contracts.php?' + queryString)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    contractData = data.contracts;
                    console.log('Received data:', {
                        totalPages: data.totalPages,
                        totalCount: data.totalCount,
                        currentPage: data.currentPage,
                        contractsCount: data.contracts.length
                    });
                    renderContracts(data);
                    renderPagination(data.totalPages, window.currentPage || currentPage, data.totalCount);
                } else {
                    throw new Error(data.error || data.debug || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Full error details:', error);
                console.error('Error stack:', error.stack);
                alert('Error loading contracts: ' + error.message);
            });
    }

    // Render Contracts
    function renderContracts(data) {
        tableBody.innerHTML = '';
        
        data.contracts.forEach(contract => {
            const tr = document.createElement('tr');
            const hasFile = contract.file_path && contract.file_path.trim() !== '';
            const eyeButtonClass = hasFile ? 'btn-view' : 'btn-view disabled';
            const eyeButtonTitle = hasFile ? 'Просмотреть файл' : 'Файл не прикреплен';
            
            tr.innerHTML = `
                <td>
                    <button class="status-btn" data-id="${contract.id}" data-current-status="${contract.status_name}" 
                            style="border-color: ${contract.status_color || '#6c757d'}; color: ${contract.status_color || '#6c757d'};">
                        <span class="status-dot" style="background-color: ${contract.status_color || '#6c757d'}"></span>
                        ${contract.status_name || '-'}
                    </button>
                </td>
                <td>${contract.contract_number || '-'}</td>
                <td>${contract.company_name || '-'}</td>
                <td>${contract.contract_type || '-'}</td>
                <td>${formatDate(contract.contract_date)}</td>
                <td>${formatDate(contract.created_at)}</td>
                <td>${contract.created_by_name || '-'}</td>
                <td class="action-buttons">
                    <button class="${eyeButtonClass}" data-id="${contract.id}" data-file-path="${contract.file_path || ''}" title="${eyeButtonTitle}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-delete" data-id="${contract.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(tr);
        });

        // Add event listeners for status buttons
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', handleStatusClick);
        });

        // Add event listeners for view buttons
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', handleViewClick);
        });

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => handleAction('delete', btn.dataset.id));
        });
    }

    // Handle Status Click - shows status modal like in manage_partners.php
    function handleStatusClick(e) {
        const statusBtn = e.target.classList.contains('status-btn') ? 
            e.target : e.target.closest('.status-btn');
        
        if (!statusBtn) return;
        
        const contractId = statusBtn.dataset.id;
        const currentStatus = statusBtn.dataset.currentStatus;
        
        console.log('Status button clicked:', {
            contractId: contractId,
            currentStatus: currentStatus,
            entityType: currentTab
        });
        
        // Show status modal
        const statusModal = document.getElementById('statusModal');
        statusModal.style.display = 'block';
        
        // Store current contract info for status change
        window.currentContractForStatusChange = {
            id: contractId,
            entityType: currentTab,
            currentStatus: currentStatus
        };
        
        console.log('Contract info stored:', window.currentContractForStatusChange);
        
        // Add specific event listeners for status options
        const statusOptions = document.querySelectorAll('.status-option');
        statusOptions.forEach(option => {
            option.onclick = function() {
                const newStatus = this.dataset.status;
                console.log('Status option clicked:', newStatus);
                
                if (window.currentContractForStatusChange && newStatus) {
                    updateContractStatus(
                        window.currentContractForStatusChange.id, 
                        window.currentContractForStatusChange.entityType, 
                        newStatus
                    );
                    statusModal.style.display = 'none';
                    window.currentContractForStatusChange = null;
                }
            };
        });
    }

    // Handle View Click - opens file preview modal
    function handleViewClick(e) {
        const viewBtn = e.target.classList.contains('btn-view') ? 
            e.target : e.target.closest('.btn-view');
        
        if (!viewBtn) return;
        
        // Check if button is disabled
        if (viewBtn.classList.contains('disabled')) {
            return;
        }
        
        const contractId = viewBtn.dataset.id;
        const filePath = viewBtn.dataset.filePath;
        
        console.log('View button clicked:', {
            contractId: contractId,
            filePath: filePath,
            entityType: currentTab
        });
        
        if (filePath && filePath.trim() !== '') {
            // Show file preview modal
            showFilePreview(contractId, filePath);
        } else {
            alert('Файл не найден');
        }
    }

    // Show File Preview Modal
    function showFilePreview(contractId, filePath) {
        const modal = document.getElementById('filePreviewModal');
        const container = document.getElementById('filePreviewContainer');
        const title = document.getElementById('filePreviewTitle');
        const downloadBtn = document.getElementById('downloadFileBtn');
        
        // Show loading state
        container.innerHTML = '<div class="file-loading"><i class="fas fa-spinner"></i> Загрузка файла...</div>';
        modal.style.display = 'block';
        
        // Get file extension
        const fileExtension = filePath.split('.').pop().toLowerCase();
        const fileName = filePath.split('/').pop();
        
        // Set title
        title.textContent = `Просмотр файла: ${fileName}`;
        
        // Set download button
        downloadBtn.onclick = function() {
            const fullPath = '../' + filePath;
            window.open(fullPath, '_blank');
        };
        
        // Create preview URL
        const previewUrl = `../assets/preview_contract_file.php?contract_id=${contractId}&entity_type=${currentTab}`;
        
        // Handle different file types
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'].includes(fileExtension)) {
            // Images - display directly
            container.innerHTML = `<img src="${previewUrl}" alt="${fileName}" onerror="handleFileError()">`;
        } else if (fileExtension === 'pdf') {
            // PDFs - use iframe
            container.innerHTML = `<iframe src="${previewUrl}" onload="handleFileLoad()" onerror="handleFileError()"></iframe>`;
        } else if (['doc', 'docx', 'xls', 'xlsx'].includes(fileExtension)) {
            // Office documents - show message with download option
            container.innerHTML = `
                <div class="file-error">
                    <i class="fas fa-file-alt"></i>
                    <div>
                        <p>Предварительный просмотр файлов Microsoft Office не поддерживается в браузере.</p>
                        <p>Используйте кнопку "Скачать" для загрузки файла.</p>
                    </div>
                </div>
            `;
        } else {
            // Other files - show message with download option
            container.innerHTML = `
                <div class="file-error">
                    <i class="fas fa-file"></i>
                    <div>
                        <p>Предварительный просмотр файлов типа .${fileExtension} не поддерживается.</p>
                        <p>Используйте кнопку "Скачать" для загрузки файла.</p>
                    </div>
                </div>
            `;
        }
    }

    // Handle file load success
    function handleFileLoad() {
        console.log('File loaded successfully');
    }

    // Handle file load error
    function handleFileError() {
        const container = document.getElementById('filePreviewContainer');
        container.innerHTML = `
            <div class="file-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <p>Ошибка загрузки файла</p>
                    <p>Файл не найден или поврежден</p>
                </div>
            </div>
        `;
    }

    // Update Contract Status - remains the same
    function updateContractStatus(contractId, entityType, newStatus) {
        console.log('updateContractStatus called with:', {
            contractId: contractId,
            entityType: entityType,
            newStatus: newStatus
        });
        
        fetch('../assets/manage_contracts_update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                contract_id: contractId,
                entity_type: entityType,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Status update response:', data);
            if (data.success) {
                loadContracts(); // Reload the table
            } else {
                throw new Error(data.error || 'Failed to update status');
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            // Reload contracts to reset the select to its previous value
            loadContracts();
            alert(error.message || 'Error updating status');
        });
    }

    // Handle Action
    function handleAction(action, contractId) {
        if (action === 'delete') {
            checkActionPassword('delete', () => {
                deleteContract(contractId);
            });
        }
    }

    // Delete Contract
    function deleteContract(contractId) {
        if (!confirm('Вы уверены, что хотите удалить этот договор?')) {
            return;
        }

        fetch('../assets/manage_contracts_delete_contract.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                contract_id: contractId,
                entity_type: currentTab
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadContracts(); // Reload the contracts list
                alert('Договор успешно удален');
            } else {
                throw new Error(data.error || 'Failed to delete contract');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting contract: ' + error.message);
        });
    }

    // Password Confirmation
    function checkActionPassword(actionType, callback) {
        // First check if password is required for this action
        fetch('../assets/manage_contracts_check_password.php', {
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
            if (data.request_password === 1) {
                // Password is required, show modal
                pendingAction = { callback, actionType };
                modal.style.display = 'block';
                actionPassword.value = '';
                actionPassword.focus();
            } else {
                // Password not required, execute callback directly
                callback();
            }
        })
        .catch(error => {
            console.error('Error checking password requirement:', error);
            // If error, assume password is required
            pendingAction = { callback, actionType };
            modal.style.display = 'block';
            actionPassword.value = '';
            actionPassword.focus();
        });
    }

    // Modal Events - Remove non-existent elements
    // Note: These elements don't exist in the HTML, so we'll remove these event listeners
    // The password modal uses a form submit instead of button clicks
    
    // Password form submit handler
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!pendingAction) return;
        
        // Create the password verification request
        const requestData = {
            action_type: pendingAction.actionType,
            password: actionPassword.value
        };
        
        console.log('Submitting password for action:', pendingAction.actionType);
        
        fetch('../assets/manage_contracts_verify_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.isValid) {
                modal.style.display = 'none';
                pendingAction.callback();
                pendingAction = null;
            } else {
                alert('Неверный пароль');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка проверки пароля');
        });
    });
    
    // Password modal close button
    document.getElementById('passwordModalClose').addEventListener('click', function() {
        modal.style.display = 'none';
        pendingAction = null;
    });
    
    // Status Modal Event Handlers
    document.addEventListener('click', function(e) {
        // Handle status option clicks (including clicks on child elements)
        const statusOption = e.target.classList.contains('status-option') ? 
            e.target : e.target.closest('.status-option');
        
        if (statusOption) {
            const newStatus = statusOption.dataset.status;
            const contractInfo = window.currentContractForStatusChange;
            
            console.log('Status option clicked:', {
                statusOption: statusOption,
                newStatus: newStatus,
                contractInfo: contractInfo
            });
            
            if (contractInfo && newStatus) {
                updateContractStatus(contractInfo.id, contractInfo.entityType, newStatus);
                document.getElementById('statusModal').style.display = 'none';
                window.currentContractForStatusChange = null;
            } else {
                console.log('Missing data:', { contractInfo, newStatus });
            }
        }
        
        if (e.target.id === 'cancelStatusChange') {
            document.getElementById('statusModal').style.display = 'none';
            window.currentContractForStatusChange = null;
        }
    });

    // Close status modal when clicking outside
    document.getElementById('statusModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            window.currentContractForStatusChange = null;
        }
    });

    // File Preview Modal Event Handlers (moved here to ensure DOM is ready)
    const filePreviewClose = document.getElementById('filePreviewClose');
    const closeFilePreview = document.getElementById('closeFilePreview');
    const filePreviewModal = document.getElementById('filePreviewModal');
    
    console.log('File preview elements found:', {
        filePreviewClose: filePreviewClose,
        closeFilePreview: closeFilePreview,
        filePreviewModal: filePreviewModal
    });
    
    if (filePreviewClose) {
        filePreviewClose.addEventListener('click', function() {
            console.log('File preview close X button clicked');
            document.getElementById('filePreviewModal').style.display = 'none';
        });
    }
    
    if (closeFilePreview) {
        closeFilePreview.addEventListener('click', function() {
            console.log('File preview close button clicked');
            document.getElementById('filePreviewModal').style.display = 'none';
        });
    }
    
    if (filePreviewModal) {
        filePreviewModal.addEventListener('click', function(e) {
            if (e.target === this) {
                console.log('File preview modal outside click');
                this.style.display = 'none';
            }
        });
    }

    // Utility Functions
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return date.toLocaleDateString('en-GB'); // or your preferred locale
    }

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

    function updateSortIcons() {
        document.querySelectorAll('th[data-sort] i').forEach(icon => {
            const th = icon.closest('th');
            if (th.dataset.sort === currentSort.field) {
                icon.className = `fas fa-sort-${currentSort.direction === 'asc' ? 'up' : 'down'}`;
            } else {
                icon.className = 'fas fa-sort';
            }
        });
    }

    // Generate Table Headers
    function generateTableHeaders() {
        const headerRow = document.getElementById('tableHeaders');
        if (!headerRow) return;
        
        headerRow.innerHTML = `
            <th data-sort="status">
                Статус
                <i class="fas fa-sort"></i>
            </th>
            <th data-sort="contract_number">
                Номер договора
                <i class="fas fa-sort"></i>
            </th>
            <th data-sort="company_name">
                Название компании
                <i class="fas fa-sort"></i>
            </th>
            <th data-sort="contract_type">
                Тип договора
                <i class="fas fa-sort"></i>
            </th>
            <th data-sort="contract_date">
                Дата договора
                <i class="fas fa-sort"></i>
            </th>
            <th data-sort="created_at">
                Дата создания
                <i class="fas fa-sort"></i>
            </th>
            <th data-sort="created_by">
                Создано
                <i class="fas fa-sort"></i>
            </th>
            <th>
                Действия
            </th>
        `;
        
        // Add sort event listeners
        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                const field = th.dataset.sort;
                if (currentSort.field === field) {
                    currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort = { field, direction: 'asc' };
                }
                updateSortIcons();
                loadContracts();
            });
        });
        
        updateSortIcons();
    }
}); 