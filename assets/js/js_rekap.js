// js_rekap.js - Optimized and Fixed Version
// ================================================
// GLOBAL VARIABLES
// ================================================
let currentFilter = 'semua';
let currentSearchTerm = '';
let currentPage = 1;
let rowsPerPage = 10;
let totalRows = 0;
let perkaraChart, registrationChart;
let sortConfig = {
    column: null,
    direction: null
};
let hideDropdownTimeout;
let sortingInitialized = false;
let originalRowOrder = [];

console.log('js_rekap.js loaded - initializing...');

// ================================================
// CHART FUNCTIONS
// ================================================
function initializeCharts() {
    const perkaraCtx = document.getElementById('perkaraChart');
    if (perkaraCtx && typeof dashboardData !== 'undefined' && dashboardData.jenisPerkaraData) {
        console.log('Initializing chart with', dashboardData.jenisPerkaraData.length, 'categories');

        const labels = dashboardData.jenisPerkaraData.map(item => item.nama);
        const data = dashboardData.jenisPerkaraData.map(item => item.jumlah);

        console.log('Labels:', labels);
        console.log('Data:', data);

        // Generate colors
        const baseColors = [
            'rgba(52, 152, 219, 0.8)', 'rgba(46, 204, 113, 0.8)',
            'rgba(230, 126, 34, 0.8)', 'rgba(155, 89, 182, 0.8)',
            'rgba(26, 188, 156, 0.8)', 'rgba(241, 196, 15, 0.8)',
            'rgba(231, 76, 60, 0.8)', 'rgba(52, 73, 94, 0.8)',
            'rgba(142, 68, 173, 0.8)', 'rgba(39, 174, 96, 0.8)', 
            'rgba(192, 57, 43, 0.8)', 'rgba(241, 196, 15, 0.8)'
        ];
        
        const colors = data.map((_, i) => baseColors[i % baseColors.length]);
        const borderColors = colors.map(c => c.replace('0.8', '1'));

        // Calculate dynamic height
        const categoryCount = labels.length;
        const heightPerBar = 50;
        const calculatedCanvasHeight = Math.max(categoryCount * heightPerBar, 400);
        
        perkaraCtx.style.height = calculatedCanvasHeight + 'px';
        perkaraCtx.height = calculatedCanvasHeight;
        
        console.log('Canvas height set to:', calculatedCanvasHeight + 'px');

        // Destroy existing chart if any
        if (window.perkaraChart instanceof Chart) {
            window.perkaraChart.destroy();
            console.log('Previous chart destroyed');
        }

        // Create horizontal bar chart
        window.perkaraChart = new Chart(perkaraCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Perkara',
                    data: data,
                    backgroundColor: colors,
                    borderColor: borderColors,
                    borderWidth: 1,
                    barPercentage: 0.8,
                    categoryPercentage: 0.9
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'nearest',
                    axis: 'y',
                    intersect: false
                },
                plugins: { 
                    legend: { 
                        display: false 
                    },
                    tooltip: { 
                        enabled: true,
                        mode: 'index',
                        axis: 'y',
                        intersect: false,
                        position: 'average',
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                return 'Jumlah Perkara: ' + context.parsed.x;
                            }
                        }
                    }
                },
                layout: { 
                    padding: {
                        top: 15,
                        bottom: 15,
                        left: 10,
                        right: 25
                    }
                },
                scales: { 
                    x: {
                        beginAtZero: true,
                        ticks: { 
                            precision: 0,
                            font: { size: 11 }
                        },
                        grid: { 
                            color: 'rgba(200,200,200,0.2)' 
                        }
                    },
                    y: {
                        beforeFit: function(axis) {
                            axis.ticks = axis.chart.data.labels.map((label, i) => ({
                                value: i,
                                label: label
                            }));
                        },
                        ticks: {
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0,
                            font: { size: 10 },
                            padding: 8,
                            sampleSize: labels.length,
                            callback: function(value, index) {
                                const label = this.getLabelForValue(value);
                                return label.length > 35 ? label.slice(0, 35) + '...' : label;
                            }
                        },
                        grid: { 
                            display: false 
                        }
                    }
                }
            }
        });
        
        console.log('Chart rendered successfully with', categoryCount, 'categories');
    }

    // Donut chart for registration method
    const registrationCtx = document.getElementById('registrationChart');
    if (registrationCtx && typeof dashboardData !== 'undefined' && dashboardData.caraDaftar) {
        console.log('Initializing donut chart');
        
        if (window.registrationChart instanceof Chart) {
            window.registrationChart.destroy();
        }
        
        window.registrationChart = new Chart(registrationCtx, {
            type: 'doughnut',
            data: {
                labels: ['E-Court', 'Manual'],
                datasets: [{
                    data: [dashboardData.caraDaftar.eCourt, dashboardData.caraDaftar.manual],
                    backgroundColor: ['rgba(243, 156, 18, 0.8)', 'rgba(108, 117, 125, 0.8)'],
                    borderColor: ['rgba(243, 156, 18, 1)', 'rgba(108, 117, 125, 1)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        position: 'bottom',
                        labels: { font: { size: 12 } }
                    }
                }
            }
        });
        
        console.log('Donut chart rendered');
    }
}

function animateProgressBars() {
    if (typeof dashboardData === 'undefined' || !dashboardData.status || !dashboardData.totalAllCases) return;
    
    const total = dashboardData.totalAllCases;
    setTimeout(() => {
        const putusPercent = total > 0 ? (dashboardData.status.putus / total * 100) : 0;
        const minutasiPercent = total > 0 ? (dashboardData.status.minutasi / total * 100) : 0;
        const prosesPercent = total > 0 ? (dashboardData.status.dalamProses / total * 100) : 0;
        
        const putusBar = document.getElementById('putusBar');
        const minutasiBar = document.getElementById('minutasiBar');
        const prosesBar = document.getElementById('prosesBar');
        
        if (putusBar) {
            putusBar.style.width = putusPercent + '%';
            putusBar.textContent = Math.round(putusPercent) + '%';
        }
        if (minutasiBar) {
            minutasiBar.style.width = minutasiPercent + '%';
            minutasiBar.textContent = Math.round(minutasiPercent) + '%';
        }
        if (prosesBar) {
            prosesBar.style.width = prosesPercent + '%';
            prosesBar.textContent = Math.round(prosesPercent) + '%';
        }
    }, 300);
}

// ================================================
// MODE SWITCHING
// ================================================
function switchMode(mode) {
    const singleForm = document.getElementById('singleDateForm');
    const rangeForm = document.getElementById('rangeDateForm');
    const quickButtons = document.querySelector('.quick-date-buttons');
    const modeTabs = document.querySelectorAll('.mode-tab');
    
    modeTabs.forEach(tab => tab.classList.remove('active'));
    
    if (mode === 'single') {
        if (singleForm) {
            singleForm.style.display = 'block';
            singleForm.classList.add('active');
        }
        if (rangeForm) {
            rangeForm.style.display = 'none';
            rangeForm.classList.remove('active');
        }
        if (quickButtons) quickButtons.style.display = 'none';
        if (modeTabs[0]) modeTabs[0].classList.add('active');
    } else {
        if (singleForm) {
            singleForm.style.display = 'none';
            singleForm.classList.remove('active');
        }
        if (rangeForm) {
            rangeForm.style.display = 'block';
            rangeForm.classList.add('active');
        }
        if (quickButtons) quickButtons.style.display = 'flex';
        if (modeTabs[1]) modeTabs[1].classList.add('active');
    }
}

// ================================================
// PRESERVE FILTER STATE
// ================================================
function preserveFilterInLinks() {
    console.log('Preserving filter state in navigation links...');
    
    const urlParams = new URLSearchParams(window.location.search);
    const tanggal = urlParams.get('tanggal');
    const tanggalMulai = urlParams.get('tanggal_mulai');
    const tanggalAkhir = urlParams.get('tanggal_akhir');
    
    if (!tanggal && !tanggalMulai && !tanggalAkhir) {
        console.log('No date filter to preserve');
        return;
    }
    
    let filterParams = '';
    if (tanggal) {
        filterParams = `tanggal=${tanggal}`;
    } else if (tanggalMulai && tanggalAkhir) {
        filterParams = `tanggal_mulai=${tanggalMulai}&tanggal_akhir=${tanggalAkhir}`;
    }
    
    console.log('Filter params to preserve:', filterParams);
    
    const selectors = [
        'a[href*="detail.php"]',
        'a[href*="pihak"]',
        'a[href*="rekap"]',
        'a.menu-item',
        'a.btn:not(.export-btn)',
    ];
    
    selectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        
        elements.forEach(element => {
            if (element.dataset.filterPreserved) return;
            
            const href = element.getAttribute('href');
            
            if (!href || href === '#' || href.startsWith('javascript:')) return;
            if (href.includes('tanggal=') || href.includes('tanggal_mulai=')) return;
            if (href.startsWith('http://') || href.startsWith('https://')) return;
            
            const separator = href.includes('?') ? '&' : '?';
            const newHref = `${href}${separator}${filterParams}`;
            
            element.setAttribute('href', newHref);
            element.dataset.filterPreserved = 'true';
        });
    });
    
    console.log('Filter state preserved in navigation links');
}

function setupCardClickHandlersWithFilter() {
    console.log('Setting up card click handlers...');
    
    // Get date params
    const urlParams = new URLSearchParams(window.location.search);
    let dateParams = '';
    
    if (urlParams.has('tanggal')) {
        dateParams = 'tanggal=' + urlParams.get('tanggal');
    } else if (urlParams.has('tanggal_mulai') && urlParams.has('tanggal_akhir')) {
        dateParams = 'tanggal_mulai=' + urlParams.get('tanggal_mulai') + 
                    '&tanggal_akhir=' + urlParams.get('tanggal_akhir');
    }
    
    // Get all cards
    const cards = document.querySelectorAll('.dashboard-cards .card');
    console.log('Found', cards.length, 'cards');
    
    if (cards.length === 0) {
        console.error('No cards found!');
        return;
    }
    
    // Setup each card with onclick
    cards.forEach((card, index) => {
        const kategori = card.getAttribute('data-kategori');
        const kategoriDetail = card.getAttribute('data-kategori-detail');
        const jenisNama = card.getAttribute('data-jenis-nama');
        
        console.log(`Card ${index + 1}:`, { kategori, kategoriDetail, jenisNama });
        
        // gunakan onclick
        card.onclick = function() {
            console.log('\nCARD CLICKED!', this);
            
            const k = this.getAttribute('data-kategori');
            const kd = this.getAttribute('data-kategori-detail');
            
            let targetKat = 'permohonan';
            
            if (kd && kd.trim() !== '') {
                targetKat = kd;
            } else if (['ecourt', 'manual', 'putus', 'minutasi'].includes(k)) {
                targetKat = k;
            }
            
            let url = 'perkara/detail.php?kategori=' + encodeURIComponent(targetKat);
            
            if (dateParams) {
                url += '&' + dateParams;
            }
            
            console.log('GO TO:', url);
            window.location.href = url;
        };
        
        console.log(`Card ${index + 1} ready`);
    });
    
    console.log('All handlers installed!\n');
}

function addBackToDashboardButton() {
    if (!window.location.pathname.includes('detail.php')) return;
    
    const urlParams = new URLSearchParams(window.location.search);
    const tanggal = urlParams.get('tanggal');
    const tanggalMulai = urlParams.get('tanggal_mulai');
    const tanggalAkhir = urlParams.get('tanggal_akhir');
    
    let dashboardUrl = '../index.php';
    if (tanggal) {
        dashboardUrl += `?tanggal=${tanggal}`;
    } else if (tanggalMulai && tanggalAkhir) {
        dashboardUrl += `?tanggal_mulai=${tanggalMulai}&tanggal_akhir=${tanggalAkhir}`;
    }
    
    const existingBackBtn = document.querySelector('a[href*="index.php"]');
    if (existingBackBtn && !existingBackBtn.href.includes('tanggal')) {
        existingBackBtn.href = dashboardUrl;
    }
}

function restoreFilterFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    
    const tanggal = urlParams.get('tanggal');
    if (tanggal) {
        const tanggalInput = document.querySelector('input[name="tanggal"]');
        if (tanggalInput) {
            tanggalInput.value = tanggal;
        }
    }
    
    const tanggalMulai = urlParams.get('tanggal_mulai');
    const tanggalAkhir = urlParams.get('tanggal_akhir');
    if (tanggalMulai && tanggalAkhir) {
        const mulaiInput = document.querySelector('input[name="tanggal_mulai"]');
        const akhirInput = document.querySelector('input[name="tanggal_akhir"]');
        
        if (mulaiInput && akhirInput) {
            mulaiInput.value = tanggalMulai;
            akhirInput.value = tanggalAkhir;
        }
    }
}

// ================================================
// FILTER & SEARCH FUNCTIONS
// ================================================
function filterPerkaraTable(filterType) {
    currentFilter = filterType;
    applyFilters();
}

function applyFilters() {
    const rows = document.querySelectorAll('.perkara-row');
    let visibleRows = [];
    
    rows.forEach(row => {
        row.style.display = ''; 
    });
    
    rows.forEach(row => {
        const jenisFilter = row.getAttribute('data-jenis');
        const nomorPerkara = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
        const efilingId = row.querySelector('td:nth-child(7)')?.textContent.toLowerCase() || '';
        
        let matchFilter = (currentFilter === 'semua' || jenisFilter === currentFilter);
        let matchSearch = (currentSearchTerm === '' || nomorPerkara.includes(currentSearchTerm) || 
                          efilingId.includes(currentSearchTerm));
        
        if (matchFilter && matchSearch) {
            visibleRows.push(row);
        } else {
            row.style.display = 'none'; 
        }
    });
    
    totalRows = visibleRows.length;
    updateSearchResultCount(visibleRows.length);
    updateActiveFilters();
    paginateRows(visibleRows);
}

function updateSearchResultCount(count) {
    const countElement = document.getElementById('searchResultCount');
    if (countElement) {
        countElement.textContent = count + ' perkara';
    }
}

function updateActiveFilters() {
    const activeFiltersDiv = document.getElementById('activeFilters');
    const filterTagsDiv = activeFiltersDiv?.querySelector('.filter-tags');
    
    if (!activeFiltersDiv || !filterTagsDiv) return;
    
    let hasActiveFilters = false;
    filterTagsDiv.innerHTML = '';
    
    if (currentFilter !== 'semua') {
        hasActiveFilters = true;
        const selectedItem = document.querySelector(`.dropdown-item[data-filter="${currentFilter}"]`);
        const filterName = selectedItem?.querySelector('span:first-of-type')?.textContent || currentFilter;
        
        const tag = document.createElement('div');
        tag.style.cssText = 'background: #e3f2fd; color: #1976d2; padding: 6px 12px; border-radius: 16px; font-size: 12px; display: flex; align-items: center; gap: 8px;';
        tag.innerHTML = `<i class="fas fa-filter"></i><span>${filterName}</span>
            <button onclick="clearFilterTag('jenis')" style="background: none; border: none; color: #1976d2; cursor: pointer; padding: 0; font-size: 14px;">
                <i class="fas fa-times"></i></button>`;
        filterTagsDiv.appendChild(tag);
    }
    
    if (currentSearchTerm !== '') {
        hasActiveFilters = true;
        const tag = document.createElement('div');
        tag.style.cssText = 'background: #fff3cd; color: #856404; padding: 6px 12px; border-radius: 16px; font-size: 12px; display: flex; align-items: center; gap: 8px;';
        tag.innerHTML = `<i class="fas fa-search"></i><span>Pencarian: "${currentSearchTerm}"</span>
            <button onclick="clearFilterTag('search')" style="background: none; border: none; color: #856404; cursor: pointer; padding: 0; font-size: 14px;">
                <i class="fas fa-times"></i></button>`;
        filterTagsDiv.appendChild(tag);
    }
    
    activeFiltersDiv.style.display = hasActiveFilters ? 'block' : 'none';
}

function selectFilter(filterValue, displayText) {
    currentFilter = filterValue;
    currentPage = 1;
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = displayText;
    
    const items = document.querySelectorAll('.dropdown-item');
    items.forEach(item => item.classList.remove('active'));
    
    const selectedItem = document.querySelector(`.dropdown-item[data-filter="${filterValue}"]`);
    if (selectedItem) selectedItem.classList.add('active');
    
    hideDropdown();
    applyFilters();
}

function handleSearchInput() {
    const input = document.getElementById('searchInput');
    const searchTerm = input.value.toLowerCase();
    const items = document.querySelectorAll('.dropdown-item');
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchTerm) ? 'flex' : 'none';
    });
}

function showDropdown() {
    const dropdown = document.getElementById('dropdownList');
    const icon = document.getElementById('dropdownIcon');
    if (dropdown) dropdown.style.display = 'block';
    if (icon) icon.style.transform = 'rotate(180deg)';
}

function hideDropdown() {
    const dropdown = document.getElementById('dropdownList');
    const icon = document.getElementById('dropdownIcon');
    if (dropdown) dropdown.style.display = 'none';
    if (icon) icon.style.transform = 'rotate(0deg)';
}

function hideDropdownDelay() {
    hideDropdownTimeout = setTimeout(() => hideDropdown(), 200);
}

function toggleDropdown() {
    const dropdown = document.getElementById('dropdownList');
    if (dropdown && dropdown.style.display === 'none') {
        showDropdown();
    } else {
        hideDropdown();
    }
}

function performDetailSearch() {
    const input = document.getElementById('detailSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    
    currentSearchTerm = input ? input.value.toLowerCase() : '';
    if (clearBtn) clearBtn.style.display = currentSearchTerm ? 'block' : 'none';
    
    currentPage = 1;
    applyFilters();
}

function clearDetailSearch() {
    const input = document.getElementById('detailSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    
    if (input) input.value = '';
    if (clearBtn) clearBtn.style.display = 'none';
    
    currentSearchTerm = '';
    currentPage = 1;
    applyFilters();
}

function clearFilterTag(type) {
    if (type === 'jenis') {
        currentFilter = 'semua';
        currentPage = 1;
        
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            const totalPerkara = document.querySelectorAll('.perkara-row').length;
            searchInput.value = `Semua Perkara (${totalPerkara})`;
        }
        
        const items = document.querySelectorAll('.dropdown-item');
        items.forEach(item => item.classList.remove('active'));
        const semuaItem = document.querySelector('.dropdown-item[data-filter="semua"]');
        if (semuaItem) semuaItem.classList.add('active');
    } else if (type === 'search') {
        currentPage = 1;
        clearDetailSearch();
        return;
    }
    
    applyFilters();
}

function clearAllFilters() {
    currentFilter = 'semua';
    currentSearchTerm = '';
    currentPage = 1;
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        const totalPerkara = document.querySelectorAll('.perkara-row').length;
        searchInput.value = `Semua Perkara (${totalPerkara})`;
    }
    
    const detailSearchInput = document.getElementById('detailSearchInput');
    if (detailSearchInput) detailSearchInput.value = '';
    
    const clearBtn = document.getElementById('clearSearchBtn');
    if (clearBtn) clearBtn.style.display = 'none';
    
    const items = document.querySelectorAll('.dropdown-item');
    items.forEach(item => item.classList.remove('active'));
    const semuaItem = document.querySelector('.dropdown-item[data-filter="semua"]');
    if (semuaItem) semuaItem.classList.add('active');
    
    applyFilters();
}

// ================================================
// TABLE SORTING
// ================================================
function saveOriginalIndexes() {
    const rows = document.querySelectorAll('.perkara-row');
    originalRowOrder = Array.from(rows);
    rows.forEach((row, index) => {
        row.dataset.originalIndex = index;
    });
    console.log('Original indexes saved for', rows.length, 'rows');
}

function setupTableSorting() {
    if (sortingInitialized) {
        console.log('Sorting already initialized');
        return true;
    }
    
    console.log('Setting up table sorting...');
    
    const table = document.querySelector('table');
    if (!table) {
        console.error('Table not found');
        return false;
    }
    
    const thead = table.querySelector('thead');
    if (!thead) {
        console.error('Thead not found');
        return false;
    }
    
    const headerRow = thead.querySelector('tr');
    if (!headerRow) {
        console.error('Header row not found');
        return false;
    }
    
    const headers = headerRow.querySelectorAll('th');
    if (!headers || headers.length === 0) {
        console.error('No headers found');
        return false;
    }
    
    console.log(`Found ${headers.length} headers`);
    
    // Setup setiap header
    headers.forEach((header, columnIndex) => {
        // Skip kolom yang hidden
        const isHidden = header.style.display === 'none' || 
                        window.getComputedStyle(header).display === 'none';
        
        if (isHidden) {
            console.log(`Column ${columnIndex} is hidden, skipping sort setup`);
            return;
        }
        
        // Skip jika sudah ada sort icon
        if (header.querySelector('.sort-icon')) {
            console.log(`Column ${columnIndex} already has sort icon`);
            return;
        }
        
        // Setup styling
        header.style.cursor = 'pointer';
        header.style.userSelect = 'none';
        header.style.position = 'relative';
        header.style.paddingRight = '35px';
        header.title = 'Klik untuk mengurutkan';
        
        // Tambahkan sort icon
        const sortIcon = document.createElement('span');
        sortIcon.className = 'sort-icon';
        sortIcon.style.cssText = `
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            font-size: 12px !important;
            color: rgba(255,255,255,0.7) !important;
            pointer-events: none !important;
            z-index: 10 !important;
        `;
        sortIcon.innerHTML = '<i class="fas fa-sort"></i>';
        header.appendChild(sortIcon);
        
        // Event listener untuk sorting
        header.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log(`Clicked column ${columnIndex}: "${this.textContent.trim()}"`);
            
            // Update sort config
            if (sortConfig.column === columnIndex) {
                if (sortConfig.direction === 'asc') {
                    sortConfig.direction = 'desc';
                } else if (sortConfig.direction === 'desc') {
                    sortConfig.direction = null;
                    sortConfig.column = null;
                } else {
                    sortConfig.direction = 'asc';
                }
            } else {
                sortConfig.column = columnIndex;
                sortConfig.direction = 'asc';
            }
            
            console.log(`Sort config: {column: ${sortConfig.column}, direction: ${sortConfig.direction}}`);
            
            // Update hanya icon yang diklik
            updateSortIcons(headers, sortConfig.column, sortConfig.direction);
            
            // Sort atau reset
            if (sortConfig.direction === null) {
                resetTableOrder();
            } else {
                sortTableByColumn(columnIndex, sortConfig.direction);
            }
        });
        
        console.log(`Column ${columnIndex} setup complete`);
    });
    
    sortingInitialized = true;
    console.log('Table sorting setup complete');
    return true;
}

function updateSortIcons(headers, activeIndex, direction) {
    headers.forEach((header, index) => {
        const icon = header.querySelector('.sort-icon i');
        if (!icon) return;
        
        if (index === activeIndex) {
            // Update HANYA kolom yang diklik
            if (direction === 'asc') {
                icon.className = 'fas fa-sort-up';
                icon.style.color = '#fff';
            } else if (direction === 'desc') {
                icon.className = 'fas fa-sort-down';
                icon.style.color = '#fff';
            } else {
                icon.className = 'fas fa-sort';
                icon.style.color = 'rgba(255,255,255,0.7)';
            }
        } else {
            // Reset kolom lainnya
            icon.className = 'fas fa-sort';
            icon.style.color = 'rgba(255,255,255,0.7)';
        }
    });
}

function parseCellValue(cell) {
    if (!cell) return { type: 'empty', value: '' };
    let text = cell.textContent.trim();

    if (text === '' || text === '-') {
        return { type: 'empty', value: '' };
    }

    // === DATE PARSING ===
    
    // Format: dd/mm/yyyy atau dd-mm-yyyy
    let dateMatch = text.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
    if (dateMatch) {
        const d = dateMatch[1].padStart(2, '0');
        const m = dateMatch[2].padStart(2, '0');
        const y = dateMatch[3];
        const date = new Date(`${y}-${m}-${d}`);
        if (!isNaN(date.getTime())) {
            return { type: 'date', value: date };
        }
    }

    // Format: yyyy/mm/dd atau yyyy-mm-dd
    dateMatch = text.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/);
    if (dateMatch) {
        const y = dateMatch[1];
        const m = dateMatch[2].padStart(2, '0');
        const d = dateMatch[3].padStart(2, '0');
        const date = new Date(`${y}-${m}-${d}`);
        if (!isNaN(date.getTime())) {
            return { type: 'date', value: date };
        }
    }

    // Format: dd NamaBulan yyyy (Indonesian/English months)
    dateMatch = text.match(/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/);
    if (dateMatch) {
        const months = {
            'januari': 1, 'februari': 2, 'maret': 3, 'april': 4,
            'mei': 5, 'juni': 6, 'juli': 7, 'agustus': 8,
            'september': 9, 'oktober': 10, 'november': 11, 'desember': 12,
            'january': 1, 'february': 2, 'march': 3, 'may': 5,
            'june': 6, 'july': 7, 'august': 8, 'october': 10, 'december': 12
        };
        const d = dateMatch[1].padStart(2, '0');
        const monthName = dateMatch[2].toLowerCase();
        const m = months[monthName];
        const y = dateMatch[3];

        if (m) {
            const date = new Date(`${y}-${String(m).padStart(2, '0')}-${d}`);
            if (!isNaN(date.getTime())) {
                return { type: 'date', value: date };
            }
        }
    }

    // === NUMBER PARSING ===
    const cleaned = text.replace(/[^\d\-,.]/g, '');
    if (/[0-9]/.test(cleaned)) {
        const num = parseFloat(cleaned.replace(/,/g, ''));
        if (!isNaN(num)) {
            return { type: 'number', value: num };
        }
    }

    // === STRING (default) ===
    return { type: 'string', value: text.toLowerCase() };
}

function sortTableByColumn(columnIndex, direction) {
    const table = document.querySelector('table');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const allRows = Array.from(tbody.querySelectorAll('tr.perkara-row'));
    
    // Check apakah ada rows
    if (allRows.length === 0) {
        console.warn('No rows found with class .perkara-row');
        return;
    }
    
    console.log(`Sorting ${allRows.length} rows by column ${columnIndex}`);
    
    // Simpan visibility state
    const visibilityMap = new Map();
    allRows.forEach(row => {
        visibilityMap.set(row, row.style.display);
    });

    // Sort rows
    allRows.sort((a, b) => {
        const cellA = a.cells[columnIndex];
        const cellB = b.cells[columnIndex];

        // untuk cell yang tidak ditemukan
        if (!cellA || !cellB) {
            console.warn(`Cell not found at column ${columnIndex}`, {
                rowA: a.cells.length + ' cells',
                rowB: b.cells.length + ' cells'
            });
            return 0;
        }

        const parsedA = parseCellValue(cellA);
        const parsedB = parseCellValue(cellB);
        
        // Debug parsing
        if (columnIndex === 1) { // Kolom Tanggal
            console.log('Parsing dates:', {
                textA: cellA.textContent.trim(),
                parsedA: parsedA,
                textB: cellB.textContent.trim(),
                parsedB: parsedB
            });
        }

        // Handle empty values
        if (parsedA.type === 'empty' && parsedB.type === 'empty') return 0;
        if (parsedA.type === 'empty') return 1;
        if (parsedB.type === 'empty') return -1;

        // Type ranking untuk mixed types
        const rank = { 'date': 3, 'number': 2, 'string': 1, 'empty': 0 };
        const rankA = rank[parsedA.type] ?? 0;
        const rankB = rank[parsedB.type] ?? 0;

        if (rankA !== rankB) {
            return rankA > rankB ? 1 : -1;
        }

        // Compare same types
        let cmp = 0;
        if (parsedA.type === 'date') {
            cmp = parsedA.value.getTime() - parsedB.value.getTime();
        } else if (parsedA.type === 'number') {
            cmp = parsedA.value - parsedB.value;
        } else if (parsedA.type === 'string') {
            cmp = parsedA.value.localeCompare(parsedB.value, 'id', { sensitivity: 'base' });
        }

        return direction === 'asc' ? cmp : -cmp;
    });

    // Re-append sorted rows dan restore visibility
    allRows.forEach(row => tbody.removeChild(row));
    allRows.forEach(row => {
        tbody.appendChild(row);
        row.style.display = visibilityMap.get(row) || '';
    });

    updateRowNumbers();
    
    console.log(`Table sorted by column ${columnIndex} (${direction})`);
}

function resetTableOrder() {
    const table = document.querySelector('table');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const allRows = Array.from(tbody.querySelectorAll('tr.perkara-row'));
    
    // Simpan visibility state
    const visibilityMap = new Map();
    allRows.forEach(row => {
        visibilityMap.set(row, row.style.display);
    });
    
    // Sort by original index
    allRows.sort((a, b) => {
        const indexA = parseInt(a.dataset.originalIndex) || 0;
        const indexB = parseInt(b.dataset.originalIndex) || 0;
        return indexA - indexB;
    });
    
    // Re-append dan restore visibility
    allRows.forEach(row => tbody.removeChild(row));
    allRows.forEach(row => {
        tbody.appendChild(row);
        row.style.display = visibilityMap.get(row) || '';
    });
    
    updateRowNumbers();
    
    console.log('Table order reset to original');
}

function updateRowNumbers() {
    const tbody = document.querySelector('table tbody');
    if (!tbody) return;
    
    const allRows = tbody.querySelectorAll('tr.perkara-row');
    let visibleNo = 1;
    
    allRows.forEach((row) => {
        const noCell = row.cells[0];
        if (!noCell) return;
        
        if (row.style.display !== 'none') {
            noCell.textContent = visibleNo++;
        }
    });
}

// ================================================
// PAGINATION
// ================================================
function initializePagination() {
    const table = document.querySelector('table');
    if (!table) {
        console.log('Table not found for pagination');
        return;
    }
    
    const tableWrapper = table.closest('.table-wrapper');
    if (!tableWrapper) {
        console.log('Table wrapper not found');
        return;
    }
    
    // Cek apakah pagination sudah ada
    if (document.getElementById('paginationTop')) {
        console.log('Pagination already exists');
        applyFilters();
        return;
    }
    
    // Pagination Controls - Top
    const paginationTopHTML = `
        <div class="pagination-top" id="paginationTop" style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
            border: 1px solid #dee2e6;
            border-bottom: none;
            margin-top: 20px;
        ">
            <div class="pagination-controls" style="display: flex; align-items: center; gap: 10px;">
                <label for="rowsPerPageSelect" style="font-weight: 500; color: #495057; font-size: 14px;">
                    Baris per halaman:
                </label>
                <select id="rowsPerPageSelect" onchange="changeRowsPerPage()" style="
                    padding: 6px 12px;
                    border: 1px solid #ced4da;
                    border-radius: 4px;
                    background: white;
                    cursor: pointer;
                    font-size: 14px;
                    outline: none;
                ">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="pagination-info" id="paginationInfoTop" style="
                font-size: 14px;
                color: #6c757d;
                font-weight: 500;
            ">Menampilkan 1-10 dari 0 perkara</div>
        </div>
    `;
    
    tableWrapper.insertAdjacentHTML('beforebegin', paginationTopHTML);
    
    // Pagination Buttons - Bottom
    const paginationBottomHTML = `
        <div class="pagination-bottom" id="paginationBottom" style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px;
            border: 1px solid #dee2e6;
            border-top: none;
        ">
            <div class="pagination-info" id="paginationInfo" style="
                font-size: 14px;
                color: #6c757d;
                font-weight: 500;
            ">Menampilkan 1-10 dari 0 perkara</div>
            <div class="pagination-buttons" id="paginationButtons" style="
                display: flex;
                gap: 5px;
                align-items: center;
            "></div>
        </div>
    `;
    
    tableWrapper.insertAdjacentHTML('afterend', paginationBottomHTML);
    
    console.log('Pagination initialized successfully');
    
    // Apply filters pertama kali
    applyFilters();
}

function paginateRows(visibleRows) {
    // Hide semua rows dulu
    visibleRows.forEach(row => row.style.display = 'none');
    
    const totalPages = Math.ceil(visibleRows.length / rowsPerPage);
    
    // Validasi currentPage
    if (visibleRows.length === 0) {
        currentPage = 1;
    } else if (currentPage > totalPages) {
        currentPage = totalPages;
    } else if (currentPage < 1) {
        currentPage = 1;
    }
    
    // Calculate range
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, visibleRows.length);
    
    // Show rows untuk halaman ini
    for (let i = startIndex; i < endIndex; i++) {
        if (visibleRows[i]) visibleRows[i].style.display = '';
    }
    
    // Update UI
    updatePaginationUI(visibleRows.length, totalPages, startIndex, endIndex);
}

function updatePaginationUI(totalRows, totalPages, startIndex, endIndex) {
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationInfoTop = document.getElementById('paginationInfoTop');
    const paginationButtons = document.getElementById('paginationButtons');
    
    // Update info text
    const infoText = totalRows === 0 
        ? 'Tidak ada data' 
        : `Menampilkan ${startIndex + 1}-${endIndex} dari ${totalRows} perkara`;
    
    if (paginationInfo) paginationInfo.textContent = infoText;
    if (paginationInfoTop) paginationInfoTop.textContent = infoText;
    
    if (!paginationButtons) return;
    
    paginationButtons.innerHTML = '';
    
    // Prev Button
    const prevButton = createPaginationButton(
        '<i class="fas fa-chevron-left"></i> Prev',
        currentPage === 1 || totalRows === 0,
        () => changePage(currentPage - 1)
    );
    paginationButtons.appendChild(prevButton);
    
    if (totalPages > 0) {
        const maxPagesToShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
        
        if (endPage - startPage < maxPagesToShow - 1) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }
        
        // First page + dots
        if (startPage > 1) {
            addPageButton(1, paginationButtons);
            if (startPage > 2) {
                const dots = document.createElement('span');
                dots.textContent = '...';
                dots.style.cssText = 'padding: 8px 12px; color: #6c757d; font-weight: 500;';
                paginationButtons.appendChild(dots);
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            addPageButton(i, paginationButtons);
        }
        
        // Dots + last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const dots = document.createElement('span');
                dots.textContent = '...';
                dots.style.cssText = 'padding: 8px 12px; color: #6c757d; font-weight: 500;';
                paginationButtons.appendChild(dots);
            }
            addPageButton(totalPages, paginationButtons);
        }
    }
    
    // Next Button
    const nextButton = createPaginationButton(
        'Next <i class="fas fa-chevron-right"></i>',
        currentPage >= totalPages || totalRows === 0,
        () => changePage(currentPage + 1)
    );
    paginationButtons.appendChild(nextButton);
}

function createPaginationButton(html, disabled, onClick) {
    const button = document.createElement('button');
    button.className = 'pagination-btn';
    button.innerHTML = html;
    button.disabled = disabled;
    if (!disabled) button.onclick = onClick;
    
    button.style.cssText = `
        padding: 8px 16px;
        border: 1px solid #dee2e6;
        background: ${disabled ? '#e9ecef' : 'white'};
        color: ${disabled ? '#6c757d' : '#495057'};
        border-radius: 4px;
        cursor: ${disabled ? 'not-allowed' : 'pointer'};
        font-size: 14px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    `;
    
    if (!disabled) {
        button.addEventListener('mouseenter', function() {
            this.style.background = '#e9ecef';
            this.style.borderColor = '#adb5bd';
        });
        button.addEventListener('mouseleave', function() {
            this.style.background = 'white';
            this.style.borderColor = '#dee2e6';
        });
    }
    
    return button;
}

function addPageButton(pageNum, container) {
    const button = document.createElement('button');
    button.className = 'pagination-btn' + (pageNum === currentPage ? ' active' : '');
    button.textContent = pageNum;
    button.onclick = () => changePage(pageNum);
    
    const isActive = pageNum === currentPage;
    
    button.style.cssText = `
        padding: 8px 12px;
        border: 1px solid ${isActive ? '#007bff' : '#dee2e6'};
        background: ${isActive ? '#007bff' : 'white'};
        color: ${isActive ? 'white' : '#495057'};
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        min-width: 40px;
        transition: all 0.2s;
        font-weight: ${isActive ? '600' : '400'};
    `;
    
    if (!isActive) {
        button.addEventListener('mouseenter', function() {
            this.style.background = '#e9ecef';
            this.style.borderColor = '#adb5bd';
        });
        button.addEventListener('mouseleave', function() {
            this.style.background = 'white';
            this.style.borderColor = '#dee2e6';
        });
    }
    
    container.appendChild(button);
}

function changePage(newPage) {
    currentPage = newPage;
    applyFilters();
    
    // Smooth scroll ke tabel
    const table = document.querySelector('table');
    if (table) {
        const tablePosition = table.getBoundingClientRect().top + window.pageYOffset - 100;
        window.scrollTo({ top: tablePosition, behavior: 'smooth' });
    }
}

function changeRowsPerPage() {
    const select = document.getElementById('rowsPerPageSelect');
    if (select) {
        rowsPerPage = parseInt(select.value);
        currentPage = 1; // Reset ke halaman 1
        applyFilters();
    }
}

// ================================================
// HEADER FILTERS - INDEPENDENT COLUMN FILTERING
// ================================================
function setupHeaderFilters() {
    const filters = document.querySelectorAll('.filter-input');
    
    // Store active filters
    const activeFilters = {};
    
    filters.forEach(input => {
        input.addEventListener('input', function() {
            const columnIndex = parseInt(this.dataset.column);
            const value = this.value.toLowerCase().trim();
            
            // Update active filters
            if (value === '') {
                delete activeFilters[columnIndex];
            } else {
                activeFilters[columnIndex] = {
                    value: value,
                    type: this.type
                };
            }
            
            // Apply all active filters
            applyHeaderFilters(activeFilters);
        });
    });
}

function applyHeaderFilters(activeFilters) {
    const rows = document.querySelectorAll('tbody tr.perkara-row');
    
    rows.forEach(row => {
        let shouldShow = true;
        
        // Check each active filter
        for (const [columnIndex, filter] of Object.entries(activeFilters)) {
            const cell = row.cells[columnIndex];
            if (!cell) {
                shouldShow = false;
                break;
            }
            
            let cellValue = cell.textContent.toLowerCase().trim();
            
            // Handle date format conversion (dd/mm/yyyy to yyyy-mm-dd)
            if (filter.type === 'date' && cellValue.includes('/')) {
                const parts = cellValue.split('/');
                if (parts.length === 3) {
                    cellValue = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                }
            }
            
            // Check if cell matches filter
            if (!cellValue.includes(filter.value)) {
                shouldShow = false;
                break;
            }
        }
        
        // Apply visibility
        if (Object.keys(activeFilters).length > 0) {
            row.style.display = shouldShow ? '' : 'none';
        } else {
            row.style.display = '';
        }
    });
    
    // Update row numbers
    updateRowNumbers();
    
    // Update pagination
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    totalRows = visibleRows.length;
    updateSearchResultCount(visibleRows.length);
}

function addClearButtonsToFilters() {
    const filters = document.querySelectorAll('.filter-input');
    
    filters.forEach(input => {
        // Skip jika clear button sudah ada
        if (input.nextElementSibling?.classList.contains('filter-clear')) return;
        
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'filter-clear';
        clearBtn.innerHTML = '×';
        clearBtn.style.cssText = `
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 18px;
            padding: 0 5px;
            display: none;
            line-height: 1;
        `;
        
        // Show/hide clear button
        input.addEventListener('input', function() {
            clearBtn.style.display = this.value ? 'block' : 'none';
        });
        
        // Clear filter on click
        clearBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            input.value = '';
            input.dispatchEvent(new Event('input'));
            this.style.display = 'none';
        });
        
        // Insert clear button
        input.parentElement.style.position = 'relative';
        input.parentElement.appendChild(clearBtn);
    });
}

// ================================================
// UTILITY FUNCTIONS
// ================================================
function initTableInteractions() {
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'all 0.2s ease';
        });
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

function setupStickyHeader() {
    const tableWrapper = document.querySelector('.table-wrapper');
    const thead = document.querySelector('thead');

    if (!tableWrapper || !thead) return;

    tableWrapper.addEventListener('scroll', function() {
        if (this.scrollTop > 0) {
            thead.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
        } else {
            thead.style.boxShadow = 'none';
        }
    });
}

function animateBadges() {
    const badges = document.querySelectorAll('.badge');
    badges.forEach((badge, index) => {
        setTimeout(() => {
            badge.style.opacity = '0';
            badge.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                badge.style.transition = 'all 0.3s ease';
                badge.style.opacity = '1';
                badge.style.transform = 'translateY(0)';
            }, 50);
        }, index * 10);
    });
}

function printTable() {
    window.print();
}

function validateDateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    if (formId === 'rangeDateForm') {
        const tanggalMulai = document.getElementById('tanggal_mulai')?.value;
        const tanggalAkhir = document.getElementById('tanggal_akhir')?.value;

        if (!tanggalMulai || !tanggalAkhir) {
            alert('Mohon pilih tanggal mulai dan tanggal akhir!');
            return false;
        }

        if (new Date(tanggalMulai) > new Date(tanggalAkhir)) {
            alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir!');
            return false;
        }
    } else if (formId === 'singleDateForm') {
        const tanggal = document.querySelector('input[name="tanggal"]')?.value;
        if (!tanggal) {
            alert('Mohon pilih tanggal!');
            return false;
        }
    }

    return true;
}

function exportData() {
    console.log('Export data functionality - to be implemented');
    alert('Fitur export sedang dalam pengembangan');
}

// ================================================
// DOM READY 
// ================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('\n=== Initializing js_rekap.js ===\n');

    // 1. Initialize charts & progress bars
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
    animateProgressBars();
    
    // 2. Setup basic interactions
    initTableInteractions();
    setupStickyHeader();
    restoreFilterFromURL();

    // 3. Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateDateForm(this.id)) {
                e.preventDefault();
            }
        });
    });

    // 4. Badge animation
    setTimeout(animateBadges, 100);

    // 5. Initialize dropdown
    const totalPerkara = document.querySelectorAll('.perkara-row').length;
    const searchInput = document.getElementById('searchInput');
    if (searchInput && searchInput.value === '') {
        searchInput.value = `Semua Perkara (${totalPerkara})`;
    }

    console.log('Total perkara rows:', totalPerkara);

    // 6. Save original indexes BEFORE any manipulation
    saveOriginalIndexes();
    
    // 7. Initialize PAGINATION
    initializePagination();

    // 8. Setup SORTING after pagination
    if (!sortingInitialized) {
        const sortSetup = setupTableSorting();
        console.log('Sorting setup result:', sortSetup);
    }

    // 9. Setup header filters
    setupHeaderFilters();
    addClearButtonsToFilters();

    // 10. Setup cards & filters (LAST)
    setupCardClickHandlersWithFilter();
    preserveFilterInLinks();
    addBackToDashboardButton();

    console.log('=== Initialization complete ===\n');
});

// ================================================
// EVENT LISTENERS
// ================================================

// Dropdown control - close when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('dropdownList');
    const container = e.target.closest('.search-dropdown-container');

    if (!container && dropdown) {
        hideDropdown();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + P for print
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        printTable();
    }
    
    // Ctrl/Cmd + E for export
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        exportData();
    }
});

// ================================================
// EXPOSE FUNCTIONS TO WINDOW
// ================================================
window.setupTableSorting = setupTableSorting;
window.updateSortIcons = updateSortIcons;
window.sortTableByColumn = sortTableByColumn;
window.resetTableOrder = resetTableOrder;
window.saveOriginalIndexes = saveOriginalIndexes;
window.updateRowNumbers = updateRowNumbers;
window.switchMode = switchMode;
window.selectFilter = selectFilter;
window.handleSearchInput = handleSearchInput;
window.showDropdown = showDropdown;
window.hideDropdown = hideDropdown;
window.hideDropdownDelay = hideDropdownDelay;
window.toggleDropdown = toggleDropdown;
window.performDetailSearch = performDetailSearch;
window.clearDetailSearch = clearDetailSearch;
window.clearFilterTag = clearFilterTag;
window.clearAllFilters = clearAllFilters;
window.exportData = exportData;
window.printTable = printTable;
window.validateDateForm = validateDateForm;
window.changePage = changePage;
window.changeRowsPerPage = changeRowsPerPage;
window.preserveFilterInLinks = preserveFilterInLinks;
window.restoreFilterFromURL = restoreFilterFromURL;
window.addBackToDashboardButton = addBackToDashboardButton;
window.setupCardClickHandlersWithFilter = setupCardClickHandlersWithFilter;

console.log('✓ All functions exposed to window');
console.log('✓ js_rekap.js loaded successfully\n');