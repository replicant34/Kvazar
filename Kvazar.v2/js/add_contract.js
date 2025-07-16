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
    
    // Make functions globally available
    window.showMessage = showMessage;
    window.showSuccess = showSuccess;
    window.showError = showError;
    window.showWarning = showWarning;
    window.showInfo = showInfo;
    window.hideMessage = hideMessage;

    // ==================== END MESSAGE SYSTEM ====================

    // Get form elements
    const entityTypeSelect = document.getElementById('entityType');
    const entityIdSelect = document.getElementById('entityId');
    const contractTypeSelect = document.getElementById('contractType');
    const contractNumberInput = document.getElementById('contractNumber');
    const generateNumberBtn = document.getElementById('generateNumber');
    const contractDateInput = document.getElementById('contractDate');
    const contractStatusSelect = document.getElementById('contractStatus');
    const contractFileInput = document.getElementById('contractFile');
    const fileUploadBtn = document.getElementById('fileUploadBtn');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const selectedFileName = document.getElementById('selectedFileName');
    const selectedFileSize = document.getElementById('selectedFileSize');
    const removeFileBtn = document.getElementById('removeFileBtn');
    const contractForm = document.getElementById('contractForm');
    const contractNumberMessage = document.getElementById('contractNumberMessage');

    // Initialize date picker
    flatpickr(contractDateInput, {
        dateFormat: "Y-m-d",
        allowInput: true
    });

    // Enable/disable form fields based on entity type selection
    entityTypeSelect.addEventListener('change', function() {
        const isSelected = this.value !== '';
        
        // Enable/disable fields
        const fieldsToToggle = [
            entityIdSelect,
            contractTypeSelect,
            contractNumberInput,
            generateNumberBtn,
            contractDateInput,
            contractStatusSelect,
            contractFileInput,
            fileUploadBtn
        ];

        fieldsToToggle.forEach(field => {
            field.disabled = !isSelected;
        });

        if (isSelected) {
            // Fetch companies based on selected type
            fetchCompanies(this.value);
        } else {
            // Reset second select
            entityIdSelect.innerHTML = '<option value="">Сначала выберите тип организации</option>';
            entityIdSelect.disabled = true;
        }
    });

    // File upload button click handler
    fileUploadBtn.addEventListener('click', function() {
        contractFileInput.click();
    });

    // File input change handler
    contractFileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            // Display file information
            selectedFileName.textContent = file.name;
            selectedFileSize.textContent = `(${formatFileSize(file.size)})`;
            fileNameDisplay.classList.add('show');
            
            // Update button text
            fileUploadBtn.innerHTML = '<i class="fas fa-check"></i><span>Файл выбран</span>';
            fileUploadBtn.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
        } else {
            hideFileDisplay();
        }
    });

    // Remove file button handler
    removeFileBtn.addEventListener('click', function() {
        contractFileInput.value = '';
        hideFileDisplay();
    });

    function hideFileDisplay() {
        fileNameDisplay.classList.remove('show');
        fileUploadBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><span>Выберите файл контракта</span>';
        fileUploadBtn.style.background = 'linear-gradient(135deg, #3498db 0%, #2980b9 100%)';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Fetch companies based on entity type
    function fetchCompanies(entityType) {
        fetch(`../assets/add_contract_fetch_entities.php?type=${entityType}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    entityIdSelect.innerHTML = '<option value="">Выберите компанию</option>';
                    data.entities.forEach(entity => {
                        const option = document.createElement('option');
                        option.value = entity.id;
                        option.textContent = entity.name;
                        entityIdSelect.appendChild(option);
                    });
                } else {
                    throw new Error(data.error || 'Error fetching companies');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Ошибка при загрузке компаний. Пожалуйста, попробуйте снова.', 'Ошибка загрузки');
            });
    }

    // Generate contract number
    generateNumberBtn.addEventListener('click', function() {
        fetch('../assets/add_contract_generate_number.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    contractNumberInput.value = data.number;
                    checkContractNumber(data.number);
                } else {
                    throw new Error(data.error || 'Error generating number');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Ошибка при генерации номера контракта. Пожалуйста, попробуйте снова.', 'Ошибка генерации');
            });
    });

    // Check contract number uniqueness
    let checkTimeout;
    contractNumberInput.addEventListener('input', function() {
        clearTimeout(checkTimeout);
        const number = this.value.trim();
        
        if (number) {
            checkTimeout = setTimeout(() => checkContractNumber(number), 500);
        } else {
            contractNumberMessage.textContent = '';
            contractNumberMessage.className = 'message';
        }
    });

    function checkContractNumber(number) {
        fetch(`../assets/add_contract_check_number.php?number=${encodeURIComponent(number)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (!data.isUnique) {
                        contractNumberMessage.textContent = 'Номер контракта уже существует';
                        contractNumberMessage.className = 'message error';
                    } else {
                        contractNumberMessage.textContent = 'Номер контракта доступен';
                        contractNumberMessage.className = 'message success';
                    }
                } else {
                    throw new Error(data.error || 'Error checking number');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contractNumberMessage.textContent = 'Ошибка при проверке номера контракта';
                contractNumberMessage.className = 'message error';
            });
    }

    // Form submission
    contractForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (contractNumberMessage.classList.contains('error')) {
            showError('Пожалуйста, исправьте номер контракта перед отправкой', 'Неверный номер контракта');
            return;
        }

        createContract();
    });

    // Add color indicator for status
    contractStatusSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const color = selectedOption.dataset.color;
        if (color) {
            this.style.borderColor = color;
        } else {
            this.style.borderColor = '#3498db'; // default border color
        }
    });

    function createContract() {
        const formData = new FormData(contractForm);

        // Debug log
        console.log('Submitting form data:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        fetch('../assets/add_contract_create_contract.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('Контракт успешно создан', 'Успешно', 6000);
                window.location.href = '../pages/manage_contracts.php';
            } else {
                throw new Error(data.error || 'Ошибка при создании контракта');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Ошибка при создании контракта: ' + error.message, 'Ошибка создания');
        });
    }
}); 