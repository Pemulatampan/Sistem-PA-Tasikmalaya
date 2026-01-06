<?php
// config/config.php

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'sipp';
$dbname_badilag = 'aps_badilag';


// Untuk modul perkara
$connection = mysql_connect($host, $username, $password);
if (!$connection) {
    die('Koneksi database gagal: ' . mysql_error());
}

if (!mysql_select_db($database, $connection)) {
    die('Gagal memilih database: ' . mysql_error());
}

mysql_query("SET NAMES utf8", $connection);

// Untuk modul penilaian_kinerja
try {
    // Koneksi Database SIPP (PDO)
    $pdo_sipp = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo_sipp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_sipp->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Koneksi Database APS Badilag (PDO)
    $pdo_badilag = new PDO("mysql:host=$host;dbname=$dbname_badilag;charset=utf8", $username, $password);
    $pdo_badilag->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_badilag->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Koneksi PDO gagal: " . $e->getMessage());
}

// Set timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

// Include functions
require_once __DIR__ . '/functions.php';

// Start session if not already started
if (!isset($_SESSION)) {
    session_start();
}

// Define constants
define('BASE_URL', 'http://localhost/Sistem%20Pengadilan%20Agama/');
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

// Error reporting (disable in production)
error_reporting(0);
ini_set('display_errors', 0);
?>