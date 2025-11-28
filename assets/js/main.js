// Main JavaScript for PC Hardware Inventory - V2 HCI-Enhanced
// 
// HCI Principles Applied:
// - Feedback: Loading states, success/error notifications, visual confirmations
// - Affordance: Clear button states, hover effects
// - Error Prevention: Confirmation dialogs, validation feedback
// - Flexibility: Keyboard shortcuts, multiple interaction methods
// - Visibility: Status indicators, progress feedback

// ============================================
// Loading Overlay (shows during all actions)
// HCI Principle: Feedback - Users always know what's happening
// ============================================

// Create the loading overlay HTML and add it to the page
function createLoadingOverlay() {
    // Check if overlay already exists
    if (document.getElementById('loadingOverlay')) {
        return;
    }
    
    const overlayHTML = `
    <div id="loadingOverlay" class="loading-overlay" style="display: none;" role="status" aria-live="polite">
        <div class="loading-content">
            <div class="loading-spinner-container">
                <div class="spinner-border text-primary loading-spinner" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="loading-progress" id="loadingProgress" style="display: none;">
                    <div class="loading-progress-bar"></div>
                </div>
            </div>
            <p id="loadingMessage" class="loading-text mt-3 mb-0">Processing...</p>
            <p id="loadingSubtext" class="loading-subtext text-muted small mt-1 mb-0" style="display: none;"></p>
        </div>
    </div>
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.7);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .loading-content {
            text-align: center;
            background: white;
            padding: 2.5rem 3.5rem;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            animation: scaleIn 0.3s ease-out;
            max-width: 90%;
        }
        @keyframes scaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .loading-spinner-container {
            position: relative;
        }
        .loading-spinner {
            width: 3.5rem;
            height: 3.5rem;
            border-width: 4px;
        }
        .loading-progress {
            width: 200px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 999px;
            margin-top: 1rem;
            overflow: hidden;
        }
        .loading-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #2563eb, #059669);
            border-radius: 999px;
            width: 0%;
            transition: width 0.3s ease;
        }
        .loading-text {
            color: #1e293b;
            font-weight: 600;
            font-size: 16px;
        }
        .loading-subtext {
            font-size: 13px;
            color: #64748b;
        }
    </style>`;
    
    document.body.insertAdjacentHTML('beforeend', overlayHTML);
}

// Show loading overlay with optional custom message
function showLoading(message = 'Processing...', subtext = '') {
    createLoadingOverlay();
    const overlay = document.getElementById('loadingOverlay');
    const messageEl = document.getElementById('loadingMessage');
    const subtextEl = document.getElementById('loadingSubtext');
    
    if (messageEl) {
        messageEl.textContent = message;
    }
    if (subtextEl) {
        if (subtext) {
            subtextEl.textContent = subtext;
            subtextEl.style.display = 'block';
        } else {
            subtextEl.style.display = 'none';
        }
    }
    if (overlay) {
        overlay.style.display = 'flex';
        // Trap focus for accessibility
        overlay.focus();
    }
}

// Update loading progress (0-100)
function updateLoadingProgress(percent) {
    const progressEl = document.getElementById('loadingProgress');
    if (progressEl) {
        progressEl.style.display = 'block';
        const bar = progressEl.querySelector('.loading-progress-bar');
        if (bar) {
            bar.style.width = Math.min(100, Math.max(0, percent)) + '%';
        }
    }
}

// Hide loading overlay
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
        // Reset progress
        const progressEl = document.getElementById('loadingProgress');
        if (progressEl) {
            progressEl.style.display = 'none';
            const bar = progressEl.querySelector('.loading-progress-bar');
            if (bar) bar.style.width = '0%';
        }
    }
}

// ============================================
// Custom Confirmation Modal (replaces browser confirm)
// HCI Principle: Error Prevention - Clear confirmation before destructive actions
// ============================================

// Create the confirmation modal HTML and add it to the page
function createConfirmationModal() {
    // Check if modal already exists
    if (document.getElementById('confirmationModal')) {
        return;
    }
    
    const modalHTML = `
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="modal-header" id="confirmModalHeader" style="border: none; padding: 1.5rem;">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="confirmationModalLabel">
                        <span class="confirm-icon-wrapper" id="confirmIconWrapper">
                            <i class="bi bi-exclamation-triangle-fill" id="confirmModalIcon"></i>
                        </span>
                        <span id="confirmModalTitle">Confirm Action</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <p id="confirmModalMessage" class="mb-0" style="font-size: 15px; line-height: 1.6;">Are you sure you want to proceed?</p>
                    <p id="confirmModalSubtext" class="text-muted small mt-2 mb-0" style="display: none;"></p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem; gap: 0.5rem;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="min-width: 100px;">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn" id="confirmModalBtn" style="min-width: 100px;">
                        <i class="bi bi-check-circle me-1"></i> Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
    <style>
        .confirm-icon-wrapper {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        #confirmationModal .modal-header.bg-danger .confirm-icon-wrapper {
            background: rgba(255,255,255,0.2);
        }
        #confirmationModal .modal-header.bg-warning .confirm-icon-wrapper {
            background: rgba(0,0,0,0.1);
        }
        #confirmationModal .modal-header.bg-info .confirm-icon-wrapper,
        #confirmationModal .modal-header.bg-primary .confirm-icon-wrapper {
            background: rgba(255,255,255,0.2);
        }
    </style>`;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Show custom confirmation modal
// Returns a Promise that resolves to true if confirmed, false if cancelled
// type can be 'danger' (for delete), 'warning' (for edit), 'primary' (for general actions)
function showConfirmation(message, title = 'Confirm Action', buttonText = 'Confirm', type = 'danger') {
    return new Promise((resolve) => {
        createConfirmationModal();
        
        const modal = document.getElementById('confirmationModal');
        const modalHeader = document.getElementById('confirmModalHeader');
        const modalTitle = document.getElementById('confirmModalTitle');
        const modalMessage = document.getElementById('confirmModalMessage');
        const modalIcon = document.getElementById('confirmModalIcon');
        const confirmBtn = document.getElementById('confirmModalBtn');
        
        // Set content
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        confirmBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> ' + buttonText;
        
        // Reset classes
        modalHeader.className = 'modal-header text-white';
        confirmBtn.className = 'btn';
        
        // Set type-specific styling
        switch (type) {
            case 'danger':
                modalHeader.classList.add('bg-danger');
                modalIcon.className = 'bi bi-exclamation-triangle-fill me-2';
                confirmBtn.classList.add('btn-danger');
                break;
            case 'warning':
                modalHeader.classList.add('bg-warning');
                modalHeader.classList.remove('text-white');
                modalIcon.className = 'bi bi-pencil-fill me-2';
                confirmBtn.classList.add('btn-warning');
                // Update close button for dark background
                modal.querySelector('.btn-close').classList.remove('btn-close-white');
                break;
            case 'info':
                modalHeader.classList.add('bg-info');
                modalIcon.className = 'bi bi-info-circle-fill me-2';
                confirmBtn.classList.add('btn-info');
                break;
            default: // primary
                modalHeader.classList.add('bg-primary');
                modalIcon.className = 'bi bi-question-circle-fill me-2';
                confirmBtn.classList.add('btn-primary');
        }
        
        // Ensure close button is white for dark backgrounds
        if (type !== 'warning') {
            modal.querySelector('.btn-close').classList.add('btn-close-white');
        }
        
        // Create Bootstrap modal instance
        const bsModal = new bootstrap.Modal(modal);
        
        // Handle confirm button click
        const handleConfirm = () => {
            cleanup();
            bsModal.hide();
            resolve(true);
        };
        
        // Handle modal dismiss (cancel or close)
        const handleDismiss = () => {
            cleanup();
            resolve(false);
        };
        
        // Cleanup event listeners
        const cleanup = () => {
            confirmBtn.removeEventListener('click', handleConfirm);
            modal.removeEventListener('hidden.bs.modal', handleDismiss);
        };
        
        // Add event listeners
        confirmBtn.addEventListener('click', handleConfirm);
        modal.addEventListener('hidden.bs.modal', handleDismiss);
        
        // Show modal
        bsModal.show();
    });
}

// Handle delete confirmation with custom modal
// Use this for links that trigger delete actions
// IMPORTANT: Always pass the element (this) when calling from onclick handlers
function confirmDelete(message = 'Are you sure you want to delete this item?', element = null) {
    // If called from onclick, prevent default and show modal
    if (element) {
        // Get the event from the window object for cross-browser compatibility
        var evt = window.event || arguments.callee.caller.arguments[0];
        if (evt) {
            evt.preventDefault();
        }
        const href = element.getAttribute('href');
        
        showConfirmation(message, 'Confirm Delete', 'Delete', 'danger').then((confirmed) => {
            if (confirmed) {
                showLoading('Deleting...');
                window.location.href = href;
            }
        });
        return false;
    }
    
    // Fallback for legacy usage without element - shows modal but caller must handle async
    // Note: All current usages pass element, so this path is defensive only
    showConfirmation(message, 'Confirm Delete', 'Delete', 'danger');
    return false;
}

// ============================================
// Custom Alert Modal (replaces browser alert)
// ============================================

// Create the alert modal HTML and add it to the page
function createAlertModal() {
    // Check if modal already exists
    if (document.getElementById('alertModal')) {
        return;
    }
    
    const modalHTML = `
    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" id="alertModalHeader">
                    <h5 class="modal-title" id="alertModalLabel">
                        <i class="bi bi-info-circle-fill me-2" id="alertModalIcon"></i>
                        <span id="alertModalTitle">Notice</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="alertModalMessage" class="mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="bi bi-check me-1"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Show custom alert modal
// type can be 'success', 'error', 'warning', or 'info'
function showAlert(message, title = 'Notice', type = 'info') {
    return new Promise((resolve) => {
        createAlertModal();
        
        const modal = document.getElementById('alertModal');
        const modalHeader = document.getElementById('alertModalHeader');
        const modalTitle = document.getElementById('alertModalTitle');
        const modalMessage = document.getElementById('alertModalMessage');
        const modalIcon = document.getElementById('alertModalIcon');
        
        // Set content
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        
        // Reset classes
        modalHeader.className = 'modal-header';
        
        // Set type-specific styling
        switch (type) {
            case 'success':
                modalHeader.classList.add('bg-success', 'text-white');
                modalIcon.className = 'bi bi-check-circle-fill me-2';
                break;
            case 'error':
                modalHeader.classList.add('bg-danger', 'text-white');
                modalIcon.className = 'bi bi-x-circle-fill me-2';
                break;
            case 'warning':
                modalHeader.classList.add('bg-warning');
                modalIcon.className = 'bi bi-exclamation-triangle-fill me-2';
                break;
            default: // info
                modalHeader.classList.add('bg-primary', 'text-white');
                modalIcon.className = 'bi bi-info-circle-fill me-2';
        }
        
        // Create Bootstrap modal instance
        const bsModal = new bootstrap.Modal(modal);
        
        // Handle modal dismiss
        const handleDismiss = () => {
            modal.removeEventListener('hidden.bs.modal', handleDismiss);
            resolve();
        };
        
        modal.addEventListener('hidden.bs.modal', handleDismiss);
        
        // Show modal
        bsModal.show();
    });
}

// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }
    return true;
}

// Number input validation
function validateNumberInput(input, min = 0) {
    const value = parseInt(input.value);
    if (isNaN(value) || value < min) {
        input.value = min;
    }
}

// Calculate total quantity
function calculateTotal() {
    const unused = parseInt(document.getElementById('unused_quantity')?.value || 0);
    const inUse = parseInt(document.getElementById('in_use_quantity')?.value || 0);
    const damaged = parseInt(document.getElementById('damaged_quantity')?.value || 0);
    const repair = parseInt(document.getElementById('repair_quantity')?.value || 0);
    
    const total = unused + inUse + damaged + repair;
    const totalInput = document.getElementById('total_quantity');
    if (totalInput) {
        totalInput.value = total;
    }
}

// Add event listeners for quantity calculations
document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = ['unused_quantity', 'in_use_quantity', 'damaged_quantity', 'repair_quantity'];
    quantityInputs.forEach(function(id) {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', calculateTotal);
        }
    });
});

// Search functionality
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
            const cell = cells[j];
            if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        
        row.style.display = found ? '' : 'none';
    }
}

// Export table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let row of rows) {
        let cols = row.querySelectorAll('td, th');
        let csvRow = [];
        for (let col of cols) {
            csvRow.push('"' + col.textContent.replace(/"/g, '""') + '"');
        }
        csv.push(csvRow.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Print page
function printPage() {
    window.print();
}

// Show loading spinner
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="spinner-container"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    }
}

// Hide loading spinner
function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '';
    }
}

// CSV Import functionality
document.addEventListener('DOMContentLoaded', function() {
    const csvFileInput = document.getElementById('csvFile');
    const importForm = document.getElementById('importCSVForm');
    const importPreview = document.getElementById('importPreview');
    const previewTable = document.getElementById('previewTable');
    
    // Preview CSV file
    if (csvFileInput) {
        csvFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(event) {
                const text = event.target.result;
                const lines = text.split('\n').filter(line => line.trim());
                
                if (lines.length < 2) {
                    showAlert('CSV file is empty or invalid', 'Invalid File', 'error');
                    return;
                }
                
                // Show preview
                const headers = lines[0].split(',');
                const previewRows = lines.slice(1, 6); // First 5 data rows
                
                let tableHTML = '<thead><tr>';
                headers.forEach(header => {
                    tableHTML += `<th>${header.trim()}</th>`;
                });
                tableHTML += '</tr></thead><tbody>';
                
                previewRows.forEach(row => {
                    const cells = row.split(',');
                    tableHTML += '<tr>';
                    cells.forEach(cell => {
                        tableHTML += `<td>${cell.trim()}</td>`;
                    });
                    tableHTML += '</tr>';
                });
                tableHTML += '</tbody>';
                
                previewTable.innerHTML = tableHTML;
                importPreview.classList.remove('d-none');
            };
            reader.readAsText(file);
        });
    }
    
    // Handle CSV import form submission
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(importForm);
            const importBtn = document.getElementById('importBtn');
            const originalText = importBtn.innerHTML;
            
            importBtn.disabled = true;
            importBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importing...';
            showLoading('Importing CSV data...');
            
            fetch(window.BASE_PATH + 'pages/import_csv.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                importBtn.disabled = false;
                importBtn.innerHTML = originalText;
                
                if (data.success) {
                    showAlert(data.message, 'Import Successful', 'success').then(function() {
                        // Close modal and reload page after alert is dismissed
                        showLoading('Refreshing page...');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('importCSVModal'));
                        modal.hide();
                        window.location.reload();
                    });
                } else {
                    showAlert('Import failed: ' + data.message, 'Import Failed', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                importBtn.disabled = false;
                importBtn.innerHTML = originalText;
                showAlert('Error importing CSV: ' + error.message, 'Error', 'error');
            });
        });
    }
});

// Location filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const locationFilters = document.querySelectorAll('.location-filter');
    
    locationFilters.forEach(filter => {
        filter.addEventListener('click', function(e) {
            e.preventDefault();
            const location = this.getAttribute('data-location');
            
            // Update dropdown button text
            const dropdownBtn = document.getElementById('locationDropdown');
            if (location === 'all') {
                dropdownBtn.innerHTML = '<i class="bi bi-geo-alt"></i> <span class="d-lg-inline">Location</span>';
            } else {
                dropdownBtn.innerHTML = `<i class="bi bi-geo-alt"></i> <span class="d-lg-inline">${location}</span>`;
            }
            
            // Filter table rows
            filterTableByLocation(location);
        });
    });
});

function filterTableByLocation(location) {
    // Find the hardware table
    const table = document.getElementById('hardwareTable');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        if (location === 'all') {
            row.style.display = '';
        } else {
            const locationCell = row.querySelector('td:nth-last-child(2)'); // Location is second to last column
            if (locationCell) {
                const cellText = locationCell.textContent.trim();
                if (cellText === location || cellText === '-') {
                    row.style.display = cellText === location ? '' : 'none';
                } else {
                    row.style.display = 'none';
                }
            }
        }
    });
}

// ============================================
// Page Navigation Loading Animation
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Show loading animation when navigating to other pages via navbar links
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle)');
    navLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Don't show loading for dropdown toggles or active page links
            if (this.classList.contains('active') || this.getAttribute('data-bs-toggle')) {
                return;
            }
            showLoading('Loading page...');
        });
    });
    
    // Show loading animation for logout link
    const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
    logoutLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            showLoading('Signing out...');
        });
    });
    
    // Show loading animation for filter form submissions
    const filterForms = document.querySelectorAll('form[method="GET"]');
    filterForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // Only show loading for filter forms (those with filter buttons)
            const hasFilterButton = form.querySelector('button[type="submit"] .bi-funnel');
            if (hasFilterButton) {
                showLoading('Applying filters...');
            }
        });
    });
    
    // Show loading animation for "View All" and similar navigation links
    const viewAllLinks = document.querySelectorAll('a.btn[href*=".php"]:not([onclick]):not([data-bs-toggle])');
    viewAllLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Only show loading for actual page navigation (safe URLs only)
            const href = this.getAttribute('href');
            // Check for valid navigation URLs - must be a relative URL or http/https
            // This excludes dangerous URL schemes like javascript:, data:, vbscript:, etc.
            var isSafeUrl = false;
            if (href) {
                var hrefLower = href.toLowerCase();
                // Only allow relative URLs (not starting with a scheme) or http/https URLs
                var hasScheme = hrefLower.indexOf(':') !== -1;
                if (!hasScheme || hrefLower.startsWith('http://') || hrefLower.startsWith('https://')) {
                    // Also exclude fragment-only URLs
                    if (!href.startsWith('#')) {
                        isSafeUrl = true;
                    }
                }
            }
            
            if (isSafeUrl) {
                // Check if it's a "Clear filters" button
                var hasClearIcon = link.querySelector('.bi-x-circle');
                if (hasClearIcon) {
                    showLoading('Clearing filters...');
                } else {
                    showLoading('Loading...');
                }
            }
        });
    });
    
    // Hide any lingering loading overlay from browser back/forward navigation cache
    // This ensures users don't see a stuck loading screen when navigating back
    hideLoading();
});

// ============================================
// HCI Enhancement: Toast Notifications
// Principle: Feedback - Non-intrusive status updates
// ============================================

function createToastContainer() {
    if (document.getElementById('toastContainer')) return;
    
    const containerHTML = `
    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9998;">
    </div>`;
    
    document.body.insertAdjacentHTML('beforeend', containerHTML);
}

function showToast(message, type = 'info', duration = 4000) {
    createToastContainer();
    const container = document.getElementById('toastContainer');
    
    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };
    
    const colors = {
        success: '#059669',
        error: '#dc2626',
        warning: '#d97706',
        info: '#2563eb'
    };
    
    const toastId = 'toast-' + Date.now();
    const toastHTML = `
    <div id="${toastId}" class="toast show align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true" 
         style="background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); min-width: 300px; animation: slideInRight 0.3s ease-out;">
        <div class="d-flex align-items-center p-3">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: ${colors[type]}15; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                <i class="bi ${icons[type]}" style="font-size: 18px; color: ${colors[type]};"></i>
            </div>
            <div class="flex-grow-1" style="font-size: 14px; color: #1e293b; font-weight: 500;">
                ${message}
            </div>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="toast" aria-label="Close" style="font-size: 10px; opacity: 0.5;"></button>
        </div>
        <div style="height: 4px; background: #e2e8f0; border-radius: 0 0 12px 12px; overflow: hidden;">
            <div class="toast-progress" style="height: 100%; background: ${colors[type]}; width: 100%; transition: width ${duration}ms linear;"></div>
        </div>
    </div>`;
    
    container.insertAdjacentHTML('beforeend', toastHTML);
    
    const toast = document.getElementById(toastId);
    const progressBar = toast.querySelector('.toast-progress');
    
    // Animate progress bar
    setTimeout(() => {
        progressBar.style.width = '0%';
    }, 50);
    
    // Auto-dismiss
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease-out forwards';
        setTimeout(() => toast.remove(), 300);
    }, duration);
    
    // Manual dismiss
    toast.querySelector('.btn-close').addEventListener('click', () => {
        toast.style.animation = 'fadeOut 0.3s ease-out forwards';
        setTimeout(() => toast.remove(), 300);
    });
}

// ============================================
// HCI Enhancement: Form Auto-save Indicator
// Principle: Feedback - User knows data is being saved
// ============================================

function showAutoSaveIndicator(status = 'saving') {
    let indicator = document.getElementById('autoSaveIndicator');
    
    if (!indicator) {
        const indicatorHTML = `
        <div id="autoSaveIndicator" class="position-fixed" style="bottom: 20px; right: 20px; z-index: 1050; display: none;">
            <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-sm" style="background: white; font-size: 13px;">
                <span class="auto-save-icon"></span>
                <span class="auto-save-text"></span>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', indicatorHTML);
        indicator = document.getElementById('autoSaveIndicator');
    }
    
    const iconEl = indicator.querySelector('.auto-save-icon');
    const textEl = indicator.querySelector('.auto-save-text');
    
    switch (status) {
        case 'saving':
            iconEl.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>';
            textEl.textContent = 'Saving...';
            break;
        case 'saved':
            iconEl.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            textEl.textContent = 'Saved';
            setTimeout(() => { indicator.style.display = 'none'; }, 2000);
            break;
        case 'error':
            iconEl.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
            textEl.textContent = 'Save failed';
            break;
    }
    
    indicator.style.display = 'block';
}

// ============================================
// HCI Enhancement: Scroll to Top Button
// Principle: Flexibility - Easy navigation
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Create scroll to top button
    const scrollBtnHTML = `
    <button id="scrollToTopBtn" class="btn btn-primary rounded-circle shadow" 
            style="position: fixed; bottom: 30px; right: 30px; width: 48px; height: 48px; display: none; z-index: 1000; opacity: 0; transition: opacity 0.3s;"
            title="Scroll to top" aria-label="Scroll to top">
        <i class="bi bi-arrow-up"></i>
    </button>`;
    document.body.insertAdjacentHTML('beforeend', scrollBtnHTML);
    
    const scrollBtn = document.getElementById('scrollToTopBtn');
    
    // Show/hide based on scroll position
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            scrollBtn.style.display = 'flex';
            scrollBtn.style.alignItems = 'center';
            scrollBtn.style.justifyContent = 'center';
            setTimeout(() => { scrollBtn.style.opacity = '1'; }, 10);
        } else {
            scrollBtn.style.opacity = '0';
            setTimeout(() => { scrollBtn.style.display = 'none'; }, 300);
        }
    });
    
    // Scroll to top on click
    scrollBtn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

// ============================================
// HCI Enhancement: Table Row Highlighting
// Principle: Visibility - Clear feedback on interactions
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Add click feedback on table rows
    document.querySelectorAll('.table tbody tr').forEach(function(row) {
        row.addEventListener('click', function(e) {
            // Don't highlight if clicking buttons/links
            if (e.target.closest('button, a, .btn')) return;
            
            // Remove previous selection
            document.querySelectorAll('.table tbody tr.selected-row').forEach(r => {
                r.classList.remove('selected-row');
            });
            
            // Add selection to clicked row
            this.classList.add('selected-row');
        });
    });
});

// Add CSS for selected row
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .table tbody tr.selected-row {
            background-color: #dbeafe !important;
            box-shadow: inset 3px 0 0 #2563eb;
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(20px); }
        }
        kbd {
            display: inline-block;
            padding: 3px 8px;
            font-size: 12px;
            font-family: 'SF Mono', Monaco, Consolas, monospace;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-shadow: 0 1px 0 #cbd5e1;
        }
    `;
    document.head.appendChild(style);
})();

// ============================================
// HCI Enhancement: Real-time Form Validation
// Principle: Error Prevention - Immediate feedback
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Add real-time validation to required inputs
    document.querySelectorAll('input[required], select[required]').forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && this.value.trim() !== '') {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });
});

// ============================================
// HCI Enhancement: Accessible Focus Management
// Principle: Flexibility & Visibility
// ============================================

// Trap focus in modals for accessibility
function trapFocusInModal(modalElement) {
    const focusableElements = modalElement.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    modalElement.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    });
}

// Apply to all modals
document.addEventListener('shown.bs.modal', function(e) {
    trapFocusInModal(e.target);
    // Focus first focusable element in modal (comprehensive selector)
    const firstFocusable = e.target.querySelector(
        'input:not([type="hidden"]):not([disabled]), ' +
        'select:not([disabled]), ' +
        'textarea:not([disabled]), ' +
        'button:not(.btn-close):not([disabled]), ' +
        'a[href], ' +
        '[tabindex]:not([tabindex="-1"])'
    );
    if (firstFocusable) firstFocusable.focus();
});

// ============================================
// HCI Enhancement: Mobile Filter Dropdown Fix
// Fix for filter dropdown getting cut off on mobile
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Fix filter dropdown positioning on mobile
    const filterDropdowns = document.querySelectorAll('.filter-dropdown');
    
    filterDropdowns.forEach(function(dropdown) {
        // When dropdown is shown, ensure it's visible
        dropdown.addEventListener('shown.bs.dropdown', repositionFilterDropdown);
        
        // Also listen for the parent's shown event
        const parentDropdown = dropdown.closest('.dropdown');
        if (parentDropdown) {
            parentDropdown.addEventListener('shown.bs.dropdown', function() {
                repositionFilterDropdown.call(dropdown);
            });
        }
    });
    
    // Handle Bootstrap dropdown events on the toggle buttons
    const filterToggleButtons = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    filterToggleButtons.forEach(function(btn) {
        btn.addEventListener('shown.bs.dropdown', function() {
            const dropdown = this.nextElementSibling;
            if (dropdown && dropdown.classList.contains('filter-dropdown')) {
                repositionFilterDropdown.call(dropdown);
            }
        });
    });
    
    function repositionFilterDropdown() {
        const dropdown = this;
        if (!dropdown) return;
        
        // Only apply fixes on mobile screens
        if (window.innerWidth <= 576) {
            // Reset any inline styles first
            dropdown.style.position = 'fixed';
            dropdown.style.top = '120px';
            dropdown.style.left = '50%';
            dropdown.style.right = 'auto';
            dropdown.style.transform = 'translateX(-50%)';
            dropdown.style.maxWidth = 'calc(100vw - 20px)';
            dropdown.style.width = 'calc(100vw - 20px)';
            dropdown.style.maxHeight = '70vh';
            dropdown.style.overflowY = 'auto';
            dropdown.style.zIndex = '1055';
        } else if (window.innerWidth <= 768) {
            // Tablet adjustments
            const rect = dropdown.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            
            // If dropdown extends beyond viewport, reposition
            if (rect.right > viewportWidth) {
                dropdown.style.right = '10px';
                dropdown.style.left = 'auto';
            }
            if (rect.left < 0) {
                dropdown.style.left = '10px';
                dropdown.style.right = 'auto';
            }
        }
    }
    
    // Reposition on window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            const openDropdowns = document.querySelectorAll('.filter-dropdown.show');
            openDropdowns.forEach(function(dropdown) {
                repositionFilterDropdown.call(dropdown);
            });
        }, 100);
    });
});

// ============================================
// HCI Enhancement: Mobile Scroll Lock for Dropdowns
// Prevents background scrolling when dropdown is open on mobile
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('shown.bs.dropdown', function() {
            if (window.innerWidth <= 576) {
                document.body.style.overflow = 'hidden';
            }
        });
        
        toggle.addEventListener('hidden.bs.dropdown', function() {
            document.body.style.overflow = '';
        });
    });
});
