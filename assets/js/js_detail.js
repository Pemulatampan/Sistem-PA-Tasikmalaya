/**
 * detail.js - JavaScript untuk halaman Detail Perkara
 * Fungsi untuk switching antara mode Harian dan Periode
 */

function switchMode(mode) {
    const singleForm = document.getElementById('singleDateForm');
    const rangeForm = document.getElementById('rangeDateForm');
    const quickButtons = document.getElementById('quickButtons');
    const modeTabs = document.querySelectorAll('.mode-tab');
    
    // Remove active class dari semua tab
    modeTabs.forEach(tab => tab.classList.remove('active'));
    
    if (mode === 'single') {
        // Mode Harian (Single Date)
        singleForm.classList.add('active');
        rangeForm.classList.remove('active');
        quickButtons.style.display = 'none';
        modeTabs[0].classList.add('active');
    } else {
        // Mode Periode (Date Range)
        singleForm.classList.remove('active');
        rangeForm.classList.add('active');
        quickButtons.style.display = 'flex';
        modeTabs[1].classList.add('active');
    }
}