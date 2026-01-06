// global.js - Global JavaScript Functions

// SIDEBAR
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebar && mainContent) {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }
}

function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('mobile-open');
}

// FORMAT FUNCTIONS
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(angka);
}

function formatTanggalIndonesia(tanggal) {
    const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const date = new Date(tanggal);
    return `${date.getDate()} ${bulan[date.getMonth()]} ${date.getFullYear()}`;
}

// NOTIFICATIONS
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.style.transform = 'translateX(0)', 10);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showSuccess(message) { showNotification(message, 'success'); }
function showError(message) { showNotification(message, 'error'); }

// CONFIRMATION
function confirmDelete(message) {
    return confirm(message || 'Apakah Anda yakin ingin menghapus data ini?');
}

// LOADING
function showLoading(element) {
    if (!element) return;
    element.style.opacity = '0.6';
    element.style.pointerEvents = 'none';
    
    const loader = document.createElement('div');
    loader.className = 'loading-overlay';
    loader.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    loader.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 24px;
        color: #3498db;
    `;
    
    element.style.position = 'relative';
    element.appendChild(loader);
}

function hideLoading(element) {
    if (!element) return;
    element.style.opacity = '1';
    element.style.pointerEvents = 'auto';
    const loader = element.querySelector('.loading-overlay');
    if (loader) loader.remove();
}

// FORM VALIDATION
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    for (let input of inputs) {
        if (!input.value.trim()) {
            showError(`Field ${input.name || input.id} harus diisi!`);
            input.focus();
            return false;
        }
    }
    return true;
}

// UTILITIES
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// MOBILE TOGGLE BUTTON
function addMobileToggleButton() {
    const headerTitle = document.querySelector('.header-title');
    if (!headerTitle || document.querySelector('.mobile-toggle')) return;
    
    const mobileToggle = document.createElement('button');
    mobileToggle.innerHTML = '<i class="fas fa-bars"></i>';
    mobileToggle.className = 'mobile-toggle';
    mobileToggle.style.cssText = `
        background: none;
        border: none;
        font-size: 18px;
        color: #2c3e50;
        cursor: pointer;
        margin-right: 15px;
        padding: 5px;
    `;
    mobileToggle.onclick = toggleMobileSidebar;
    headerTitle.parentNode.insertBefore(mobileToggle, headerTitle);
}

// INITIALIZATION
document.addEventListener('DOMContentLoaded', function() {
    // Restore sidebar state
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        if (sidebar && mainContent) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    }
    
    // Close mobile sidebar on outside click
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;
        
        const isClickInside = sidebar.contains(event.target);
        const isMobileToggle = event.target.classList.contains('mobile-toggle');
        
        if (!isClickInside && !isMobileToggle && window.innerWidth <= 768) {
            sidebar.classList.remove('mobile-open');
        }
    });
    
    // Add mobile toggle for small screens
    if (window.innerWidth <= 768) addMobileToggleButton();
    
    // Auto-hide alerts
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Set current date
    const dateElement = document.getElementById('current-date');
    if (dateElement) {
        dateElement.textContent = formatTanggalIndonesia(new Date());
    }
});