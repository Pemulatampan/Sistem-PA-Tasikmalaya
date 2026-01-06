// js_pihak.js - JavaScript Khusus tabel Pihak

// INITIALIZATION
document.addEventListener('DOMContentLoaded', function() {
    initSearch();
    initFieldFormatting();
    initAlerts();
    initTableFeatures();
});

// ===== SEARCH =====
function initSearch() {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            this.form.submit();
        }, 500));
    }
}

function changeEntries() {
    const entries = document.getElementById('entriesSelect').value;
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('entries', entries);
    urlParams.delete('page');
    window.location.href = '?' + urlParams.toString();
}

// ===== FIELD FORMATTING =====
function initFieldFormatting() {
    // Format NIK
    const nikFields = document.querySelectorAll('input[name="id"]');
    nikFields.forEach(field => {
        if (!field.readOnly) {
            field.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-Z0-9_-]/g, '').slice(0, 50);
            });
        }
    });
    
    // Format Tanggal Lahir - SIMPLIFIED VERSION
    const tanggalFields = document.querySelectorAll('input[name="tanggal_lahir"]');
    tanggalFields.forEach(field => {
        // CRITICAL: Store the initial value in a data attribute (survives all events)
        if (field.value && !field.dataset.initialValue) {
            field.dataset.initialValue = field.value;
        }
        
        let isDeleting = false;
        let userIsTyping = false;
        
        // Before any event, restore value if it was cleared unexpectedly
        ['mousedown', 'click', 'focus'].forEach(eventType => {
            field.addEventListener(eventType, function(e) {
                // If field is empty but we have a saved value, restore it
                if (!userIsTyping && this.value === '' && this.dataset.initialValue) {
                    this.value = this.dataset.initialValue;
                }
            }, true); // Use capture to run before other handlers
        });
        
        field.addEventListener('focus', function() {
            // When focused, save current value
            if (this.value) {
                this.dataset.initialValue = this.value;
            }
            userIsTyping = false;
        });
        
        field.addEventListener('keydown', function(e) {
            userIsTyping = true;
            
            if (e.key === 'Backspace' || e.key === 'Delete') {
                isDeleting = true;
                
                // Clear if invalid patterns
                if (['00/00', '0/', '00/', '/', '//'].includes(this.value)) {
                    e.preventDefault();
                    this.value = '';
                    this.dataset.initialValue = '';
                }
                
                setTimeout(() => isDeleting = false, 100);
            }
        });
        
        field.addEventListener('input', function(e) {
            // ONLY format if user is actively typing (not from programmatic changes)
            if (!userIsTyping) {
                return;
            }
            
            if (isDeleting) return;
            
            let value = this.value.replace(/[^\d]/g, '');
            
            // If cleared by user, allow it
            if (value.length === 0) {
                this.value = '';
                this.dataset.initialValue = '';
                return;
            }
            
            // Prevent 00 as day or month
            if (value.length >= 2 && value.substring(0, 2) === '00') {
                this.value = value.substring(0, 1);
                return;
            }
            if (value.length >= 4 && value.substring(2, 4) === '00') {
                this.value = value.substring(0, 2) + '/' + value.substring(3);
                return;
            }
            
            // Format with slashes
            if (value.length >= 2) value = value.substring(0, 2) + '/' + value.substring(2);
            if (value.length >= 5) value = value.substring(0, 5) + '/' + value.substring(5, 9);
            
            this.value = value;
            this.dataset.initialValue = value; // Save formatted value
        });
        
        field.addEventListener('blur', function() {
            userIsTyping = false;
            
            const trimmedValue = this.value.trim();
            
            // Clear only if truly empty or invalid
            if (trimmedValue === '' || trimmedValue === '/' || trimmedValue === '//' || 
                trimmedValue === '00/00') {
                this.value = '';
                this.dataset.initialValue = '';
                return;
            }
            
            // Otherwise save the value
            if (trimmedValue) {
                this.dataset.initialValue = trimmedValue;
            }
        });
        
        // ULTIMATE PROTECTION: Use setInterval to constantly check and restore
        setInterval(function() {
            // If not currently typing and value is empty but we have saved value
            if (!userIsTyping && field.value === '' && field.dataset.initialValue && 
                document.activeElement !== field) {
                field.value = field.dataset.initialValue;
            }
        }, 100);
    });
}

// ===== FORM VALIDATION =====
function validateForm(form) {
    const fields = form.querySelectorAll('input, textarea');
    let isValid = true;
    
    fields.forEach(field => {
        clearFieldError(field);
        
        if (field.hasAttribute('required') && !field.value.trim()) {
            showFieldError(field, 'Field ini wajib diisi');
            isValid = false;
        }
        
        if (field.value.trim() && field.name === 'tanggal_lahir') {
            const cleanValue = field.value.replace(/[\/\-\s]/g, '');
            if (cleanValue && !validateDateText(field.value)) {
                showFieldError(field, 'Format tanggal tidak valid. Gunakan DD/MM/YYYY');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    field.style.borderColor = '#dc3545';
    field.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
    
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) existingError.remove();
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.cssText = 'color:#dc3545;font-size:12px;margin-top:5px;';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.style.borderColor = '#e1e5e9';
    field.style.boxShadow = '';
    const errorMessage = field.parentNode.querySelector('.error-message');
    if (errorMessage) errorMessage.remove();
}

function validateDateText(dateString) {
    const dateRegex = /^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/;
    const match = dateString.match(dateRegex);
    if (!match) return false;
    
    const day = parseInt(match[1], 10);
    const month = parseInt(match[2], 10);
    const year = parseInt(match[3], 10);
    
    if (day < 1 || day > 31 || month < 1 || month > 12) return false;
    if (year < 1900 || year > new Date().getFullYear()) return false;
    
    const date = new Date(year, month - 1, day);
    return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
}

// ===== ALERTS =====
function initAlerts() {
    document.querySelectorAll('.alert').forEach(alert => {
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = 'float:right;background:none;border:none;font-size:18px;cursor:pointer;margin-left:10px;';
        closeBtn.addEventListener('click', () => alert.remove());
        alert.appendChild(closeBtn);
        
        if (alert.classList.contains('alert-success')) {
            setTimeout(() => alert.remove(), 5000);
        }
    });
}

// ===== TABLE FEATURES =====
function initTableFeatures() {
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener('keydown', function(e) {
    // Ctrl+F for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // Ctrl+S to save form
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const form = document.querySelector('form');
        if (form) form.submit();
    }
    
    // Escape to close alerts
    if (e.key === 'Escape') {
        document.querySelectorAll('.alert').forEach(alert => alert.style.display = 'none');
    }
});

// ===== PAGINATION =====
document.addEventListener('DOMContentLoaded', function() {
    const paginationLinks = document.querySelectorAll('.page-link');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
});

// ===== UTILITIES =====
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:1001;min-width:300px;';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// Export for global use
window.showToast = showToast;