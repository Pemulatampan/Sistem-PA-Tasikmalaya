<?php
require_once 'config/config.php';

$page_title = "Rekap Perkara";

// Cek apa menggunakan single date atau date range
if (isset($_GET['tanggal']) && !isset($_GET['tanggal_mulai'])) {
    // Mode single date (seperti aslinya)
    $tanggal_pilih = $_GET['tanggal'];
    $tanggal_mulai = $tanggal_pilih;
    $tanggal_akhir = $tanggal_pilih;
    $mode = 'single';
} else {
    // Mode date range
    $tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-d');
    $tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
    $tanggal_pilih = $tanggal_mulai; // untuk backward compatibility
    $mode = 'range';
}

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_mulai)) {
    $tanggal_mulai = date('Y-m-d');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_akhir)) {
    $tanggal_akhir = date('Y-m-d');
}

// Pastikan tanggal mulai tidak lebih besar dari tanggal akhir
if (strtotime($tanggal_mulai) > strtotime($tanggal_akhir)) {
    $temp = $tanggal_mulai;
    $tanggal_mulai = $tanggal_akhir;
    $tanggal_akhir = $temp;
}

// Query untuk mendapatkan semua jenis perkara dari master table
$sql_jenis = "SELECT id, nama, kode FROM jenis_perkara ORDER BY nama";
$result_jenis = mysql_query($sql_jenis);
if (!$result_jenis) {
    die("Query jenis perkara gagal: " . mysql_error());
}

$jenis_perkara_list = array();
while ($row = mysql_fetch_assoc($result_jenis)) {
    $jenis_perkara_list[$row['id']] = array(
        'nama' => $row['nama'],
        'kode' => $row['kode']
    );
}

// ===========
// Query dasar
// ===========
$sql_rekap = "
    SELECT 
        p.perkara_id,
        p.nomor_perkara,
        p.tanggal_pendaftaran,
        p.jenis_perkara_id,
        p.jenis_perkara_nama,
        p.jenis_perkara_kode,
        (SELECT pe2.efiling_id 
         FROM perkara_efiling_id pe2 
         WHERE pe2.perkara_id = p.perkara_id 
         LIMIT 1) as efiling_id,
        CASE 
            WHEN (SELECT pe2.efiling_id 
                  FROM perkara_efiling_id pe2 
                  WHERE pe2.perkara_id = p.perkara_id 
                  LIMIT 1) IS NOT NULL 
                 AND (SELECT pe2.efiling_id 
                      FROM perkara_efiling_id pe2 
                      WHERE pe2.perkara_id = p.perkara_id 
                      LIMIT 1) != '' 
            THEN 'e-court'
            ELSE 'manual'
        END as cara_daftar,
        (SELECT ph2.hakim_nama 
         FROM perkara_hakim_pn ph2 
         WHERE ph2.perkara_id = p.perkara_id 
           AND ph2.urutan = '1' 
           AND ph2.aktif = 'Y' 
         LIMIT 1) as hakim_nama,
        (SELECT pp2.tanggal_putusan 
         FROM perkara_putusan pp2 
         WHERE pp2.perkara_id = p.perkara_id 
         LIMIT 1) as tanggal_putusan,
        (SELECT pp2.tanggal_minutasi 
         FROM perkara_putusan pp2 
         WHERE pp2.perkara_id = p.perkara_id 
         LIMIT 1) as tanggal_minutasi,
        CASE 
            WHEN (SELECT pp2.tanggal_minutasi 
                  FROM perkara_putusan pp2 
                  WHERE pp2.perkara_id = p.perkara_id 
                  LIMIT 1) IS NOT NULL 
                 AND (SELECT pp2.tanggal_minutasi 
                      FROM perkara_putusan pp2 
                      WHERE pp2.perkara_id = p.perkara_id 
                      LIMIT 1) != '' 
            THEN 'minutasi_selesai'
            WHEN (SELECT pp2.tanggal_putusan 
                  FROM perkara_putusan pp2 
                  WHERE pp2.perkara_id = p.perkara_id 
                  LIMIT 1) IS NOT NULL 
                 AND (SELECT pp2.tanggal_putusan 
                      FROM perkara_putusan pp2 
                      WHERE pp2.perkara_id = p.perkara_id 
                      LIMIT 1) != '' 
            THEN 'putus'
            ELSE 'dalam_proses'
        END as status_perkara
    FROM perkara p
    WHERE p.tanggal_pendaftaran BETWEEN '" . mysql_real_escape_string($tanggal_mulai) . "' 
          AND '" . mysql_real_escape_string($tanggal_akhir) . "'
    ORDER BY p.tanggal_pendaftaran DESC, p.nomor_perkara
";

$result = mysql_query($sql_rekap);
if (!$result) {
    die("Query gagal: " . mysql_error());
}

$data_rekap = array();
while ($row = mysql_fetch_assoc($result)) {
    $data_rekap[] = $row;
}

// Debug: tampilkan struktur data jika dalam mode development
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    echo "<pre style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; font-size: 12px;'>";
    echo "<strong>Debug Info - Sample Data:</strong>\n";
    if (!empty($data_rekap)) {
        echo "Jumlah data: " . count($data_rekap) . "\n";
        echo "Sample record pertama:\n";
        print_r($data_rekap[0]);
        echo "\nField yang tersedia: " . implode(", ", array_keys($data_rekap[0])) . "\n";
        
        // debug untuk cek duplikasi
        $perkara_ids = array();
        $duplikasi = array();
        foreach ($data_rekap as $row) {
            if (in_array($row['perkara_id'], $perkara_ids)) {
                $duplikasi[] = $row['perkara_id'];
            }
            $perkara_ids[] = $row['perkara_id'];
        }
        
        if (!empty($duplikasi)) {
            echo "\n<strong style='color: red;'>⚠️ WARNING: Ditemukan duplikasi perkara_id:</strong>\n";
            print_r(array_unique($duplikasi));
        } else {
            echo "\n<strong style='color: green;'>✓ Tidak ada duplikasi perkara_id</strong>\n";
        }
    } else {
        echo "Tidak ada data ditemukan\n";
        echo "Query yang dijalankan:\n" . $sql_rekap . "\n";
    }
    echo "</pre>";
}

// Inisialisasi counter berdasarkan jenis perkara
$counter_jenis = array();
foreach ($jenis_perkara_list as $id => $data) {
    $counter_jenis[$id] = 0;
}

// Buat array untuk jenis perkara yang ada di data (berdasarkan jenis_perkara_nama)
$jenis_aktual_nama = array();

$e_court = 0;
$manual = 0;
$putus = 0;
$minutasi = 0;
$dalam_proses = 0;

// tracking untuk debugging
$debug_counts = array(
    'total_records' => count($data_rekap),
    'unique_perkara_ids' => array(),
    'jenis_breakdown' => array(),
    'status_breakdown' => array()
);

// Hitung berdasarkan data dari database
foreach ($data_rekap as $row) {
    $jenis_id = $row['jenis_perkara_id'];
    $jenis_nama = $row['jenis_perkara_nama'];
    $cara = isset($row['cara_daftar']) ? strtolower($row['cara_daftar']) : '';
    $status = isset($row['status_perkara']) ? strtolower($row['status_perkara']) : '';
    
    // Track unique perkara_id
    $debug_counts['unique_perkara_ids'][$row['perkara_id']] = true;

    // Hitung jenis perkara berdasarkan jenis_perkara_id
    if (isset($counter_jenis[$jenis_id])) {
        $counter_jenis[$jenis_id]++;
    }
    
    // Kumpulkan jenis perkara berdasarkan nama yang benar-benar ada
    if (!isset($jenis_aktual_nama[$jenis_nama])) {
        $jenis_aktual_nama[$jenis_nama] = array(
            'count' => 0,
            'jenis_id' => $jenis_id,
            'kode' => $row['jenis_perkara_kode']
        );
    }
    $jenis_aktual_nama[$jenis_nama]['count']++;
    
    // Track breakdown
    if (!isset($debug_counts['jenis_breakdown'][$jenis_nama])) {
        $debug_counts['jenis_breakdown'][$jenis_nama] = 0;
    }
    $debug_counts['jenis_breakdown'][$jenis_nama]++;
    
    // Hitung cara daftar
    if ($cara == 'e-court') {
        $e_court++;
    } else {
        $manual++;
    }
    
    // Hitung status perkara
    if ($status == 'minutasi_selesai') {
        $minutasi++;
        $debug_counts['status_breakdown']['minutasi_selesai'] = isset($debug_counts['status_breakdown']['minutasi_selesai']) ? $debug_counts['status_breakdown']['minutasi_selesai'] + 1 : 1;
    } elseif ($status == 'putus') {
        $putus++;
        $debug_counts['status_breakdown']['putus'] = isset($debug_counts['status_breakdown']['putus']) ? $debug_counts['status_breakdown']['putus'] + 1 : 1;
    } elseif ($status == 'dalam_proses') {
        $dalam_proses++;
        $debug_counts['status_breakdown']['dalam_proses'] = isset($debug_counts['status_breakdown']['dalam_proses']) ? $debug_counts['status_breakdown']['dalam_proses'] + 1 : 1;
    }
}

// ====================
// Mode Verifikasi Data
// ====================
if (isset($_GET['verify']) && $_GET['verify'] == '1') {
    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 8px;'>";
    echo "<h3 style='margin-top: 0; color: #856404;'><i class='fas fa-exclamation-triangle'></i> Verifikasi Data</h3>";
    
    echo "<table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>";
    echo "<tr style='background: #f8f9fa;'><th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>Metrik</th><th style='padding: 10px; border: 1px solid #dee2e6;'>Jumlah</th></tr>";
    echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'>Total Records dari Query</td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . $debug_counts['total_records'] . "</td></tr>";
    echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'>Unique Perkara IDs</td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . count($debug_counts['unique_perkara_ids']) . "</td></tr>";
    echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'>Total dari Counter</td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . array_sum($counter_jenis) . "</td></tr>";
    echo "<tr style='background: #d4edda;'><td style='padding: 10px; border: 1px solid #dee2e6;'><strong>E-Court</strong></td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . $e_court . "</td></tr>";
    echo "<tr style='background: #d4edda;'><td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Manual</strong></td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . $manual . "</td></tr>";
    echo "<tr style='background: #cfe2ff;'><td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Putus</strong></td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . $putus . "</td></tr>";
    echo "<tr style='background: #cfe2ff;'><td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Minutasi</strong></td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . $minutasi . "</td></tr>";
    echo "<tr style='background: #cfe2ff;'><td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Dalam Proses</strong></td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . $dalam_proses . "</td></tr>";
    echo "</table>";
    
    echo "<h4 style='margin-top: 20px; color: #856404;'>Breakdown per Jenis Perkara:</h4>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'><th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>Jenis Perkara</th><th style='padding: 10px; border: 1px solid #dee2e6;'>Jumlah</th></tr>";
    foreach ($debug_counts['jenis_breakdown'] as $jenis => $count) {
        echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'>" . htmlspecialchars($jenis) . "</td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>" . $count . "</td></tr>";
    }
    echo "</table>";
    
    // Cek konsistensi
    $is_consistent = ($debug_counts['total_records'] == count($debug_counts['unique_perkara_ids'])) && 
                     ($debug_counts['total_records'] == array_sum($counter_jenis));
    
    if ($is_consistent) {
        echo "<div style='background: #d1e7dd; color: #0f5132; padding: 15px; margin-top: 20px; border-radius: 5px; border-left: 4px solid #198754;'>";
        echo "<strong>✓ Data Konsisten:</strong> Tidak ditemukan duplikasi atau inkonsistensi data";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #842029; padding: 15px; margin-top: 20px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
        echo "<strong>✗ Warning:</strong> Ditemukan inkonsistensi data! Periksa query atau data di database";
        echo "</div>";
    }
    
    echo "<p style='margin-top: 15px; color: #856404;'><small><i class='fas fa-info-circle'></i> Tambahkan <code>?verify=1</code> di URL untuk melihat verifikasi ini</small></p>";
    echo "</div>";
}

// Urutkan jenis perkara aktual berdasarkan nama
ksort($jenis_aktual_nama);

// Hitung total per kategori menggunakan unique count
$total_masuk = count($debug_counts['unique_perkara_ids']); 
$total_cara_daftar = $e_court + $manual;
$total_putus_minutasi = $putus + $minutasi;

// Hitung jumlah hari
$date1 = strtotime($tanggal_mulai);
$date2 = strtotime($tanggal_akhir);
$jumlah_hari = floor(($date2 - $date1) / (60 * 60 * 24)) + 1;

// mapping jenis perkara ke kategori detail.php
$kategori_mapping = array(
    // cerai
    'Cerai Gugat' => 'cerai_gugat',
    'Gugatan Cerai' => 'cerai_gugat',
    'Perceraian' => 'cerai_gugat',
    
    'Cerai Talak' => 'cerai_talak',
    'Talak' => 'cerai_talak',
    
    // dispensasi
    'Dispensasi Kawin' => 'dispensasi_kawin',
    'Dispensasi Nikah' => 'dispensasi_kawin',
    'Dispensasi' => 'dispensasi_kawin',
    'Izin Kawin' => 'dispensasi_kawin',
    
    // asal usul anak
    'Asal Usul Anak' => 'asal_usul_anak',
    'Penetapan Asal Usul' => 'asal_usul_anak',
    'Asal-Usul' => 'asal_usul_anak',
    
    // ahli waris (P3HP) - Harus di atas Perwalian
    'P3HP' => 'p3hp',
    'P3PH' => 'p3hp',
    'Penetapan Ahli Waris' => 'p3hp',
    'Ahli Waris' => 'p3hp',
    
    // pengesahan nikah
    'Pengesahan Perkawinan' => 'pengesahan_nikah',
    'Istbat Nikah' => 'pengesahan_nikah',
    'Pengesahan Nikah' => 'pengesahan_nikah',
    'Istbat' => 'pengesahan_nikah',
    'Isbat Nikah' => 'pengesahan_nikah',
    'Isbat' => 'pengesahan_nikah',
    
    // perwakilan - Setelah P3HP
    'Wali Adhol' => 'perwalian',
    'Perwalian' => 'perwalian',
    'Penetapan Wali' => 'perwalian',
    'Penunjukkan orang lain sebagai Wali' => 'perwalian',
    'Pencabutan Kekuasaan Wali' => 'perwalian',
    'Ganti Rugi Terhadap Wali' => 'perwalian',
    
    // harta bersama
    'Harta Bersama' => 'harta_bersama',
    'Pembagian Harta Bersama' => 'harta_bersama',
    
    // izin poligami
    'Izin Poligami' => 'izin_poligami',
    'Poligami' => 'izin_poligami',
    
    // kewarisan
    'Kewarisan' => 'kewarisan',
    'Sengketa Waris' => 'kewarisan',
    'Warisan' => 'kewarisan',
    
    // wakaf
    'Wakaf' => 'wakaf',
    'Sengketa Wakaf' => 'wakaf',
    'Perwakafan' => 'wakaf',
    
    // penguasaan anak
    'Penguasaan Anak' => 'penguasaan_anak',
    'Hadhanah' => 'penguasaan_anak',
    'Hak Asuh Anak' => 'penguasaan_anak',
    
    // hibah
    'Hibah' => 'hibah',
    'Sengketa Hibah' => 'hibah',
    'Pembatalan Hibah' => 'hibah',
    
    // pembatalan perkawinan
    'Pembatalan Perkawinan' => 'pembatalan_perkawinan',
    'Pembatalan Nikah' => 'pembatalan_perkawinan',
    'Penolakan Perkawinan' => 'pembatalan_perkawinan',
    'Pencegahan Perkawinan' => 'pembatalan_perkawinan',
    
    // ekonomi syariah
    'Ekonomi Syariah' => 'ekonomi_syariah',
    'Sengketa Ekonomi Syariah' => 'ekonomi_syariah',
    'Ekonomi Syari\'ah' => 'ekonomi_syariah',
    
    // lain-lain
    'Lain-Lain' => 'lain_lain',
    'Lainnya' => 'lain_lain',
    'Lain-lain' => 'lain_lain',
    'LAIN-LAIN' => 'lain_lain',
    'LAIN-LAIN *' => 'lain_lain',
);

// Fungsi untuk menentukan kategori detail berdasarkan jenis perkara
function tentukanKategoriDetail($jenis_nama, $kategori_mapping) {
    $jenis_nama_lower = strtolower(trim($jenis_nama));
    
    // PRIORITY 1: Cek exact match dulu (case insensitive)
    foreach ($kategori_mapping as $keyword => $kategori) {
        if (strtolower($keyword) === $jenis_nama_lower) {
            return $kategori;
        }
    }

    // PRIORITY 2: Cek partial match dengan urutan spesifik ke umum
    // Khusus untuk lain-lain - cek paling awal
    if (stripos($jenis_nama, 'lain') !== false) {
        return 'lain_lain';
    }
    
    // Khusus untuk ahli waris - cek dulu sebelum perwalian
    if (stripos($jenis_nama, 'P3HP') !== false || 
        stripos($jenis_nama, 'P3PH') !== false ||
        stripos($jenis_nama, 'ahli waris') !== false) {
        return 'p3hp';
    }
    
    // Cek perwalian setelah memastikan bukan ahli waris
    if (stripos($jenis_nama, 'wali') !== false || 
        stripos($jenis_nama, 'perwalian') !== false) {
        // Pastikan bukan ahli waris
        if (stripos($jenis_nama, 'ahli waris') === false && 
            stripos($jenis_nama, 'p3hp') === false &&
            stripos($jenis_nama, 'p3ph') === false) {
            return 'perwalian';
        }
    }
    
    // Cek mapping lainnya
    foreach ($kategori_mapping as $keyword => $kategori) {
        if (stripos($jenis_nama, $keyword) !== false) {
            return $kategori;
        }
    }
    
    // Deteksi gugatan vs permohonan
    if (stripos($jenis_nama, 'gugat') !== false || 
        stripos($jenis_nama, 'sengketa') !== false) {
        return 'gugatan';
    }
    
    // Default: permohonan
    return 'permohonan';
}

include 'includes/header.php';
?>

<!-- Link ke CSS Internal -->
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/chart-custom.css">

<!-- Date Picker Section -->
<div class="date-picker-section">
    <div class="date-picker-card">
        <div class="date-picker-header">
            <h3><i class="fas fa-calendar-alt"></i> Pilih Periode Rekap</h3>
        </div>
        
        <!-- Tab untuk memilih mode -->
        <div class="date-mode-tabs">
            <button type="button" class="mode-tab <?php echo $mode == 'single' ? 'active' : ''; ?>" onclick="switchMode('single')">
                <i class="fas fa-calendar-day"></i> Harian
            </button>
            <button type="button" class="mode-tab <?php echo $mode == 'range' ? 'active' : ''; ?>" onclick="switchMode('range')">
                <i class="fas fa-calendar-week"></i> Periode
            </button>
        </div>
        
        <!-- Form Single Date -->
        <form method="GET" action="" class="date-picker-form" id="singleDateForm" style="display: <?php echo $mode == 'single' ? 'block' : 'none'; ?>;">
            <div class="date-input-group">
                <input type="date" name="tanggal" value="<?php echo $tanggal_pilih; ?>" class="date-input">
                <button type="submit" class="date-submit-btn">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
            </div>
        </form>
        
        <!-- Form Date Range -->
        <form method="GET" action="" class="date-picker-form" id="rangeDateForm" style="display: <?php echo $mode == 'range' ? 'block' : 'none'; ?>;">
            <div class="date-input-group">
                <div class="date-input-wrapper">
                    <label for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="<?php echo $tanggal_mulai; ?>" class="date-input">
                </div>
                <div class="date-input-wrapper">
                    <label for="tanggal_akhir">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="<?php echo $tanggal_akhir; ?>" class="date-input">
                </div>
                <div class="date-button-wrapper">
                    <button type="submit" class="date-submit-btn">
                        <i class="fas fa-search"></i> Tampilkan
                    </button>
                </div>
            </div>
        </form>
        
        <div class="quick-date-buttons" style="display: <?php echo $mode == 'range' ? 'flex' : 'none'; ?>;">
            <a href="?tanggal_mulai=<?php echo date('Y-m-d'); ?>&tanggal_akhir=<?php echo date('Y-m-d'); ?>" 
               class="quick-btn <?php echo ($mode == 'range' && $tanggal_mulai == date('Y-m-d') && $tanggal_akhir == date('Y-m-d')) ? 'active' : ''; ?>">
               Hari Ini
            </a>
            <a href="?tanggal_mulai=<?php echo date('Y-m-d', strtotime('-6 days')); ?>&tanggal_akhir=<?php echo date('Y-m-d'); ?>" 
               class="quick-btn <?php echo ($mode == 'range' && $tanggal_mulai == date('Y-m-d', strtotime('-6 days')) && $tanggal_akhir == date('Y-m-d')) ? 'active' : ''; ?>">
               7 Hari Terakhir
            </a>
            <a href="?tanggal_mulai=<?php echo date('Y-m-d', strtotime('-29 days')); ?>&tanggal_akhir=<?php echo date('Y-m-d'); ?>" 
               class="quick-btn <?php echo ($mode == 'range' && $tanggal_mulai == date('Y-m-d', strtotime('-29 days')) && $tanggal_akhir == date('Y-m-d')) ? 'active' : ''; ?>">
               30 Hari Terakhir
            </a>
            <a href="?tanggal_mulai=<?php echo date('Y-m-01'); ?>&tanggal_akhir=<?php echo date('Y-m-d'); ?>" 
               class="quick-btn <?php echo ($mode == 'range' && $tanggal_mulai == date('Y-m-01') && $tanggal_akhir == date('Y-m-d')) ? 'active' : ''; ?>">
               Bulan Ini
            </a>
        </div>
        
        <div class="selected-date">
            <?php if ($mode == 'single'): ?>
                <span>Menampilkan data untuk: <strong><?php echo formatTanggalIndonesia($tanggal_pilih); ?></strong></span>
            <?php else: ?>
                <span>Menampilkan data periode: <strong><?php echo formatTanggalIndonesia($tanggal_mulai); ?></strong> sampai <strong><?php echo formatTanggalIndonesia($tanggal_akhir); ?></strong> (<?php echo $jumlah_hari; ?> hari)</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<h2 style="margin-bottom: 25px; color: #2c3e50;">
    <?php if ($mode == 'single'): ?>
        Rekapitulasi Perkara Harian
    <?php else: ?>
        <i class="fas fa-chart-bar" style="margin-right: 10px; color: #27ae60;"></i>
        Rekapitulasi Perkara <?php echo ($jumlah_hari == 1) ? 'Harian' : 'Periode ' . $jumlah_hari . ' Hari'; ?>
    <?php endif; ?>
</h2>

<div class="dashboard-cards">
    <?php 
    // Definisikan warna untuk setiap jenis perkara
    $card_colors = array('blue', 'green', 'orange', 'purple', 'teal', 'indigo', 'pink', 'red');
    $color_index = 0;
    
    foreach ($jenis_perkara_list as $jenis_id => $jenis_data): 
        if ($counter_jenis[$jenis_id] > 0): // Hanya tampilkan yang ada datanya
            
            // Tentukan kategori detail menggunakan fungsi baru
            $jenis_nama = $jenis_data['nama'];
            $kategori_detail = tentukanKategoriDetail($jenis_nama, $kategori_mapping);
    ?>
    <div class="card card-<?php echo $card_colors[$color_index % count($card_colors)]; ?>" 
         data-kategori="jenis_<?php echo $jenis_id; ?>" 
         data-kategori-detail="<?php echo $kategori_detail; ?>"
         data-jenis-nama="<?php echo htmlspecialchars($jenis_nama); ?>"
         data-jenis-id="<?php echo $jenis_id; ?>">
        <div class="card-header">
            <div class="card-title"><?php echo htmlspecialchars($jenis_data['nama']); ?></div>
            <div class="card-icon">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <div class="card-value"><?php echo $counter_jenis[$jenis_id]; ?></div>
        <?php if (isset($_GET['show_kategori']) && $_GET['show_kategori'] == '1'): ?>
        <div class="card-debug">
            Kategori: <strong><?php echo $kategori_detail; ?></strong>
        </div>
        <?php endif; ?>
    </div>
    <?php 
        $color_index++;
        endif;
    endforeach; 
    ?>

    <div class="card card-orange" data-kategori="ecourt">
        <div class="card-header">
            <div class="card-title">E-Court</div>
            <div class="card-icon">
                <i class="fas fa-laptop"></i>
            </div>
        </div>
        <div class="card-value"><?php echo $e_court; ?></div>
    </div>

    <div class="card card-yellow" data-kategori="manual">
        <div class="card-header">
            <div class="card-title">Manual</div>
            <div class="card-icon">
                <i class="fas fa-edit"></i>
            </div>
        </div>
        <div class="card-value"><?php echo $manual; ?></div>
    </div>

    <div class="card card-success" data-kategori="putus">
        <div class="card-header">
            <div class="card-title">Putus</div>
            <div class="card-icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="card-value"><?php echo $putus; ?></div>
    </div>

    <div class="card card-info" data-kategori="minutasi">
        <div class="card-header">
            <div class="card-title">Minutasi</div>
            <div class="card-icon">
                <i class="fas fa-folder"></i>
            </div>
        </div>
        <div class="card-value"><?php echo $minutasi; ?></div>
    </div>
</div>

<?php
// Siapkan data untuk JavaScript
$jenis_perkara_data = [];
foreach ($jenis_perkara_list as $jenis_id => $jenis_data) {
    if ($counter_jenis[$jenis_id] > 0) {
        $jenis_perkara_data[] = [
            'nama' => $jenis_data['nama'],
            'jumlah' => $counter_jenis[$jenis_id]
        ];
    }
}

// Hitung total untuk progress bars
$total_all_cases = $total_masuk > 0 ? $total_masuk : ($putus + $minutasi + ($total_masuk - $putus - $minutasi));
$dalam_proses = max(0, $total_all_cases - $putus - $minutasi);
?>

<!-- Dashboard Content -->
<div class="dashboard-container">
    <!-- Charts Row -->
    <div class="charts-row">
        <div class="chart-container">
            <div class="chart-total" id="totalPerkaraDisplay">
                <?php echo $total_masuk; ?>
            </div>
            <h3 class="chart-title">Total Perkara Masuk</h3>
            <div class="chart-subtitle">
                <?php echo ($mode == 'single') ? 'Hari Ini' : $jumlah_hari . ' Hari'; ?>
            </div>
            <div class="chart-canvas">
                <canvas id="perkaraChart"></canvas>
            </div>
        </div>
        <div class="chart-container">
            <h3 class="chart-title">Distribusi Cara Daftar</h3>
            <div class="chart-subtitle">
                E-Court: <strong><?php echo $e_court; ?></strong> | Manual: <strong><?php echo $manual; ?></strong>
            </div>
            <div class="chart-canvas">
                <canvas id="registrationChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Progress Section -->
    <div class="progress-section">
        <h3 class="progress-title">Status Penyelesaian Perkara</h3>
        <div class="progress-item">
            <div class="progress-label">
                <span class="progress-label-text">Perkara Putus</span>
                <span class="progress-label-value" id="putusCount">
                    <?php echo $putus; ?> (<?php echo $total_all_cases > 0 ? round(($putus / $total_all_cases) * 100) : 0; ?>%)
                </span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar putus" id="putusBar" style="width: 0%">0%</div>
            </div>
        </div>
        <div class="progress-item">
            <div class="progress-label">
                <span class="progress-label-text">Minutasi Selesai</span>
                <span class="progress-label-value" id="minutasiCount">
                    <?php echo $minutasi; ?> (<?php echo $total_all_cases > 0 ? round(($minutasi / $total_all_cases) * 100) : 0; ?>%)
                </span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar minutasi" id="minutasiBar" style="width: 0%">0%</div>
            </div>
        </div>
        <div class="progress-item">
            <div class="progress-label">
                <span class="progress-label-text">Dalam Proses</span>
                <span class="progress-label-value" id="prosesCount">
                    <?php echo $dalam_proses; ?> (<?php echo $total_all_cases > 0 ? round(($dalam_proses / $total_all_cases) * 100) : 0; ?>%)
                </span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar proses" id="prosesBar" style="width: 0%">0%</div>
            </div>
        </div>
        <button class="export-btn" onclick="exportData()">📊 Export Data CSV</button>
    </div>
</div>

<!-- Export Buttons -->
<div class="export-buttons">
    <?php if ($mode == 'single'): ?>
        <a href="perkara/export_pdf.php?tanggal=<?php echo $tanggal_pilih; ?>" class="export-btn export-btn-pdf" target="_blank">
            <i class="fas fa-file-pdf"></i>
            Export ke PDF
        </a>
        <a href="perkara/export_excel.php?tanggal=<?php echo $tanggal_pilih; ?>" class="export-btn export-btn-excel">
            <i class="fas fa-file-excel"></i>
            Export ke Excel
        </a>
    <?php else: ?>
        <a href="perkara/export_pdf.php?tanggal_mulai=<?php echo $tanggal_mulai; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" class="export-btn export-btn-pdf" target="_blank">
            <i class="fas fa-file-pdf"></i>
            Export ke PDF
        </a>
        <a href="perkara/export_excel.php?tanggal_mulai=<?php echo $tanggal_mulai; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" class="export-btn export-btn-excel">
            <i class="fas fa-file-excel"></i>
            Export ke Excel
        </a>
    <?php endif; ?>
</div>

<!-- Filter Jenis Perkara dengan Search Dropdown -->
<div class="jenis-filter-section">
    <div>
        <h5>
            <i class="fas fa-filter"></i>
            Filter Jenis Perkara
        </h5>
        
        <!-- Search Dropdown -->
        <div class="search-dropdown-container">
            <div class="search-dropdown-input">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Ketik untuk mencari jenis perkara..." 
                       autocomplete="off" 
                       oninput="handleSearchInput()" 
                       onfocus="showDropdown()" 
                       onblur="hideDropdownDelay()">
                <i class="fas fa-chevron-down" id="dropdownIcon" onclick="toggleDropdown()"></i>
            </div>
            
            <div id="dropdownList" class="dropdown-list">
                <!-- Opsi Semua Perkara -->
                <div class="dropdown-item active" data-filter="semua" onclick="selectFilter('semua', 'Semua Perkara (<?php echo $total_masuk; ?>)')">
                    <i class="fas fa-list"></i>
                    <span>Semua Perkara</span>
                    <span class="item-count">(<?php echo $total_masuk; ?>)</span>
                </div>
                
                <!-- Opsi berdasarkan jenis perkara aktual -->
                <?php foreach ($jenis_aktual_nama as $jenis_nama => $jenis_info): ?>
                <div class="dropdown-item" data-filter="nama_<?php echo md5($jenis_nama); ?>" onclick="selectFilter('nama_<?php echo md5($jenis_nama); ?>', '<?php echo addslashes($jenis_nama); ?> (<?php echo $jenis_info['count']; ?>)')">
                    <i class="fas fa-file-alt"></i>
                    <span><?php echo htmlspecialchars($jenis_nama); ?></span>
                    <span class="item-count">(<?php echo $jenis_info['count']; ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Search Section -->
<div class="enhanced-search-section">
    <div>
        <h5>
            <i class="fas fa-search"></i>
            Pencarian Detail
        </h5>
    </div>
    
    <div class="search-controls">
        <!-- Search Input -->
        <div class="search-input-wrapper">
            <label for="detailSearchInput">
                Cari Nomor Perkara atau E-filing ID
            </label>
            <div class="detail-search-input">
                <i class="fas fa-search"></i>
                <input type="text" id="detailSearchInput" placeholder="Masukkan nomor perkara atau e-filing ID..." 
                       autocomplete="off"
                       oninput="performDetailSearch()">
                <button type="button" id="clearSearchBtn" onclick="clearDetailSearch()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="search-help">
                <i class="fas fa-info-circle"></i>
                Tip: Ketik minimal 3 karakter untuk mulai pencarian
            </div>
        </div>
        
        <!-- Search Stats -->
        <div class="search-stats">
            <div>Hasil Pencarian</div>
            <div id="searchResultCount">
                <?php echo count($data_rekap); ?> perkara
            </div>
        </div>
        
        <!-- Clear All Button -->
        <button type="button" onclick="clearAllFilters()" class="clear-all-btn">
            <i class="fas fa-eraser"></i>
            Hapus Semua Filter
        </button>
    </div>
    
    <!-- Active Filters Display -->
    <div id="activeFilters" class="active-filters">
        <div>Filter Aktif:</div>
        <div class="filter-tags"></div>
    </div>
</div>

<!-- Detail Data Perkara -->
<div class="detail-card">
    <h3 class="detail-card-title" id="detailTableTitle" data-base-title="<?php if ($mode == 'single'): ?>Detail Perkara Tanggal <?php echo formatTanggalIndonesia($tanggal_pilih); ?><?php else: ?>Detail Perkara Periode <?php echo formatTanggalIndonesia($tanggal_mulai); ?> - <?php echo formatTanggalIndonesia($tanggal_akhir); ?><?php endif; ?>">
        <?php if ($mode == 'single'): ?>
            Detail Perkara Tanggal <?php echo formatTanggalIndonesia($tanggal_pilih); ?>
        <?php else: ?>
            Detail Perkara Periode <?php echo formatTanggalIndonesia($tanggal_mulai); ?> - <?php echo formatTanggalIndonesia($tanggal_akhir); ?>
        <?php endif; ?>
        <span id="totalCount">(<?php echo count($data_rekap); ?> perkara)</span>
    </h3>
    
    <?php if (count($data_rekap) > 0): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>No. Perkara</th>
                    <th>Jenis</th>
                    <th>Cara Daftar</th>
                    <th>Hakim PN</th>
                    <th>E-filing ID</th>
                    <th>Status</th>
                    <th>Tgl Putusan</th>
                    <th>Tgl Minutasi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($data_rekap as $row): 
                ?>
                <tr class="perkara-row" 
                    data-jenis="nama_<?php echo md5($row['jenis_perkara_nama']); ?>" 
                    data-jenis-nama="<?php echo htmlspecialchars($row['jenis_perkara_nama']); ?>">
                    <td><?php echo $no++; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_pendaftaran'])); ?></td>
                    <td><?php echo htmlspecialchars($row['nomor_perkara']); ?></td>
                    <td>
                        <span class="badge badge-primary">
                            <?php echo htmlspecialchars($row['jenis_perkara_nama']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $row['cara_daftar'] == 'e-court' ? 'warning' : 'secondary'; ?>">
                            <?php echo $row['cara_daftar'] == 'e-court' ? 'E-Court' : 'Manual'; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars(isset($row['hakim_nama']) ? $row['hakim_nama'] : '-'); ?></td>
                    <td><?php echo htmlspecialchars(isset($row['efiling_id']) && $row['efiling_id'] ? $row['efiling_id'] : '-'); ?></td>
                    <td>
                        <?php
                        $status_colors = array(
                            'dalam_proses' => 'warning',
                            'putus' => 'success',
                            'minutasi_selesai' => 'primary'
                        );
                        $status_labels = array(
                            'dalam_proses' => 'Dalam Proses',
                            'putus' => 'Putus',
                            'minutasi_selesai' => 'Minutasi Selesai'
                        );
                        $status_key = $row['status_perkara'];
                        ?>
                        <span class="badge badge-<?php echo isset($status_colors[$status_key]) ? $status_colors[$status_key] : 'secondary'; ?>">
                            <?php echo isset($status_labels[$status_key]) ? $status_labels[$status_key] : ucfirst(str_replace('_', ' ', $status_key)); ?>
                        </span>
                    </td>
                    <td><?php echo $row['tanggal_putusan'] ? date('d/m/Y', strtotime($row['tanggal_putusan'])) : '-'; ?></td>
                    <td><?php echo $row['tanggal_minutasi'] ? date('d/m/Y', strtotime($row['tanggal_minutasi'])) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>Tidak ada data perkara pada <?php echo ($mode == 'single') ? 'tanggal ' . formatTanggalIndonesia($tanggal_pilih) : 'periode ' . formatTanggalIndonesia($tanggal_mulai) . ' - ' . formatTanggalIndonesia($tanggal_akhir); ?></p>
        <p style="font-size: 14px; color: #95a5a6;">Silakan pilih tanggal atau periode lain</p>
    </div>
    <?php endif; ?>
</div>

<!-- Chart.js CDN -->
<script src="assets/js/chart.umd.min.js"></script>

<!-- Link ke JavaScript internal -->
<script src="assets/js/js_rekap.js"></script>

<!-- Data untuk JavaScript -->
<script>
// Set dashboard data untuk digunakan oleh js_rekap.js
var dashboardData = {
    jenisPerkaraData: <?php echo json_encode($jenis_perkara_data); ?>,
    caraDaftar: { 
        eCourt: <?php echo $e_court; ?>, 
        manual: <?php echo $manual; ?> 
    },
    status: { 
        putus: <?php echo $putus; ?>, 
        minutasi: <?php echo $minutasi; ?>, 
        dalamProses: <?php echo $dalam_proses; ?>
    },
    totalAllCases: <?php echo $total_all_cases; ?>
};
</script>

<?php 
// debugging tool
if (isset($_GET['show_mapping']) && $_GET['show_mapping'] == '1'): 
?>
<div class="debug-mapping-container">
    <h4>
        <i class="fas fa-bug"></i>
        Debug: Kategori Mapping
    </h4>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Jenis Perkara</th>
                <th>Kategori Detail</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jenis_perkara_list as $jenis_id => $jenis_data): 
                if ($counter_jenis[$jenis_id] > 0):
                    $kategori = tentukanKategoriDetail($jenis_data['nama'], $kategori_mapping);
            ?>
            <tr>
                <td><?php echo $jenis_id; ?></td>
                <td><?php echo htmlspecialchars($jenis_data['nama']); ?></td>
                <td>
                    <span><?php echo $kategori; ?></span>
                </td>
                <td>
                    <?php echo $counter_jenis[$jenis_id]; ?>
                </td>
            </tr>
            <?php 
                endif;
            endforeach; 
            ?>
        </tbody>
    </table>
    <p>
        <i class="fas fa-info-circle"></i> <strong>Cara Menggunakan:</strong> Tambahkan <code>?show_mapping=1</code> di URL untuk melihat debug mapping.<br>
        Tambahkan <code>&show_kategori=1</code> untuk melihat kategori di setiap card.
    </p>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>