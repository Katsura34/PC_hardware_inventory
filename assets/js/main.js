// Main JavaScript for PC Hardware Inventory

// ============================================
// Loading Overlay (shows during all actions)
// ============================================

// Create the loading overlay HTML and add it to the page
function createLoadingOverlay() {
    // Check if overlay already exists
    if (document.getElementById('loadingOverlay')) {
        return;
    }
    
    const overlayHTML = `
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-content">
            <div class="spinner-border text-primary loading-spinner" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p id="loadingMessage" class="loading-text mt-3 mb-0">Processing...</p>
        </div>
    </div>
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
        }
        .loading-content {
            text-align: center;
            background: white;
            padding: 2rem 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        .loading-spinner {
            width: 3rem;
            height: 3rem;
        }
        .loading-text {
            color: #333;
            font-weight: 500;
        }
    </style>`;
    
    document.body.insertAdjacentHTML('beforeend', overlayHTML);
}

// Show loading overlay with optional custom message
function showLoading(message = 'Processing...') {
    createLoadingOverlay();
    const overlay = document.getElementById('loadingOverlay');
    const messageEl = document.getElementById('loadingMessage');
    if (messageEl) {
        messageEl.textContent = message;
    }
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

// Hide loading overlay
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// ============================================
// Custom Confirmation Modal (replaces browser confirm)
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
            <div class="modal-content">
                <div class="modal-header" id="confirmModalHeader">
                    <h5 class="modal-title" id="confirmationModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2" id="confirmModalIcon"></i>
                        <span id="confirmModalTitle">Confirm Action</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmModalMessage" class="mb-0">Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn" id="confirmModalBtn">
                        <i class="bi bi-check-circle me-1"></i> Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    
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
