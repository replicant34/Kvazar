// External Courier Form JavaScript
document.addEventListener('DOMContentLoaded', function() {
    setupFormHandlers();
    setupRadioHandlers();
    setupExistingDriverHandler();
    setupFormValidation();
});

function setupFormHandlers() {
    const form = document.getElementById('assignmentForm');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
}

function setupRadioHandlers() {
    // Driver type radio handlers
    const driverRadios = document.querySelectorAll('input[name="driver_type"]');
    driverRadios.forEach(radio => {
        radio.addEventListener('change', handleDriverTypeChange);
    });
    
    // Vehicle type radio handlers
    const vehicleRadios = document.querySelectorAll('input[name="vehicle_type"]');
    vehicleRadios.forEach(radio => {
        radio.addEventListener('change', handleVehicleTypeChange);
    });
    
    // Initialize visibility based on default selections
    handleDriverTypeChange();
    handleVehicleTypeChange();
}

function handleDriverTypeChange() {
    const existingDriverRadio = document.getElementById('existing_driver');
    const newDriverRadio = document.getElementById('new_driver');
    const existingDriverSection = document.getElementById('existing_driver_section');
    const newDriverSection = document.getElementById('new_driver_section');
    
    if (existingDriverRadio && existingDriverRadio.checked) {
        if (existingDriverSection) existingDriverSection.style.display = 'block';
        if (newDriverSection) newDriverSection.style.display = 'none';
        clearNewDriverFields();
    } else if (newDriverRadio && newDriverRadio.checked) {
        if (existingDriverSection) existingDriverSection.style.display = 'none';
        if (newDriverSection) newDriverSection.style.display = 'block';
        clearExistingDriverSelection();
    }
}

function handleVehicleTypeChange() {
    const existingVehicleRadio = document.getElementById('existing_vehicle');
    const newVehicleRadio = document.getElementById('new_vehicle');
    const existingVehicleSection = document.getElementById('existing_vehicle_section');
    const newVehicleSection = document.getElementById('new_vehicle_section');
    
    if (existingVehicleRadio && existingVehicleRadio.checked) {
        if (existingVehicleSection) existingVehicleSection.style.display = 'block';
        if (newVehicleSection) newVehicleSection.style.display = 'none';
        clearNewVehicleFields();
    } else if (newVehicleRadio && newVehicleRadio.checked) {
        if (existingVehicleSection) existingVehicleSection.style.display = 'none';
        if (newVehicleSection) newVehicleSection.style.display = 'block';
        clearExistingVehicleSelection();
    }
}

function clearNewDriverFields() {
    const fields = ['new_driver_name', 'new_driver_phone', 'new_driver_passport'];
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = '';
            field.removeAttribute('required');
        }
    });
}

function clearExistingDriverSelection() {
    const existingDriverSelect = document.getElementById('existingDriverSelect');
    const driverInfo = document.getElementById('driverInfo');
    
    if (existingDriverSelect) {
        existingDriverSelect.value = '';
    }
    if (driverInfo) {
        driverInfo.style.display = 'none';
    }
}

function clearNewVehicleFields() {
    const fields = ['new_vehicle_brand', 'new_vehicle_plate'];
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = '';
            field.removeAttribute('required');
        }
    });
}

function clearExistingVehicleSelection() {
    const existingVehicleSelect = document.getElementById('existingVehicleSelect');
    if (existingVehicleSelect) {
        existingVehicleSelect.value = '';
    }
}

function setupExistingDriverHandler() {
    const existingDriverSelect = document.getElementById('existingDriverSelect');
    if (existingDriverSelect) {
        existingDriverSelect.addEventListener('change', handleExistingDriverSelect);
    }
}

function handleExistingDriverSelect() {
    const select = document.getElementById('existingDriverSelect');
    const driverInfo = document.getElementById('driverInfo');
    const driverPhone = document.getElementById('driverPhone');
    const driverPassport = document.getElementById('driverPassport');
    
    if (select && select.value) {
        const selectedOption = select.options[select.selectedIndex];
        const phone = selectedOption.dataset.phone || 'Не указан';
        const passport = selectedOption.dataset.passport || 'Не указан';
        
        if (driverPhone) driverPhone.textContent = phone;
        if (driverPassport) driverPassport.textContent = passport;
        if (driverInfo) driverInfo.style.display = 'block';
    } else {
        if (driverInfo) driverInfo.style.display = 'none';
    }
}

function setupFormValidation() {
    // Add real-time validation
    const requiredFields = document.querySelectorAll('input[required], select[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', validateField);
        field.addEventListener('input', clearFieldError);
    });
}

function validateField(event) {
    const field = event.target;
    const value = field.value.trim();
    
    clearFieldError(event);
    
    if (!value) {
        showFieldError(field, 'Это поле обязательно для заполнения');
        return false;
    }
    
    // Specific validation rules
    if (field.type === 'tel') {
        if (!isValidPhone(value)) {
            showFieldError(field, 'Введите корректный номер телефона');
            return false;
        }
    }
    
    if (field.name === 'new_driver_passport') {
        if (value.length < 6) {
            showFieldError(field, 'Паспортные данные должны содержать минимум 6 символов');
            return false;
        }
    }
    
    if (field.name === 'new_vehicle_plate') {
        if (!isValidPlateNumber(value)) {
            showFieldError(field, 'Введите корректный номер транспортного средства');
            return false;
        }
    }
    
    return true;
}

function isValidPhone(phone) {
    // Simple phone validation - at least 10 digits
    const phoneRegex = /^\+?[\d\s\-\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

function isValidPlateNumber(plate) {
    // Simple plate validation - at least 5 characters
    return plate.length >= 5;
}

function showFieldError(field, message) {
    clearFieldError({ target: field });
    
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    errorElement.style.color = '#e74c3c';
    errorElement.style.fontSize = '12px';
    errorElement.style.marginTop = '5px';
    
    field.style.borderColor = '#e74c3c';
    field.parentNode.appendChild(errorElement);
}

function clearFieldError(event) {
    const field = event.target;
    const errorElement = field.parentNode.querySelector('.field-error');
    
    if (errorElement) {
        errorElement.remove();
    }
    
    field.style.borderColor = '';
}

function handleFormSubmit(event) {
    event.preventDefault();
    
    const submitButton = document.querySelector('.btn-submit');
    const originalText = submitButton.innerHTML;
    
    // Validate form
    if (!validateForm()) {
        showNotification('Пожалуйста, заполните все обязательные поля корректно', 'error');
        return;
    }
    
    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
    submitButton.classList.add('loading');
    
    // Prepare form data
    const formData = new FormData(event.target);
    
    // Add required fields based on selections
    updateRequiredFields(formData);
    
    // Submit form
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Replace page content with response
        document.open();
        document.write(html);
        document.close();
    })
    .catch(error => {
        console.error('Error submitting form:', error);
        showNotification('Произошла ошибка при отправке формы. Попробуйте еще раз.', 'error');
        
        // Restore button
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
        submitButton.classList.remove('loading');
    });
}

function updateRequiredFields(formData) {
    const driverType = formData.get('driver_type');
    const vehicleType = formData.get('vehicle_type');
    
    // Set required attributes for new driver fields
    if (driverType === 'new') {
        const newDriverFields = ['new_driver_name', 'new_driver_phone', 'new_driver_passport'];
        newDriverFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.setAttribute('required', 'required');
            }
        });
    }
    
    // Set required attributes for new vehicle fields
    if (vehicleType === 'new') {
        const newVehicleFields = ['new_vehicle_brand', 'new_vehicle_plate'];
        newVehicleFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.setAttribute('required', 'required');
            }
        });
    }
}

function validateForm() {
    let isValid = true;
    const driverTypeChecked = document.querySelector('input[name="driver_type"]:checked');
    const vehicleTypeChecked = document.querySelector('input[name="vehicle_type"]:checked');
    
    if (!driverTypeChecked) {
        showNotification('Выберите тип водителя', 'error');
        return false;
    }
    
    if (!vehicleTypeChecked) {
        showNotification('Выберите тип транспортного средства', 'error');
        return false;
    }
    
    // Validate driver selection
    if (driverTypeChecked.value === 'existing') {
        const existingDriverSelect = document.getElementById('existingDriverSelect');
        if (!existingDriverSelect || !existingDriverSelect.value) {
            showNotification('Выберите водителя из списка', 'error');
            return false;
        }
    } else if (driverTypeChecked.value === 'new') {
        const newDriverFields = ['new_driver_name', 'new_driver_phone', 'new_driver_passport'];
        for (const fieldName of newDriverFields) {
            const field = document.getElementById(fieldName);
            if (!field || !field.value.trim()) {
                showNotification('Заполните все поля нового водителя', 'error');
                return false;
            }
            
            // Validate specific fields
            if (fieldName === 'new_driver_phone' && !isValidPhone(field.value)) {
                showNotification('Введите корректный номер телефона водителя', 'error');
                return false;
            }
            
            if (fieldName === 'new_driver_passport' && field.value.trim().length < 6) {
                showNotification('Паспортные данные должны содержать минимум 6 символов', 'error');
                return false;
            }
        }
    }
    
    // Validate vehicle selection
    if (vehicleTypeChecked.value === 'existing') {
        const existingVehicleSelect = document.getElementById('existingVehicleSelect');
        if (!existingVehicleSelect || !existingVehicleSelect.value) {
            showNotification('Выберите транспортное средство из списка', 'error');
            return false;
        }
    } else if (vehicleTypeChecked.value === 'new') {
        const newVehicleFields = ['new_vehicle_brand', 'new_vehicle_plate'];
        for (const fieldName of newVehicleFields) {
            const field = document.getElementById(fieldName);
            if (!field || !field.value.trim()) {
                showNotification('Заполните все поля нового транспортного средства', 'error');
                return false;
            }
            
            if (fieldName === 'new_vehicle_plate' && !isValidPlateNumber(field.value)) {
                showNotification('Введите корректный номер транспортного средства', 'error');
                return false;
            }
        }
    }
    
    return isValid;
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${getNotificationIcon(type)}"></i>
            <span>${message}</span>
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
        zIndex: '10000',
        maxWidth: '400px',
        boxShadow: '0 4px 12px rgba(0, 0, 0, 0.3)',
        animation: 'slideInRight 0.3s ease',
        backgroundColor: getNotificationColor(type)
    });
    
    // Add to body
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
    
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
        `;
        document.head.appendChild(style);
    }
}

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

// Prevent double form submission
let formSubmitted = false;
document.addEventListener('submit', function(event) {
    if (formSubmitted) {
        event.preventDefault();
        return false;
    }
    formSubmitted = true;
    
    // Reset after 5 seconds to allow retry if needed
    setTimeout(() => {
        formSubmitted = false;
    }, 5000);
});

// Page visibility handling
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        // Reset form submission flag when page becomes visible again
        formSubmitted = false;
    }
}); 