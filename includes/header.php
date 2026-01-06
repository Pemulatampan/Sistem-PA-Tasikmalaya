<?php
// includes/header.php
if (!isset($page_title)) {
    $page_title = "Sistem Pengadilan";
}

// Deteksi apakah file ada di subfolder atau root
$is_subfolder = (strpos($_SERVER['PHP_SELF'], '/pihak/') !== false || 
                 strpos($_SERVER['PHP_SELF'], '/penilaian_kinerja/') !== false);
$base_path = $is_subfolder ? '../' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Font Awesome -->
    <link href="<?php echo $base_path; ?>assets/css/all.min.css" rel="stylesheet">
    
    <!-- CSS Global -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/global.css?v=<?php echo time(); ?>">
    
    <!-- Page Specific CSS -->
    <?php if (isset($additional_css)) echo $additional_css; ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <div class="logo-icon">
                    <span style="font-weight: bold; font-size: 16px;">SPA</span>
                </div>
                <div class="logo-text">Sistem Pengadilan Agama</div>
            </div>
            <button class="toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <div class="sidebar-menu">
            <div class="menu-title">Menu Utama</div>
            <a href="<?php echo $base_path; ?>index.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && !$is_subfolder) ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span class="menu-text">Rekap Perkara</span>
            </a>
            
            <div class="menu-title">CRUD Perkara</div>
            <a href="<?php echo $base_path; ?>pihak/index.php" class="menu-item <?php echo (strpos($_SERVER['PHP_SELF'], '/pihak/') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span class="menu-text">Tabel Perkara</span>
            </a>

            <div class="menu-title">Modul Penilaian</div>
            <a href="<?php echo $base_path; ?>penilaian_kinerja/index.php" 
               class="menu-item <?php echo (strpos($_SERVER['PHP_SELF'], '/penilaian_kinerja/') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-check"></i>
                <span class="menu-text">Penilaian Kinerja</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1 class="header-title"><?php echo $page_title; ?></h1>
            </div>
            <div class="header-right">
                <div class="header-date" id="current-date">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date('d F Y'); ?>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <?php
            // Tampilkan flash message jika ada
            if (function_exists('getFlashMessage')) {
                $flash = getFlashMessage();
                if ($flash) {
                    echo '<div class="alert alert-' . $flash['type'] . '">';
                    echo '<i class="fas fa-' . ($flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle') . '"></i>';
                    echo ' ' . $flash['message'];
                    echo '</div>';
                }
            }
            ?>