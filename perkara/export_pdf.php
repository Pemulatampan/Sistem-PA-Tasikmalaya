<?php
// perkara/export_pdf_fpdf.php
require_once '../config/config.php';
require_once '../config/functions.php'; // Include functions.php untuk formatTanggalIndonesia()
require_once('../fpdf/fpdf.php'); // Sesuaikan path FPDF

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

// Escape string untuk keamanan
$tanggal_mulai_safe = mysql_real_escape_string($tanggal_mulai);
$tanggal_akhir_safe = mysql_real_escape_string($tanggal_akhir);

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
    WHERE p.tanggal_pendaftaran BETWEEN '$tanggal_mulai_safe' 
          AND '$tanggal_akhir_safe'
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
$e_court = 0;
$manual = 0;
$putus = 0;
$minutasi = 0;
$dalam_proses = 0;

while ($row = mysql_fetch_assoc($result)) {
    $kode = isset($row['jenis_perkara_kode']) ? $row['jenis_perkara_kode'] : '';
    $nama_lower = strtolower($row['jenis_perkara_nama']);
    $cara = isset($row['cara_daftar']) ? strtolower($row['cara_daftar']) : '';
    $status = isset($row['status_perkara']) ? strtolower($row['status_perkara']) : '';
    
    // Hitung cara daftar
    if ($cara == 'e-court') {
        $e_court++;
    } else {
        $manual++;
    }
    
    // Hitung status perkara
    if ($status == 'putus') {
        $putus++;
    } elseif ($status == 'minutasi_selesai') {
        $minutasi++;
    } else {
        $dalam_proses++;
    }
    
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

$total_masuk = $count_pdtg + $count_pdtp;

// Hitung jumlah hari
$date1 = strtotime($tanggal_mulai);
$date2 = strtotime($tanggal_akhir);
$jumlah_hari = floor(($date2 - $date1) / (60 * 60 * 24)) + 1;

// Generate title berdasarkan mode
if ($mode == 'single') {
    $title_periode = formatTanggalIndonesia($tanggal_pilih);
    $laporan_title = "LAPORAN REKAPITULASI PERKARA HARIAN";
    $filename = 'Laporan_Rekap_Perkara_' . str_replace('-', '', $tanggal_pilih) . '.pdf';
} else {
    $title_periode = formatTanggalIndonesia($tanggal_mulai) . ' - ' . formatTanggalIndonesia($tanggal_akhir) . ' (' . $jumlah_hari . ' hari)';
    $laporan_title = "LAPORAN REKAPITULASI PERKARA PERIODE";
    $filename = 'Laporan_Rekap_Perkara_' . str_replace('-', '', $tanggal_mulai) . '_sd_' . str_replace('-', '', $tanggal_akhir) . '.pdf';
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

// Extended FPDF Class untuk mendukung UTF-8 dan tabel yang lebih baik
class PDF extends FPDF {
    private $title_periode;
    private $laporan_title;
    
    function __construct($title_periode, $laporan_title) {
        parent::__construct('L', 'mm', 'A4');
        $this->title_periode = $title_periode;
        $this->laporan_title = $laporan_title;
    }
    
    // Header
    function Header() {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, $this->laporan_title, 0, 1, 'C');
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 6, $this->title_periode, 0, 1, 'C');
        $this->Line(10, $this->GetY(), 287, $this->GetY());
        $this->Ln(3);
    }
    
    // Footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 7);
        $this->Cell(0, 5, 'Halaman ' . $this->PageNo() . ' - Dicetak: ' . date('d/m/Y H:i:s'), 0, 0, 'C');
    }
    
    // Tabel dengan border
    function FancyTable($header, $data, $widths) {
        // Header
        $this->SetFillColor(51, 51, 51);
        $this->SetTextColor(255);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 7);
        
        for($i = 0; $i < count($header); $i++) {
            $this->Cell($widths[$i], 6, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Data
        $this->SetFillColor(248, 249, 250);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 6);
        
        $fill = false;
        $no = 1;
        foreach($data as $row) {
            $this->Cell($widths[0], 5, $no, 'LR', 0, 'C', $fill);
            for($i = 1; $i < count($widths); $i++) {
                $this->Cell($widths[$i], 5, $row[$i-1], 'LR', 0, 'L', $fill);
            }
            $this->Ln();
            $fill = !$fill;
            $no++;
        }
        $this->Cell(array_sum($widths), 0, '', 'T');
    }
    
    // Box untuk statistik
    function StatBox($title, $value, $w, $h) {
        $this->SetDrawColor(153, 153, 153);
        $this->SetLineWidth(.3);
        $this->Rect($this->GetX(), $this->GetY(), $w, $h);
        
        $x = $this->GetX();
        $y = $this->GetY();
        
        // Title
        $this->SetFont('Arial', '', 7);
        $this->SetXY($x, $y + 2);
        $this->Cell($w, 4, $title, 0, 1, 'C');
        
        // Value
        $this->SetFont('Arial', 'B', 16);
        $this->SetXY($x, $y + 7);
        $this->Cell($w, 8, $value, 0, 0, 'C');
    }
}

// Buat PDF
$pdf = new PDF($title_periode, $laporan_title);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

// Info Section
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(40, 5, 'Tanggal Cetak:', 0, 0);
$pdf->Cell(0, 5, formatTanggalIndonesia(date('Y-m-d')) . ' ' . date('H:i:s'), 0, 1);
$pdf->Cell(40, 5, 'Total Perkara:', 0, 0);
$pdf->Cell(0, 5, $total_masuk . ' perkara', 0, 1);
$pdf->Ln(3);

// Statistik Perkara
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, 'STATISTIK PERKARA', 0, 1);
$pdf->Ln(2);

// Baris 1: Jenis Perkara
$box_width = 90;
$box_height = 15;
$start_x = $pdf->GetX();
$start_y = $pdf->GetY();

$pdf->StatBox('Gugatan (Pdt.G)', $count_pdtg, $box_width, $box_height);
$pdf->SetXY($start_x + $box_width + 3, $start_y);
$pdf->StatBox('Permohonan (Pdt.P)', $count_pdtp, $box_width, $box_height);
$pdf->SetXY($start_x + ($box_width + 3) * 2, $start_y);
$pdf->StatBox('Total Perkara', $total_masuk, $box_width, $box_height);

// Baris 2: Cara Daftar
$start_y = $pdf->GetY() + $box_height + 3;
$pdf->SetXY($start_x, $start_y);
$pdf->StatBox('E-Court', $e_court, $box_width, $box_height);
$pdf->SetXY($start_x + $box_width + 3, $start_y);
$pdf->StatBox('Manual', $manual, $box_width, $box_height);
$pdf->SetXY($start_x + ($box_width + 3) * 2, $start_y);
$pdf->StatBox('Total Cara Daftar', $e_court + $manual, $box_width, $box_height);

// Baris 3: Status
$start_y = $pdf->GetY() + $box_height + 3;
$pdf->SetXY($start_x, $start_y);
$pdf->StatBox('Putus', $putus, $box_width, $box_height);
$pdf->SetXY($start_x + $box_width + 3, $start_y);
$pdf->StatBox('Minutasi', $minutasi, $box_width, $box_height);
$pdf->SetXY($start_x + ($box_width + 3) * 2, $start_y);
$pdf->StatBox('Dalam Proses', $dalam_proses, $box_width, $box_height);

$pdf->SetY($start_y + $box_height + 8);

// Detail Perkara Gugatan (Pdt.G)
if ($count_pdtg > 0) {
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 6, 'DETAIL PERKARA GUGATAN (Pdt.G) - ' . $count_pdtg . ' Perkara', 0, 1);
    $pdf->Ln(2);
    
    $header = array('No', 'Tanggal', 'No. Perkara', 'Jenis Perkara', 'Cara', 'Pihak 1', 'Pihak 2', 'Hakim', 'Status');
    $widths = array(8, 18, 30, 35, 15, 45, 45, 35, 20);
    
    $table_data = array();
    foreach($data_pdtg as $row) {
        $table_data[] = array(
            date('d/m/Y', strtotime($row['tanggal_pendaftaran'])),
            substr($row['nomor_perkara'], 0, 25),
            substr($row['jenis_perkara_nama'], 0, 28),
            $row['cara_daftar'] == 'e-court' ? 'E-Court' : 'Manual',
            substr($row['pihak1_text'], 0, 35),
            substr($row['pihak2_text'], 0, 35),
            substr($row['hakim_nama'] ?: '-', 0, 28),
            substr(formatStatus($row['status_perkara']), 0, 18)
        );
    }
    
    $pdf->FancyTable($header, $table_data, $widths);
    $pdf->Ln(5);
}

// Detail Perkara Permohonan (Pdt.P)
if ($count_pdtp > 0) {
    // Cek apakah perlu halaman baru
    if ($pdf->GetY() > 170) {
        $pdf->AddPage();
    }
    
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 6, 'DETAIL PERKARA PERMOHONAN (Pdt.P) - ' . $count_pdtp . ' Perkara', 0, 1);
    $pdf->Ln(2);
    
    $header = array('No', 'Tanggal', 'No. Perkara', 'Jenis Perkara', 'Cara', 'Pihak 1', 'Pihak 2', 'Hakim', 'Status');
    $widths = array(8, 18, 30, 35, 15, 45, 45, 35, 20);
    
    $table_data = array();
    foreach($data_pdtp as $row) {
        $table_data[] = array(
            date('d/m/Y', strtotime($row['tanggal_pendaftaran'])),
            substr($row['nomor_perkara'], 0, 25),
            substr($row['jenis_perkara_nama'], 0, 28),
            $row['cara_daftar'] == 'e-court' ? 'E-Court' : 'Manual',
            substr($row['pihak1_text'], 0, 35),
            substr($row['pihak2_text'], 0, 35),
            substr($row['hakim_nama'] ?: '-', 0, 28),
            substr(formatStatus($row['status_perkara']), 0, 18)
        );
    }
    
    $pdf->FancyTable($header, $table_data, $widths);
    $pdf->Ln(5);
}

// Ringkasan
if ($pdf->GetY() > 170) {
    $pdf->AddPage();
}

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 6, 'RINGKASAN LAPORAN', 0, 1);
$pdf->Ln(2);

// Tabel Ringkasan
$pdf->SetFillColor(51, 51, 51);
$pdf->SetTextColor(255);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(15, 6, 'No', 1, 0, 'C', true);
$pdf->Cell(120, 6, 'Kategori Perkara', 1, 0, 'C', true);
$pdf->Cell(30, 6, 'Jumlah', 1, 1, 'C', true);

$pdf->SetTextColor(0);
$pdf->SetFont('Arial', '', 8);
$no_ringkasan = 1;

if ($count_pdtg > 0) {
    $pdf->Cell(15, 6, $no_ringkasan++, 1, 0, 'C');
    $pdf->Cell(120, 6, 'Perkara Gugatan (Pdt.G)', 1, 0, 'L');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(30, 6, $count_pdtg, 1, 1, 'C');
    $pdf->SetFont('Arial', '', 8);
}

if ($count_pdtp > 0) {
    $pdf->Cell(15, 6, $no_ringkasan++, 1, 0, 'C');
    $pdf->Cell(120, 6, 'Perkara Permohonan (Pdt.P)', 1, 0, 'L');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(30, 6, $count_pdtp, 1, 1, 'C');
    $pdf->SetFont('Arial', '', 8);
}

// Total
$pdf->SetFillColor(102, 102, 102);
$pdf->SetTextColor(255);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(135, 7, 'TOTAL SELURUH PERKARA', 1, 0, 'C', true);
$pdf->Cell(30, 7, $total_masuk, 1, 1, 'C', true);

// Output PDF
$pdf->Output('D', $filename);

mysql_close($connection);
exit();
?>