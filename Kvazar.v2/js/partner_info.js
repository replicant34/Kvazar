// Partner Information Handler
class PartnerInfoHandler {
    constructor() {
        this.modal = null;
        this.originalContent = null;
        this.init();
    }

    init() {
        // Get the modal that's already in the page
        this.modal = document.getElementById('partnerInfoModal');
        
        // Store the original content for restoration
        const content = document.querySelector('.partner-info-content');
        if (content) {
            this.originalContent = content.innerHTML;
        }
        
        // Add click handlers for partner IDs
        this.addPartnerIdClickHandlers();
        
        // Add modal event listeners
        this.addModalEventListeners();
    }

    addPartnerIdClickHandlers() {
        // Use event delegation for partner ID clicks
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('partner-id-link')) {
                e.preventDefault();
                const partnerId = e.target.dataset.partnerId;
                const partnerType = e.target.dataset.partnerType;
                this.showPartnerInfo(partnerId, partnerType);
            }
        });
    }

    addModalEventListeners() {
        if (!this.modal) {
            console.error('Partner info modal not found');
            return;
        }
        
        // Close button handlers
        const closeBtn = document.getElementById('partnerInfoModalClose');
        const cancelBtn = document.getElementById('closePartnerInfo');
        const downloadBtn = document.getElementById('downloadPartnerPdf');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hideModal());
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.hideModal());
        }
        
        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => this.downloadPdf());
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

    showPartnerInfo(partnerId, partnerType) {
        if (!this.modal) {
            console.error('Modal not found');
            return;
        }

        // Show loading state
        this.showModal();
        this.showLoadingState();

        // Determine the correct path for the fetch request
        const currentPath = window.location.pathname;
        let fetchPath;
        
        if (currentPath.includes('/pages/')) {
            // We're in the pages directory (manage_users.php)
            fetchPath = '../assets/fetch_partner_info.php';
        } else {
            // We're in the root directory (test page)
            fetchPath = 'assets/fetch_partner_info.php';
        }

        // Fetch partner information
        fetch(`${fetchPath}?id=${partnerId}&type=${partnerType}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.displayPartnerInfo(data.data);
                } else {
                    this.showError(data.error || 'Ошибка загрузки данных');
                }
            })
            .catch(error => {
                console.error('Error fetching partner info:', error);
                this.showError('Ошибка соединения с сервером');
            });
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

    showLoadingState() {
        const content = document.querySelector('.partner-info-content');
        if (content) {
            content.innerHTML = '<div class="partner-info-loading"></div>';
        }
    }

    showError(message) {
        const content = document.querySelector('.partner-info-content');
        if (content) {
            content.innerHTML = `<div class="partner-info-error">${message}</div>`;
        }
    }

    displayPartnerInfo(partnerData) {
        const content = document.querySelector('.partner-info-content');
        if (!content) {
            console.error('Content element not found');
            return;
        }
        
        // Restore the original content structure
        if (this.originalContent) {
            content.innerHTML = this.originalContent;
        }

        // Update modal title and badge
        const title = document.getElementById('partnerInfoTitle');
        const badge = document.getElementById('partnerTypeBadge');
        
        if (title) {
            title.textContent = `Информация о партнере #${partnerData.id}`;
        }
        
        if (badge) {
            const typeLabels = {
                'client': 'Клиент',
                'courier': 'Курьер',
                'agent': 'Агент'
            };
            badge.textContent = typeLabels[partnerData.type] || partnerData.type;
        }

        // Populate company information
        this.setElementText('companyType', partnerData.company_info.company_type);
        this.setElementText('fullCompanyName', partnerData.company_info.full_name);
        this.setElementText('shortCompanyName', partnerData.company_info.short_name);
        this.setElementText('inn', partnerData.company_info.inn);
        this.setElementText('kpp', partnerData.company_info.kpp);
        this.setElementText('ogrn', partnerData.company_info.ogrn);

        // Populate addresses
        this.setElementText('physicalAddress', partnerData.addresses.physical);
        this.setElementText('legalAddress', partnerData.addresses.legal);

        // Populate bank information
        this.setElementText('bankName', partnerData.bank_info.bank_name);
        this.setElementText('bik', partnerData.bank_info.bik);
        this.setElementText('settlementAccount', partnerData.bank_info.settlement_account);
        this.setElementText('correspondentAccount', partnerData.bank_info.correspondent_account);

        // Populate contact information
        this.setElementText('contactPerson', partnerData.contact_info.contact_person);
        this.setElementText('contactPosition', partnerData.contact_info.contact_position);
        this.setElementText('contactPhone', partnerData.contact_info.contact_phone);
        this.setElementText('contactEmail', partnerData.contact_info.contact_email);

        // Populate head information
        this.setElementText('headPosition', partnerData.head_info.head_position);
        this.setElementText('headName', partnerData.head_info.head_name);

        // Populate dates
        this.setElementText('createdDate', this.formatDate(partnerData.dates.created));
        this.setElementText('updatedDate', this.formatDate(partnerData.dates.updated));
    }

    setElementText(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value || '';
        }
    }

    formatDate(dateString) {
        if (!dateString) return '';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleString('ru-RU', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return dateString;
        }
    }

    // Method to make partner IDs clickable in tables
    makePartnerIdsClickable() {
        const partnerCells = document.querySelectorAll('td[data-partner-id]');
        partnerCells.forEach(cell => {
            const partnerId = cell.dataset.partnerId;
            const partnerType = cell.dataset.partnerType;
            
            if (partnerId && partnerType && partnerId !== '') {
                cell.innerHTML = `<a href="#" class="partner-id-link" data-partner-id="${partnerId}" data-partner-type="${partnerType}">${partnerId}</a>`;
            }
        });
    }

    // Method to download PDF
    downloadPdf() {
        // Get current partner data from the modal
        const partnerId = document.getElementById('partnerInfoTitle')?.textContent.match(/#(\d+)/)?.[1];
        const partnerType = document.getElementById('partnerTypeBadge')?.textContent;
        
        if (!partnerId || !partnerType) {
            console.error('Partner information not available for download');
            return;
        }
        
        // Determine the correct path for the download request
        const currentPath = window.location.pathname;
        let downloadPath;
        
        if (currentPath.includes('/pages/')) {
            // We're in the pages directory (manage_users.php)
            downloadPath = '../assets/generate_partner_pdf.php';
        } else {
            // We're in the root directory
            downloadPath = 'assets/generate_partner_pdf.php';
        }
        
        // Create download URL
        const downloadUrl = `${downloadPath}?id=${partnerId}&type=${this.getPartnerTypeKey(partnerType)}`;
        
        // Trigger download
        window.open(downloadUrl, '_blank');
    }
    
    // Helper method to convert partner type label to key
    getPartnerTypeKey(typeLabel) {
        const typeMap = {
            'Клиент': 'client',
            'Курьер': 'courier',
            'Агент': 'agent'
        };
        return typeMap[typeLabel] || 'client';
    }
}

// Initialize partner info handler when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.partnerInfoHandler = new PartnerInfoHandler();
    
    // Make partner IDs clickable if they exist on page load
    setTimeout(() => {
        if (window.partnerInfoHandler) {
            window.partnerInfoHandler.makePartnerIdsClickable();
        }
    }, 500);
});

// Function to be called after AJAX table updates
function refreshPartnerIdLinks() {
    if (window.partnerInfoHandler) {
        window.partnerInfoHandler.makePartnerIdsClickable();
    }
} 