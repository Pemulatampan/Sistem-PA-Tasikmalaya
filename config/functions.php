<?php
// config/functions.php
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Escape string untuk SQL
function escape_string($data) {
    global $connection;
    return mysql_real_escape_string($data, $connection);
}

// Format tanggal ke Indonesia
function formatTanggalIndonesia($tanggal) {
    $bulan = array(
        1 => 'Januari', 
        2 => 'Februari', 
        3 => 'Maret', 
        4 => 'April', 
        5 => 'Mei', 
        6 => 'Juni',
        7 => 'Juli', 
        8 => 'Agustus', 
        9 => 'September', 
        10 => 'Oktober', 
        11 => 'November', 
        12 => 'Desember'
    );

    // Gunakan method lama untuk kompatibilitas
    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return $tanggal;
    }
    
    $hari = date('d', $timestamp);
    $bulan_num = (int) date('n', $timestamp);
    $tahun = date('Y', $timestamp);

    return $hari . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
}

// Format tanggal lengkap dengan nama hari
function formatTanggalIndonesiaLengkap($tanggal) {
    $bulan = array(
        1 => 'Januari', 
        2 => 'Februari', 
        3 => 'Maret', 
        4 => 'April', 
        5 => 'Mei', 
        6 => 'Juni',
        7 => 'Juli', 
        8 => 'Agustus', 
        9 => 'September', 
        10 => 'Oktober', 
        11 => 'November', 
        12 => 'Desember'
    );
    
    $hari = array(
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    );

    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return $tanggal;
    }
    
    $day_name = date('l', $timestamp);
    $day = date('d', $timestamp);
    $month_num = (int) date('n', $timestamp);
    $year = date('Y', $timestamp);
    
    return $hari[$day_name] . ', ' . $day . ' ' . $bulan[$month_num] . ' ' . $year;
}

// Validasi tanggal
function isValidDate($date, $format = 'Y-m-d') {
    // Untuk PHP versi lama - cek apakah format tanggal valid
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $timestamp = strtotime($date);
        return $timestamp !== false && $timestamp > 0;
    }
    return false;
}

// Generate nomor perkara
function generateNomorPerkara($jenis_perkara, $tahun = null) {
    global $connection;
    
    if ($tahun === null) {
        $tahun = date('Y');
    }
    
    // Get last number for this year
    $query = "SELECT nomor_perkara FROM perkara 
              WHERE YEAR(tanggal_pendaftaran) = " . (int) $tahun . " 
              ORDER BY perkara_id DESC LIMIT 1";
    $result = mysql_query($query, $connection);
    
    $urutan = 1;
    if ($result && mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
        $nomor_terakhir = $row['nomor_perkara'];
        // Extract number from format: 001/Pdt.G/2024/PA.Xxx
        if (preg_match('/^(\d+)\//', $nomor_terakhir, $matches)) {
            $urutan = (int) $matches[1] + 1;
        }
    }
    
    $prefix = ($jenis_perkara == 'gugatan') ? 'Pdt.G' : 'Pdt.P';
    $nomor = sprintf("%03d/%s/%s/PA.Xxx", $urutan, $prefix, $tahun);
    
    return $nomor;
}

// Redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Flash message functions
function setFlashMessage($type, $message) {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['flash_message'] = array('type' => $type, 'message' => $message);
}

function getFlashMessage() {
    if (!isset($_SESSION)) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Format rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Format number dengan pemisah ribuan
function formatNumber($number) {
    return number_format($number, 0, ',', '.');
}

// Sanitize filename
function sanitizeFilename($filename) {
    // Remove special characters and spaces
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Remove multiple underscores
    $filename = preg_replace('/_+/', '_', $filename);
    return trim($filename, '_');
}

// Get safe post data
function getPost($key, $default = '') {
    return isset($_POST[$key]) ? clean_input($_POST[$key]) : $default;
}

// get safe get data
function getGet($key, $default = '') {
    return isset($_GET[$key]) ? clean_input($_GET[$key]) : $default;
}

// Check if method is post
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
?>