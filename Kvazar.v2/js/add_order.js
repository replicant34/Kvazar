document.addEventListener('DOMContentLoaded', function() {
    // ==================== MESSAGE SYSTEM ====================
    
    // Create message container if it doesn't exist
    function createMessageContainer() {
        let container = document.getElementById('message-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'message-container';
            container.className = 'message-container';
            document.body.appendChild(container);
        }
        return container;
    }
    
    // Show message with different types
    function showMessage(message, type = 'info', title = null, duration = 5000) {
        const container = createMessageContainer();
        
        // Create message element
        const messageEl = document.createElement('div');
        messageEl.className = `message-toast message-${type}`;
        
        // Get icon based on type
        let icon;
        let defaultTitle;
        switch (type) {
            case 'success':
                icon = 'fas fa-check-circle';
                defaultTitle = 'Успешно';
                break;
            case 'error':
                icon = 'fas fa-exclamation-triangle';
                defaultTitle = 'Ошибка';
                break;
            case 'warning':
                icon = 'fas fa-exclamation-circle';
                defaultTitle = 'Предупреждение';
                break;
            case 'info':
            default:
                icon = 'fas fa-info-circle';
                defaultTitle = 'Информация';
                break;
        }
        
        const finalTitle = title || defaultTitle;
        
        messageEl.innerHTML = `
            <div class="message-icon">
                <i class="${icon}"></i>
            </div>
            <div class="message-content">
                <div class="message-title">${finalTitle}</div>
                <div class="message-text">${message}</div>
            </div>
            <button class="message-close" type="button">
                <i class="fas fa-times"></i>
            </button>
            <div class="message-progress"></div>
        `;
        
        // Add to container
        container.appendChild(messageEl);
        
        // Handle close button
        const closeBtn = messageEl.querySelector('.message-close');
        closeBtn.addEventListener('click', () => {
            hideMessage(messageEl);
        });
        
        // Handle click to close
        messageEl.addEventListener('click', () => {
            hideMessage(messageEl);
        });
        
        // Auto-hide after duration
        if (duration > 0) {
            setTimeout(() => {
                hideMessage(messageEl);
            }, duration);
        }
        
        return messageEl;
    }
    
    // Hide message with animation
    function hideMessage(messageEl) {
        if (messageEl && messageEl.parentElement) {
            messageEl.classList.add('message-hiding');
            setTimeout(() => {
                if (messageEl.parentElement) {
                    messageEl.parentElement.removeChild(messageEl);
                }
            }, 300);
        }
    }
    
    // Convenience functions
    function showSuccess(message, title = null, duration = 5000) {
        return showMessage(message, 'success', title, duration);
    }
    
    function showError(message, title = null, duration = 7000) {
        return showMessage(message, 'error', title, duration);
    }
    
    function showWarning(message, title = null, duration = 6000) {
        return showMessage(message, 'warning', title, duration);
    }
    
    function showInfo(message, title = null, duration = 5000) {
        return showMessage(message, 'info', title, duration);
    }
    
    // Show confirmation dialog
    function showConfirmation(message, title = 'Подтверждение', options = {}) {
        return new Promise((resolve) => {
            const dialog = document.createElement('div');
            dialog.className = 'confirmation-dialog';
            
            const {
                confirmText = 'Да',
                cancelText = 'Отмена',
                confirmType = 'primary',
                icon = 'fas fa-question-circle'
            } = options;
            
            dialog.innerHTML = `
                <div class="confirmation-content">
                    <div class="confirmation-icon">
                        <i class="${icon}"></i>
                    </div>
                    <h3 class="confirmation-title">${title}</h3>
                    <p class="confirmation-message">${message}</p>
                    <div class="confirmation-buttons">
                        <button class="confirmation-btn confirmation-btn-${confirmType}" data-action="confirm">
                            ${confirmText}
                        </button>
                        <button class="confirmation-btn confirmation-btn-secondary" data-action="cancel">
                            ${cancelText}
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(dialog);
            
            // Handle button clicks
            dialog.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                if (action) {
                    document.body.removeChild(dialog);
                    resolve(action === 'confirm');
                }
            });
            
            // Handle escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    document.removeEventListener('keydown', handleEscape);
                    document.body.removeChild(dialog);
                    resolve(false);
                }
            };
            
            document.addEventListener('keydown', handleEscape);
        });
    }
    
    // Show loading message
    function showLoading(message = 'Загрузка...', title = null) {
        const loadingMessage = showMessage(
            `<span class="message-loading"></span>${message}`, 
            'info', 
            title, 
            0 // Don't auto-hide
        );
        
        // Remove close button and progress bar for loading messages
        const closeBtn = loadingMessage.querySelector('.message-close');
        const progressBar = loadingMessage.querySelector('.message-progress');
        if (closeBtn) closeBtn.style.display = 'none';
        if (progressBar) progressBar.style.display = 'none';
        
        return loadingMessage;
    }
    
    // Make functions globally available
    window.showMessage = showMessage;
    window.showSuccess = showSuccess;
    window.showError = showError;
    window.showWarning = showWarning;
    window.showInfo = showInfo;
    window.showConfirmation = showConfirmation;
    window.showLoading = showLoading;
    window.hideMessage = hideMessage;

    // ==================== END MESSAGE SYSTEM ====================

    // Setup Russian calendar support
    setupRussianCalendar();
    // Tab switching - Bootstrap tabs are handled automatically by Bootstrap JS
    // No custom tab switching needed since we're using Bootstrap tabs

    // Client search functionality
    const clientSearch = document.getElementById('clientSearch');
    const clientIdInput = document.getElementById('client_id');
    
    if (clientSearch && clientIdInput) {
        // Create search results container
        const searchResults = document.createElement('div');
        searchResults.className = 'search-results';
        clientSearch.parentNode.appendChild(searchResults);

        let selectedIndex = -1;

        // Function to show search results
        function showSearchResults(searchTerm) {
            searchResults.innerHTML = '';
            // Fetch client options from a global JS variable or via AJAX
            // For now, use a global variable window.availableClients if present
            const options = (window.availableClients || []).filter(option => 
                option.name.toLowerCase().includes(searchTerm.toLowerCase())
            );

            options.forEach((option, index) => {
                const div = document.createElement('div');
                div.className = 'search-result-item';
                div.textContent = option.name;
                
                div.addEventListener('click', () => {
                    clientIdInput.value = option.id;
                    clientSearch.value = option.name;
                    searchResults.style.display = 'none';
                    handleClientSelection(option.id);
                });

                div.addEventListener('mouseover', () => {
                    selectedIndex = index;
                    updateSelectedItem();
                });

                searchResults.appendChild(div);
            });

            searchResults.style.display = options.length ? 'block' : 'none';
            selectedIndex = -1;
            updateSelectedItem();
        }

        function updateSelectedItem() {
            const items = searchResults.getElementsByClassName('search-result-item');
            Array.from(items).forEach((item, index) => {
                item.classList.toggle('selected', index === selectedIndex);
            });
        }

        // Input event handler
        clientSearch.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            // Only show results if there are at least 2 characters
            if (searchTerm.length >= 2) {
                showSearchResults(searchTerm);
            } else {
                searchResults.style.display = 'none';
            }
            // Clear hidden input when typing
            clientIdInput.value = '';
        });

        // Keyboard navigation
        clientSearch.addEventListener('keydown', function(e) {
            const items = searchResults.getElementsByClassName('search-result-item');
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    updateSelectedItem();
                    if (items[selectedIndex]) {
                        items[selectedIndex].scrollIntoView({ block: 'nearest' });
                    }
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelectedItem();
                    if (items[selectedIndex]) {
                        items[selectedIndex].scrollIntoView({ block: 'nearest' });
                    }
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        items[selectedIndex].click();
                    }
                    break;
                case 'Escape':
                    searchResults.style.display = 'none';
                    break;
            }
        });

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!clientSearch.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        // Show all options when clicking on empty input
        clientSearch.addEventListener('click', function() {
            if (!this.value.trim()) {
                showSearchResults('');
            }
        });

        // Show results if there's already text in the input on focus
        clientSearch.addEventListener('focus', function() {
            if (this.value.trim()) {
                showSearchResults(this.value);
            }
        });
    }

    // Helper: Make availableClients array from PHP
    if (!window.availableClients) {
        window.availableClients = [];
        // This will be filled by PHP below
    }

    // Add this after client selection code
    const contractInput = document.getElementById('contract');
    const contractMessage = document.querySelector('.contract-message');

    // Add these constants with other declarations
    const contractDateInput = document.getElementById('contract_date');
    const contractDateMessage = document.querySelector('.contract-date-message');

    // Function to fetch and handle contracts
    function fetchContracts(clientId) {
        console.log('Fetching contracts for client:', clientId);

        fetch(`../assets/add_order_get_clients_contracts.php?client_id=${clientId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data);

                if (data.success) {
                    const contracts = data.contracts || [];
                    const contractDate = data.contract_date;
                    
                    // Handle contract number
                    if (contracts.length === 0) {
                        contractInput.value = 'Договор не найден';
                        contractInput.classList.add('error');
                        contractInput.style.borderColor = '#e74c3c';
                        contractMessage.textContent = '';
                    } else {
                        contractInput.value = contracts[0];
                        contractInput.classList.remove('error');
                        contractInput.style.borderColor = '#3498db';
                        contractMessage.textContent = '';
                    }

                    // Handle contract date
                    if (contractDate) {
                        contractDateInput.value = contractDate;
                        contractDateInput.classList.remove('error');
                        contractDateInput.style.borderColor = '#3498db';
                        contractDateMessage.textContent = '';
                    } else {
                        contractDateInput.value = 'Дата не найдена';
                        contractDateInput.classList.add('error');
                        contractDateMessage.textContent = '';
                    }
                } else {
                    // Error handling for both fields
                    contractInput.value = 'Ошибка';
                    contractInput.classList.add('error');
                    contractInput.style.borderColor = '#e74c3c';
                    contractMessage.textContent = data.error || 'Ошибка при получении договоров';

                    contractDateInput.value = 'Ошибка';
                    contractDateInput.classList.add('error');
                    contractDateInput.style.borderColor = '#e74c3c';
                    contractDateMessage.textContent = '';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                // Error handling for both fields
                contractInput.value = 'Ошибка';
                contractInput.classList.add('error');
                contractInput.style.borderColor = '#e74c3c';
                contractMessage.textContent = 'Ошибка при получении договоров';

                contractDateInput.value = 'Ошибка';
                contractDateInput.classList.add('error');
                contractDateInput.style.borderColor = '#e74c3c';
                contractDateMessage.textContent = '';
            });
    }

    // Contractor handling - dropdown is already populated by PHP
    const contractorSelect = document.getElementById('contractor');
    
    // No need to fetch contractor data as it's already populated by PHP
    // The dropdown is already loaded with all contractors from the database

    // Add after the contractor code
    const orderNumberInput = document.getElementById('order_number');
    const generateButton = document.getElementById('generate_order_number');
    const orderNumberMessage = document.querySelector('.order-number-message');

    // Add form submission validation
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            if (orderNumberInput.classList.contains('error')) {
                e.preventDefault();
                showError('Пожалуйста, исправьте номер заказа перед отправкой', 'Неверный номер заказа');
            }
        });
    }

    // Add this to your existing client selection handler
    function handleClientSelection(clientId) {
        const contractStatic = document.getElementById('contract_static');
        const contractSelect = document.getElementById('contract_select');
        const contractMessage = document.querySelector('.contract-message');
        const contractHint = document.querySelector('.contract-hint');
        const contractDate = document.getElementById('contract_date');
        const contractMessageBar = document.querySelector('.contract-message-bar');

        if (!clientId) {
            // Reset and disable fields when no client selected
            contractStatic.style.display = 'none';
            contractSelect.style.display = 'block';
            contractStatic.disabled = true;
            contractSelect.disabled = true;
            contractDate.disabled = true;
            contractStatic.value = '';
            contractSelect.innerHTML = '<option value="">Выберите договор</option>';
            contractDate.value = '';
            contractMessage.textContent = '';
            contractHint.style.display = 'none';
            return;
        }

        fetch(`../assets/add_order_get_clients_contracts.php?client_id=${clientId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    contractMessage.textContent = 'Ошибка при загрузке договоров';
                    contractHint.style.display = 'none';
                    return;
                }

                if (!data.contracts || data.contracts.length === 0) {
                    contractMessageBar.innerHTML = `
                        <span class="contract-info-message">
                            <i class="fas fa-info-circle"></i>
                            Нет активных договоров. 
                            <a href="add_contract.php" target="_blank" style="margin-left: 4px; text-decoration: underline; color: #3498db; font-weight: 500;">Добавить договор</a>
                        </span>
                    `;
                    contractMessageBar.style.display = 'block';
                    contractHint.style.display = 'none';
                    contractStatic.style.display = 'none';
                    contractSelect.style.display = 'block';
                    contractStatic.disabled = true;
                    contractSelect.disabled = true;
                    contractDate.disabled = true;
                    contractDate.value = '';
                    return;
                } else {
                    contractMessageBar.innerHTML = '';
                    contractMessageBar.style.display = 'none';
                }

                // Clear previous message
                contractMessage.textContent = '';
                contractHint.style.display = 'none';

                if (data.contracts.length === 1) {
                    // Single contract - show static input
                    contractStatic.value = data.contracts[0].Contract_number;
                    contractDate.value = formatDate(data.contracts[0].Contract_date);
                    contractStatic.disabled = false;
                    contractSelect.disabled = true;
                    contractDate.disabled = false;
                    contractStatic.style.display = 'block';
                    contractSelect.style.display = 'none';
                } else {
                    // Multiple contracts - show select
                    contractSelect.innerHTML = '<option value="">Выберите договор</option>';
                    data.contracts.forEach(contract => {
                        const option = document.createElement('option');
                        option.value = contract.Contract_number;
                        option.textContent = contract.Contract_number;
                        option.dataset.date = contract.Contract_date;
                        contractSelect.appendChild(option);
                    });
                    contractSelect.disabled = false;
                    contractStatic.disabled = true;
                    contractDate.disabled = false;
                    contractSelect.style.display = 'block';
                    contractStatic.style.display = 'none';
                    
                    // Add change event listener for contract select
                    contractSelect.onchange = function() {
                        const selectedOption = this.options[this.selectedIndex];
                        if (selectedOption.value) {
                            contractDate.value = formatDate(selectedOption.dataset.date);
                        } else {
                            contractDate.value = '';
                        }
                    };
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contractMessage.textContent = 'Ошибка при загрузке договоров';
            });
    }

    // Helper function to format date (YYYY-MM-DD to DD.MM.YYYY)
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    // Order date handling
    const orderDateInput = document.getElementById('order_date');

    // Add after the order date handling code
    
    // Shipping type handling
    const shippingTypeSelect = document.getElementById('shipping_type');
    console.log('shippingTypeSelect found:', shippingTypeSelect);
    
    function fetchShippingTypes() {
        console.log('fetchShippingTypes called');
        fetch('../assets/add_order_get_shipping_types.php')
            .then(response => response.json())
            .then(data => {
                console.log('Shipping types data received:', data);
                if (data.error) {
                    throw new Error(data.error);
                }

                if (data.types && data.types.length > 0) {
                    // Keep the first "Select Shipping Type" option
                    shippingTypeSelect.innerHTML = '<option value="">Выберите тип перевозки</option>';
                    
                    // Add shipping types from database
                    data.types.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.Type_id;
                        option.textContent = type.Type_name;
                        shippingTypeSelect.appendChild(option);
                    });
                    console.log('Shipping types populated successfully');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                shippingTypeSelect.innerHTML = '<option value="">Ошибка при загрузке типов перевозки</option>';
            });
    }

    // Load shipping types when page loads
    if (shippingTypeSelect) {
        console.log('Calling fetchShippingTypes');
        fetchShippingTypes();
    } else {
        console.error('shippingTypeSelect not found!');
    }

    // Function to check if order number is unique
    function checkOrderNumber(number) {
        if (!number) {
            orderNumberInput.classList.remove('error');
            orderNumberMessage.textContent = '';
            return;
        }

        fetch(`../assets/add_order_check_order_number.php?order_number=${encodeURIComponent(number)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                if (!data.isUnique) {
                    orderNumberInput.classList.add('error');
                    orderNumberMessage.textContent = 'Этот номер заказа уже существует';
                } else {
                    orderNumberInput.classList.remove('error');
                    orderNumberMessage.textContent = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                orderNumberMessage.textContent = 'Ошибка при проверке номера заказа';
            });
    }

    // Generate new order number
    function generateOrderNumber() {
        fetch('../assets/add_order_generate_order_number.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                orderNumberInput.value = data.number;
                orderNumberInput.classList.remove('error');
                orderNumberMessage.textContent = '';
            })
            .catch(error => {
                console.error('Error:', error);
                orderNumberMessage.textContent = 'Ошибка при генерации номера заказа';
            });
    }

    // Add event listeners
    if (generateButton) {
        generateButton.addEventListener('click', generateOrderNumber);
    }

    if (orderNumberInput) {
        orderNumberInput.addEventListener('input', function() {
            // Format validation
            const orderNumberPattern = /^\d{1,9}$/;
            if (this.value && !orderNumberPattern.test(this.value)) {
                this.classList.add('error');
                orderNumberMessage.textContent = 'Пожалуйста, введите число от 1 до 999999999';
                return;
            }
            
            checkOrderNumber(this.value);
        });
    }

    // Transport type handling
    const transportTypeSelect = document.getElementById('transport_type');
    console.log('transportTypeSelect found:', transportTypeSelect);
    const cargoWeightInput = document.getElementById('cargo_weight');
    const weightUnitButtons = document.querySelectorAll('.unit-btn');
    const weightMessage = document.querySelector('.weight-message');
    let currentUnit = 'ton';

    // Function to fetch and populate transport types
    function fetchTransportTypes() {
        console.log('fetchTransportTypes called');
        fetch('../assets/add_order_get_transport_types.php')
            .then(response => response.json())
            .then(data => {
                console.log('Raw transport data:', data);
                if (data.error) {
                    throw new Error(data.error);
                }

                if (data.types && data.types.length > 0) {
                    transportTypeSelect.innerHTML = '<option value="">Выберите тип транспорта</option>';
                    
                    data.types.forEach(type => {
                        console.log('Transport type data:', type);
                        const option = document.createElement('option');
                        option.value = type.Type_id;
                        option.textContent = type.Type_name;
                        option.dataset.maxLoadTon = type.Max_load_ton;
                        option.dataset.maxLoadKg = type.Max_load_kg;
                        option.dataset.maxCapacity = type.Max_capacity_m3;
                        option.dataset.refrigerator = type.Refrigerator;
                        option.dataset.minTemp = type.Min_temp;
                        option.dataset.maxTemp = type.Max_temp;
                        option.dataset.transportRate = type.transport_rate;
                        option.dataset.hours = type.hours;
                        option.dataset.overworkRate = type.overwork_rate;
                        console.log('Option dataset after setting:', option.dataset);
                        transportTypeSelect.appendChild(option);
                    });
                    console.log('Transport types populated successfully');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                transportTypeSelect.innerHTML = '<option value="">Ошибка при загрузке типов транспорта</option>';
            });
    }

    // Load transport types when page loads
    if (transportTypeSelect) {
        console.log('Calling fetchTransportTypes');
        fetchTransportTypes();
    } else {
        console.error('transportTypeSelect not found!');
    }

    // Initially disable weight input and unit buttons
    function setWeightControlsState(disabled) {
        cargoWeightInput.disabled = disabled;
        cargoWeightInput.title = disabled ? 'Пожалуйста, выберите тип транспорта' : '';
        
        // Add click handler for input
        if (disabled) {
            cargoWeightInput.addEventListener('click', showTooltip);
        } else {
            cargoWeightInput.removeEventListener('click', showTooltip);
            removeTooltip(cargoWeightInput);
        }
        
        weightUnitButtons.forEach(btn => {
            btn.disabled = disabled;
            btn.title = disabled ? 'Пожалуйста, выберите тип транспорта' : '';
            if (disabled) {
                btn.style.opacity = '0.6';
                btn.style.cursor = 'not-allowed';
                btn.addEventListener('click', showTooltip);
            } else {
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
                btn.removeEventListener('click', showTooltip);
                removeTooltip(btn);
            }
        });
    }

    // Set initial state
    setWeightControlsState(true);

    function validateWeight(weight, transportType) {
        if (!transportType) return true;
        
        const maxTons = parseFloat(transportType.dataset.maxLoadTon);
        const maxKg = parseFloat(transportType.dataset.maxLoadKg);
        
        if (currentUnit === 'ton') {
            if (weight > maxTons) {
                weightMessage.textContent = `Максимальный вес для ${transportType.textContent} составляет ${maxTons} тонн`;
                cargoWeightInput.classList.add('error');
                return false;
            }
        } else {
            if (weight > maxKg) {
                weightMessage.textContent = `Максимальный вес для ${transportType.textContent} составляет ${maxKg} кг`;
                cargoWeightInput.classList.add('error');
                return false;
            }
        }
        
        weightMessage.textContent = '';
        cargoWeightInput.classList.remove('error');
        return true;
    }

    // Weight unit toggle
    weightUnitButtons.forEach(button => {
        button.addEventListener('click', function() {
            const newUnit = this.dataset.unit;
            if (newUnit === currentUnit) return;

            // Toggle active class
            weightUnitButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Convert value if input has a value
            if (cargoWeightInput.value) {
                const currentValue = parseFloat(cargoWeightInput.value);
                if (newUnit === 'kg') {
                    cargoWeightInput.value = (currentValue * 1000).toFixed(2);
                } else {
                    cargoWeightInput.value = (currentValue / 1000).toFixed(2);
                }
            }

            currentUnit = newUnit;
            
            // Revalidate with new unit
            if (cargoWeightInput.value) {
                const selectedTransport = transportTypeSelect.options[transportTypeSelect.selectedIndex];
                validateWeight(parseFloat(cargoWeightInput.value), selectedTransport);
            }
        });
    });

    // Weight input validation
    if (cargoWeightInput) {
        cargoWeightInput.addEventListener('input', function() {
            const weight = parseFloat(this.value);
            const selectedTransport = transportTypeSelect.options[transportTypeSelect.selectedIndex];
            
            if (!selectedTransport.value) {
                weightMessage.textContent = 'Пожалуйста, выберите тип транспорта';
                this.classList.add('error');
                return;
            }
            
            validateWeight(weight, selectedTransport);
        });
    }

    // Add validation to transport type selection
    if (transportTypeSelect) {
        transportTypeSelect.addEventListener('change', function() {
            console.log('Transport type changed');
            const isTransportSelected = this.value !== '';
            setWeightControlsState(!isTransportSelected);
            
            if (cargoWeightInput.value) {
                const weight = parseFloat(cargoWeightInput.value);
                const selectedTransport = this.options[this.selectedIndex];
                validateWeight(weight, selectedTransport);
            }
            
            // Handle temperature inputs
            const selectedOption = this.options[this.selectedIndex];
            const tempPrintList = document.getElementById('temp_print_list');
            const isRefrigerated = selectedOption && selectedOption.dataset.refrigerator === 'Yes';
            
            // Enable/disable temperature-related inputs based on refrigerator value
            minTempInput.disabled = !isRefrigerated;
            maxTempInput.disabled = !isRefrigerated;
            tempPrintList.disabled = !isRefrigerated;
            
            if (isRefrigerated) {
                const minTemp = parseFloat(selectedOption.dataset.minTemp);
                const maxTemp = parseFloat(selectedOption.dataset.maxTemp);
                minTempInput.placeholder = `Min ${minTemp}°C`;
                maxTempInput.placeholder = `Max ${maxTemp}°C`;
                tempPrintList.value = tempPrintList.value || 'Нет'; // Set default value if empty
            } else {
                minTempInput.value = '';
                maxTempInput.value = '';
                minTempInput.placeholder = 'N/A';
                maxTempInput.placeholder = 'N/A';
                temperatureMessage.textContent = '';
                tempPrintList.value = '';
            }
        });
    }

    // Function to show tooltip
    function showTooltip(e) {
        // Remove any existing tooltips
        document.querySelectorAll('.tooltip-message').forEach(t => t.remove());
        
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip-message';
        tooltip.textContent = this.title;
        this.parentNode.appendChild(tooltip);
        
        // Position the tooltip above the element
        const rect = this.getBoundingClientRect();
        tooltip.style.display = 'block';
        
        // Remove tooltip after 2 seconds
        setTimeout(() => removeTooltip(this), 2000);
    }
    
    // Function to remove tooltip
    function removeTooltip(element) {
        const tooltip = element.parentNode.querySelector('.tooltip-message');
        if (tooltip) {
            tooltip.remove();
        }
    }

    // Volume handling
    const cargoVolumeInput = document.getElementById('cargo_volume');
    const lengthInput = document.getElementById('length');
    const widthInput = document.getElementById('width');
    const heightInput = document.getElementById('height');
    const volumeMessage = document.querySelector('.volume-message');

    // Function to calculate volume from dimensions
    function calculateVolume() {
        const length = parseFloat(lengthInput.value) || 0;
        const width = parseFloat(widthInput.value) || 0;
        const height = parseFloat(heightInput.value) || 0;
        
        if (length && width && height) {
            const volume = (length * width * height).toFixed(2);
            cargoVolumeInput.value = volume;
            validateVolume(volume);
        }
    }

    // Function to validate volume against transport capacity
    function validateVolume(volume) {
        const selectedTransport = transportTypeSelect.options[transportTypeSelect.selectedIndex];
        if (!selectedTransport.value) return;

        const maxCapacity = parseFloat(selectedTransport.dataset.maxCapacity);
        if (volume > maxCapacity) {
            volumeMessage.textContent = `Максимальный объем для ${selectedTransport.textContent} составляет ${maxCapacity} м³`;
            cargoVolumeInput.classList.add('error');
            return false;
        }

        volumeMessage.textContent = '';
        cargoVolumeInput.classList.remove('error');
        return true;
    }

    // Add event listeners for dimension inputs
    [lengthInput, widthInput, heightInput].forEach(input => {
        input.addEventListener('input', calculateVolume);
    });

    // Add event listener for direct volume input
    cargoVolumeInput.addEventListener('input', function() {
        const volume = parseFloat(this.value);
        if (volume) {
            // Clear dimension inputs when volume is entered directly
            lengthInput.value = '';
            widthInput.value = '';
            heightInput.value = '';
            validateVolume(volume);
        }
    });

    // Add volume validation to transport type selection
    if (transportTypeSelect) {
        const originalChangeHandler = transportTypeSelect.onchange;
        transportTypeSelect.addEventListener('change', function() {
            if (originalChangeHandler) originalChangeHandler.call(this);
            if (cargoVolumeInput.value) {
                validateVolume(parseFloat(cargoVolumeInput.value));
            }
        });
    }

    // Initially disable volume inputs
    function setVolumeControlsState(disabled) {
        cargoVolumeInput.disabled = disabled;
        lengthInput.disabled = disabled;
        widthInput.disabled = disabled;
        heightInput.disabled = disabled;

        [cargoVolumeInput, lengthInput, widthInput, heightInput].forEach(input => {
            input.title = disabled ? 'Пожалуйста, выберите тип транспорта' : '';
            if (disabled) {
                input.addEventListener('click', showTooltip);
            } else {
                input.removeEventListener('click', showTooltip);
                removeTooltip(input);
            }
        });
    }

    // Update setWeightControlsState to include volume controls
    const originalSetWeightControlsState = setWeightControlsState;
    setWeightControlsState = function(disabled) {
        originalSetWeightControlsState(disabled);
        setVolumeControlsState(disabled);
    };

    // Set initial state
    setVolumeControlsState(true);

    // Temperature handling
    const minTempInput = document.getElementById('min_temperature');
    const maxTempInput = document.getElementById('max_temperature');
    const temperatureMessage = document.querySelector('.temperature-message');

    // Function to validate minimum temperature
    function validateMinTemperature() {
        const selectedTransport = transportTypeSelect.options[transportTypeSelect.selectedIndex];
        console.log('Validating min temperature:');
        console.log('Selected transport:', selectedTransport.textContent);
        console.log('Is refrigerated:', selectedTransport.dataset.refrigerator);
        console.log('Transport min temp:', selectedTransport.dataset.minTemp);
        console.log('Input value:', minTempInput.value);

        if (!selectedTransport.value || selectedTransport.dataset.refrigerator !== 'Yes') {
            console.log('Validation skipped: No transport selected or not refrigerated');
            temperatureMessage.textContent = '';
            minTempInput.classList.remove('error');
            return true;
        }

        const transportMinTemp = parseFloat(selectedTransport.dataset.minTemp);
        const inputMinTemp = parseFloat(minTempInput.value);

        // Check if we have valid input value
        if (isNaN(inputMinTemp)) {
            console.log('Validation skipped: Empty input');
            return true; // Don't show error for empty field
        }

        console.log('Comparing temperatures:');
        console.log('Transport min:', transportMinTemp);
        console.log('Input min:', inputMinTemp);

        if (inputMinTemp < transportMinTemp) {
            console.log('Error: Input temperature too low');
            temperatureMessage.textContent = `Минимальная температура не может быть ниже ${transportMinTemp}°C`;
            minTempInput.classList.add('error');
            return false;
        }

        // Similar logging for max temperature check
        const inputMaxTemp = parseFloat(maxTempInput.value);
        console.log('Max temp check:', inputMaxTemp);
        if (!isNaN(inputMaxTemp) && inputMinTemp > inputMaxTemp) {
            console.log('Error: Min temperature higher than max');
            temperatureMessage.textContent = 'Минимальная температура не может быть выше максимальной температуры';
            minTempInput.classList.add('error');
            maxTempInput.classList.add('error');
            return false;
        }

        console.log('Validation passed');
        temperatureMessage.textContent = '';
        minTempInput.classList.remove('error');
        maxTempInput.classList.remove('error');
        return true;
    }

    // Function to validate maximum temperature
    function validateMaxTemperature() {
        const selectedTransport = transportTypeSelect.options[transportTypeSelect.selectedIndex];
        if (!selectedTransport.value || selectedTransport.dataset.refrigerator !== 'Yes') {
            temperatureMessage.textContent = '';
            maxTempInput.classList.remove('error');
            return true;
        }

        const transportMaxTemp = parseFloat(selectedTransport.dataset.maxTemp);
        const inputMaxTemp = parseFloat(maxTempInput.value);

        // Check if we have valid input value
        if (isNaN(inputMaxTemp)) {
            return true; // Don't show error for empty field
        }

        if (inputMaxTemp > transportMaxTemp) {
            temperatureMessage.textContent = `Максимальная температура не может быть выше ${transportMaxTemp}°C`;
            maxTempInput.classList.add('error');
            return false;
        }

        // Check if min temperature is set and validate the range
        const inputMinTemp = parseFloat(minTempInput.value);
        if (!isNaN(inputMinTemp) && inputMinTemp > inputMaxTemp) {
            temperatureMessage.textContent = 'Максимальная температура не может быть ниже минимальной температуры';
            minTempInput.classList.add('error');
            maxTempInput.classList.add('error');
            return false;
        }

        temperatureMessage.textContent = '';
        minTempInput.classList.remove('error');
        maxTempInput.classList.remove('error');
        return true;
    }

    // Add event listeners for temperature inputs
    minTempInput.addEventListener('input', validateMinTemperature);
    maxTempInput.addEventListener('input', validateMaxTemperature);

    // Cargo name handling
    const cargoNameSelect = document.getElementById('cargo_name');

    // Function to fetch and populate cargo names
    function fetchCargoNames() {
        if (!cargoNameSelect) return;

        fetch('../assets/add_order_get_cargo_names.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.cargo_names) {
                    // Keep the first "Select Cargo Name" option
                    cargoNameSelect.innerHTML = '<option value="">Выберите наименование груза</option>';
                    
                    // Add cargo names from database
                    data.cargo_names.forEach(cargo => {
                        const option = document.createElement('option');
                        option.value = cargo.id;
                        option.textContent = cargo.cargo_name;
                        cargoNameSelect.appendChild(option);
                    });
                } else {
                    throw new Error(data.error || 'Failed to load cargo names');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                cargoNameSelect.innerHTML = '<option value="">Ошибка при загрузке наименований груза</option>';
            });
    }

    // Load cargo names when page loads
    fetchCargoNames();

    // Add after cargo name handling code
    
    // Loading type handling
    const loadingTypeSelect = document.getElementById('loading_type');

    // Function to fetch and populate loading types
    function fetchLoadingTypes() {
        if (!loadingTypeSelect) return;

        fetch('../assets/add_order_get_loading_types.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.loading_types) {
                    // Keep the first "Select Loading Type" option
                    loadingTypeSelect.innerHTML = '<option value="">Выберите тип погрузки</option>';
                    
                    // Add loading types from database
                    data.loading_types.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.id;
                        option.textContent = type.loading_name;
                        loadingTypeSelect.appendChild(option);
                    });

                    // Set default value to first loading type if available
                    if (data.loading_types.length > 0) {
                        loadingTypeSelect.value = data.loading_types[0].id;
                    }
                } else {
                    throw new Error(data.error || 'Failed to load loading types');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                loadingTypeSelect.innerHTML = '<option value="">Ошибка при загрузке типов погрузки</option>';
            });
    }

    // Load loading types when page loads
    fetchLoadingTypes();

    // Add after loading type handling code
    
    // Packaging type handling
    const packagingTypeSelect = document.getElementById('packaging_type');

    // Function to fetch and populate packaging types
    function fetchPackagingTypes() {
        if (!packagingTypeSelect) return;

        fetch('../assets/add_order_get_packaging_types.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.packaging_types) {
                    // Keep the first "Select Packaging Type" option
                    packagingTypeSelect.innerHTML = '<option value="">Выберите тип упаковки</option>';
                    
                    // Add packaging types from database
                    data.packaging_types.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.id;
                        option.textContent = type.packaging_name;
                        packagingTypeSelect.appendChild(option);
                    });

                    // Set default value to first packaging type if available
                    if (data.packaging_types.length > 0) {
                        packagingTypeSelect.value = data.packaging_types[0].id;
                    }
                } else {
                    throw new Error(data.error || 'Failed to load packaging types');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                packagingTypeSelect.innerHTML = '<option value="">Ошибка при загрузке типов упаковки</option>';
            });
    }

    // Load packaging types when page loads
    fetchPackagingTypes();

    // Add after packaging type handling code
    
    // Quantity spinner handling
    const quantityInput = document.getElementById('cargo_quantity');
    const spinnerUp = document.querySelector('.spinner-up');
    const spinnerDown = document.querySelector('.spinner-down');

    if (quantityInput && spinnerUp && spinnerDown) {
        // Function to update quantity
        function updateQuantity(increment) {
            let currentValue = parseInt(quantityInput.value) || 0;
            let newValue = increment ? currentValue + 1 : currentValue - 1;
            
            // Ensure minimum value is 1
            newValue = Math.max(1, newValue);
            
            quantityInput.value = newValue;
            
            // Trigger change event
            const event = new Event('change');
            quantityInput.dispatchEvent(event);
        }

        // Add click handlers for spinner buttons
        spinnerUp.addEventListener('click', () => updateQuantity(true));
        spinnerDown.addEventListener('click', () => updateQuantity(false));

        // Add keyboard support
        quantityInput.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                updateQuantity(true);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                updateQuantity(false);
            }
        });

        // Ensure minimum value of 1
        quantityInput.addEventListener('change', function() {
            let value = parseInt(this.value) || 1;
            value = Math.max(1, value);
            this.value = value;
        });
    }

    // Add after quantity spinner handling code
    
    // Price and currency handling
    const priceInput = document.getElementById('cargo_price');
    const currencySelect = document.getElementById('currency');

    // Function to fetch and populate currencies
    function fetchCurrencies() {
        if (!currencySelect) return;

        fetch('../assets/add_order_get_currencies.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.currencies) {
                    // Keep the first "Currency" option
                    currencySelect.innerHTML = '<option value="">Валюта</option>';
                    
                    // Add currencies from database
                    data.currencies.forEach(currency => {
                        const option = document.createElement('option');
                        option.value = currency.id;
                        option.textContent = `${currency.currency_code} (${currency.currency_symbol})`;
                        currencySelect.appendChild(option);
                    });

                    // Set default value to first currency (RUB)
                    if (data.currencies.length > 0) {
                        currencySelect.value = data.currencies[0].id;
                    }
                } else {
                    throw new Error(data.error || 'Failed to load currencies');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                currencySelect.innerHTML = '<option value="">Ошибка при загрузке валют</option>';
            });
    }

    // Load currencies when page loads
    fetchCurrencies();

    // Add price input validation
    if (priceInput) {
        priceInput.addEventListener('input', function() {
            const price = parseFloat(this.value);
            if (price < 0) {
                this.value = 0;
            }
        });
    }

    // Add after currency handling code
    
    // Rate handling
    const rateInput = document.getElementById('rate');
    const contractSelect = document.getElementById('contract_select');

    // Function to fetch and display rate
    function fetchContractRate(contractNumber) {
        if (!contractNumber) {
            rateInput.value = '';
            return;
        }

        console.log('Fetching rate for contract:', contractNumber);

        fetch(`../assets/add_order_get_contract_rate.php?contract_number=${contractNumber}`)
            .then(response => response.json())
            .then(data => {
                console.log('Rate data received:', data);
                if (data.success && data.rate) {
                    rateInput.value = data.rate.rate;
                } else {
                    rateInput.value = 'Ставка не найдена';
                }
            })
            .catch(error => {
                console.error('Error fetching rate:', error);
                rateInput.value = 'Error loading rate';
            });
    }

    // Make sure we're listening for contract changes
    if (contractSelect) {
        // Remove any existing event listeners
        contractSelect.removeEventListener('change', fetchContractRate);
        
        // Add the event listener
        contractSelect.addEventListener('change', function() {
            console.log('Contract changed');
            fetchContractRate(this.value);
        });

        // If a contract is already selected, fetch its rate
        if (contractSelect.value) {
            fetchContractRate(contractSelect.value);
        }
    }

    // Add after rate handling code
    
    // Insurance status handling
    const insuranceSelect = document.getElementById('insurance_status');

    // Set default value to "No need"
    if (insuranceSelect) {
        insuranceSelect.value = '1';
    }

    // Add after insurance handling code
    
    // Total insurance calculation
    const totalInsuranceInput = document.getElementById('total_insurance');
    
    function calculateTotalInsurance() {
        if (!totalInsuranceInput || !priceInput || !rateInput) return;
        
        const price = parseFloat(priceInput.value) || 0;
        const rate = parseFloat(rateInput.value) || 0;
        
        if (price && rate) {
            const total = price * rate;
            totalInsuranceInput.value = total.toFixed(2);
        } else {
            totalInsuranceInput.value = '';
        }
    }

    // Calculate total insurance when price or rate changes
    if (priceInput) {
        priceInput.addEventListener('input', calculateTotalInsurance);
    }

    // Update calculation when rate is fetched
    const originalFetchContractRate = fetchContractRate;
    fetchContractRate = function(contractNumber) {
        originalFetchContractRate(contractNumber);
        // Add slight delay to ensure rate is updated
        setTimeout(calculateTotalInsurance, 100);
    };

    // Add after transport type handling code
    
    // Transport rate handling
    const transportRateInput = document.getElementById('transport_rate');
    
    function updateTransportRate() {
        console.log('updateTransportRate called');
        if (!transportRateInput || !transportTypeSelect) {
            console.log('Missing required elements:', {
                transportRateInput: !!transportRateInput,
                transportTypeSelect: !!transportTypeSelect
            });
            return;
        }
        
        const selectedTransport = transportTypeSelect.options[transportTypeSelect.selectedIndex];
        console.log('Selected transport:', selectedTransport);
        console.log('Selected transport value:', selectedTransport?.value);
        console.log('Selected transport dataset:', selectedTransport?.dataset);
        
        if (!selectedTransport || !selectedTransport.value) {
            console.log('No transport selected, clearing rate');
            transportRateInput.value = '';
            return;
        }

        // Get base transport rate from the selected transport type
        const baseRate = parseFloat(selectedTransport.dataset.transportRate) || 0;
        console.log('Base transport rate from dataset:', selectedTransport.dataset.transportRate);
        console.log('Parsed base rate:', baseRate);
        
        // Get contract coefficient if available
        const contractSelect = document.getElementById('contract_select');
        const contractNumber = contractSelect ? contractSelect.value : '';
        console.log('Contract number:', contractNumber);

        if (!contractNumber) {
            console.log('No contract selected, using base rate:', baseRate);
            transportRateInput.value = baseRate.toFixed(2);
            return;
        }

        // Fetch coefficient from the contract
        console.log('Fetching coefficient for contract:', contractNumber);
        fetch(`../assets/add_order_get_contract_coeffs.php?contract_number=${contractNumber}`)
            .then(response => {
                console.log('Coefficient response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Coefficient data received:', data);
                if (data.success && data.coefficients) {
                    const coef = parseFloat(data.coefficients.coef_transport_rate) || 1.0;
                    console.log('Applied coefficient:', coef);
                    const finalRate = baseRate * coef;
                    console.log('Final calculated rate:', finalRate);
                    transportRateInput.value = finalRate.toFixed(2);
                } else {
                    console.log('No coefficients found, using base rate');
                    transportRateInput.value = baseRate.toFixed(2);
                }
            })
            .catch(error => {
                console.error('Error fetching coefficients:', error);
                console.log('Using base rate due to error');
                transportRateInput.value = baseRate.toFixed(2);
            });
    }

    // Update transport rate when transport type changes
    if (transportTypeSelect) {
        transportTypeSelect.addEventListener('change', function() {
            console.log('Transport type changed');
            updateTransportRate();
        });
    }

    // Update transport rate when contract changes
    if (contractSelect) {
        contractSelect.addEventListener('change', function() {
            console.log('Contract changed');
            updateTransportRate();
        });
    }

    // Hours handling
    const hoursInput = document.getElementById('transport_hours');
    
    function updateHours() {
        if (!hoursInput || !transportTypeSelect) return;
        
        const selectedTransport = transportTypeSelect.options[transportTypeSelect.selectedIndex];
        if (!selectedTransport || !selectedTransport.value) {
            hoursInput.value = '';
            return;
        }

        // Get default hours from the selected transport type
        const defaultHours = parseInt(selectedTransport.dataset.hours) || 8;
        hoursInput.value = defaultHours;
    }

    // Update hours when transport type changes
    if (transportTypeSelect) {
        const originalTransportChange = transportTypeSelect.onchange;
        transportTypeSelect.onchange = function(e) {
            if (originalTransportChange) originalTransportChange.call(this, e);
            updateHours();
        };
    }

    // Ensure only numeric input for hours
    if (hoursInput) {
        hoursInput.addEventListener('input', function() {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Ensure minimum value of 1
            if (this.value && parseInt(this.value) < 1) {
                this.value = '1';
            }
        });
    }

    // Hours spinner handling
    if (hoursInput) {
        const hoursUp = hoursInput.parentElement.querySelector('.spinner-up');
        const hoursDown = hoursInput.parentElement.querySelector('.spinner-down');

        hoursUp.addEventListener('click', function() {
            let value = parseInt(hoursInput.value) || 0;
            hoursInput.value = value + 1;
            // Trigger the input event to run validation
            hoursInput.dispatchEvent(new Event('input'));
        });

        hoursDown.addEventListener('click', function() {
            let value = parseInt(hoursInput.value) || 2;
            hoursInput.value = Math.max(1, value - 1);
            // Trigger the input event to run validation
            hoursInput.dispatchEvent(new Event('input'));
        });

        // Prevent the form from submitting when clicking spinner buttons
        hoursUp.addEventListener('click', function(e) {
            e.preventDefault();
        });
        
        hoursDown.addEventListener('click', function(e) {
            e.preventDefault();
        });
    }

    // Add after transport rate handling code
    
    // Overwork rate handling
    const overworkRateInput = document.getElementById('overwork_rate');
    
    function updateOverworkRate() {
        if (!overworkRateInput || !transportTypeSelect) return;
        
        const selectedTransport = transportTypeSelect.options[transportTypeSelect.selectedIndex];
        if (!selectedTransport || !selectedTransport.value) {
            overworkRateInput.value = '';
            return;
        }

        // Get base overwork rate from the selected transport type
        const baseRate = parseFloat(selectedTransport.dataset.overworkRate) || 0;
        
        // Get contract coefficient if available
        const contractNumber = document.getElementById('contract_select').value;
        if (!contractNumber) {
            overworkRateInput.value = baseRate.toFixed(2);
            return;
        }

        // Fetch coefficient from the contract
        fetch(`../assets/add_order_get_contract_coeffs.php?contract_number=${contractNumber}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.coefficients) {
                    const coef = parseFloat(data.coefficients.coef_overwork_rate) || 1.0;
                    const finalRate = baseRate * coef;
                    overworkRateInput.value = finalRate.toFixed(2);
                } else {
                    overworkRateInput.value = baseRate.toFixed(2);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                overworkRateInput.value = baseRate.toFixed(2);
            });
    }

    // Update overwork rate when transport type changes
    if (transportTypeSelect) {
        transportTypeSelect.addEventListener('change', function() {
            updateOverworkRate();
        });
    }

    // Update overwork rate when contract changes
    if (contractSelect) {
        contractSelect.addEventListener('change', function() {
            updateOverworkRate();
        });
    }

    // Overwork hours handling
    const overworkHoursInput = document.getElementById('overwork_hours');
    
    // Ensure only numeric input for overwork hours
    if (overworkHoursInput) {
        overworkHoursInput.addEventListener('input', function() {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Ensure minimum value of 0
            if (this.value && parseInt(this.value) < 0) {
                this.value = '0';
            }
        });

        // Overwork hours spinner handling
        const overworkHoursUp = overworkHoursInput.parentElement.querySelector('.spinner-up');
        const overworkHoursDown = overworkHoursInput.parentElement.querySelector('.spinner-down');

        overworkHoursUp.addEventListener('click', function(e) {
            e.preventDefault();
            let value = parseInt(overworkHoursInput.value) || 0;
            overworkHoursInput.value = value + 1;
            // Trigger the input event to run validation
            overworkHoursInput.dispatchEvent(new Event('input'));
        });

        overworkHoursDown.addEventListener('click', function(e) {
            e.preventDefault();
            let value = parseInt(overworkHoursInput.value) || 1;
            overworkHoursInput.value = Math.max(0, value - 1);
            // Trigger the input event to run validation
            overworkHoursInput.dispatchEvent(new Event('input'));
        });
    }

    // Transport total calculation
    const transportTotalInput = document.getElementById('transport_total');
    
    function calculateTransportTotal() {
        if (!transportTotalInput || !transportRateInput || !hoursInput || !overworkRateInput || !overworkHoursInput) return;
        
        const transportRate = parseFloat(transportRateInput.value) || 0;
        const hours = parseInt(hoursInput.value) || 0;
        const overworkRate = parseFloat(overworkRateInput.value) || 0;
        const overworkHours = parseInt(overworkHoursInput.value) || 0;
        
        const total = (transportRate * hours) + (overworkRate * overworkHours);
        transportTotalInput.value = total.toFixed(2);
    }

    // Calculate total when any of the input values change
    if (transportRateInput) {
        const observer = new MutationObserver(() => calculateTransportTotal());
        observer.observe(transportRateInput, { attributes: true });
    }

    if (hoursInput) {
        hoursInput.addEventListener('input', calculateTransportTotal);
    }

    if (overworkRateInput) {
        const observer = new MutationObserver(() => calculateTransportTotal());
        observer.observe(overworkRateInput, { attributes: true });
    }

    if (overworkHoursInput) {
        overworkHoursInput.addEventListener('input', calculateTransportTotal);
    }

    // Update calculation when transport type or contract changes
    if (transportTypeSelect) {
        transportTypeSelect.addEventListener('change', function() {
            setTimeout(calculateTransportTotal, 100); // Slight delay to ensure rates are updated
        });
    }

    if (contractSelect) {
        contractSelect.addEventListener('change', function() {
            setTimeout(calculateTransportTotal, 100); // Slight delay to ensure rates are updated
        });
    }

    // Extra service handling
    const extraServiceSelect = document.getElementById('extra_service');
    
    // Function to fetch and populate extra services
    function fetchExtraServices() {
        if (!extraServiceSelect) return;

        fetch('../assets/add_order_get_extra_service.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.services) {
                    // Keep the first "Select Extra Service" option
                    extraServiceSelect.innerHTML = '<option value="">Выберите дополнительную услугу</option>';
                    
                    data.services.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.id;
                        option.textContent = service.service_name;
                        option.dataset.price = service.price;
                        extraServiceSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                extraServiceSelect.innerHTML = '<option value="">Ошибка при загрузке услуг</option>';
            });
    }

    // Call the function when page loads
    fetchExtraServices();

    // Extra service quantity handling
    const extraServiceQuantityInput = document.getElementById('extra_service_quantity');
    
    // Ensure only numeric input for extra service quantity
    if (extraServiceQuantityInput) {
        extraServiceQuantityInput.addEventListener('input', function() {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Ensure minimum value of 1
            if (this.value && parseInt(this.value) < 1) {
                this.value = '1';
            }
        });

        // Extra service quantity spinner handling
        const quantityUp = extraServiceQuantityInput.parentElement.querySelector('.spinner-up');
        const quantityDown = extraServiceQuantityInput.parentElement.querySelector('.spinner-down');

        quantityUp.addEventListener('click', function(e) {
            e.preventDefault();
            let value = parseInt(extraServiceQuantityInput.value) || 0;
            extraServiceQuantityInput.value = value + 1;
            // Trigger the input event to run validation
            extraServiceQuantityInput.dispatchEvent(new Event('input'));
        });

        quantityDown.addEventListener('click', function(e) {
            e.preventDefault();
            let value = parseInt(extraServiceQuantityInput.value) || 2;
            extraServiceQuantityInput.value = Math.max(1, value - 1);
            // Trigger the input event to run validation
            extraServiceQuantityInput.dispatchEvent(new Event('input'));
        });
    }

    // Reset quantity when service changes
    if (extraServiceSelect) {
        extraServiceSelect.addEventListener('change', function() {
            if (extraServiceQuantityInput) {
                extraServiceQuantityInput.value = '1';
            }
        });
    }

    // Add after extra service handling code
    const extraServicePriceInput = document.getElementById('extra_service_price');
    
    // Update price when service changes
    if (extraServiceSelect) {
        extraServiceSelect.addEventListener('change', function() {
            if (extraServicePriceInput) {
                const selectedService = this.options[this.selectedIndex];
                if (selectedService && selectedService.value) {
                    const price = parseFloat(selectedService.dataset.price) || 0;
                    extraServicePriceInput.value = price.toFixed(2);
                } else {
                    extraServicePriceInput.value = '';
                }
            }
        });
    }

    // Extra service total calculation
    const extraServiceTotalInput = document.getElementById('extra_service_total');
    
    function calculateExtraServiceTotal() {
        if (!extraServiceTotalInput || !extraServicePriceInput || !extraServiceQuantityInput) return;
        
        const price = parseFloat(extraServicePriceInput.value) || 0;
        const quantity = parseInt(extraServiceQuantityInput.value) || 0;
        
        const total = price * quantity;
        extraServiceTotalInput.value = total.toFixed(2);
    }

    // Calculate total when price or quantity changes
    if (extraServicePriceInput) {
        const observer = new MutationObserver(() => calculateExtraServiceTotal());
        observer.observe(extraServicePriceInput, { attributes: true });
    }

    if (extraServiceQuantityInput) {
        extraServiceQuantityInput.addEventListener('input', calculateExtraServiceTotal);
    }

    // Update calculation when service changes
    if (extraServiceSelect) {
        extraServiceSelect.addEventListener('change', function() {
            setTimeout(calculateExtraServiceTotal, 100); // Slight delay to ensure price is updated
        });
    }

    // Extra service row handling
    const addExtraServiceBtn = document.getElementById('add-extra-service');
    const extraServicesContainer = document.getElementById('extra-services-container');
    let extraServiceRowCount = 1;

    function createExtraServiceRow() {
        const row = document.createElement('div');
        row.className = 'form-row extra-service-row';
        row.innerHTML = `
            <div class="form-group">
                <label for="extra_service_${extraServiceRowCount}">Дополнительная услуга</label>
                <div class="extra-service-container">
                    <select id="extra_service_${extraServiceRowCount}" 
                            name="extra_service_${extraServiceRowCount}" 
                            class="form-input extra-service-select">
                        <option value="">Выберите дополнительную услугу</option>
                    </select>
                    <div class="extra-service-message"></div>
                </div>
            </div>
            <div class="form-group">
                <label for="extra_service_quantity_${extraServiceRowCount}">Количество</label>
                <div class="hours-container">
                    <input type="number" 
                           id="extra_service_quantity_${extraServiceRowCount}"
                           name="extra_service_quantity_${extraServiceRowCount}"
                           class="form-input extra-service-quantity-input spinner-input" 
                           min="1"
                           step="1"
                           value="1"
                           required>
                    <div class="spinner-buttons">
                        <button type="button" class="spinner-up">
                            <i class="fas fa-chevron-up"></i>
                        </button>
                        <button type="button" class="spinner-down">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="extra_service_price_${extraServiceRowCount}">Цена</label>
                <div class="extra-service-price-container">
                    <input type="number" 
                           id="extra_service_price_${extraServiceRowCount}" 
                           name="extra_service_price_${extraServiceRowCount}" 
                           class="form-input extra-service-price-input" 
                           readonly
                           disabled>
                    <div class="extra-service-price-message"></div>
                </div>
            </div>
            <div class="form-group">
                <label for="extra_service_total_${extraServiceRowCount}">Итого</label>
                <div class="extra-service-total-container">
                    <input type="number" 
                           id="extra_service_total_${extraServiceRowCount}" 
                           name="extra_service_total_${extraServiceRowCount}" 
                           class="form-input extra-service-total-input" 
                           readonly
                           disabled>
                    <div class="extra-service-total-message"></div>
                </div>
            </div>
        `;

        // Populate the new select with services
        const select = row.querySelector(`#extra_service_${extraServiceRowCount}`);
        populateExtraServices(select);

        // Add event listeners for the new row
        setupExtraServiceRowEvents(row, extraServiceRowCount);

        extraServicesContainer.appendChild(row);
        extraServiceRowCount++;
        updateRemoveButtonState();
    }

    function setupExtraServiceRowEvents(row, rowIndex) {
        const select = row.querySelector(`#extra_service_${rowIndex}`);
        const quantityInput = row.querySelector(`#extra_service_quantity_${rowIndex}`);
        const priceInput = row.querySelector(`#extra_service_price_${rowIndex}`);
        const totalInput = row.querySelector(`#extra_service_total_${rowIndex}`);
        const spinnerUp = row.querySelector('.spinner-up');
        const spinnerDown = row.querySelector('.spinner-down');

        // Setup quantity spinner buttons
        spinnerUp.addEventListener('click', function(e) {
            e.preventDefault();
            let value = parseInt(quantityInput.value) || 0;
            quantityInput.value = value + 1;
            quantityInput.dispatchEvent(new Event('input'));
        });

        spinnerDown.addEventListener('click', function(e) {
            e.preventDefault();
            let value = parseInt(quantityInput.value) || 2;
            quantityInput.value = Math.max(1, value - 1);
            quantityInput.dispatchEvent(new Event('input'));
        });

        // Update price when service changes
        select.addEventListener('change', function() {
            const selectedService = this.options[this.selectedIndex];
            if (selectedService && selectedService.value) {
                const price = parseFloat(selectedService.dataset.price) || 0;
                priceInput.value = price.toFixed(2);
                quantityInput.value = '1';
                calculateRowTotal();
            } else {
                priceInput.value = '';
                totalInput.value = '';
            }
        });

        // Update total when quantity changes
        quantityInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value && parseInt(this.value) < 1) {
                this.value = '1';
            }
            calculateRowTotal();
        });

        function calculateRowTotal() {
            const price = parseFloat(priceInput.value) || 0;
            const quantity = parseInt(quantityInput.value) || 0;
            totalInput.value = (price * quantity).toFixed(2);
        }
    }

    function populateExtraServices(select) {
        fetch('../assets/add_order_get_extra_service.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.services) {
                    select.innerHTML = '<option value="">Выберите дополнительную услугу</option>';
                    data.services.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.id;
                        option.textContent = service.service_name;
                        option.dataset.price = service.price;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                select.innerHTML = '<option value="">Ошибка при загрузке услуг</option>';
            });
    }

    if (addExtraServiceBtn) {
        addExtraServiceBtn.addEventListener('click', createExtraServiceRow);
    }

    // Add remove button handling
    const removeExtraServiceBtn = document.getElementById('remove-extra-service');

    function updateRemoveButtonState() {
        const extraServiceRows = document.querySelectorAll('.extra-service-row');
        const removeButton = document.getElementById('remove-extra-service');
        
        if (removeButton) {
            // Enable remove button only if there's more than one extra service row
            removeButton.disabled = extraServiceRows.length <= 1;
        }
    }

    function removeLastExtraService() {
        const rows = extraServicesContainer.querySelectorAll('.extra-service-row');
        if (rows.length > 1) {
            rows[rows.length - 1].remove();
            updateRemoveButtonState();
            // Update totals after removing a row
            setTimeout(updateTotalsSummary, 100);
        }
    }

    if (removeExtraServiceBtn) {
        removeExtraServiceBtn.addEventListener('click', removeLastExtraService);
    }

    // Create initial extra service row
    if (extraServicesContainer && extraServicesContainer.children.length === 0) {
        createExtraServiceRow();
    }
    
    // Initial state
    updateRemoveButtonState();

    // Route Tab Functionality
    const routePointsContainer = document.getElementById('route-points-container');
    const addRoutePointBtn = document.getElementById('add-route-point');
    const removeRoutePointBtn = document.getElementById('remove-route-point');
    const routeNextBtn = document.getElementById('route-next');
    let pointCounter = 1;

    // Russian calendar configuration
    function setupRussianCalendar() {
        // Set Russian locale for date inputs
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            input.setAttribute('lang', 'ru');
            input.setAttribute('data-date-format', 'dd.mm.yyyy');
        });
    }

    // Time picker configuration with Russian labels
    const timeIntervals = generateTimeIntervals();

    function generateTimeIntervals() {
        const intervals = [];
        for (let hour = 0; hour < 24; hour++) {
            for (let minute = 0; minute < 60; minute += 15) {
                intervals.push(
                    `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`
                );
            }
        }
        return intervals;
    }

    // Setup time picker modal
    function setupTimePickerModal(timeInput, pointNumber) {
        const modalId = `timePickerModal_${pointNumber}`;
        
        // Create modal if it doesn't exist
        if (!document.getElementById(modalId)) {
            createTimePickerModal(modalId, timeInput);
        }
        
        // Add click event to show time picker
        const clockIcon = document.createElement('span');
        clockIcon.className = 'time-picker-icon';
        clockIcon.innerHTML = '<i class="fas fa-clock"></i>';
        clockIcon.style.cssText = `
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            z-index: 10;
        `;
        
        // Make the container relative
        timeInput.parentElement.style.position = 'relative';
        timeInput.parentElement.appendChild(clockIcon);
        
        clockIcon.addEventListener('click', function(e) {
            e.preventDefault();
            showTimePickerModal(modalId, timeInput);
        });
    }

    function createTimePickerModal(modalId, timeInput) {
        const modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'time-picker-modal';
        modal.style.cssText = `
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        `;
        
        modal.innerHTML = `
                         <div class="time-picker-content" style="
                 background: white;
                 margin: 10% auto;
                 padding: 20px;
                 border-radius: 8px;
                 width: 375px;
                 max-width: 90%;
             ">
                <div class="time-picker-header" style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                ">
                    <h3 style="margin: 0; color: #333;">Выберите время</h3>
                    <button type="button" class="close-time-picker" style="
                        background: none;
                        border: none;
                        font-size: 20px;
                        cursor: pointer;
                        color: #999;
                    ">&times;</button>
                </div>
                <div class="time-picker-body">
                    <div class="time-display" style="
                        text-align: center;
                        font-size: 24px;
                        font-weight: bold;
                        margin-bottom: 20px;
                        color: #2c3e50;
                    ">00:00</div>
                    <div class="time-selectors" style="
                        display: flex;
                        justify-content: space-between;
                        gap: 20px;
                    ">
                        <div class="hour-selector" style="flex: 1;">
                            <label style="
                                display: block;
                                text-align: center;
                                margin-bottom: 10px;
                                font-weight: bold;
                                color: #555;
                            ">Часы</label>
                            <div class="hour-buttons" style="
                                display: grid;
                                grid-template-columns: repeat(4, 1fr);
                                gap: 5px;
                                max-height: 200px;
                                overflow-y: auto;
                                border: 1px solid #ddd;
                                padding: 10px;
                                border-radius: 4px;
                            "></div>
                        </div>
                                                 <div class="minute-selector" style="flex: 1;">
                             <label style="
                                 display: block;
                                 text-align: center;
                                 margin-bottom: 10px;
                                 font-weight: bold;
                                 color: #555;
                             ">Минуты</label>
                             <div class="minute-buttons" style="
                                 display: grid;
                                 grid-template-columns: repeat(2, 1fr);
                                 gap: 8px;
                                 max-height: 200px;
                                 overflow-y: auto;
                                 border: 1px solid #ddd;
                                 padding: 15px;
                                 border-radius: 4px;
                                 justify-items: center;
                             "></div>
                         </div>
                    </div>
                    <div class="time-picker-actions" style="
                        margin-top: 20px;
                        text-align: center;
                        border-top: 1px solid #eee;
                        padding-top: 15px;
                    ">
                        <button type="button" class="btn-time-confirm" style="
                            background: #2ecc71;
                            color: white;
                            border: none;
                            padding: 8px 20px;
                            border-radius: 4px;
                            cursor: pointer;
                            margin-right: 10px;
                        ">Подтвердить</button>
                        <button type="button" class="btn-time-cancel" style="
                            background: #95a5a6;
                            color: white;
                            border: none;
                            padding: 8px 20px;
                            border-radius: 4px;
                            cursor: pointer;
                        ">Отмена</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Populate hour buttons
        const hourButtons = modal.querySelector('.hour-buttons');
        for (let i = 0; i < 24; i++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = i.toString().padStart(2, '0');
            btn.className = 'hour-btn';
            btn.style.cssText = `
                padding: 8px;
                border: 1px solid #ddd;
                background: white;
                cursor: pointer;
                border-radius: 3px;
                transition: all 0.2s;
            `;
            btn.dataset.hour = i;
            hourButtons.appendChild(btn);
        }
        
        // Populate minute buttons
        const minuteButtons = modal.querySelector('.minute-buttons');
        for (let i = 0; i < 60; i += 15) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = i.toString().padStart(2, '0');
            btn.className = 'minute-btn';
                         btn.style.cssText = `
                 padding: 12px 16px;
                 border: 1px solid #ddd;
                 background: white;
                 cursor: pointer;
                 border-radius: 3px;
                 transition: all 0.2s;
                 width: 100%;
                 min-width: 50px;
             `;
             btn.dataset.minute = i;
             minuteButtons.appendChild(btn);
        }
        
        // Add event listeners
        setupTimePickerEvents(modal, timeInput);
    }

    function setupTimePickerEvents(modal, timeInput) {
        let selectedHour = 0;
        let selectedMinute = 0;
        
        const timeDisplay = modal.querySelector('.time-display');
        const hourButtons = modal.querySelectorAll('.hour-btn');
        const minuteButtons = modal.querySelectorAll('.minute-btn');
        
        function updateTimeDisplay() {
            timeDisplay.textContent = 
                `${selectedHour.toString().padStart(2, '0')}:${selectedMinute.toString().padStart(2, '0')}`;
        }
        
        function updateButtonStates() {
            hourButtons.forEach(btn => {
                if (parseInt(btn.dataset.hour) === selectedHour) {
                    btn.style.background = '#3498db';
                    btn.style.color = 'white';
                } else {
                    btn.style.background = 'white';
                    btn.style.color = 'black';
                }
            });
            
            minuteButtons.forEach(btn => {
                if (parseInt(btn.dataset.minute) === selectedMinute) {
                    btn.style.background = '#3498db';
                    btn.style.color = 'white';
                } else {
                    btn.style.background = 'white';
                    btn.style.color = 'black';
                }
            });
        }
        
        // Hour button events
        hourButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                selectedHour = parseInt(this.dataset.hour);
                updateTimeDisplay();
                updateButtonStates();
            });
        });
        
        // Minute button events
        minuteButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                selectedMinute = parseInt(this.dataset.minute);
                updateTimeDisplay();
                updateButtonStates();
            });
        });
        
        // Close events
        modal.querySelector('.close-time-picker').addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        });
        
        modal.querySelector('.btn-time-cancel').addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        });
        
        // Confirm event
        modal.querySelector('.btn-time-confirm').addEventListener('click', function() {
            timeInput.value = `${selectedHour.toString().padStart(2, '0')}:${selectedMinute.toString().padStart(2, '0')}`;
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            timeInput.dispatchEvent(new Event('change'));
        });
        
        // Click outside to close
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        });
        
        // Store functions for later use
        modal.updateTime = function(hour, minute) {
            selectedHour = hour;
            selectedMinute = minute;
            updateTimeDisplay();
            updateButtonStates();
        };
    }

    function showTimePickerModal(modalId, timeInput) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        // Prevent body shift by adding class without scrollbar compensation
        document.body.classList.add('modal-open');
        
        // Parse current time
        const currentTime = timeInput.value || '00:00';
        const [hours, minutes] = currentTime.split(':').map(n => parseInt(n) || 0);
        
        modal.updateTime(hours, Math.floor(minutes / 15) * 15);
        modal.style.display = 'block';
    }

    // Russian date picker functionality
    function setupDatePickerModal(dateInput, pointNumber) {
        const modalId = `datePickerModal_${pointNumber}`;
        
        // Create modal if it doesn't exist
        if (!document.getElementById(modalId)) {
            createDatePickerModal(modalId, dateInput);
        }
        
        // Add click event to show date picker
        const calendarIcon = document.createElement('span');
        calendarIcon.className = 'date-picker-icon';
        calendarIcon.innerHTML = '<i class="fas fa-calendar-alt"></i>';
        calendarIcon.style.cssText = `
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            z-index: 10;
            font-size: 14px;
            pointer-events: auto;
        `;
        
        // Make the container relative
        dateInput.parentElement.style.position = 'relative';
        dateInput.parentElement.appendChild(calendarIcon);
        
        calendarIcon.addEventListener('click', function(e) {
            e.preventDefault();
            showDatePickerModal(modalId, dateInput);
        });
        
        // Also trigger on input click
        dateInput.addEventListener('click', function(e) {
            e.preventDefault();
            showDatePickerModal(modalId, dateInput);
        });
    }

    function createDatePickerModal(modalId, dateInput) {
        const modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'date-picker-modal';
        modal.style.cssText = `
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        `;
        
        modal.innerHTML = `
            <div class="date-picker-content" style="
                background: white;
                margin: 5% auto;
                padding: 20px;
                border-radius: 8px;
                width: 400px;
                max-width: 90%;
            ">
                <div class="date-picker-header" style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                ">
                    <h3 style="margin: 0; color: #333;">Выберите дату</h3>
                    <button type="button" class="close-date-picker" style="
                        background: none;
                        border: none;
                        font-size: 20px;
                        cursor: pointer;
                        color: #999;
                    ">&times;</button>
                </div>
                <div class="date-picker-body">
                    <div class="date-navigation" style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 20px;
                    ">
                        <button type="button" class="nav-btn prev-month" style="
                            background: #3498db;
                            color: white;
                            border: none;
                            padding: 8px 12px;
                            border-radius: 4px;
                            cursor: pointer;
                        ">‹</button>
                        <div class="current-month" style="
                            font-size: 18px;
                            font-weight: bold;
                            color: #2c3e50;
                        "></div>
                        <button type="button" class="nav-btn next-month" style="
                            background: #3498db;
                            color: white;
                            border: none;
                            padding: 8px 12px;
                            border-radius: 4px;
                            cursor: pointer;
                        ">›</button>
                    </div>
                    <div class="calendar-grid" style="
                        display: grid;
                        grid-template-columns: repeat(7, 1fr);
                        gap: 2px;
                        margin-bottom: 20px;
                    ">
                        <div class="day-header" style="
                            text-align: center;
                            font-weight: bold;
                            padding: 8px;
                            background: #ecf0f1;
                            color: #2c3e50;
                        ">Пн</div>
                        <div class="day-header" style="
                            text-align: center;
                            font-weight: bold;
                            padding: 8px;
                            background: #ecf0f1;
                            color: #2c3e50;
                        ">Вт</div>
                        <div class="day-header" style="
                            text-align: center;
                            font-weight: bold;
                            padding: 8px;
                            background: #ecf0f1;
                            color: #2c3e50;
                        ">Ср</div>
                        <div class="day-header" style="
                            text-align: center;
                            font-weight: bold;
                            padding: 8px;
                            background: #ecf0f1;
                            color: #2c3e50;
                        ">Чт</div>
                        <div class="day-header" style="
                            text-align: center;
                            font-weight: bold;
                            padding: 8px;
                            background: #ecf0f1;
                            color: #2c3e50;
                        ">Пт</div>
                        <div class="day-header" style="
                            text-align: center;
                            font-weight: bold;
                            padding: 8px;
                            background: #ecf0f1;
                            color: #2c3e50;
                        ">Сб</div>
                        <div class="day-header" style="
                            text-align: center;
                            font-weight: bold;
                            padding: 8px;
                            background: #ecf0f1;
                            color: #2c3e50;
                        ">Вс</div>
                    </div>
                    <div class="date-picker-actions" style="
                        text-align: center;
                        border-top: 1px solid #eee;
                        padding-top: 15px;
                    ">
                                                 <button type="button" class="btn-apply" style="
                             background: #2ecc71;
                             color: white;
                             border: none;
                             padding: 8px 16px;
                             border-radius: 4px;
                             cursor: pointer;
                             margin-right: 10px;
                         ">Применить</button>
                        <button type="button" class="btn-date-cancel" style="
                            background: #95a5a6;
                            color: white;
                            border: none;
                            padding: 8px 20px;
                            border-radius: 4px;
                            cursor: pointer;
                        ">Отмена</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        setupDatePickerEvents(modal, dateInput);
    }

    function setupDatePickerEvents(modal, dateInput) {
        const monthNames = [
            'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
        ];
        
        let currentDate = new Date();
        let selectedDate = null;
        
        const currentMonthEl = modal.querySelector('.current-month');
        const calendarGrid = modal.querySelector('.calendar-grid');
        const prevBtn = modal.querySelector('.prev-month');
        const nextBtn = modal.querySelector('.next-month');
        
        function renderCalendar() {
            const currentMonth = currentDate.getMonth();
            const currentYear = currentDate.getFullYear();
            
            currentMonthEl.textContent = `${monthNames[currentMonth]} ${currentYear}`;
            
            // Clear existing days
            const existingDays = calendarGrid.querySelectorAll('.day-cell');
            existingDays.forEach(day => day.remove());
            
            // Get first day of month and number of days
            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            const daysInMonth = lastDay.getDate();
            
            // Get first Monday of the calendar (Russian calendar starts with Monday)
            const startDate = new Date(firstDay);
            const dayOfWeek = (firstDay.getDay() + 6) % 7; // Convert Sunday=0 to Monday=0
            startDate.setDate(firstDay.getDate() - dayOfWeek);
            
            // Generate 42 days (6 weeks)
            for (let i = 0; i < 42; i++) {
                // Use getTime() + milliseconds for more reliable date calculation
                const date = new Date(startDate.getTime() + (i * 24 * 60 * 60 * 1000));
                
                const dayElement = document.createElement('div');
                dayElement.className = 'day-cell';
                dayElement.textContent = date.getDate();
                
                // Store date components to avoid timezone issues
                dayElement.dataset.year = date.getFullYear();
                dayElement.dataset.month = date.getMonth();
                dayElement.dataset.day = date.getDate();
                
                const isCurrentMonth = date.getMonth() === currentMonth;
                const isToday = date.toDateString() === new Date().toDateString();
                const isSelected = selectedDate && date.toDateString() === selectedDate.toDateString();
                
                dayElement.style.cssText = `
                    text-align: center;
                    padding: 10px;
                    cursor: pointer;
                    border-radius: 4px;
                    transition: all 0.2s;
                    ${isCurrentMonth ? 'color: #2c3e50;' : 'color: #bdc3c7;'}
                    ${isToday ? 'background: #e8f4fd; border: 2px solid #3498db;' : 'background: white;'}
                    ${isSelected ? 'background: #3498db; color: white;' : ''}
                `;
                
                dayElement.addEventListener('click', function() {
                    // Reconstruct the date from stored components to avoid timezone issues
                    const clickedDate = new Date(
                        parseInt(this.dataset.year),
                        parseInt(this.dataset.month),
                        parseInt(this.dataset.day)
                    );
                    selectedDate = clickedDate;
                    renderCalendar();
                });
                
                dayElement.addEventListener('mouseenter', function() {
                    if (!isSelected) {
                        this.style.background = '#ecf0f1';
                    }
                });
                
                dayElement.addEventListener('mouseleave', function() {
                    if (!isSelected) {
                        this.style.background = isToday ? '#e8f4fd' : 'white';
                    }
                });
                
                calendarGrid.appendChild(dayElement);
            }
        }
        
        // Navigation events
        prevBtn.addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });
        
        nextBtn.addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });
        
        // Apply button
        modal.querySelector('.btn-apply').addEventListener('click', function() {
            if (selectedDate) {
                // Format date without timezone conversion
                const year = selectedDate.getFullYear();
                const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
                const day = String(selectedDate.getDate()).padStart(2, '0');
                const dateStr = `${year}-${month}-${day}`;
                dateInput.value = dateStr;
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
                dateInput.dispatchEvent(new Event('change'));
            } else {
                // If no date selected, use today
                selectedDate = new Date();
                const year = selectedDate.getFullYear();
                const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
                const day = String(selectedDate.getDate()).padStart(2, '0');
                const dateStr = `${year}-${month}-${day}`;
                dateInput.value = dateStr;
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
                dateInput.dispatchEvent(new Event('change'));
            }
        });
        
        // Close events
        modal.querySelector('.close-date-picker').addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        });
        
        modal.querySelector('.btn-date-cancel').addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        });
        
        // Click outside to close
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        });
        
        // Double click to confirm date
        calendarGrid.addEventListener('dblclick', function(e) {
            if (e.target.classList.contains('day-cell')) {
                // Use the selectedDate instead of recalculating
                if (selectedDate) {
                    // Format date without timezone conversion
                    const year = selectedDate.getFullYear();
                    const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
                    const day = String(selectedDate.getDate()).padStart(2, '0');
                    const dateStr = `${year}-${month}-${day}`;
                    dateInput.value = dateStr;
                    modal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    dateInput.dispatchEvent(new Event('change'));
                }
            }
        });
        
        // Store functions for later use
        modal.renderCalendar = renderCalendar;
        modal.setSelectedDate = function(date) {
            selectedDate = date;
            currentDate = new Date(date);
            renderCalendar();
        };
    }

    function showDatePickerModal(modalId, dateInput) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        // Prevent body shift by adding class without scrollbar compensation
        document.body.classList.add('modal-open');
        
        // Parse current date
        if (dateInput.value) {
            const currentDate = new Date(dateInput.value);
            modal.setSelectedDate(currentDate);
        } else {
            modal.renderCalendar();
        }
        
        modal.style.display = 'block';
    }

    // Date validation
    function validateDate(dateInput) {
        const selectedDate = new Date(dateInput.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate < today) {
            const warning = dateInput.parentElement.querySelector('.date-message');
            warning.textContent = 'Выбранная дата в прошлом';
            warning.classList.add('date-warning');
        } else {
            const warning = dateInput.parentElement.querySelector('.date-message');
            warning.textContent = '';
            warning.classList.remove('date-warning');
        }
    }

    // Time validation and formatting with Russian support
    function formatTimeInput(input) {
        // Allow deletion and proper editing
        if (input.value === '') return;
        
        let value = input.value.replace(/[^\d:]/g, '');
        
        // Handle various input formats
        if (value.includes(':')) {
            const parts = value.split(':');
            const hours = parseInt(parts[0]) || 0;
            const minutes = parseInt(parts[1]) || 0;
            
            const validHours = Math.min(23, Math.max(0, hours));
            const validMinutes = Math.min(59, Math.max(0, minutes));
            
            input.value = validHours.toString().padStart(2, '0') + ':' + validMinutes.toString().padStart(2, '0');
        } else if (value.length >= 3) {
            const hours = parseInt(value.substring(0, 2));
            const minutes = parseInt(value.substring(2, 4));
            
            const validHours = Math.min(23, Math.max(0, hours));
            const validMinutes = Math.min(59, Math.max(0, minutes));
            
            input.value = validHours.toString().padStart(2, '0') + ':' + validMinutes.toString().padStart(2, '0');
        }
    }

    // Create new point
    function createRoutePoint() {
        pointCounter++;
        if (pointCounter > 50) {
            alert('Maximum 50 points allowed');
            pointCounter--;
            return;
        }

        const pointTemplate = `
            <div class="cost-section route-point" data-position="${pointCounter}">
                <h3 class="cost-section-title">Пункт ${pointCounter}</h3>
                <!-- First Row: Action Type, Company Name, Address -->
                <div class="form-row">
                                            <!-- Action Type -->
                        <div class="form-group">
                            <label for="action_type_${pointCounter}">Тип операции</label>
                            <div class="action-type-container">
                                <select id="action_type_${pointCounter}" 
                                        name="action_type_${pointCounter}" 
                                        class="form-input" 
                                        required>
                                    <option value="Погрузка">Погрузка</option>
                                    <option value="Выгрузка">Выгрузка</option>
                                </select>
                            </div>
                        </div>

                    <!-- Company Name -->
                    <div class="form-group">
                        <label for="company_name_${pointCounter}">Название компании</label>
                        <div class="company-name-container">
                            <div class="combo-select">
                                <input type="text" 
                                       id="company_name_${pointCounter}" 
                                       name="company_name_${pointCounter}" 
                                       class="form-input company-input combo-input" 
                                       required 
                                       autocomplete="off"
                                       placeholder="Выберите или введите компанию">
                                <div class="combo-arrow">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="combo-dropdown company-dropdown">
                                    <!-- Options will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="form-group">
                        <label for="address_loading_${pointCounter}">Адрес</label>
                        <div class="address-container">
                            <div class="combo-select">
                                <input type="text" 
                                       id="address_loading_${pointCounter}" 
                                       name="address_loading_${pointCounter}" 
                                       class="form-input address-input combo-input" 
                                       required 
                                       autocomplete="off"
                                       placeholder="Выберите или введите адрес">
                                <div class="combo-arrow">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="combo-dropdown address-dropdown">
                                    <!-- Options will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Row: Date, Time, Contact Person, Phone Number -->
                <div class="form-row">
                    <!-- Date -->
                    <div class="form-group">
                        <label for="point_date_${pointCounter}">Дата</label>
                        <div class="point-date-container">
                            <input type="date" 
                                   id="point_date_${pointCounter}" 
                                   name="point_date_${pointCounter}" 
                                   class="form-input" 
                                   required>
                            <div class="date-message"></div>
                        </div>
                    </div>

                    <!-- Time -->
                    <div class="form-group">
                        <label for="point_time_${pointCounter}">Время</label>
                        <div class="point-time-container">
                            <input type="text" 
                                   id="point_time_${pointCounter}" 
                                   name="point_time_${pointCounter}" 
                                   class="form-input time-input" 
                                   required 
                                   placeholder="HH:MM">
                            <div class="time-message"></div>
                        </div>
                    </div>

                    <!-- Contact Person -->
                    <div class="form-group">
                        <label for="contact_person_${pointCounter}">Контактное лицо</label>
                        <div class="contact-person-container">
                            <div class="combo-select">
                                <input type="text" 
                                       id="contact_person_${pointCounter}" 
                                       name="contact_person_${pointCounter}" 
                                       class="form-input contact-input combo-input" 
                                       required 
                                       autocomplete="off"
                                       placeholder="Выберите или введите контакт">
                                <div class="combo-arrow">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="combo-dropdown contact-dropdown">
                                    <!-- Options will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <div class="form-group">
                        <label for="phone_number_${pointCounter}">Телефон</label>
                        <div class="phone-number-container">
                            <input type="text" 
                                   id="phone_number_${pointCounter}" 
                                   name="phone_number_${pointCounter}" 
                                   class="form-input" 
                                   required>
                        </div>
                    </div>
                </div>

                <!-- Additional Contacts Section -->
                <div class="additional-contacts-section" id="additional_contacts_${pointCounter}">
                    <div class="additional-contacts-header">
                        <button type="button" class="btn-add-contact" data-point="${pointCounter}">
                            <i class="fas fa-plus"></i> Добавить контакт
                        </button>
                    </div>
                    <div class="additional-contacts-container" id="additional_contacts_container_${pointCounter}">
                        <!-- Additional contacts will be added here -->
                    </div>
                </div>
            </div>
        `;

        routePointsContainer.insertAdjacentHTML('beforeend', pointTemplate);
        updateRouteRemoveButtonState();
        attachPointEventListeners(pointCounter);
        
        // Initialize additional contacts for the new point
        const addContactBtn = document.querySelector(`.btn-add-contact[data-point="${pointCounter}"]`);
        if (addContactBtn) {
            addContactBtn.addEventListener('click', handleAddContact);
        }
    }

    // Attach event listeners to a point's inputs
    function attachPointEventListeners(pointNumber) {
        const dateInput = document.getElementById(`point_date_${pointNumber}`);
        const timeInput = document.getElementById(`point_time_${pointNumber}`);
        const contactInput = document.getElementById(`contact_person_${pointNumber}`);
        const phoneInput = document.getElementById(`phone_number_${pointNumber}`);
        const companyInput = document.getElementById(`company_name_${pointNumber}`);
        const addressInput = document.getElementById(`address_loading_${pointNumber}`);

                    // Date validation with Russian support
        dateInput.addEventListener('change', function() {
            validateDate(this);
            validateChronologicalOrder();
        });
        
        // Set Russian locale for this date input
        dateInput.setAttribute('lang', 'ru');
        
        // Add Russian date picker functionality
        setupDatePickerModal(dateInput, pointNumber);

        // Time input handling with Russian support
        timeInput.addEventListener('input', function() {
            formatTimeInput(this);
            validateChronologicalOrder();
        });
        
        // Add blur event to ensure proper formatting
        timeInput.addEventListener('blur', function() {
            formatTimeInput(this);
        });
        
        // Add focus event to help with editing
        timeInput.addEventListener('focus', function() {
            // Select all text when focusing for easy editing
            this.select();
        });
        
        // Add Russian time picker functionality
        setupTimePickerModal(timeInput, pointNumber);

        // Setup combo-select functionality for company, address, and contact inputs
        setupComboSelect(companyInput);
        setupComboSelect(addressInput);
        setupComboSelect(contactInput);
    }

    // Combo-select helper functions
    function setupComboSelect(input) {

        const comboSelect = input.closest('.combo-select');
        if (!comboSelect) return;
        
        const dropdown = comboSelect.querySelector('.combo-dropdown');
        if (!dropdown) return;
        
        // Check if already initialized to prevent duplicate handlers
        if (comboSelect.dataset.initialized === 'true') {

            return;
        }
        
        // Mark as initialized
        comboSelect.dataset.initialized = 'true';
        
        // Input click handler - show all options
        input.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleComboDropdown(comboSelect);
        });
        
        // Input typing handler - filter options
        input.addEventListener('input', function() {
            const value = this.value.trim();
            if (value.length >= 2) {
                showComboDropdown(comboSelect);
                if (input.id.includes('company_name')) {
                    const pointNumber = input.id.replace('company_name_', '');
                    const addressInput = document.getElementById(`address_loading_${pointNumber}`);
                    fetchCompanySuggestions(value, dropdown, addressInput);
                } else if (input.id.includes('address_loading')) {
                    const pointNumber = input.id.replace('address_loading_', '');
                    const companyInput = document.getElementById(`company_name_${pointNumber}`);
                    const companyName = companyInput ? companyInput.value.trim() : '';
                    fetchAddressSuggestions(value, dropdown, companyName);
                } else if (input.id.includes('contact_person')) {
                    const idParts = input.id.split('_');
                    let phoneInputId;
                    if (idParts.length === 3) {
                        // Format: contact_person_1 (main contact)
                        phoneInputId = `phone_number_${idParts[2]}`;
                    } else if (idParts.length === 4) {
                        // Format: contact_person_1_2 (additional contact)
                        phoneInputId = `phone_number_${idParts[2]}_${idParts[3]}`;
                    }
                    const phoneInput = document.getElementById(phoneInputId);
                    fetchContactSuggestions(value, dropdown, phoneInput);
                }
            } else {
                hideComboDropdown(comboSelect);
            }
        });
        
        // Arrow click handler with debugging
        const arrow = comboSelect.querySelector('.combo-arrow');
        if (arrow) {
            // Add click handler
            function handleArrowClick(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleComboDropdown(comboSelect);
            }
            
            arrow.addEventListener('click', handleArrowClick);
            
            // Make sure the arrow is properly styled for clicks
            arrow.style.cursor = 'pointer';
            arrow.style.pointerEvents = 'auto';
            arrow.style.userSelect = 'none';
            
        }
        
        // Add a fallback click handler for the combo-select container
        // This will open dropdown if clicked anywhere in the right area
        comboSelect.addEventListener('click', function(e) {
            // Only trigger if clicking on the combo-select itself or the arrow, not the input
            if (e.target === comboSelect || e.target.closest('.combo-arrow')) {
                e.preventDefault();
                e.stopPropagation();
                toggleComboDropdown(comboSelect);
            }
        });
    }

    function toggleComboDropdown(comboSelect) {
        if (comboSelect.classList.contains('open')) {
            hideComboDropdown(comboSelect);
        } else {
            showComboDropdown(comboSelect);
        }
    }

    function showComboDropdown(comboSelect) {
        // Close other dropdowns
        closeAllComboDropdowns();
        
        const input = comboSelect.querySelector('.combo-input');
        const dropdown = comboSelect.querySelector('.combo-dropdown');
        
        if (!input || !dropdown) {
            console.error('Missing input or dropdown element');
            return;
        }
        
        comboSelect.classList.add('open');
        dropdown.classList.add('show');
        
        // Prepare dropdown for content
        dropdown.innerHTML = '';
        
        // Fetch all options when opening dropdown
        if (input.id.includes('company_name')) {
            const pointNumber = input.id.replace('company_name_', '');
            const addressInput = document.getElementById(`address_loading_${pointNumber}`);
            fetchCompanySuggestions('', dropdown, addressInput);
        } else if (input.id.includes('address_loading')) {
            const pointNumber = input.id.replace('address_loading_', '');
            const companyInput = document.getElementById(`company_name_${pointNumber}`);
            const companyName = companyInput ? companyInput.value.trim() : '';
            fetchAddressSuggestions('', dropdown, companyName);
        } else if (input.id.includes('contact_person')) {
            const idParts = input.id.split('_');
            let phoneInputId;
            if (idParts.length === 3) {
                // Format: contact_person_1 (main contact)
                phoneInputId = `phone_number_${idParts[2]}`;
            } else if (idParts.length === 4) {
                // Format: contact_person_1_2 (additional contact)
                phoneInputId = `phone_number_${idParts[2]}_${idParts[3]}`;
            }
            const phoneInput = document.getElementById(phoneInputId);
            fetchContactSuggestions('', dropdown, phoneInput);
        }
    }

    function hideComboDropdown(comboSelect) {
        const dropdown = comboSelect.querySelector('.combo-dropdown');
        comboSelect.classList.remove('open');
        dropdown.classList.remove('show');
    }

    function closeAllComboDropdowns() {
        document.querySelectorAll('.combo-select.open').forEach(combo => {
            hideComboDropdown(combo);
        });
    }

    // Track if global listeners have been added
    let globalListenersAdded = false;

    // Initialize combo-select functionality
    function initializeComboSelects() {
        // Setup existing combos
        const comboInputs = document.querySelectorAll('.combo-input');
        comboInputs.forEach((input, index) => {
            setupComboSelect(input);
        });
        
        // Only add global listeners once
        if (!globalListenersAdded) {
            globalListenersAdded = true;
            
            // Handle clicks outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.combo-select')) {
                    closeAllComboDropdowns();
                }
            });
            
            // Handle arrow clicks with event delegation (as backup)
            document.addEventListener('click', function(e) {
                const arrow = e.target.closest('.combo-arrow');
                if (arrow) {
                    e.preventDefault();
                    e.stopPropagation();
                    const comboSelect = arrow.closest('.combo-select');
                    if (comboSelect) {
                        toggleComboDropdown(comboSelect);
                    }
                    return;
                }
            });
            
            // Handle option clicks with event delegation
            document.addEventListener('click', function(e) {
                if (e.target.closest('.combo-option')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const option = e.target.closest('.combo-option');
                    const dropdown = option.closest('.combo-dropdown');
                    const comboSelect = dropdown.closest('.combo-select');
                    const input = comboSelect.querySelector('.combo-input');
                    
                    const optionPrimary = option.querySelector('.option-primary');
                    if (!optionPrimary) {
                        console.error('No .option-primary found in option:', option);
                        return;
                    }
                    
                    const optionText = optionPrimary.textContent;
                    input.value = optionText;
                    
                    hideComboDropdown(comboSelect);
                    
                    // If this is a company selection, update address suggestions
                    if (input.id.includes('company_name')) {
                        const pointNumber = input.id.replace('company_name_', '');
                        const addressInput = document.getElementById(`address_loading_${pointNumber}`);
                        if (addressInput) {
                            const addressDropdown = addressInput.closest('.combo-select').querySelector('.combo-dropdown');
                            fetchAddressSuggestions('', addressDropdown, optionText);
                        }
                    }
                    // If this is a contact selection, update phone number
                    else if (input.id.includes('contact_person')) {
                        const pointNumber = input.id.replace('contact_person_', '');
                        const phoneInput = document.getElementById(`phone_number_${pointNumber}`);
                        const phoneNumber = option.getAttribute('data-phone');
                        if (phoneInput && phoneNumber) {
                            phoneInput.value = phoneNumber;
                        }
                    }
                }
            });
        }
    }

    // Fetch contact suggestions from all sources
    function fetchContactSuggestions(searchTerm, dropdown, phoneInput) {
        console.log('DEBUG: fetchContactSuggestions called with:', {searchTerm, dropdown, phoneInput});
        
        if (!dropdown) {
            console.warn('fetchContactSuggestions called with null dropdown');
            return;
        }
        
        const clientId = document.getElementById('client_id').value;
        console.log('DEBUG: Client ID:', clientId);
        
        if (!clientId) {
            console.log('No client selected for contact suggestions');
            dropdown.classList.remove('show');
            return;
        }
        
        const url = `../assets/add_order_get_contacts.php?term=${encodeURIComponent(searchTerm)}&client_id=${clientId}`;
        console.log('DEBUG: Fetching from URL:', url);
        
        fetch(url)
            .then(response => {
                console.log('DEBUG: Fetch response received:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('DEBUG: Fetch data received:', data);
                dropdown.innerHTML = '';
                if (data.success && data.contacts && data.contacts.length > 0) {
                    console.log('DEBUG: Creating', data.contacts.length, 'contact options');
                    data.contacts.forEach(contact => {
                        const div = document.createElement('div');
                        div.className = 'combo-option';
                        div.innerHTML = `
                            <div>
                                <div class="option-primary">${contact.name}</div>
                                <div class="option-secondary">${contact.phone}</div>
                            </div>
                            <span class="option-count">Использовано: ${contact.usage_count} раз</span>
                        `;
                        // Store phone number as data attribute for easy access
                        div.setAttribute('data-phone', contact.phone);
                        dropdown.appendChild(div);
                    });
                    dropdown.classList.add('show');
                    console.log('DEBUG: Options added, dropdown classes:', dropdown.className);
                    console.log('DEBUG: Dropdown innerHTML:', dropdown.innerHTML);
                    console.log('DEBUG: Final dropdown display style:', window.getComputedStyle(dropdown).display);
                } else {
                    console.log('DEBUG: No contacts returned or success=false');
                    dropdown.classList.remove('show');
                }
            })
            .catch(error => {
                console.error('Error fetching contacts:', error);
                dropdown.classList.remove('show');
            });
    }

    // Fetch company suggestions from route history
    function fetchCompanySuggestions(searchTerm, dropdown, addressInput) {
        console.log('DEBUG: fetchCompanySuggestions called with:', {searchTerm, dropdown, addressInput});
        
        if (!dropdown) {
            console.warn('fetchCompanySuggestions called with null dropdown');
            return;
        }
        
        const clientId = document.getElementById('client_id').value;
        console.log('DEBUG: Company fetch - Client ID:', clientId);
        
        if (!clientId) {
            console.log('DEBUG: No client ID, hiding dropdown');
            dropdown.classList.remove('show');
            return;
        }
        
        const url = `../assets/get_client_route_history.php?client_id=${clientId}&type=companies&search=${encodeURIComponent(searchTerm)}`;
        console.log('DEBUG: Company fetch URL:', url);
        
        fetch(url)
            .then(response => {
                console.log('DEBUG: Company fetch response received:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('DEBUG: Company fetch data received:', data);
                dropdown.innerHTML = '';
                if (data.success && data.companies && data.companies.length > 0) {
                    console.log('DEBUG: Creating', data.companies.length, 'company options');
                    data.companies.forEach(company => {
                        const div = document.createElement('div');
                        div.className = 'combo-option';
                        div.innerHTML = `
                            <span class="option-primary">${company.name}</span>
                            <span class="option-count">Использовано: ${company.usage_count} раз</span>
                        `;
                        dropdown.appendChild(div);
                    });
                    dropdown.classList.add('show');
                    console.log('DEBUG: Company options added, dropdown classes:', dropdown.className);
                    console.log('DEBUG: Company dropdown innerHTML:', dropdown.innerHTML);
                } else {
                    console.log('DEBUG: No companies returned or success=false');
                    dropdown.classList.remove('show');
                }
            })
            .catch(error => {
                console.error('Error fetching company suggestions:', error);
                console.error('Full error details:', {
                    message: error.message,
                    stack: error.stack,
                    url: url
                });
                dropdown.classList.remove('show');
            });
    }

    // Fetch address suggestions from route history
    function fetchAddressSuggestions(searchTerm, dropdown, companyName = '') {
        if (!dropdown) {
            console.warn('fetchAddressSuggestions called with null dropdown');
            return;
        }
        
        const clientId = document.getElementById('client_id').value;
        
        if (!clientId) {
            dropdown.classList.remove('show');
            return;
        }
        
        let url = `../assets/get_client_route_history.php?client_id=${clientId}&type=addresses&search=${encodeURIComponent(searchTerm)}`;
        if (companyName) {
            url += `&company_name=${encodeURIComponent(companyName)}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                dropdown.innerHTML = '';
                if (data.success && data.addresses && data.addresses.length > 0) {
                    data.addresses.forEach(addressData => {
                        const div = document.createElement('div');
                        div.className = 'combo-option';
                        div.innerHTML = `
                            <div class="option-primary">${addressData.address}</div>
                            ${addressData.company_name && !companyName ? `<div class="option-secondary">${addressData.company_name}</div>` : ''}
                            <span class="option-count">Использовано: ${addressData.usage_count} раз</span>
                        `;
                        dropdown.appendChild(div);
                    });
                    dropdown.classList.add('show');
                } else {
                    dropdown.classList.remove('show');
                }
            })
            .catch(error => {
                console.error('Error fetching address suggestions:', error);
                dropdown.classList.remove('show');
            });
    }

    // Validate chronological order
    function validateChronologicalOrder() {
        const points = document.querySelectorAll('.route-point');
        let lastDateTime = null;
        let isValid = true;

        points.forEach((point, index) => {
            const dateInput = point.querySelector('input[type="date"]');
            const timeInput = point.querySelector('.time-input');
            
            if (dateInput.value && timeInput.value) {
                const currentDateTime = new Date(`${dateInput.value}T${timeInput.value}`);
                
                if (lastDateTime && currentDateTime < lastDateTime) {
                    isValid = false;
                    dateInput.classList.add('error');
                    timeInput.classList.add('error');
                } else {
                    dateInput.classList.remove('error');
                    timeInput.classList.remove('error');
                    lastDateTime = currentDateTime;
                }
            }
        });

        return isValid;
    }

    // Add event listeners for add/remove points
    if (addRoutePointBtn) {
        addRoutePointBtn.addEventListener('click', createRoutePoint);
    }

    // Route point remove button state management
    function updateRouteRemoveButtonState() {
        const routePoints = document.querySelectorAll('.route-point');
        const removeButton = document.getElementById('remove-route-point');
        
        if (removeButton) {
            // Enable remove button only if there's more than one route point
            removeButton.disabled = routePoints.length <= 1;
        }
    }

    if (removeRoutePointBtn) {
        removeRoutePointBtn.addEventListener('click', function() {
            const points = document.querySelectorAll('.route-point');
            if (points.length > 1) {
                points[points.length - 1].remove();
                pointCounter--;
                updateRouteRemoveButtonState();
                
                // Validate chronological order after removing a point
                validateChronologicalOrder();
            }
        });
    }

    // Route next button validation is now handled in the HTML tab navigation
    // This removes duplicate validation that was causing double messages

    // Add this to your existing JavaScript
    if (document.getElementById('additional-next')) {
        document.getElementById('additional-next').addEventListener('click', function() {
            // Proceed to next tab
            const nextTab = document.querySelector('#orderTabs .nav-link[href="#tab4"]');
            if (nextTab) {
                nextTab.click();
            }
        });
    }

    // ======== TOTALS CALCULATION FUNCTIONALITY ========
    function updateTotalsSummary() {
        // Get all the values
        const cargoPrice = parseFloat(document.getElementById('cargo_price').value) || 0;
        
        // Check if insurance is needed
        const insuranceStatus = document.getElementById('insurance_status');
        const isInsuranceNeeded = insuranceStatus && insuranceStatus.value === '2'; // '2' means "Требуется" (Required)
        const totalInsurance = isInsuranceNeeded ? (parseFloat(document.getElementById('total_insurance').value) || 0) : 0;
        
        const transportTotal = parseFloat(document.getElementById('transport_total').value) || 0;
        
        // Calculate extra services total
        let extraServicesTotal = 0;
        const extraServiceRows = document.querySelectorAll('.extra-service-row');
        extraServiceRows.forEach(row => {
            const totalInput = row.querySelector('input[name*="extra_service_total"]');
            if (totalInput && totalInput.value) {
                extraServicesTotal += parseFloat(totalInput.value) || 0;
            }
        });

        // Calculate grand total (EXCLUDING cargo price - only service costs)
        const grandTotal = totalInsurance + transportTotal + extraServicesTotal;

        // Update only the grand total display with animation
        updateTotalValue('summary-grand-total', grandTotal);

        console.log('Totals updated (cargo price excluded from grand total):', {
            cargoPrice: cargoPrice + ' (excluded)',
            insuranceNeeded: isInsuranceNeeded,
            totalInsurance,
            transportTotal,
            extraServicesTotal,
            grandTotal
        });
    }

    function updateTotalValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            // Add updating animation
            element.classList.add('updating');
            
            // Get selected currency
            const currencySelect = document.getElementById('currency');
            let currencySymbol = '';
            let currencyCode = '';
            
            if (currencySelect && currencySelect.selectedOptions.length > 0) {
                const selectedOption = currencySelect.selectedOptions[0];
                currencyCode = selectedOption.textContent || '';
                // Extract currency symbol/code from the option text
                if (currencyCode.includes('USD') || currencyCode.includes('$')) {
                    currencySymbol = '$';
                } else if (currencyCode.includes('EUR') || currencyCode.includes('€')) {
                    currencySymbol = '€';
                } else if (currencyCode.includes('RUB') || currencyCode.includes('₽')) {
                    currencySymbol = '₽';
                } else if (currencyCode.includes('AZN') || currencyCode.includes('₼')) {
                    currencySymbol = '₼';
                } else {
                    // Use the currency code as fallback
                    currencySymbol = currencyCode.split(' ')[0] || '';
                }
            }
            
            // Format the value with currency
            const formattedValue = value.toFixed(2);
            if (currencySymbol) {
                element.textContent = `${formattedValue} ${currencySymbol}`;
            } else {
                element.textContent = formattedValue;
            }
            
            // Remove animation class after animation completes
            setTimeout(() => {
                element.classList.remove('updating');
            }, 300);
        }
    }

    // Add event listeners to all inputs that affect totals
    function setupTotalsEventListeners() {
        // Listen to cargo price changes
        const cargoPriceInput = document.getElementById('cargo_price');
        if (cargoPriceInput) {
            cargoPriceInput.addEventListener('input', updateTotalsSummary);
            cargoPriceInput.addEventListener('change', updateTotalsSummary);
        }

        // Listen to currency changes
        const currencySelect = document.getElementById('currency');
        if (currencySelect) {
            currencySelect.addEventListener('change', updateTotalsSummary);
        }

        // Listen to fields that trigger insurance calculation
        const insuranceStatusInput = document.getElementById('insurance_status');
        const rateInput = document.getElementById('rate');
        
        if (insuranceStatusInput) {
            insuranceStatusInput.addEventListener('change', () => {
                setTimeout(updateTotalsSummary, 200); // Delay to allow calculation to complete
            });
        }
        
        if (rateInput) {
            rateInput.addEventListener('input', () => {
                setTimeout(updateTotalsSummary, 200);
            });
            rateInput.addEventListener('change', () => {
                setTimeout(updateTotalsSummary, 200);
            });
        }

        // Listen to fields that trigger transport total calculation
        const transportRateInput = document.getElementById('transport_rate');
        const transportHoursInput = document.getElementById('transport_hours');
        const overworkRateInput = document.getElementById('overwork_rate');
        const overworkHoursInput = document.getElementById('overwork_hours');
        
        [transportRateInput, transportHoursInput, overworkRateInput, overworkHoursInput].forEach(input => {
            if (input) {
                input.addEventListener('input', () => {
                    setTimeout(updateTotalsSummary, 200);
                });
                input.addEventListener('change', () => {
                    setTimeout(updateTotalsSummary, 200);
                });
            }
        });

        // Also listen to transport type changes as they affect rates
        const transportTypeInput = document.getElementById('transport_type');
        if (transportTypeInput) {
            transportTypeInput.addEventListener('change', () => {
                setTimeout(updateTotalsSummary, 500); // Longer delay for rate fetching
            });
        }

        // Listen to extra services changes
        function setupExtraServiceListeners() {
            const extraServiceRows = document.querySelectorAll('.extra-service-row');
            extraServiceRows.forEach((row, index) => {
                const serviceSelect = row.querySelector('select[name*="extra_service_"]');
                const quantityInput = row.querySelector('input[name*="extra_service_quantity"]');
                const priceInput = row.querySelector('input[name*="extra_service_price"]');
                const totalInput = row.querySelector('input[name*="extra_service_total"]');
                
                // Add listeners with delays to allow calculations to complete
                if (serviceSelect) {
                    serviceSelect.addEventListener('change', () => {
                        setTimeout(updateTotalsSummary, 300);
                    });
                }
                
                if (quantityInput) {
                    quantityInput.addEventListener('input', () => {
                        setTimeout(updateTotalsSummary, 200);
                    });
                    quantityInput.addEventListener('change', () => {
                        setTimeout(updateTotalsSummary, 200);
                    });
                }
                
                // Add spinner button listeners
                const spinnerUp = row.querySelector('.spinner-up');
                const spinnerDown = row.querySelector('.spinner-down');
                
                if (spinnerUp) {
                    spinnerUp.addEventListener('click', () => {
                        setTimeout(updateTotalsSummary, 200);
                    });
                }
                
                if (spinnerDown) {
                    spinnerDown.addEventListener('click', () => {
                        setTimeout(updateTotalsSummary, 200);
                    });
                }
            });
        }

        // Set up initial listeners for existing extra service rows
        setupExtraServiceListeners();

        // Listen for new extra service rows being added
        const extraServicesContainer = document.getElementById('extra-services-container');
        if (extraServicesContainer) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === Node.ELEMENT_NODE && node.classList.contains('extra-service-row')) {
                                // Set up listeners for the new row
                                const serviceSelect = node.querySelector('select[name*="extra_service_"]');
                                const quantityInput = node.querySelector('input[name*="extra_service_quantity"]');
                                const spinnerUp = node.querySelector('.spinner-up');
                                const spinnerDown = node.querySelector('.spinner-down');
                                
                                if (serviceSelect) {
                                    serviceSelect.addEventListener('change', () => {
                                        setTimeout(updateTotalsSummary, 300);
                                    });
                                }
                                
                                if (quantityInput) {
                                    quantityInput.addEventListener('input', () => {
                                        setTimeout(updateTotalsSummary, 200);
                                    });
                                    quantityInput.addEventListener('change', () => {
                                        setTimeout(updateTotalsSummary, 200);
                                    });
                                }
                                
                                if (spinnerUp) {
                                    spinnerUp.addEventListener('click', () => {
                                        setTimeout(updateTotalsSummary, 200);
                                    });
                                }
                                
                                if (spinnerDown) {
                                    spinnerDown.addEventListener('click', () => {
                                        setTimeout(updateTotalsSummary, 200);
                                    });
                                }
                            }
                        });
                        // Update totals when rows are added or removed
                        setTimeout(updateTotalsSummary, 100);
                    }
                });
            });
            observer.observe(extraServicesContainer, { childList: true, subtree: true });
        }
    }

    // Monitor readonly fields for changes using polling
    let lastValues = {
        totalInsurance: 0,
        transportTotal: 0,
        extraServicesTotal: 0
    };

    function monitorCalculatedFields() {
        const totalInsuranceInput = document.getElementById('total_insurance');
        const transportTotalInput = document.getElementById('transport_total');
        
        // Calculate current extra services total
        let currentExtraServices = 0;
        const extraServiceRows = document.querySelectorAll('.extra-service-row');
        extraServiceRows.forEach(row => {
            const totalInput = row.querySelector('input[name*="extra_service_total"]');
            if (totalInput && totalInput.value) {
                currentExtraServices += parseFloat(totalInput.value) || 0;
            }
        });
        
        if (totalInsuranceInput && transportTotalInput) {
            const currentInsurance = parseFloat(totalInsuranceInput.value) || 0;
            const currentTransport = parseFloat(transportTotalInput.value) || 0;
            
            if (currentInsurance !== lastValues.totalInsurance || 
                currentTransport !== lastValues.transportTotal ||
                currentExtraServices !== lastValues.extraServicesTotal) {
                
                lastValues.totalInsurance = currentInsurance;
                lastValues.transportTotal = currentTransport;
                lastValues.extraServicesTotal = currentExtraServices;
                
                console.log('Detected changes in calculated fields:', {
                    insurance: currentInsurance,
                    transport: currentTransport,
                    extraServices: currentExtraServices
                });
                
                updateTotalsSummary();
            }
        }
    }

    // Poll for changes every 500ms
    let monitorInterval;

    // Initialize totals when the Cost tab is shown
    function initializeTotals() {
        // Set up all event listeners
        setupTotalsEventListeners();
        
        // Start monitoring calculated fields
        if (monitorInterval) {
            clearInterval(monitorInterval);
        }
        monitorInterval = setInterval(monitorCalculatedFields, 500);
        
        // Initial calculation
        updateTotalsSummary();
        
        console.log('Totals calculation system initialized with field monitoring');
    }

    // Initialize totals when DOM is ready and when Cost tab becomes active
    const costTab = document.querySelector('#orderTabs .nav-link[href="#cost"]');
    if (costTab) {
        costTab.addEventListener('shown.bs.tab', initializeTotals);
    }

    // Also initialize if we're already on the cost tab
    setTimeout(() => {
        if (document.querySelector('#cost.active')) {
            initializeTotals();
        }
    }, 500);

    // Initialize form data on page load
    console.log('Initializing add order form...');
    
    // Load all dropdown data
    console.log('Calling fetchShippingTypes...');
    fetchShippingTypes();
    console.log('Calling fetchTransportTypes...');
    fetchTransportTypes();
    console.log('Calling fetchCargoNames...');
    fetchCargoNames();
    console.log('Calling fetchLoadingTypes...');
    fetchLoadingTypes();
    console.log('Calling fetchPackagingTypes...');
    fetchPackagingTypes();
    console.log('Calling fetchCurrencies...');
    fetchCurrencies();
    console.log('Calling fetchExtraServices...');
    fetchExtraServices();
    
    // Set up initial extra service row
    console.log('Creating extra service row...');
    createExtraServiceRow();
    
    // Generate initial order number if user has permission
    const generateBtn = document.getElementById('generate_order_number');
    if (generateBtn && !generateBtn.disabled) {
        console.log('Generating initial order number...');
        generateOrderNumber();
    }
    
    // Initialize totals calculation system
    console.log('Initializing totals calculation...');
    setTimeout(() => {
        initializeTotals();
    }, 1000);
    
    // Initialize button states
    console.log('Initializing button states...');
    updateRemoveButtonState(); // Extra services remove button
    updateRouteRemoveButtonState(); // Route points remove button
    
    // Initialize event listeners for the first route point (created in HTML)
    console.log('Initializing first route point event listeners...');
    attachPointEventListeners(1);
    
    // Initialize combo-select dropdowns
    initializeComboSelects();
    
    // Initialize additional contacts functionality
    console.log('Initializing additional contacts...');
    initializeAdditionalContacts();
    
    console.log('Add order form initialization complete');
    


    // Additional Contacts Functionality
    function initializeAdditionalContacts() {
        // Initialize add contact buttons for existing route points
        document.querySelectorAll('.btn-add-contact').forEach(button => {
            button.addEventListener('click', handleAddContact);
        });
        
        console.log('Additional contacts initialized');
    }
    
    function handleAddContact(e) {
        const button = e.target.closest('.btn-add-contact');
        const pointNumber = button.getAttribute('data-point');
        const container = document.getElementById(`additional_contacts_container_${pointNumber}`);
        
        // Count existing additional contacts
        const existingContacts = container.querySelectorAll('.additional-contact-row').length;
        
        if (existingContacts >= 2) { // Max 2 additional contacts (3 total including main contact)
            showWarning('Максимум 3 контакта на пункт маршрута', 'Лимит контактов');
            return;
        }
        
        // Create new contact row
        const contactRow = createAdditionalContactRow(pointNumber, existingContacts + 2); // +2 because main contact is #1
        container.appendChild(contactRow);
        
        // Initialize combo-select for new contact
        const newContactInput = contactRow.querySelector('.contact-input');
        if (newContactInput) {
            setupComboSelect(newContactInput);
        }
        
        // Update button state
        updateAddContactButtonState(pointNumber);
        
        console.log(`Added additional contact for point ${pointNumber}`);
    }
    
    function createAdditionalContactRow(pointNumber, contactNumber) {
        const row = document.createElement('div');
        row.className = 'additional-contact-row';
        row.setAttribute('data-contact-number', contactNumber);
        
        row.innerHTML = `
            <div class="form-group">
                <label for="contact_person_${pointNumber}_${contactNumber}">Контактное лицо ${contactNumber}</label>
                <div class="contact-person-container">
                    <div class="combo-select">
                        <input type="text" 
                               id="contact_person_${pointNumber}_${contactNumber}" 
                               name="contact_person_${pointNumber}_${contactNumber}" 
                               class="form-input contact-input combo-input" 
                               autocomplete="off"
                               placeholder="Выберите или введите контакт">
                        <div class="combo-arrow">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="combo-dropdown contact-dropdown">
                            <!-- Options will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="phone_number_${pointNumber}_${contactNumber}">Телефон ${contactNumber}</label>
                <div class="phone-number-container">
                    <input type="text" 
                           id="phone_number_${pointNumber}_${contactNumber}" 
                           name="phone_number_${pointNumber}_${contactNumber}" 
                           class="form-input">
                    <button type="button" class="btn-remove-contact" onclick="removeAdditionalContact(this, ${pointNumber})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        return row;
    }
    
    function updateAddContactButtonState(pointNumber) {
        const button = document.querySelector(`.btn-add-contact[data-point="${pointNumber}"]`);
        const container = document.getElementById(`additional_contacts_container_${pointNumber}`);
        const existingContacts = container.querySelectorAll('.additional-contact-row').length;
        
        if (existingContacts >= 2) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-check"></i> Максимум контактов';
        } else {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-plus"></i> Добавить контакт';
        }
    }
    
    // Global function to remove additional contact
    window.removeAdditionalContact = function(button, pointNumber) {
        const row = button.closest('.additional-contact-row');
        row.remove();
        
        // Update button state
        updateAddContactButtonState(pointNumber);
        
        // Renumber remaining contacts
        renumberAdditionalContacts(pointNumber);
        
        console.log(`Removed additional contact for point ${pointNumber}`);
    };
    
    function renumberAdditionalContacts(pointNumber) {
        const container = document.getElementById(`additional_contacts_container_${pointNumber}`);
        const rows = container.querySelectorAll('.additional-contact-row');
        
        rows.forEach((row, index) => {
            const contactNumber = index + 2; // +2 because main contact is #1
            row.setAttribute('data-contact-number', contactNumber);
            
            // Update input IDs and names
            const contactInput = row.querySelector('.contact-input');
            const phoneInput = row.querySelector('.phone-number-container input');
            const contactLabel = row.querySelector('.contact-person-container label');
            const phoneLabel = row.querySelector('.phone-number-container label');
            
            if (contactInput) {
                contactInput.id = `contact_person_${pointNumber}_${contactNumber}`;
                contactInput.name = `contact_person_${pointNumber}_${contactNumber}`;
            }
            
            if (phoneInput) {
                phoneInput.id = `phone_number_${pointNumber}_${contactNumber}`;
                phoneInput.name = `phone_number_${pointNumber}_${contactNumber}`;
            }
            
            if (contactLabel) {
                contactLabel.textContent = `Контактное лицо ${contactNumber}`;
                contactLabel.setAttribute('for', `contact_person_${pointNumber}_${contactNumber}`);
            }
            
            if (phoneLabel) {
                phoneLabel.textContent = `Телефон ${contactNumber}`;
                phoneLabel.setAttribute('for', `phone_number_${pointNumber}_${contactNumber}`);
            }
        });
    }

    // File Upload Functionality
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('file-input');
    const browseBtn = document.getElementById('browse-files');
    const uploadedFiles = document.getElementById('uploaded-files');
    const filesCount = document.getElementById('files-count');
    
    let selectedFiles = [];
    const maxFiles = 3;
    
    // Initialize file upload
    if (uploadArea && fileInput && browseBtn) {
        initializeFileUpload();
    }
    
    function initializeFileUpload() {
        // Browse button click
        browseBtn.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Upload area click
        uploadArea.addEventListener('click', (e) => {
            if (e.target === uploadArea || e.target.closest('.upload-content')) {
                fileInput.click();
            }
        });
        
        // File input change
        fileInput.addEventListener('change', (e) => {
            handleFileSelection(e.target.files);
            // Clear the file input to allow selecting the same files again
            e.target.value = '';
        });
        
        // Drag and drop events
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            if (!uploadArea.contains(e.relatedTarget)) {
                uploadArea.classList.remove('dragover');
            }
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            handleFileSelection(e.dataTransfer.files);
        });
    }
    
    function handleFileSelection(files) {
        const fileArray = Array.from(files);
        
        console.log(`Attempting to add ${fileArray.length} files. Current count: ${selectedFiles.length}`);
        
        // Check if adding these files would exceed the limit
        if (selectedFiles.length + fileArray.length > maxFiles) {
            showWarning(`Максимум ${maxFiles} файла разрешено. Выберите меньше файлов.`, 'Слишком много файлов');
            return;
        }
        
        // Validate and add files
        let validFilesCount = 0;
        fileArray.forEach(file => {
            console.log(`Validating file: ${file.name}, size: ${file.size}, type: ${file.type}`);
            if (validateFile(file)) {
                selectedFiles.push({
                    file: file,
                    id: Date.now() + Math.random(),
                    uploaded: false
                });
                validFilesCount++;
            }
        });
        
        console.log(`Added ${validFilesCount} valid files. Total files: ${selectedFiles.length}`);
        
        updateFileDisplay();
        uploadFiles();
    }
    
    function validateFile(file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain'
        ];
        
        // Check for duplicate files
        const isDuplicate = selectedFiles.some(fileObj => 
            fileObj.file.name === file.name && 
            fileObj.file.size === file.size
        );
        
        if (isDuplicate) {
            console.log(`Duplicate file detected: ${file.name}`);
                            showWarning(`Файл "${file.name}" уже добавлен`, 'Дублирующий файл');
            return false;
        }
        
        if (file.size > maxSize) {
            console.log(`File too large: ${file.name}, size: ${file.size}`);
                            showError(`Файл "${file.name}" слишком большой. Максимальный размер: 10 МБ`, 'Файл слишком большой');
            return false;
        }
        
        if (!allowedTypes.includes(file.type)) {
            console.log(`Invalid file type: ${file.name}, type: ${file.type}`);
                            showError(`Файл "${file.name}" имеет неподдерживаемый формат. Разрешенные форматы: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF, TXT`, 'Неподдерживаемый формат');
            return false;
        }
        
        console.log(`File validation passed: ${file.name}`);
        return true;
    }
    
    function updateFileDisplay() {
        uploadedFiles.innerHTML = '';
        filesCount.textContent = selectedFiles.length;
        
        selectedFiles.forEach(fileObj => {
            const fileItem = createFileItem(fileObj);
            uploadedFiles.appendChild(fileItem);
        });
    }
    
    function createFileItem(fileObj) {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.dataset.fileId = fileObj.id;
        
        if (fileObj.uploaded) {
            fileItem.classList.add('uploaded');
        } else if (fileObj.uploading) {
            fileItem.classList.add('uploading');
        }
        
        const fileIcon = getFileIcon(fileObj.file.type);
        const fileSize = formatFileSize(fileObj.file.size);
        
        fileItem.innerHTML = `
            <div class="file-info">
                <i class="fas ${fileIcon.icon} file-icon ${fileIcon.class}"></i>
                <div class="file-details">
                    <div class="file-name">${fileObj.file.name}</div>
                    <div class="file-size">${fileSize}</div>
                    ${fileObj.error ? `<div class="upload-error">${fileObj.error}</div>` : ''}
                </div>
            </div>
            <div class="file-actions">
                <button type="button" class="btn-remove-file" onclick="removeFile('${fileObj.id}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            ${fileObj.uploading ? '<div class="upload-progress"><div class="progress-bar"><div class="progress-fill"></div></div></div>' : ''}
        `;
        
        return fileItem;
    }
    
    function getFileIcon(mimeType) {
        if (mimeType.includes('pdf')) {
            return { icon: 'fa-file-pdf', class: 'pdf' };
        } else if (mimeType.includes('word') || mimeType.includes('document')) {
            return { icon: 'fa-file-word', class: 'doc' };
        } else if (mimeType.includes('excel') || mimeType.includes('sheet')) {
            return { icon: 'fa-file-excel', class: 'excel' };
        } else if (mimeType.includes('image')) {
            return { icon: 'fa-file-image', class: 'image' };
        } else if (mimeType.includes('text')) {
            return { icon: 'fa-file-alt', class: 'text' };
        } else {
            return { icon: 'fa-file', class: 'default' };
        }
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function uploadFiles() {
        const filesToUpload = selectedFiles.filter(fileObj => !fileObj.uploaded && !fileObj.uploading);
        
        if (filesToUpload.length === 0) {
            return;
        }
        
        const formData = new FormData();
        filesToUpload.forEach(fileObj => {
            formData.append('files[]', fileObj.file);
            fileObj.uploading = true;
        });
        
        updateFileDisplay();
        
        fetch('../assets/upload_supporting_documents.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mark files as uploaded
                filesToUpload.forEach((fileObj, index) => {
                    fileObj.uploading = false;
                    fileObj.uploaded = true;
                    if (data.files[index]) {
                        fileObj.tempName = data.files[index].temp_name;
                        fileObj.tempPath = data.files[index].temp_path;
                    }
                });
                
                // Show any errors
                if (data.errors && data.errors.length > 0) {
                    console.warn('Upload errors:', data.errors);
                    data.errors.forEach(error => {
                        console.error('Upload error:', error);
                    });
                }
            } else {
                // Mark files as failed
                filesToUpload.forEach(fileObj => {
                    fileObj.uploading = false;
                    fileObj.error = data.error || 'Upload failed';
                });
                showError('Ошибка загрузки файлов: ' + (data.error || 'Unknown error'), 'Ошибка загрузки');
            }
            
            updateFileDisplay();
        })
        .catch(error => {
            console.error('Upload error:', error);
            filesToUpload.forEach(fileObj => {
                fileObj.uploading = false;
                fileObj.error = 'Network error';
            });
            updateFileDisplay();
            showError('Ошибка загрузки файлов: ' + error.message, 'Ошибка загрузки');
        });
    }
    
    // Global function to remove files (called from HTML)
    window.removeFile = function(fileId) {
        const index = selectedFiles.findIndex(fileObj => fileObj.id == fileId);
        if (index > -1) {
            selectedFiles.splice(index, 1);
            updateFileDisplay();
            
            // Clear the file input to reset its state
            if (fileInput) {
                fileInput.value = '';
            }
            
            console.log(`File removed. Remaining files: ${selectedFiles.length}`);
        }
    };
    
    // Function to get uploaded file data for form submission
    window.getUploadedFiles = function() {
        return selectedFiles.filter(fileObj => fileObj.uploaded).map(fileObj => ({
            original_name: fileObj.file.name,
            temp_name: fileObj.tempName,
            temp_path: fileObj.tempPath,
            size: fileObj.file.size,
            type: fileObj.file.type
        }));
    };

    // Main order submission function
    window.submitOrder = function() {
        
        // Show loading state (for confirm button in preview modal)
        const submitBtn = document.getElementById('confirmSubmitOrder');
        let originalText = '';
        
        if (submitBtn) {
            originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Создание заказа...';
        }
        
        try {
            // Collect all form data
            const formData = new FormData();
            
            // Basic order information
            formData.append('client_id', document.getElementById('client_id')?.value || '');
            formData.append('contractor_id', document.getElementById('contractor')?.value || '');
            formData.append('display_order_number', document.getElementById('order_number')?.value || '');
            formData.append('order_date', document.getElementById('order_date')?.value || '');
            formData.append('shipping_type', document.getElementById('shipping_type')?.value || '');
            formData.append('transport_type', document.getElementById('transport_type')?.value || '');
            
            // Handle contract selection - check both static and select fields
            const contractSelect = document.getElementById('contract_select');
            const contractStatic = document.getElementById('contract_static');
            
            let contractValue = '';
            
            // Check which field is active (visible and enabled)
            if (contractStatic && contractStatic.style.display !== 'none' && !contractStatic.disabled) {
                contractValue = contractStatic.value || '';
            } else if (contractSelect && contractSelect.style.display !== 'none' && !contractSelect.disabled) {
                contractValue = contractSelect.value || '';
            }
            
            formData.append('contract_number', contractValue);
            formData.append('contract_id', getContractIdFromNumber(contractValue));
            
            // Cargo information
            formData.append('cargo_name', document.getElementById('cargo_name')?.value || '');
            formData.append('cargo_weight', document.getElementById('cargo_weight')?.value || '');
            formData.append('weight_unit', getActiveWeightUnit());
            formData.append('cargo_volume', document.getElementById('cargo_volume')?.value || '');
            formData.append('length', document.getElementById('length')?.value || '');
            formData.append('width', document.getElementById('width')?.value || '');
            formData.append('height', document.getElementById('height')?.value || '');
            formData.append('cargo_quantity', document.getElementById('cargo_quantity')?.value || '');
            formData.append('loading_type', document.getElementById('loading_type')?.value || '');
            formData.append('packaging_type', document.getElementById('packaging_type')?.value || '');
            formData.append('min_temperature', document.getElementById('min_temperature')?.value || '');
            formData.append('max_temperature', document.getElementById('max_temperature')?.value || '');
            formData.append('temp_print_list', document.getElementById('temp_print_list')?.value || '');
            
            // Cost information
            formData.append('cargo_price', document.getElementById('cargo_price')?.value || '');
            formData.append('currency_id', document.getElementById('currency')?.value || '');
            formData.append('rate', document.getElementById('rate')?.value || '');
            formData.append('total_insurance', document.getElementById('total_insurance')?.value || '');
            formData.append('transport_rate', document.getElementById('transport_rate')?.value || '');
            formData.append('transport_hours', document.getElementById('transport_hours')?.value || '');
            formData.append('overwork_hours', document.getElementById('overwork_hours')?.value || '');
            formData.append('transport_total', document.getElementById('transport_total')?.value || '');
            formData.append('order_total', document.getElementById('summary-grand-total')?.textContent?.replace(/[^\d.,]/g, '') || '');
            
            // Additional information
            formData.append('order_notes', document.getElementById('order_notes')?.value || '');
            
            // Collect extra services
            const extraServices = collectExtraServices();
            if (extraServices.length > 0) {
                formData.append('extra_services', JSON.stringify(extraServices));
                formData.append('extra_services_total', calculateExtraServicesTotal(extraServices));
            }
            
            // Collect route points
            const routePoints = collectRoutePoints();
            if (routePoints.length > 0) {
                formData.append('route_points', JSON.stringify(routePoints));
            }
            
            // Collect uploaded files
            const uploadedFiles = window.getUploadedFiles ? window.getUploadedFiles() : [];
            if (uploadedFiles.length > 0) {
                formData.append('uploaded_files', JSON.stringify(uploadedFiles));
            }
            
            // Validate required fields
            if (!validateOrderData(formData)) {
                throw new Error('Please fill in all required fields');
            }
            
            // Submit form data to server
            
            // Submit to server
            fetch('../assets/submit_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Raw response status:', response.status);
                console.log('Raw response headers:', response.headers);
                return response.text(); // Get raw text first
            })
            .then(responseText => {
                console.log('Raw response text:', responseText);
                try {
                    const data = JSON.parse(responseText);
                    return data;
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Response that failed to parse:', responseText);
                    throw new Error('Invalid JSON response: ' + responseText.substring(0, 200) + '...');
                }
            })
            .then(data => {
                if (data.success) {
                                // Success - show success message and redirect or reset form
            showSuccess(`Заказ успешно создан!\nНомер заказа: ${data.order_number}`, 'Заказ создан', 8000);
                    
                    // Option 1: Redirect to order view/list
                    // window.location.href = '../pages/manage_orders.php';
                    
                    // Option 2: Reset form for new order
                    if (confirm('Хотите создать еще один заказ?')) {
                        window.location.reload();
                    } else {
                        // Redirect to orders list or dashboard
                        window.location.href = '../pages/admin_dashboard.php';
                    }
                } else {
                    throw new Error(data.error || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Order submission error:', error);
                showError('Ошибка при создании заказа: ' + error.message, 'Ошибка создания');
            })
            .finally(() => {
                // Restore button state
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
            
        } catch (error) {
            console.error('Order submission error:', error);
            showError('Ошибка при подготовке заказа: ' + error.message, 'Ошибка подготовки');
            
            // Restore button state
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    };
    
    // Helper functions for order submission
    function getActiveWeightUnit() {
        const activeBtn = document.querySelector('.unit-btn.active');
        return activeBtn ? (activeBtn.dataset.unit === 'ton' ? 'тонн' : 'кг') : 'тонн';
    }
    
    function getContractIdFromNumber(contractNumber) {
        // This would need to be implemented based on how contract IDs are stored
        // For now, return empty string
        return '';
    }
    
    function collectExtraServices() {
        const services = [];
        const serviceRows = document.querySelectorAll('.extra-service-row');
        
        serviceRows.forEach(row => {
            const serviceName = row.querySelector('.extra-service-select')?.value;
            const servicePrice = row.querySelector('.extra-service-price-input')?.value;
            const quantity = row.querySelector('.extra-service-quantity-input')?.value;
            const total = row.querySelector('.extra-service-total-input')?.value;
            
            if (serviceName && servicePrice && quantity) {
                services.push({
                    service_name: serviceName,
                    service_price: parseFloat(servicePrice) || 0,
                    quantity: parseInt(quantity) || 1,
                    total: parseFloat(total) || 0
                });
            }
        });
        
        return services;
    }
    
    function collectRoutePoints() {
        const points = [];
        const routePoints = document.querySelectorAll('.route-point');
        
        routePoints.forEach((pointElement, index) => {
            const position = pointElement.dataset.position || (index + 1);
            const actionType = pointElement.querySelector(`#action_type_${position}`)?.value;
            const date = pointElement.querySelector(`#point_date_${position}`)?.value;
            const time = pointElement.querySelector(`#point_time_${position}`)?.value;
            const address = pointElement.querySelector(`#address_loading_${position}`)?.value;
            const companyName = pointElement.querySelector(`#company_name_${position}`)?.value;
            const contactPerson = pointElement.querySelector(`#contact_person_${position}`)?.value;
            const phoneNumber = pointElement.querySelector(`#phone_number_${position}`)?.value;
            
            // Collect additional contacts
            const additionalContacts = [];
            const additionalContactRows = pointElement.querySelectorAll('.additional-contact-row');
            additionalContactRows.forEach(row => {
                const contactInput = row.querySelector('.contact-input');
                const phoneInput = row.querySelector('.phone-number-container input');
                
                if (contactInput?.value && phoneInput?.value) {
                    additionalContacts.push({
                        contact_person: contactInput.value,
                        phone_number: phoneInput.value
                    });
                }
            });
            
            if (actionType || date || time || address || companyName) {
                points.push({
                    action_type: actionType,
                    date: date,
                    time: time,
                    address: address,
                    company_name: companyName,
                    contact_person: contactPerson,
                    phone_number: phoneNumber,
                    additional_contacts: additionalContacts
                });
            }
        });
        
        return points;
    }
    
    function calculateExtraServicesTotal(services) {
        return services.reduce((total, service) => total + (service.total || 0), 0);
    }
    
    function validateOrderData(formData) {
        // Check if dropdown fields are loaded
        const shippingTypeSelect = document.getElementById('shipping_type');
        const transportTypeSelect = document.getElementById('transport_type');
        
        if (shippingTypeSelect && shippingTypeSelect.options.length <= 1) {
            showInfo('Пожалуйста, подождите, пока загрузятся типы перевозки...', 'Загрузка данных');
            return false;
        }
        
        if (transportTypeSelect && transportTypeSelect.options.length <= 1) {
            showInfo('Пожалуйста, подождите, пока загрузятся типы транспорта...', 'Загрузка данных');
            return false;
        }
        
        // Check basic required fields with special handling for client
        const clientId = formData.get('client_id');
        if (!clientId) {
            const clientSearchInput = document.getElementById('clientSearch');
            if (clientSearchInput) {
                showWarning('Пожалуйста, выберите клиента из списка автозаполнения', 'Неверный выбор клиента');
                clientSearchInput.focus();
                clientSearchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                showError('Поле "Клиент" обязательно для заполнения', 'Обязательное поле');
            }
            return false;
        }

                 // Order number is required
         const orderNumber = formData.get('display_order_number');
         if (!orderNumber) {
             showError('Поле "Номер заказа" обязательно для заполнения', 'Обязательное поле');
             const element = document.getElementById('order_number');
             if (element) {
                 element.focus();
                 element.scrollIntoView({ behavior: 'smooth', block: 'center' });
             }
             return false;
         }

         // Route validation (at least one point required)
         const routePoints = collectRoutePoints();
         if (!routePoints || routePoints.length === 0) {
             showError('Необходимо добавить хотя бы один пункт маршрута', 'Маршрут не задан');
             // Navigate to route tab
             document.querySelector('[href="#route"]').click();
             return false;
         }
        
        // Check if client is selected and contract is available
        if (clientId) {
            const contractSelect = document.getElementById('contract_select');
            const contractStatic = document.getElementById('contract_static');
            
            // Check if static field is active (single contract case)
            if (contractStatic && contractStatic.style.display !== 'none' && !contractStatic.disabled) {
                const contractValue = contractStatic.value || '';
                
                if (!contractValue) {
                    showWarning('Договор не выбран. Пожалуйста, выберите клиента заново.', 'Не выбран договор');
                    contractStatic.focus();
                    return false;
                }
            }
            // Check if select field is active (multiple contracts case)
            else if (contractSelect && contractSelect.style.display !== 'none' && !contractSelect.disabled) {
                if (contractSelect.options.length > 1) {
                    const contractValue = contractSelect.value || '';
                    
                    if (!contractValue) {
                        showError('Пожалуйста, выберите договор для клиента', 'Договор не выбран');
                        contractSelect.focus();
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
}); 