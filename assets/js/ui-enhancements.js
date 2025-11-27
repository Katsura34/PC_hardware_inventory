/**
 * UI Enhancements JavaScript
 * 
 * HCI (Human-Computer Interaction) focused behaviors:
 * - Responsive topbar menu handling
 * - Search input focus with keyboard shortcuts
 * - Accessible modal management
 * - Toast notifications
 * - Real-time search filtering
 * 
 * @version 2.0
 */

(function() {
    'use strict';

    // ============================================
    // Configuration
    // ============================================
    const CONFIG = {
        searchShortcut: '/',
        toastDuration: 4000,
        debounceDelay: 300
    };

    // ============================================
    // Utility Functions
    // ============================================
    
    /**
     * Debounce function to limit rapid calls
     * @param {Function} func - Function to debounce
     * @param {number} wait - Delay in milliseconds
     * @returns {Function} Debounced function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction() {
            const args = arguments;
            const later = function() {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Trap focus within an element (for modals)
     * @param {HTMLElement} element - Container element
     */
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), ' +
            'textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length === 0) return;
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        element.addEventListener('keydown', function(e) {
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

    /**
     * Announce message to screen readers
     * @param {string} message - Message to announce
     * @param {string} priority - 'polite' or 'assertive'
     */
    function announce(message, priority) {
        if (priority === void 0) { priority = 'polite'; }
        let announcer = document.getElementById('sr-announcer');
        
        if (!announcer) {
            announcer = document.createElement('div');
            announcer.id = 'sr-announcer';
            announcer.setAttribute('aria-live', priority);
            announcer.setAttribute('aria-atomic', 'true');
            announcer.className = 'sr-only';
            announcer.style.cssText = 'position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;';
            document.body.appendChild(announcer);
        }
        
        // Clear and set new message
        announcer.textContent = '';
        setTimeout(function() { announcer.textContent = message; }, 100);
    }

    // ============================================
    // Search Focus Functionality
    // ============================================
    const Search = {
        searchInput: null,
        
        init: function() {
            // Find search input
            this.searchInput = document.getElementById('searchInput') ||
                              document.querySelector('input[type="search"]') ||
                              document.querySelector('input[placeholder*="Search"]');
            
            if (!this.searchInput) return;
            
            // Add real-time filtering if table exists
            this.setupRealTimeFilter();
        },
        
        focus: function() {
            if (this.searchInput) {
                this.searchInput.focus();
                this.searchInput.select();
                announce('Search input focused');
            }
        },
        
        setupRealTimeFilter: function() {
            var input = this.searchInput;
            var tableId = input.getAttribute('data-table') || 
                         (input.getAttribute('onkeyup') ? 
                          input.getAttribute('onkeyup').match(/'([^']+)'/g) : null);
            
            // Try to find table ID from the page
            if (!tableId) {
                var table = document.querySelector('.table');
                if (table && table.id) {
                    tableId = table.id;
                }
            }
            
            if (tableId && typeof tableId === 'string') {
                input.addEventListener('input', debounce(function() {
                    Search.filterTable(input.value, tableId);
                }, CONFIG.debounceDelay));
            }
        },
        
        filterTable: function(query, tableId) {
            var table = document.getElementById(tableId);
            if (!table) return;
            
            var rows = table.querySelectorAll('tbody tr');
            var filter = query.toLowerCase();
            var visibleCount = 0;
            
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                var isVisible = text.indexOf(filter) > -1;
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });
            
            // Announce results to screen readers
            announce(visibleCount + ' results found');
        }
    };

    // ============================================
    // Accessible Modal Management
    // ============================================
    const Modal = {
        lastFocusedElement: null,
        
        init: function() {
            // Listen for modal events
            document.addEventListener('show.bs.modal', function(e) {
                Modal.onOpen(e.target);
            });
            
            document.addEventListener('hidden.bs.modal', function(e) {
                Modal.onClose();
            });
            
            // Add close on Escape key for all modals
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    var openModal = document.querySelector('.modal.show');
                    if (openModal) {
                        var bsModal = bootstrap.Modal.getInstance(openModal);
                        if (bsModal) bsModal.hide();
                    }
                }
            });
        },
        
        onOpen: function(modal) {
            // Save last focused element
            this.lastFocusedElement = document.activeElement;
            
            // Trap focus within modal
            trapFocus(modal);
            
            // Focus first focusable element
            setTimeout(function() {
                var firstFocusable = modal.querySelector(
                    'input:not([type="hidden"]):not([disabled]), ' +
                    'select:not([disabled]), ' +
                    'textarea:not([disabled]), ' +
                    'button:not(.btn-close):not([disabled])'
                );
                if (firstFocusable) {
                    firstFocusable.focus();
                }
            }, 100);
            
            // Announce modal title
            var title = modal.querySelector('.modal-title');
            if (title) {
                announce('Dialog opened: ' + title.textContent);
            }
        },
        
        onClose: function() {
            // Return focus to last focused element
            if (this.lastFocusedElement) {
                this.lastFocusedElement.focus();
            }
            announce('Dialog closed');
        }
    };

    // ============================================
    // HCI Toast Notifications
    // ============================================
    const Toast = {
        container: null,
        
        init: function() {
            this.createContainer();
        },
        
        createContainer: function() {
            if (document.getElementById('hci-toast-container')) return;
            
            var container = document.createElement('div');
            container.id = 'hci-toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9998';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(container);
            this.container = container;
        },
        
        show: function(message, type, duration) {
            if (type === void 0) { type = 'info'; }
            if (duration === void 0) { duration = CONFIG.toastDuration; }
            
            if (!this.container) this.createContainer();
            
            var icons = {
                success: 'bi-check-circle-fill',
                error: 'bi-x-circle-fill',
                warning: 'bi-exclamation-triangle-fill',
                info: 'bi-info-circle-fill'
            };
            
            var colors = {
                success: '#2ea44f',
                error: '#e04646',
                warning: '#d97706',
                info: '#1e6fb8'
            };
            
            var toastId = 'toast-' + Date.now();
            var toast = document.createElement('div');
            toast.id = toastId;
            toast.className = 'toast-hci toast-' + type;
            toast.setAttribute('role', 'alert');
            toast.style.cssText = 'background: #fff; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); min-width: 300px; animation: slideInRight 0.3s ease-out;';
            
            toast.innerHTML = 
                '<div class="toast-body d-flex align-items-center gap-3 p-3">' +
                    '<div class="toast-icon" style="width: 36px; height: 36px; border-radius: 8px; background: ' + colors[type] + '15; display: flex; align-items: center; justify-content: center;">' +
                        '<i class="bi ' + icons[type] + '" style="color: ' + colors[type] + '; font-size: 18px;" aria-hidden="true"></i>' +
                    '</div>' +
                    '<div style="flex: 1; font-weight: 500; color: #1e293b;">' + message + '</div>' +
                    '<button type="button" class="btn-close" aria-label="Close toast"></button>' +
                '</div>' +
                '<div class="toast-progress" style="height: 3px; background: #e6e8eb;">' +
                    '<div class="toast-progress-bar" style="height: 100%; background: ' + colors[type] + '; width: 100%; transition: width ' + duration + 'ms linear;"></div>' +
                '</div>';
            
            this.container.appendChild(toast);
            
            // Animate progress bar
            setTimeout(function() {
                var progressBar = toast.querySelector('.toast-progress-bar');
                if (progressBar) progressBar.style.width = '0%';
            }, 50);
            
            // Auto dismiss
            var dismissTimeout = setTimeout(function() {
                Toast.dismiss(toast);
            }, duration);
            
            // Manual dismiss
            toast.querySelector('.btn-close').addEventListener('click', function() {
                clearTimeout(dismissTimeout);
                Toast.dismiss(toast);
            });
            
            // Announce to screen readers
            announce(message, 'polite');
        },
        
        dismiss: function(toast) {
            toast.style.animation = 'fadeOut 0.3s ease-out forwards';
            setTimeout(function() { 
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        },
        
        success: function(message) { this.show(message, 'success'); },
        error: function(message) { this.show(message, 'error'); },
        warning: function(message) { this.show(message, 'warning'); },
        info: function(message) { this.show(message, 'info'); }
    };

    // ============================================
    // Undo Toast for Delete Actions
    // ============================================
    const UndoToast = {
        show: function(message, undoCallback, duration) {
            if (duration === void 0) { duration = 5000; }
            
            if (!Toast.container) Toast.createContainer();
            
            var toastId = 'undo-toast-' + Date.now();
            var toast = document.createElement('div');
            toast.id = toastId;
            toast.className = 'toast-hci toast-warning';
            toast.setAttribute('role', 'alert');
            toast.style.cssText = 'background: #fff; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); min-width: 300px; animation: slideInRight 0.3s ease-out;';
            
            toast.innerHTML = 
                '<div class="toast-body d-flex align-items-center gap-3 p-3">' +
                    '<div style="flex: 1; font-weight: 500; color: #1e293b;">' + message + '</div>' +
                    '<button type="button" class="btn btn-sm btn-warning undo-btn">Undo</button>' +
                    '<button type="button" class="btn-close" aria-label="Close"></button>' +
                '</div>' +
                '<div class="toast-progress" style="height: 3px; background: #e6e8eb;">' +
                    '<div class="toast-progress-bar" style="height: 100%; background: #d97706; width: 100%; transition: width ' + duration + 'ms linear;"></div>' +
                '</div>';
            
            Toast.container.appendChild(toast);
            
            // Animate progress bar
            setTimeout(function() {
                var progressBar = toast.querySelector('.toast-progress-bar');
                if (progressBar) progressBar.style.width = '0%';
            }, 50);
            
            // Undo button
            toast.querySelector('.undo-btn').addEventListener('click', function() {
                if (undoCallback) undoCallback();
                Toast.dismiss(toast);
            });
            
            // Auto dismiss
            var dismissTimeout = setTimeout(function() {
                Toast.dismiss(toast);
            }, duration);
            
            // Manual dismiss
            toast.querySelector('.btn-close').addEventListener('click', function() {
                clearTimeout(dismissTimeout);
                Toast.dismiss(toast);
            });
        }
    };

    // ============================================
    // Keyboard Navigation Enhancement
    // ============================================
    const KeyboardNav = {
        init: function() {
            // Add keyboard shortcut hints
            this.addKeyboardHints();
            
            // Enhanced table row navigation
            this.setupTableNavigation();
        },
        
        addKeyboardHints: function() {
            // Find buttons that could have shortcuts
            var addButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
            addButtons.forEach(function(btn) {
                if (btn.textContent.indexOf('Add') > -1) {
                    btn.setAttribute('title', btn.getAttribute('title') || '' + ' (Shortcut: Alt+A)');
                }
            });
            
            // Add Alt+A shortcut for Add buttons
            document.addEventListener('keydown', function(e) {
                if (e.altKey && e.key === 'a') {
                    e.preventDefault();
                    var addBtn = document.querySelector('.btn-primary-cta[data-bs-toggle="modal"], .btn-primary[data-bs-toggle="modal"]');
                    if (addBtn) addBtn.click();
                }
            });
        },
        
        setupTableNavigation: function() {
            var tables = document.querySelectorAll('.table');
            
            tables.forEach(function(table) {
                var rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(function(row, index) {
                    row.setAttribute('tabindex', '0');
                    
                    row.addEventListener('keydown', function(e) {
                        if (e.key === 'ArrowDown' && rows[index + 1]) {
                            e.preventDefault();
                            rows[index + 1].focus();
                        } else if (e.key === 'ArrowUp' && rows[index - 1]) {
                            e.preventDefault();
                            rows[index - 1].focus();
                        } else if (e.key === 'Enter') {
                            // Trigger edit on Enter
                            var editBtn = row.querySelector('.btn-info, [onclick*="edit"]');
                            if (editBtn) editBtn.click();
                        } else if (e.key === 'Delete') {
                            // Trigger delete on Delete key
                            var deleteBtn = row.querySelector('.btn-danger');
                            if (deleteBtn) deleteBtn.click();
                        }
                    });
                });
            });
        }
    };

    // ============================================
    // Add CSS keyframes for animations
    // ============================================
    function addAnimationStyles() {
        if (document.getElementById('hci-animations')) return;
        
        var style = document.createElement('style');
        style.id = 'hci-animations';
        style.textContent = 
            '@keyframes slideInRight {' +
            '  from { transform: translateX(100%); opacity: 0; }' +
            '  to { transform: translateX(0); opacity: 1; }' +
            '}' +
            '@keyframes fadeOut {' +
            '  from { opacity: 1; transform: translateX(0); }' +
            '  to { opacity: 0; transform: translateX(20px); }' +
            '}' +
            '.sr-only {' +
            '  position: absolute;' +
            '  width: 1px;' +
            '  height: 1px;' +
            '  padding: 0;' +
            '  margin: -1px;' +
            '  overflow: hidden;' +
            '  clip: rect(0, 0, 0, 0);' +
            '  white-space: nowrap;' +
            '  border: 0;' +
            '}';
        document.head.appendChild(style);
    }

    // ============================================
    // Initialize all modules on DOM ready
    // ============================================
    function init() {
        addAnimationStyles();
        Search.init();
        Modal.init();
        Toast.init();
        KeyboardNav.init();
    }

    // Run on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ============================================
    // Expose public API
    // ============================================
    window.HCI = {
        Toast: Toast,
        UndoToast: UndoToast,
        announce: announce
    };

})();
