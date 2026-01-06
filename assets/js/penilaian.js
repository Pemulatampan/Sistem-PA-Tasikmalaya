document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
});

// Initialize Dashboard
function initDashboard() {
    animateProgressBars();
    initTableScroll();
    handleFormSubmit();
    initPerPageSelector();
    checkCacheStatus();
}

// Animate Progress Bars
function animateProgressBars() {
    const progressBars = document.querySelectorAll('.progress-fill-compact');
    
    progressBars.forEach((bar, index) => {
        const targetWidth = bar.style.width;
        bar.style.width = '0';
        
        setTimeout(() => {
            bar.style.width = targetWidth;
        }, 100 + (index * 50));
    });
}

// Initialize Table Scroll with Drag
function initTableScroll() {
    const tableWrapper = document.querySelector('.table-wrapper');
    
    if (!tableWrapper) return;
    
    // Scroll indicator
    let scrollTimeout;
    
    tableWrapper.addEventListener('scroll', function() {
        // Add scrolling class for potential styling
        this.classList.add('is-scrolling');
        
        clearTimeout(scrollTimeout);
        
        scrollTimeout = setTimeout(() => {
            this.classList.remove('is-scrolling');
        }, 1000);
    });
    
    // Drag to scroll functionality
    let isDown = false;
    let startX;
    let scrollLeft;
    
    tableWrapper.addEventListener('mousedown', (e) => {
        if (tableWrapper.scrollWidth <= tableWrapper.clientWidth) return;
        
        isDown = true;
        tableWrapper.classList.add('is-grabbing');
        startX = e.pageX - tableWrapper.offsetLeft;
        scrollLeft = tableWrapper.scrollLeft;
        tableWrapper.style.cursor = 'grabbing';
    });
    
    tableWrapper.addEventListener('mouseleave', () => {
        isDown = false;
        tableWrapper.classList.remove('is-grabbing');
        tableWrapper.style.cursor = 'grab';
    });
    
    tableWrapper.addEventListener('mouseup', () => {
        isDown = false;
        tableWrapper.classList.remove('is-grabbing');
        tableWrapper.style.cursor = 'grab';
    });
    
    tableWrapper.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - tableWrapper.offsetLeft;
        const walk = (x - startX) * 2;
        tableWrapper.scrollLeft = scrollLeft - walk;
    });
    
    // Set initial cursor
    if (tableWrapper.scrollWidth > tableWrapper.clientWidth) {
        tableWrapper.style.cursor = 'grab';
    }
}

// Handle Form Submit
function handleFormSubmit() {
    const filterForm = document.querySelector('.filter-form');
    
    if (!filterForm) return;
    
    filterForm.addEventListener('submit', function(e) {
        const button = this.querySelector('button[type="submit"]');
        if (button) {
            button.textContent = 'Memuat...';
            button.disabled = true;
        }
    });
}

// Initialize Per Page Selector
function initPerPageSelector() {
    const perPageSelect = document.querySelector('select[name="per_page"]');
    
    if (!perPageSelect) return;
    
    perPageSelect.addEventListener('change', function() {
        changePerPage(this.value);
    });
}

// Change Per Page (for detail_publikasi.php)
function changePerPage(value) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('per_page', value);
    urlParams.set('page', 1);
    window.location.search = urlParams.toString();
}

// Format Number to Locale String
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

// Get Progress Color based on percentage
function getProgressColor(percentage) {
    if (percentage >= 85) {
        return '#28a745';
    } else if (percentage >= 70) {
        return '#20c997';
    } else if (percentage >= 55) {
        return '#ffc107';
    } else {
        return '#dc3545';
    }
}

/**
 * Export Table to CSV
function exportTableToCSV(filename = 'penilaian-kinerja.csv') {
    const table = document.querySelector('table');
    if (!table) {
        showToast('Tabel tidak ditemukan', 'error');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        let csvRow = [];
        const cells = row.querySelectorAll('td, th');
        
        cells.forEach(cell => {
            csvRow.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        });
        
        csv.push(csvRow.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
    showToast('File CSV berhasil diunduh', 'success');
}

/**
 * Download CSV File
function downloadCSV(csv, filename) {
    const csvFile = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
} 
*/

// Print Dashboard
function printDashboard() {
    window.print();
}

// Refresh Dashboard
function refreshDashboard() {
    location.reload();
}

// Show Toast Notification
function showToast(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        padding: 15px 20px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 9999;
        font-size: 14px;
        font-weight: 500;
        max-width: 350px;
        animation: slideIn 0.3s ease;
    `;
    
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (toast.parentNode) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, duration);
}

// Debounce Function - Utility
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

// Throttle Function - Utility
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// CSS Animations for Toast
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .table-wrapper.is-scrolling {
        box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
    }
    
    .table-wrapper.is-grabbing {
        cursor: grabbing !important;
        user-select: none;
    }
`;
document.head.appendChild(style);