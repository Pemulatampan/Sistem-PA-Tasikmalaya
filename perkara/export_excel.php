<?php
// perkara/export_excel.php
require_once '../config/config.php';

// Cek apakah menggunakan single date atau date range
if (isset($_GET['tanggal']) && !isset($_GET['tanggal_mulai'])) {
    $tanggal_pilih = $_GET['tanggal'];
    $tanggal_mulai = $tanggal_pilih;
    $tanggal_akhir = $tanggal_pilih;
    $mode = 'single';
} else {
    $tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-d');
    $tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
    $tanggal_pilih = $tanggal_mulai;
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

// Query untuk mendapatkan rekap perkara
$sql_rekap = "
    SELECT 
        p.jenis_perkara_id,
        jp.nama as jenis_perkara_nama,
        jp.kode as jenis_perkara_kode,
        p.nomor_perkara,
        p.perkara_id,
        p.tanggal_pendaftaran,
        p.pihak1_text,
        p.pihak2_text,
        CASE 
            WHEN pe.efiling_id IS NOT NULL AND pe.efiling_id != '' THEN 'e-court'
            ELSE 'manual'
        END as cara_daftar,
        CASE 
            WHEN pp.tanggal_minutasi IS NOT NULL THEN 'minutasi_selesai'
            WHEN pp.tanggal_putusan IS NOT NULL THEN 'putus'
            ELSE 'dalam_proses'
        END as status_perkara,
        pp.tanggal_putusan,
        pp.tanggal_minutasi,
        pe.efiling_id,
        ph.hakim_nama
    FROM perkara p
    LEFT JOIN jenis_perkara jp ON p.jenis_perkara_id = jp.id
    LEFT JOIN perkara_efiling_id pe ON p.perkara_id = pe.perkara_id
    LEFT JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
    LEFT JOIN perkara_hakim_pn ph ON p.perkara_id = ph.perkara_id AND ph.urutan = '1'
    WHERE p.tanggal_pendaftaran BETWEEN '" . mysql_real_escape_string($tanggal_mulai) . "' 
          AND '" . mysql_real_escape_string($tanggal_akhir) . "'
    ORDER BY jp.nama, p.tanggal_pendaftaran DESC, p.nomor_perkara
";

$result = mysql_query($sql_rekap);
if (!$result) {
    die("Query gagal: " . mysql_error());
}

// Pisahkan data menjadi Pdt.G dan Pdt.P
$data_pdtg = array();
$data_pdtp = array();
$count_pdtg = 0;
$count_pdtp = 0;

while ($row = mysql_fetch_assoc($result)) {
    $kode = isset($row['jenis_perkara_kode']) ? $row['jenis_perkara_kode'] : '';
    $nama_lower = strtolower($row['jenis_perkara_nama']);
    
    // Klasifikasi berdasarkan kode Pdt.G atau Pdt.P
    if (strpos($kode, 'Pdt.G') !== false || 
        strpos($nama_lower, 'cerai gugat') !== false ||
        strpos($nama_lower, 'gugatan') !== false) {
        $data_pdtg[] = $row;
        $count_pdtg++;
    } else {
        $data_pdtp[] = $row;
        $count_pdtp++;
    }
}

// Hitung jumlah hari
$date1 = strtotime($tanggal_mulai);
$date2 = strtotime($tanggal_akhir);
$jumlah_hari = floor(($date2 - $date1) / (60 * 60 * 24)) + 1;

// Generate filename
if ($mode == 'single') {
    $filename = 'Laporan_Rekap_Perkara_' . str_replace('-', '', $tanggal_pilih) . '.xls';
    $title_periode = formatTanggalIndonesia($tanggal_pilih);
} else {
    $filename = 'Laporan_Rekap_Perkara_' . str_replace('-', '', $tanggal_mulai) . '_sd_' . str_replace('-', '', $tanggal_akhir) . '.xls';
    $title_periode = formatTanggalIndonesia($tanggal_mulai) . ' - ' . formatTanggalIndonesia($tanggal_akhir) . ' (' . $jumlah_hari . ' hari)';
}

// Set headers untuk download Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Function untuk clean Excel output
function cleanForExcel($str) {
    if (is_null($str) || $str === '') return '-';
    $str = str_replace(array("\r", "\n", "\t", '"'), array(' ', ' ', ' ', '""'), $str);
    if (in_array(substr($str, 0, 1), array('=', '+', '-', '@'))) {
        $str = "'" . $str;
    }
    return $str;
}

// Function untuk format status
function formatStatus($status) {
    $status_labels = array(
        'dalam_proses' => 'Dalam Proses',
        'putus' => 'Putus',
        'minutasi_selesai' => 'Minutasi Selesai'
    );
    return isset($status_labels[$status]) ? $status_labels[$status] : ucfirst(str_replace('_', ' ', $status));
}

// Function untuk render tabel
function renderTable($data, $kategori, $count, $title_periode) {
    $kategori_upper = strtoupper($kategori);
    
    echo "<table border='1'>";
    echo "<tr><td colspan='12'><b>LAPORAN PERKARA " . $kategori_upper . "</b></td></tr>";
    echo "<tr><td colspan='12'>Periode: " . $title_periode . "</td></tr>";
    echo "<tr><td colspan='12'>Dicetak: " . formatTanggalIndonesia(date('Y-m-d')) . " " . date('H:i:s') . "</td></tr>";
    echo "<tr><td colspan='12'>Total Perkara " . $kategori_upper . ": " . $count . " perkara</td></tr>";
    echo "<tr><td colspan='12'></td></tr>";

    // Header tabel
    echo "<tr>";
    echo "<td><b>No</b></td>";
    echo "<td><b>Tanggal</b></td>";
    echo "<td><b>No. Perkara</b></td>";
    echo "<td><b>Jenis Perkara</b></td>";
    echo "<td><b>Cara Daftar</b></td>";
    echo "<td><b>Pihak 1</b></td>";
    echo "<td><b>Pihak 2</b></td>";
    echo "<td><b>Hakim PN</b></td>";
    echo "<td><b>E-filing ID</b></td>";
    echo "<td><b>Status</b></td>";
    echo "<td><b>Tgl Putusan</b></td>";
    echo "<td><b>Tgl Minutasi</b></td>";
    echo "</tr>";

    // Data
    $no = 1;
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>" . $no . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($row['tanggal_pendaftaran'])) . "</td>";
        echo "<td>" . cleanForExcel($row['nomor_perkara']) . "</td>";
        echo "<td>" . cleanForExcel($row['jenis_perkara_nama']) . "</td>";
        echo "<td>" . ($row['cara_daftar'] == 'e-court' ? 'E-Court' : 'Manual') . "</td>";
        echo "<td>" . cleanForExcel($row['pihak1_text']) . "</td>";
        echo "<td>" . cleanForExcel($row['pihak2_text']) . "</td>";
        echo "<td>" . cleanForExcel($row['hakim_nama']) . "</td>";
        echo "<td>" . cleanForExcel($row['efiling_id']) . "</td>";
        echo "<td>" . cleanForExcel(formatStatus($row['status_perkara'])) . "</td>";
        echo "<td>" . (!empty($row['tanggal_putusan']) ? date('d/m/Y', strtotime($row['tanggal_putusan'])) : '-') . "</td>";
        echo "<td>" . (!empty($row['tanggal_minutasi']) ? date('d/m/Y', strtotime($row['tanggal_minutasi'])) : '-') . "</td>";
        echo "</tr>";
        $no++;
    }

    echo "</table>";
}

// Output Excel
echo "<html>";
echo "<head>";
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
echo "</head>";
echo "<body>";

// TABEL PDT.G (GUGATAN)
if ($count_pdtg > 0) {
    renderTable($data_pdtg, "Pdt.G", $count_pdtg, $title_periode);
    echo "<br><br><br>";
}

// TABEL PDT.P (PERMOHONAN)
if ($count_pdtp > 0) {
    renderTable($data_pdtp, "Pdt.P", $count_pdtp, $title_periode);
    echo "<br><br><br>";
}

// TABEL RINGKASAN
echo "<table border='1'>";
echo "<tr><td colspan='3'><b>RINGKASAN LAPORAN PERKARA</b></td></tr>";
echo "<tr><td colspan='3'>Periode: " . $title_periode . "</td></tr>";
echo "<tr><td colspan='3'>Dicetak: " . formatTanggalIndonesia(date('Y-m-d')) . " " . date('H:i:s') . "</td></tr>";
echo "<tr><td colspan='3'></td></tr>";

echo "<tr>";
echo "<td><b>No</b></td>";
echo "<td><b>Kategori Perkara</b></td>";
echo "<td><b>Jumlah</b></td>";
echo "</tr>";

$total_no = 1;
if ($count_pdtg > 0) {
    echo "<tr>";
    echo "<td>" . $total_no++ . "</td>";
    echo "<td>Perkara Gugatan (Pdt.G)</td>";
    echo "<td>" . $count_pdtg . "</td>";
    echo "</tr>";
}

if ($count_pdtp > 0) {
    echo "<tr>";
    echo "<td>" . $total_no++ . "</td>";
    echo "<td>Perkara Permohonan (Pdt.P)</td>";
    echo "<td>" . $count_pdtp . "</td>";
    echo "</tr>";
}

$total_semua = $count_pdtg + $count_pdtp;
echo "<tr>";
echo "<td colspan='2'><b>TOTAL SELURUH PERKARA</b></td>";
echo "<td><b>" . $total_semua . "</b></td>";
echo "</tr>";

echo "</table>";

echo "</body>";
echo "</html>";

mysql_close($connection);
exit();
?>