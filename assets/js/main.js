// Main JavaScript for PC Hardware Inventory

// Confirm delete action
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
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
                    alert('CSV file is empty or invalid');
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
            
            fetch(window.BASE_PATH + 'pages/import_csv.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                importBtn.disabled = false;
                importBtn.innerHTML = originalText;
                
                if (data.success) {
                    alert(data.message);
                    // Close modal and reload page
                    const modal = bootstrap.Modal.getInstance(document.getElementById('importCSVModal'));
                    modal.hide();
                    window.location.reload();
                } else {
                    alert('Import failed: ' + data.message);
                }
            })
            .catch(error => {
                importBtn.disabled = false;
                importBtn.innerHTML = originalText;
                alert('Error importing CSV: ' + error.message);
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
