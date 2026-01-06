<?php
// perkara/rekap.php
$page_title = "Rekap Perkara";
include '../includes/header.php';
include '../config/config.php';

// Cek apakah menggunakan single date atau date range
if (isset($_GET['tanggal']) && !isset($_GET['tanggal_mulai'])) {
    // Mode single date (backward compatibility)
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

// Hitung jumlah hari
$date1 = strtotime($tanggal_mulai);
$date2 = strtotime($tanggal_akhir);
$jumlah_hari = floor(($date2 - $date1) / (60 * 60 * 24)) + 1;

// Query data perkara
$sql_rekap = "
    SELECT 
        p.perkara_id,
        p.nomor_perkara,
        p.tanggal_pendaftaran,
        p.jenis_perkara_id,
        p.jenis_perkara_nama,
        p.jenis_perkara_kode,
        pe.efiling_id,
        CASE 
            WHEN pe.efiling_id IS NOT NULL AND pe.efiling_id != '' 
            THEN 'e-court'
            ELSE 'manual'
        END as cara_daftar,
        ph.hakim_nama,
        pp.tanggal_putusan,
        pp.tanggal_minutasi,
        CASE 
            WHEN pp.tanggal_minutasi IS NOT NULL AND pp.tanggal_minutasi != '' 
            THEN 'minutasi_selesai'
            WHEN pp.tanggal_putusan IS NOT NULL AND pp.tanggal_putusan != '' 
            THEN 'putus'
            ELSE 'dalam_proses'
        END as status_perkara
    FROM perkara p
    LEFT JOIN (
        SELECT perkara_id, efiling_id
        FROM perkara_efiling_id
        WHERE efiling_id IS NOT NULL AND efiling_id != ''
        GROUP BY perkara_id
        HAVING efiling_id = MIN(efiling_id)
    ) pe ON p.perkara_id = pe.perkara_id
    LEFT JOIN (
        SELECT perkara_id, hakim_nama
        FROM perkara_hakim_pn
        WHERE urutan = '1' AND aktif = 'Y'
        GROUP BY perkara_id
        HAVING hakim_nama = MIN(hakim_nama)
    ) ph ON p.perkara_id = ph.perkara_id
    LEFT JOIN (
        SELECT 
            perkara_id, 
            MIN(tanggal_putusan) as tanggal_putusan, 
            MIN(tanggal_minutasi) as tanggal_minutasi
        FROM perkara_putusan
        GROUP BY perkara_id
    ) pp ON p.perkara_id = pp.perkara_id
    WHERE p.tanggal_pendaftaran BETWEEN '" . mysql_real_escape_string($tanggal_mulai) . "' 
          AND '" . mysql_real_escape_string($tanggal_akhir) . "'
    GROUP BY p.perkara_id
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

// Hitung total yang BENAR - guaranteed unique
$total_rows = mysql_num_rows($result);
?>

<!-- Link ke CSS Rekap -->
<link rel="stylesheet" href="../assets/css/main.css">

<div class="container">
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
                    <div class="date-input-wrapper">
                        <label for="tanggal">Tanggal:</label>
                        <input type="date" name="tanggal" id="tanggal" value="<?php echo $tanggal_pilih; ?>" class="date-input">
                    </div>
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
                    <button type="submit" class="date-submit-btn">
                        <i class="fas fa-search"></i> Tampilkan
                    </button>
                </div>
            </form>
            
            <!-- untuk mode range -->
            <div class="quick-date-buttons" id="quickButtons" style="display: <?php echo $mode == 'range' ? 'flex' : 'none'; ?>;">
                <a href="?tanggal_mulai=<?php echo date('Y-m-d'); ?>&tanggal_akhir=<?php echo date('Y-m-d'); ?>" class="quick-btn">
                   Hari Ini
                </a>
                <a href="?tanggal_mulai=<?php echo date('Y-m-d', strtotime('-6 days')); ?>&tanggal_akhir=<?php echo date('Y-m-d'); ?>" class="quick-btn">
                   7 Hari Terakhir
                </a>
                <a href="?tanggal_mulai=<?php echo date('Y-m-01'); ?>&tanggal_akhir=<?php echo date('Y-m-d'); ?>" class="quick-btn">
                   Bulan Ini
                </a>
            </div>
            
            <!-- Selected Date Info -->
            <div class="selected-date">
                <?php if ($mode == 'single'): ?>
                    <span>Menampilkan data untuk: <strong><?php echo formatTanggalIndonesia($tanggal_pilih); ?></strong></span>
                <?php else: ?>
                    <span>Menampilkan data periode: <strong><?php echo formatTanggalIndonesia($tanggal_mulai); ?></strong> sampai <strong><?php echo formatTanggalIndonesia($tanggal_akhir); ?></strong> (<?php echo $jumlah_hari; ?> hari)</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h2 style="margin-bottom:20px; color: #2c3e50;">
        <i class="fas fa-chart-bar" style="margin-right: 10px; color: #27ae60;"></i>
        <?php if ($mode == 'single'): ?>
            Rekap Perkara - <?php echo formatTanggalIndonesia($tanggal_pilih); ?>
        <?php else: ?>
            Rekap Perkara Periode <?php echo ($jumlah_hari == 1) ? 'Harian' : $jumlah_hari . ' Hari'; ?>
        <?php endif; ?>
    </h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nomor Perkara</th>
                    <th>Jenis</th>
                    <th>Cara Daftar</th>
                    <th>Hakim PN</th>
                    <th>E-filing ID</th>
                    <th>Status</th>
                    <th>Tgl Putusan</th>
                    <th>Tgl Minutasi</th>
                    <th>Durasi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                if ($total_rows > 0):
                    while ($row = mysql_fetch_assoc($result)):
                        // Tentukan jenis perkara berdasarkan nomor perkara
                        $jenis_perkara = 'Permohonan';
                        if (strpos($row['nomor_perkara'], 'Pdt.G') !== false) {
                            $jenis_perkara = 'Gugatan';
                        }
                ?>
                <tr>
                    <td style="text-align:center;"><?php echo $no++; ?></td>
                    <td style="text-align:center;"><?php echo date('d/m/Y', strtotime($row['tanggal_pendaftaran'])); ?></td>
                    <td><?php echo htmlspecialchars($row['nomor_perkara']); ?></td>
                    <td style="text-align:center;">
                        <span class="badge <?php echo $jenis_perkara == 'Gugatan' ? 'badge-gugatan' : 'badge-permohonan'; ?>">
                            <?php echo $jenis_perkara; ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <span class="badge <?php echo $row['cara_daftar'] == 'e-court' ? 'badge-ecourt' : 'badge-manual'; ?>">
                            <?php echo $row['cara_daftar'] == 'e-court' ? 'E-Court' : 'Manual'; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars(isset($row['hakim_nama']) && $row['hakim_nama'] != null ? $row['hakim_nama'] : '-'); ?></td>
                    <td style="text-align:center;"><?php echo htmlspecialchars(isset($row['efiling_id']) && $row['efiling_id'] != null ? $row['efiling_id'] : '-'); ?></td>
                    <td style="text-align:center;">
                        <?php
                        $status_classes = array(
                            'dalam_proses' => 'badge-proses',
                            'putus' => 'badge-putus',
                            'minutasi_selesai' => 'badge-minutasi'
                        );
                        $status_labels = array(
                            'dalam_proses' => 'Dalam Proses',
                            'putus' => 'Putus',
                            'minutasi_selesai' => 'Minutasi Selesai'
                        );

                        $status = $row['status_perkara'];
                        $badge_class = isset($status_classes[$status]) ? $status_classes[$status] : 'badge-manual';
                        $label = isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
                        ?>
                        <span class="badge <?php echo $badge_class; ?>">
                            <?php echo $label; ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <?php 
                        echo !empty($row['tanggal_putusan']) 
                            ? date('d/m/Y', strtotime($row['tanggal_putusan'])) 
                            : '-'; 
                        ?>
                    </td>
                    <td style="text-align:center;">
                        <?php 
                        echo !empty($row['tanggal_minutasi']) 
                            ? date('d/m/Y', strtotime($row['tanggal_minutasi'])) 
                            : '-'; 
                        ?>
                    </td>
                    <td style="text-align:center;">
                        <?php 
                        if (!empty($row['tanggal_putusan'])) {
                            $daftar = strtotime($row['tanggal_pendaftaran']);
                            $putus = strtotime($row['tanggal_putusan']);
                            $diff = floor(abs($putus - $daftar) / (60*60*24));
                            echo $diff . " hari";
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
                <?php
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="11" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>
                        <?php if ($mode == 'single'): ?>
                            Tidak ada perkara untuk tanggal <?php echo formatTanggalIndonesia($tanggal_pilih); ?>
                        <?php else: ?>
                            Tidak ada perkara untuk periode <?php echo formatTanggalIndonesia($tanggal_mulai); ?> - <?php echo formatTanggalIndonesia($tanggal_akhir); ?>
                        <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Export Buttons -->
    <div class="export-buttons">
        <?php if ($mode == 'single'): ?>
            <a href="export_pdf.php?tanggal=<?php echo $tanggal_pilih; ?>" class="export-btn export-btn-pdf" target="_blank">
                <i class="fas fa-file-pdf"></i> Export ke PDF
            </a>
            <a href="export_excel.php?tanggal=<?php echo $tanggal_pilih; ?>" class="export-btn export-btn-excel">
                <i class="fas fa-file-excel"></i> Export ke Excel
            </a>
        <?php else: ?>
            <a href="export_pdf.php?tanggal_mulai=<?php echo $tanggal_mulai; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" class="export-btn export-btn-pdf" target="_blank">
                <i class="fas fa-file-pdf"></i> Export ke PDF
            </a>
            <a href="export_excel.php?tanggal_mulai=<?php echo $tanggal_mulai; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" class="export-btn export-btn-excel">
                <i class="fas fa-file-excel"></i> Export ke Excel
            </a>
        <?php endif; ?>
    </div>

    <!-- Summary Info -->
    <?php if ($total_rows > 0): ?>
    <div class="summary-section">
        <h4>
            <i class="fas fa-info-circle"></i>
            Ringkasan Data
        </h4>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Total Perkara</div>
                <div class="summary-value"><?php echo $total_rows; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Periode</div>
                <div class="summary-value">
                    <?php if ($mode == 'single'): ?>
                        <?php echo formatTanggalIndonesia($tanggal_pilih); ?>
                    <?php else: ?>
                        <?php echo $jumlah_hari; ?> Hari
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Link ke JavaScript Rekap -->
<script src="../assets/js/js_rekap.js"></script>

<?php
include '../includes/footer.php';
mysql_close($connection);
?>