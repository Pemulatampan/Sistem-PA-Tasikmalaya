<?php
// perkara/detail.php
include '../config/config.php';

$kategori = isset($_GET['kategori']) ? clean_input($_GET['kategori']) : 'ecourt';

// Mapping kategori
$kategori_names = array(
    // Kategori Umum
    'gugatan' => 'Detail Gugatan',
    'permohonan' => 'Detail Permohonan', 
    'ecourt' => 'Detail E-Court',
    'manual' => 'Detail Manual',
    'putus' => 'Detail Putus',
    'minutasi' => 'Detail Minutasi',
    
    // Kategori Perkara
    'asal_usul_anak' => 'Detail Asal Usul Anak',
    'cerai_gugat' => 'Detail Cerai Gugat', 
    'cerai_talak' => 'Detail Cerai Talak',
    'dispensasi_kawin' => 'Detail Dispensasi Kawin',
    'p3hp' => 'Detail P3HP/Penetapan Ahli Waris',
    'pengesahan_nikah' => 'Detail Pengesahan Perkawinan/Istbat Nikah',
    'perwalian' => 'Detail Perwalian/Wali Adhol',
    
    // Kategori perkara tambahan
    'harta_bersama' => 'Detail Harta Bersama',
    'izin_poligami' => 'Detail Izin Poligami',
    'kewarisan' => 'Detail Kewarisan',
    'wakaf' => 'Detail Wakaf',
    'penguasaan_anak' => 'Detail Penguasaan Anak/Hadhanah',
    'hibah' => 'Detail Hibah',
    'pembatalan_perkawinan' => 'Detail Pembatalan Perkawinan',
    'ekonomi_syariah' => 'Detail Ekonomi Syariah',
    'lain_lain' => 'Detail Lain-lain'
);

// Validasi kategori yang diperluas
$valid_kategori = array(
    // Kategori Umum
    'gugatan', 'permohonan', 'ecourt', 'manual', 'putus', 'minutasi',
    
    // Kategori Jenis Perkara
    'asal_usul_anak', 'cerai_gugat', 'cerai_talak', 'dispensasi_kawin', 
    'p3hp', 'pengesahan_nikah', 'perwalian',
    
    // Kategori Baru
    'harta_bersama', 'izin_poligami', 'kewarisan', 'wakaf', 
    'penguasaan_anak', 'hibah', 'pembatalan_perkawinan', 'ekonomi_syariah',
    'lain_lain'
);

// Jika kategori tidak valid, fallback ke ecourt
if (!in_array($kategori, $valid_kategori)) {
    $kategori = 'ecourt';
}

$page_title = isset($kategori_names[$kategori]) ? $kategori_names[$kategori] : "Detail Perkara";

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

// Pagination dan Search parameters
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// ===========
// Query dasar
// ===========
$base_query = "
    SELECT 
        p.perkara_id,
        p.nomor_perkara,
        p.jenis_perkara_nama,
        p.tanggal_pendaftaran,
        p.pihak1_text,
        p.pihak2_text,
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
        END as status_perkara,
        (SELECT pp2.tanggal_putusan 
         FROM perkara_putusan pp2 
         WHERE pp2.perkara_id = p.perkara_id 
         LIMIT 1) as tanggal_putusan,
        (SELECT pp2.tanggal_minutasi 
         FROM perkara_putusan pp2 
         WHERE pp2.perkara_id = p.perkara_id 
         LIMIT 1) as tanggal_minutasi,
        (SELECT ph2.hakim_nama 
         FROM perkara_hakim_pn ph2 
         WHERE ph2.perkara_id = p.perkara_id 
           AND ph2.urutan = '1' 
           AND ph2.aktif = 'Y' 
         LIMIT 1) as hakim_nama
    FROM perkara p
    WHERE p.tanggal_pendaftaran BETWEEN '" . mysql_real_escape_string($tanggal_mulai) . "' 
          AND '" . mysql_real_escape_string($tanggal_akhir) . "'
";

// Filter berdasarkan kategori yang dipilih user
switch($kategori) {
    case 'gugatan':
        $base_query .= " AND p.nomor_perkara LIKE '%Pdt.G%'";
        $judul_kategori = "Gugatan";
        break;
        
    case 'permohonan':
        $base_query .= " AND p.nomor_perkara LIKE '%Pdt.P%'";
        $judul_kategori = "Permohonan";
        break;
        
    case 'ecourt':
        $base_query .= " AND (SELECT pe2.efiling_id FROM perkara_efiling_id pe2 WHERE pe2.perkara_id = p.perkara_id LIMIT 1) IS NOT NULL 
                         AND (SELECT pe2.efiling_id FROM perkara_efiling_id pe2 WHERE pe2.perkara_id = p.perkara_id LIMIT 1) != ''";
        $judul_kategori = "E-Court";
        break;
        
    case 'manual':
        $base_query .= " AND ((SELECT pe2.efiling_id FROM perkara_efiling_id pe2 WHERE pe2.perkara_id = p.perkara_id LIMIT 1) IS NULL 
                          OR (SELECT pe2.efiling_id FROM perkara_efiling_id pe2 WHERE pe2.perkara_id = p.perkara_id LIMIT 1) = '')";
        $judul_kategori = "Manual";
        break;
        
    case 'putus':
        $base_query .= " AND (SELECT pp2.tanggal_putusan FROM perkara_putusan pp2 WHERE pp2.perkara_id = p.perkara_id LIMIT 1) IS NOT NULL 
                         AND (SELECT pp2.tanggal_minutasi FROM perkara_putusan pp2 WHERE pp2.perkara_id = p.perkara_id LIMIT 1) IS NULL";
        $judul_kategori = "Putus";
        break;
        
    case 'minutasi':
        $base_query .= " AND (SELECT pp2.tanggal_minutasi FROM perkara_putusan pp2 WHERE pp2.perkara_id = p.perkara_id LIMIT 1) IS NOT NULL";
        $judul_kategori = "Minutasi";
        break;
        
    case 'asal_usul_anak':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%asal usul anak%' 
                        OR LOWER(p.jenis_perkara_nama) LIKE '%penetapan asal usul%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%asal-usul%')";
        $judul_kategori = "Asal Usul Anak";
        break;
        
    case 'cerai_gugat':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%cerai gugat%' 
                        OR LOWER(p.jenis_perkara_nama) LIKE '%perceraian%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%gugatan cerai%')";
        $judul_kategori = "Cerai Gugat";
        break;
        
    case 'cerai_talak':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%cerai talak%' 
                        OR LOWER(p.jenis_perkara_nama) LIKE '%talak%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%permohonan cerai talak%')";
        $judul_kategori = "Cerai Talak";
        break;
        
    case 'dispensasi_kawin':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%dispensasi%' 
                        OR LOWER(p.jenis_perkara_nama) LIKE '%izin kawin%')";
        $judul_kategori = "Dispensasi Kawin";
        break;
        
    case 'p3hp':
        // P3HP exclude perwalian
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%p3hp%' 
                        OR LOWER(p.jenis_perkara_nama) LIKE '%p3ph%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%penetapan ahli waris%' 
                        OR LOWER(p.jenis_perkara_nama) LIKE '%ahli waris%')
                        AND LOWER(p.jenis_perkara_nama) NOT LIKE '%wali adhol%'
                        AND LOWER(p.jenis_perkara_nama) NOT LIKE '%perwalian%'";
        $judul_kategori = "P3HP/Penetapan Ahli Waris";
        break;
        
    case 'pengesahan_nikah':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%pengesahan%' 
                        OR LOWER(p.jenis_perkara_nama) LIKE '%istbat%' 
                        OR LOWER(p.jenis_perkara_nama) LIKE '%isbat%')";
        $judul_kategori = "Pengesahan Perkawinan/Istbat Nikah";
        break;
        
    case 'perwalian':
        // Perwalian exclude ahli waris
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%wali adhol%' 
                        OR LOWER(p.jenis_perkara_nama) LIKE '%perwalian%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%penetapan wali%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%pencabutan kekuasaan wali%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%ganti rugi terhadap wali%')
                        AND LOWER(p.jenis_perkara_nama) NOT LIKE '%ahli waris%'
                        AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3hp%'
                        AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3ph%'";
        $judul_kategori = "Perwalian/Wali Adhol";
        break;
        
    case 'harta_bersama':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%harta bersama%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%pembagian harta%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%harta gono%')";
        $judul_kategori = "Harta Bersama";
        break;
        
    case 'izin_poligami':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%poligami%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%izin poligami%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%berpoligami%')";
        $judul_kategori = "Izin Poligami";
        break;
        
    case 'kewarisan':
        // penetapan ahli waris
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%kewarisan%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa waris%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%warisan%')
                        AND LOWER(p.jenis_perkara_nama) NOT LIKE '%penetapan ahli waris%'
                        AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3hp%'
                        AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3ph%'";
        $judul_kategori = "Kewarisan";
        break;
        
    case 'wakaf':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%wakaf%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%perwakafan%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa wakaf%')";
        $judul_kategori = "Wakaf";
        break;
        
    case 'penguasaan_anak':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%penguasaan anak%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%hadhanah%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%hadlonah%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%hak asuh%')";
        $judul_kategori = "Penguasaan Anak/Hadhanah";
        break;
        
    case 'hibah':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%hibah%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa hibah%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%pembatalan hibah%')";
        $judul_kategori = "Hibah";
        break;
        
    case 'pembatalan_perkawinan':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%pembatalan perkawinan%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%pembatalan nikah%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%penolakan perkawinan%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%pencegahan perkawinan%')";
        $judul_kategori = "Pembatalan Perkawinan";
        break;
        
    case 'ekonomi_syariah':
        $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%ekonomi syariah%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%ekonomi syari%'
                        OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa ekonomi syariah%')";
        $judul_kategori = "Ekonomi Syariah";
        break;

    case 'lain_lain':
    $base_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%lain%')";
    $judul_kategori = "Lain-lain";
    break;
        
    default:
        $judul_kategori = "Semua Perkara";
        break;
}

// Tambahkan search filter jika ada
if (!empty($search)) {
    $search_escaped = mysql_real_escape_string($search);
    $base_query .= " AND (p.nomor_perkara LIKE '%$search_escaped%' 
                    OR p.pihak1_text LIKE '%$search_escaped%' 
                    OR p.pihak2_text LIKE '%$search_escaped%' 
                    OR p.jenis_perkara_nama LIKE '%$search_escaped%'
                    OR (SELECT ph2.hakim_nama FROM perkara_hakim_pn ph2 WHERE ph2.perkara_id = p.perkara_id AND ph2.urutan = '1' AND ph2.aktif = 'Y' LIMIT 1) LIKE '%$search_escaped%')";
}

// =======================
// Query untuk total data 
// =======================
$count_query = "
    SELECT COUNT(DISTINCT p.perkara_id) as total
    FROM perkara p
    WHERE p.tanggal_pendaftaran BETWEEN '" . mysql_real_escape_string($tanggal_mulai) . "' 
          AND '" . mysql_real_escape_string($tanggal_akhir) . "'
";

// filter kategori yang sama ke count_query (simplified dengan EXISTS)
switch($kategori) {
    case 'gugatan':
        $count_query .= " AND p.nomor_perkara LIKE '%Pdt.G%'";
        break;
    case 'permohonan':
        $count_query .= " AND p.nomor_perkara LIKE '%Pdt.P%'";
        break;
    case 'ecourt':
        $count_query .= " AND EXISTS (SELECT 1 FROM perkara_efiling_id pe WHERE pe.perkara_id = p.perkara_id AND pe.efiling_id IS NOT NULL AND pe.efiling_id != '')";
        break;
    case 'manual':
        $count_query .= " AND NOT EXISTS (SELECT 1 FROM perkara_efiling_id pe WHERE pe.perkara_id = p.perkara_id AND pe.efiling_id IS NOT NULL AND pe.efiling_id != '')";
        break;
    case 'putus':
        $count_query .= " AND EXISTS (SELECT 1 FROM perkara_putusan pp WHERE pp.perkara_id = p.perkara_id AND pp.tanggal_putusan IS NOT NULL AND pp.tanggal_minutasi IS NULL)";
        break;
    case 'minutasi':
        $count_query .= " AND EXISTS (SELECT 1 FROM perkara_putusan pp WHERE pp.perkara_id = p.perkara_id AND pp.tanggal_minutasi IS NOT NULL)";
        break;
    case 'asal_usul_anak':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%asal usul anak%' OR LOWER(p.jenis_perkara_nama) LIKE '%penetapan asal usul%' OR LOWER(p.jenis_perkara_nama) LIKE '%asal-usul%')";
        break;
    case 'cerai_gugat':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%cerai gugat%' OR LOWER(p.jenis_perkara_nama) LIKE '%perceraian%' OR LOWER(p.jenis_perkara_nama) LIKE '%gugatan cerai%')";
        break;
    case 'cerai_talak':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%cerai talak%' OR LOWER(p.jenis_perkara_nama) LIKE '%talak%' OR LOWER(p.jenis_perkara_nama) LIKE '%permohonan cerai talak%')";
        break;
    case 'dispensasi_kawin':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%dispensasi%' OR LOWER(p.jenis_perkara_nama) LIKE '%izin kawin%')";
        break;
    case 'p3hp':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%p3hp%' OR LOWER(p.jenis_perkara_nama) LIKE '%p3ph%' OR LOWER(p.jenis_perkara_nama) LIKE '%penetapan ahli waris%' OR LOWER(p.jenis_perkara_nama) LIKE '%ahli waris%') AND LOWER(p.jenis_perkara_nama) NOT LIKE '%wali adhol%' AND LOWER(p.jenis_perkara_nama) NOT LIKE '%perwalian%'";
        break;
    case 'pengesahan_nikah':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%pengesahan%' OR LOWER(p.jenis_perkara_nama) LIKE '%istbat%' OR LOWER(p.jenis_perkara_nama) LIKE '%isbat%')";
        break;
    case 'perwalian':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%wali adhol%' OR LOWER(p.jenis_perkara_nama) LIKE '%perwalian%' OR LOWER(p.jenis_perkara_nama) LIKE '%penetapan wali%' OR LOWER(p.jenis_perkara_nama) LIKE '%pencabutan kekuasaan wali%' OR LOWER(p.jenis_perkara_nama) LIKE '%ganti rugi terhadap wali%') AND LOWER(p.jenis_perkara_nama) NOT LIKE '%ahli waris%' AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3hp%' AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3ph%'";
        break;
    case 'harta_bersama':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%harta bersama%' OR LOWER(p.jenis_perkara_nama) LIKE '%pembagian harta%' OR LOWER(p.jenis_perkara_nama) LIKE '%harta gono%')";
        break;
    case 'izin_poligami':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%poligami%' OR LOWER(p.jenis_perkara_nama) LIKE '%izin poligami%' OR LOWER(p.jenis_perkara_nama) LIKE '%berpoligami%')";
        break;
    case 'kewarisan':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%kewarisan%' OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa waris%' OR LOWER(p.jenis_perkara_nama) LIKE '%warisan%') AND LOWER(p.jenis_perkara_nama) NOT LIKE '%penetapan ahli waris%' AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3hp%' AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3ph%'";
        break;
    case 'wakaf':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%wakaf%' OR LOWER(p.jenis_perkara_nama) LIKE '%perwakafan%' OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa wakaf%')";
        break;
    case 'penguasaan_anak':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%penguasaan anak%' OR LOWER(p.jenis_perkara_nama) LIKE '%hadhanah%' OR LOWER(p.jenis_perkara_nama) LIKE '%hadlonah%' OR LOWER(p.jenis_perkara_nama) LIKE '%hak asuh%')";
        break;
    case 'hibah':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%hibah%' OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa hibah%' OR LOWER(p.jenis_perkara_nama) LIKE '%pembatalan hibah%')";
        break;
    case 'pembatalan_perkawinan':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%pembatalan perkawinan%' OR LOWER(p.jenis_perkara_nama) LIKE '%pembatalan nikah%' OR LOWER(p.jenis_perkara_nama) LIKE '%penolakan perkawinan%' OR LOWER(p.jenis_perkara_nama) LIKE '%pencegahan perkawinan%')";
        break;
    case 'ekonomi_syariah':
        $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%ekonomi syariah%' OR LOWER(p.jenis_perkara_nama) LIKE '%ekonomi syari%' OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa ekonomi syariah%')";
        break;
    case 'lain_lain':
    $count_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%lain%')";
    break;
}

// Tambahkan search filter ke count_query jika ada
if (!empty($search)) {
    $search_escaped = mysql_real_escape_string($search);
    $count_query .= " AND (p.nomor_perkara LIKE '%$search_escaped%' 
                    OR p.pihak1_text LIKE '%$search_escaped%' 
                    OR p.pihak2_text LIKE '%$search_escaped%' 
                    OR p.jenis_perkara_nama LIKE '%$search_escaped%'
                    OR EXISTS (SELECT 1 FROM perkara_hakim_pn ph WHERE ph.perkara_id = p.perkara_id AND ph.urutan = '1' AND ph.aktif = 'Y' AND ph.hakim_nama LIKE '%$search_escaped%'))";
}

$count_result = mysql_query($count_query);
if (!$count_result) {
    die("Count query gagal: " . mysql_error() . "<br>Query: " . $count_query);
}
$count_row = mysql_fetch_assoc($count_result);
$total_data = $count_row['total'];
$total_pages = ceil($total_data / $items_per_page);

// Query dengan pagination
$final_query = $base_query . " ORDER BY p.tanggal_pendaftaran DESC, p.perkara_id DESC LIMIT $offset, $items_per_page";
$result = mysql_query($final_query);
if (!$result) {
    die("Query gagal: " . mysql_error() . "<br>Query: " . $final_query);
}

// =============================
// data spesipik untuk kategori
// =============================
$analysis_query = "
    SELECT 
        COUNT(DISTINCT CASE WHEN EXISTS (SELECT 1 FROM perkara_efiling_id pe WHERE pe.perkara_id = p.perkara_id AND pe.efiling_id IS NOT NULL AND pe.efiling_id != '') THEN p.perkara_id END) as ecourt_count,
        COUNT(DISTINCT CASE WHEN NOT EXISTS (SELECT 1 FROM perkara_efiling_id pe WHERE pe.perkara_id = p.perkara_id AND pe.efiling_id IS NOT NULL AND pe.efiling_id != '') THEN p.perkara_id END) as manual_count,
        COUNT(DISTINCT CASE WHEN EXISTS (SELECT 1 FROM perkara_putusan pp WHERE pp.perkara_id = p.perkara_id AND pp.tanggal_putusan IS NOT NULL) THEN p.perkara_id END) as putus_count,
        COUNT(DISTINCT CASE WHEN EXISTS (SELECT 1 FROM perkara_putusan pp WHERE pp.perkara_id = p.perkara_id AND pp.tanggal_minutasi IS NOT NULL) THEN p.perkara_id END) as minutasi_count,
        COUNT(DISTINCT CASE WHEN EXISTS (SELECT 1 FROM perkara_efiling_id pe WHERE pe.perkara_id = p.perkara_id AND pe.efiling_id IS NOT NULL AND pe.efiling_id != '') THEN p.perkara_id END) as efiling_count,
        COUNT(DISTINCT p.perkara_id) as total_count
    FROM perkara p
    WHERE p.tanggal_pendaftaran BETWEEN '" . mysql_real_escape_string($tanggal_mulai) . "' 
          AND '" . mysql_real_escape_string($tanggal_akhir) . "'
";

// filter kategori yang sama
switch($kategori) {
    case 'gugatan':
        $analysis_query .= " AND p.nomor_perkara LIKE '%Pdt.G%'";
        break;
    case 'permohonan':
        $analysis_query .= " AND p.nomor_perkara LIKE '%Pdt.P%'";
        break;
    case 'ecourt':
        $analysis_query .= " AND EXISTS (SELECT 1 FROM perkara_efiling_id pe WHERE pe.perkara_id = p.perkara_id AND pe.efiling_id IS NOT NULL AND pe.efiling_id != '')";
        break;
    case 'manual':
        $analysis_query .= " AND NOT EXISTS (SELECT 1 FROM perkara_efiling_id pe WHERE pe.perkara_id = p.perkara_id AND pe.efiling_id IS NOT NULL AND pe.efiling_id != '')";
        break;
    case 'putus':
        $analysis_query .= " AND EXISTS (SELECT 1 FROM perkara_putusan pp WHERE pp.perkara_id = p.perkara_id AND pp.tanggal_putusan IS NOT NULL AND pp.tanggal_minutasi IS NULL)";
        break;
    case 'minutasi':
        $analysis_query .= " AND EXISTS (SELECT 1 FROM perkara_putusan pp WHERE pp.perkara_id = p.perkara_id AND pp.tanggal_minutasi IS NOT NULL)";
        break;
    case 'asal_usul_anak':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%asal usul anak%' OR LOWER(p.jenis_perkara_nama) LIKE '%penetapan asal usul%' OR LOWER(p.jenis_perkara_nama) LIKE '%asal-usul%')";
        break;
    case 'cerai_gugat':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%cerai gugat%' OR LOWER(p.jenis_perkara_nama) LIKE '%perceraian%' OR LOWER(p.jenis_perkara_nama) LIKE '%gugatan cerai%')";
        break;
    case 'cerai_talak':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%cerai talak%' OR LOWER(p.jenis_perkara_nama) LIKE '%talak%' OR LOWER(p.jenis_perkara_nama) LIKE '%permohonan cerai talak%')";
        break;
    case 'dispensasi_kawin':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%dispensasi%' OR LOWER(p.jenis_perkara_nama) LIKE '%izin kawin%')";
        break;
    case 'p3hp':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%p3hp%' OR LOWER(p.jenis_perkara_nama) LIKE '%p3ph%' OR LOWER(p.jenis_perkara_nama) LIKE '%penetapan ahli waris%' OR LOWER(p.jenis_perkara_nama) LIKE '%ahli waris%') AND LOWER(p.jenis_perkara_nama) NOT LIKE '%wali adhol%' AND LOWER(p.jenis_perkara_nama) NOT LIKE '%perwalian%'";
        break;
    case 'pengesahan_nikah':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%pengesahan%' OR LOWER(p.jenis_perkara_nama) LIKE '%istbat%' OR LOWER(p.jenis_perkara_nama) LIKE '%isbat%')";
        break;
    case 'perwalian':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%wali adhol%' OR LOWER(p.jenis_perkara_nama) LIKE '%perwalian%' OR LOWER(p.jenis_perkara_nama) LIKE '%penetapan wali%' OR LOWER(p.jenis_perkara_nama) LIKE '%pencabutan kekuasaan wali%' OR LOWER(p.jenis_perkara_nama) LIKE '%ganti rugi terhadap wali%') AND LOWER(p.jenis_perkara_nama) NOT LIKE '%ahli waris%' AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3hp%' AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3ph%'";
        break;
    case 'harta_bersama':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%harta bersama%' OR LOWER(p.jenis_perkara_nama) LIKE '%pembagian harta%' OR LOWER(p.jenis_perkara_nama) LIKE '%harta gono%')";
        break;
    case 'izin_poligami':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%poligami%' OR LOWER(p.jenis_perkara_nama) LIKE '%izin poligami%' OR LOWER(p.jenis_perkara_nama) LIKE '%berpoligami%')";
        break;
    case 'kewarisan':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%kewarisan%' OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa waris%' OR LOWER(p.jenis_perkara_nama) LIKE '%warisan%') AND LOWER(p.jenis_perkara_nama) NOT LIKE '%penetapan ahli waris%' AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3hp%' AND LOWER(p.jenis_perkara_nama) NOT LIKE '%p3ph%'";
        break;
    case 'wakaf':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%wakaf%' OR LOWER(p.jenis_perkara_nama) LIKE '%perwakafan%' OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa wakaf%')";
        break;
    case 'penguasaan_anak':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%penguasaan anak%' OR LOWER(p.jenis_perkara_nama) LIKE '%hadhanah%' OR LOWER(p.jenis_perkara_nama) LIKE '%hadlonah%' OR LOWER(p.jenis_perkara_nama) LIKE '%hak asuh%')";
        break;
    case 'hibah':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%hibah%' OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa hibah%' OR LOWER(p.jenis_perkara_nama) LIKE '%pembatalan hibah%')";
        break;
    case 'pembatalan_perkawinan':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%pembatalan perkawinan%' OR LOWER(p.jenis_perkara_nama) LIKE '%pembatalan nikah%' OR LOWER(p.jenis_perkara_nama) LIKE '%penolakan perkawinan%' OR LOWER(p.jenis_perkara_nama) LIKE '%pencegahan perkawinan%')";
        break;
    case 'ekonomi_syariah':
        $analysis_query .= " AND (LOWER(p.jenis_perkara_nama) LIKE '%ekonomi syariah%' OR LOWER(p.jenis_perkara_nama) LIKE '%ekonomi syari%' OR LOWER(p.jenis_perkara_nama) LIKE '%sengketa ekonomi syariah%')";
        break;
}

// search filter
if (!empty($search)) {
    $search_escaped = mysql_real_escape_string($search);
    $analysis_query .= " AND (p.nomor_perkara LIKE '%$search_escaped%' 
                    OR p.pihak1_text LIKE '%$search_escaped%' 
                    OR p.pihak2_text LIKE '%$search_escaped%' 
                    OR p.jenis_perkara_nama LIKE '%$search_escaped%'
                    OR EXISTS (SELECT 1 FROM perkara_hakim_pn ph WHERE ph.perkara_id = p.perkara_id AND ph.urutan = '1' AND ph.aktif = 'Y' AND ph.hakim_nama LIKE '%$search_escaped%'))";
}

$analysis_result = mysql_query($analysis_query);
$analysis_data = array(
    'ecourt_count' => 0, 
    'manual_count' => 0, 
    'putus_count' => 0, 
    'minutasi_count' => 0, 
    'efiling_count' => 0,
    'total_count' => 0
);

if ($analysis_result) {
    $analysis_data = mysql_fetch_assoc($analysis_result);
} else {
    die("Analysis query gagal: " . mysql_error());
}

// ===========================
// Verifikasi konsistensi data
// ===========================
if (isset($_GET['verify_detail']) && $_GET['verify_detail'] == '1') {
    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin: 20px; border-radius: 8px;'>";
    echo "<h3 style='margin-top: 0; color: #856404;'><i class='fas fa-exclamation-triangle'></i> Verifikasi Data Detail</h3>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'><th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>Metrik</th><th style='padding: 10px; border: 1px solid #dee2e6;'>Jumlah</th></tr>";
    echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'>Total dari COUNT Query</td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . $total_data . "</td></tr>";
    echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'>Total dari Analysis Query</td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . $analysis_data['total_count'] . "</td></tr>";
    echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'>E-Court</td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>" . $analysis_data['ecourt_count'] . "</td></tr>";
    echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'>Manual</td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>" . $analysis_data['manual_count'] . "</td></tr>";
    echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'>Putus</td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>" . $analysis_data['putus_count'] . "</td></tr>";
    echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'>Minutasi</td><td style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>" . $analysis_data['minutasi_count'] . "</td></tr>";
    echo "</table>";
    
    if ($total_data == $analysis_data['total_count']) {
        echo "<div style='background: #d1e7dd; color: #0f5132; padding: 15px; margin-top: 20px; border-radius: 5px;'><strong>✓ Data Konsisten</strong></div>";
    } else {
        echo "<div style='background: #f8d7da; color: #842029; padding: 15px; margin-top: 20px; border-radius: 5px;'><strong>✗ Warning: Inkonsistensi!</strong></div>";
    }
    echo "<p style='margin-top: 15px; color: #856404;'><small>Tambahkan <code>?verify_detail=1</code> untuk melihat verifikasi</small></p>";
    echo "</div>";
}

// Logika penentuan kolom yang adaptif berdasarkan kategori dan data
$show_columns = array(
    'cara_daftar' => false,
    'efiling_id' => false,
    'tanggal_putusan' => false,
    'tanggal_minutasi' => false
);

switch($kategori) {
    case 'ecourt':
        if ($analysis_data['efiling_count'] > 0) {
            $show_columns['efiling_id'] = true;
        }
        if ($analysis_data['putus_count'] > 0) $show_columns['tanggal_putusan'] = true;
        if ($analysis_data['minutasi_count'] > 0) $show_columns['tanggal_minutasi'] = true;
        break;
        
    case 'manual':
        if ($analysis_data['putus_count'] > 0) $show_columns['tanggal_putusan'] = true;
        if ($analysis_data['minutasi_count'] > 0) $show_columns['tanggal_minutasi'] = true;
        break;
        
    case 'putus':
        if ($analysis_data['ecourt_count'] > 0 && $analysis_data['manual_count'] > 0) {
            $show_columns['cara_daftar'] = true;
        }
        $show_columns['tanggal_putusan'] = true;
        if ($analysis_data['minutasi_count'] > 0) $show_columns['tanggal_minutasi'] = true;
        break;
        
    case 'minutasi':
        if ($analysis_data['ecourt_count'] > 0 && $analysis_data['manual_count'] > 0) {
            $show_columns['cara_daftar'] = true;
        }
        if ($analysis_data['putus_count'] > 0) $show_columns['tanggal_putusan'] = true;
        $show_columns['tanggal_minutasi'] = true;
        break;
        
    case 'asal_usul_anak':
    case 'cerai_gugat':
    case 'cerai_talak':
    case 'dispensasi_kawin':
    case 'p3hp':
    case 'pengesahan_nikah':
    case 'perwalian':
    case 'gugatan':
    case 'permohonan':
    case 'harta_bersama':
    case 'izin_poligami':
    case 'kewarisan':
    case 'wakaf':
    case 'penguasaan_anak':
    case 'hibah':
    case 'pembatalan_perkawinan':
    case 'ekonomi_syariah':
        if ($analysis_data['ecourt_count'] > 0 && $analysis_data['manual_count'] > 0) {
            $show_columns['cara_daftar'] = true;
        } elseif ($analysis_data['ecourt_count'] > 0 && $analysis_data['manual_count'] == 0) {
            if ($analysis_data['efiling_count'] > 0) {
                $show_columns['efiling_id'] = true;
            }
        }
        if ($analysis_data['putus_count'] > 0) $show_columns['tanggal_putusan'] = true;
        if ($analysis_data['minutasi_count'] > 0) $show_columns['tanggal_minutasi'] = true;
        break;
        
    default:
        if ($analysis_data['ecourt_count'] > 0 && $analysis_data['manual_count'] > 0) {
            $show_columns['cara_daftar'] = true;
        }
        if ($analysis_data['putus_count'] > 0) $show_columns['tanggal_putusan'] = true;
        if ($analysis_data['minutasi_count'] > 0) $show_columns['tanggal_minutasi'] = true;
        break;
}

$date1 = strtotime($tanggal_mulai);
$date2 = strtotime($tanggal_akhir);
$jumlah_hari = floor(($date2 - $date1) / (60 * 60 * 24)) + 1;

function getUrlParams($exclude = array()) {
    $params = array();
    $current_params = array('kategori', 'tanggal', 'tanggal_mulai', 'tanggal_akhir', 'search', 'page');
    
    foreach ($current_params as $param) {
        if (isset($_GET[$param]) && !in_array($param, $exclude)) {
            $params[$param] = $_GET[$param];
        }
    }
    
    return http_build_query($params);
}

function getTotalColumns($show_columns) {
    $base_columns = 7;
    $total = $base_columns;
    
    foreach ($show_columns as $show) {
        if ($show) $total++;
    }
    
    return $total;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Font Awesome -->
    <link href="../assets/css/all.min.css" rel="stylesheet">
    
    <!-- internal CSS -->
    <link href="../assets/css/main.css" rel="stylesheet">
</head>

<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="../index.php">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <span>/</span>
        <span>Detail <?php echo $judul_kategori; ?></span>
    </div>

    <!-- Header Section -->
    <div class="page-header">
        <h1>
            <i class="fas fa-list-alt" style="margin-right: 15px;"></i>
            Detail Perkara: <?php echo $judul_kategori; ?>
        </h1>
        <p>
            <?php if ($mode == 'single'): ?>
                Tanggal: <?php echo formatTanggalIndonesia($tanggal_pilih); ?>
            <?php else: ?>
                Periode: <?php echo formatTanggalIndonesia($tanggal_mulai); ?> - <?php echo formatTanggalIndonesia($tanggal_akhir); ?> (<?php echo $jumlah_hari; ?> hari)
            <?php endif; ?>
            • Total: <strong><?php echo $total_data; ?> perkara</strong>
            <?php if (!empty($search)): ?>
                • Pencarian: "<strong><?php echo htmlspecialchars($search); ?></strong>"
            <?php endif; ?>
        </p>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <h4>
            <i class="fas fa-filter" style="margin-right: 8px;"></i>
            Filter Periode
        </h4>
        
        <!-- Tab Mode -->
        <div class="mode-tabs">
            <button type="button" class="mode-tab <?php echo $mode == 'single' ? 'active' : ''; ?>" onclick="switchMode('single')">
                <i class="fas fa-calendar-day"></i> Harian
            </button>
            <button type="button" class="mode-tab <?php echo $mode == 'range' ? 'active' : ''; ?>" onclick="switchMode('range')">
                <i class="fas fa-calendar-week"></i> Periode
            </button>
        </div>
        
        <!-- Form Single Date -->
        <form method="GET" action="" id="singleDateForm" class="date-form <?php echo $mode == 'single' ? 'active' : ''; ?>">
            <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategori); ?>">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <div class="form-group">
                <label for="tanggal">Tanggal:</label>
                <input type="date" name="tanggal" id="tanggal" value="<?php echo $tanggal_pilih; ?>">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
            </div>
        </form>
        
        <!-- Form Date Range -->
        <form method="GET" action="" id="rangeDateForm" class="date-form <?php echo $mode == 'range' ? 'active' : ''; ?>">
            <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategori); ?>">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <div class="form-group">
                <div>
                    <label for="tanggal_mulai">Tanggal Mulai:</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="<?php echo $tanggal_mulai; ?>">
                </div>
                <div>
                    <label for="tanggal_akhir">Tanggal Akhir:</label>
                    <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="<?php echo $tanggal_akhir; ?>">
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
            </div>
        </form>

        <!-- Quick Date Buttons -->
        <div id="quickButtons" class="quick-buttons" style="display: <?php echo $mode == 'range' ? 'flex' : 'none'; ?>;">
            <a href="?kategori=<?php echo $kategori; ?>&tanggal_mulai=<?php echo date('Y-m-d'); ?>&tanggal_akhir=<?php echo date('Y-m-d'); ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="quick-btn">
               Hari Ini
            </a>
            <a href="?kategori=<?php echo $kategori; ?>&tanggal_mulai=<?php echo date('Y-m-d', strtotime('-6 days')); ?>&tanggal_akhir=<?php echo date('Y-m-d'); ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="quick-btn">
               7 Hari Terakhir
            </a>
            <a href="?kategori=<?php echo $kategori; ?>&tanggal_mulai=<?php echo date('Y-m-01'); ?>&tanggal_akhir=<?php echo date('Y-m-d'); ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="quick-btn">
               Bulan Ini
            </a>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <form method="GET" action="" class="search-form">
            <?php
            foreach ($_GET as $key => $value) {
                if ($key != 'search' && $key != 'page') {
                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                }
            }
            ?>
            <div class="search-input-wrapper">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Cari nomor perkara, pihak, hakim, atau jenis perkara..." 
                       class="search-input">
                <i class="fas fa-search search-icon"></i>
            </div>
            <button type="submit" class="btn-search">
                <i class="fas fa-search"></i> Cari
            </button>
            <?php if (!empty($search)): ?>
            <a href="?<?php echo getUrlParams(array('search', 'page')); ?>" class="btn-clear">
                <i class="fas fa-times"></i> Clear
            </a>
            <?php endif; ?>
        </form>
        <div style="color: #6c757d; font-size: 14px; margin-top: 10px;">
            Menampilkan <?php echo $total_data > 0 ? ($offset + 1) : 0; ?>-<?php echo min($offset + $items_per_page, $total_data); ?> dari <?php echo $total_data; ?> data
        </div>
    </div>

    <!-- Pagination Top -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?<?php echo getUrlParams(array('page')); ?>&page=<?php echo $page - 1; ?>">
            <i class="fas fa-chevron-left"></i> Prev
        </a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        
        if ($start > 1): ?>
        <a href="?<?php echo getUrlParams(array('page')); ?>&page=1">1</a>
        <?php if ($start > 2): ?>
        <span style="padding: 8px 12px; color: #6c757d;">...</span>
        <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
        <a href="?<?php echo getUrlParams(array('page')); ?>&page=<?php echo $i; ?>" 
           class="<?php echo $i == $page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>

        <?php if ($end < $total_pages): ?>
        <?php if ($end < $total_pages - 1): ?>
        <span style="padding: 8px 12px; color: #6c757d;">...</span>
        <?php endif; ?>
        <a href="?<?php echo getUrlParams(array('page')); ?>&page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
        <?php endif; ?>

        <?php if ($page < $total_pages): ?>
        <a href="?<?php echo getUrlParams(array('page')); ?>&page=<?php echo $page + 1; ?>">
            Next <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Data Table -->
    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th data-col="no">No</th>
                    <th data-col="tanggal">Tanggal</th>
                    <th data-col="nomor">No. Perkara</th>
                    <th data-col="jenis">Jenis</th>
                    <th data-col="cara" style="<?php echo !$show_columns['cara_daftar'] ? 'display:none;' : ''; ?>">Cara Daftar</th>
                    <th data-col="pihak1">Pihak 1</th>
                    <th data-col="pihak2">Pihak 2</th>
                    <th data-col="hakim">Hakim PN</th>
                    <th data-col="efiling" style="<?php echo !$show_columns['efiling_id'] ? 'display:none;' : ''; ?>">E-filing ID</th>
                    <th data-col="status">Status</th>
                    <th data-col="tgl_putusan" style="<?php echo !$show_columns['tanggal_putusan'] ? 'display:none;' : ''; ?>">Tgl Putusan</th>
                    <th data-col="tgl_minutasi" style="<?php echo !$show_columns['tanggal_minutasi'] ? 'display:none;' : ''; ?>">Tgl Minutasi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = $offset + 1;
                if (mysql_num_rows($result) > 0):
                    while ($row = mysql_fetch_assoc($result)):
                        $jenis_perkara = 'Permohonan';
                        if (strpos($row['nomor_perkara'], 'Pdt.G') !== false) {
                            $jenis_perkara = 'Gugatan';
                        }
                ?>
                <tr class="perkara-row">
                    <td data-col="no" style="text-align: center; font-weight: bold;"><?php echo $no++; ?></td>
                    <td data-col="tanggal" style="text-align: center; white-space: nowrap;"><?php echo date('d/m/Y', strtotime($row['tanggal_pendaftaran'])); ?></td>
                    <td data-col="nomor" style="font-size: 12px; font-family: monospace;"><?php echo htmlspecialchars($row['nomor_perkara']); ?></td>
                    <td data-col="jenis" style="text-align: center;">
                        <span class="badge badge-<?php echo $jenis_perkara == 'Gugatan' ? 'gugatan' : 'permohonan'; ?>">
                            <?php echo $jenis_perkara; ?>
                        </span>
                    </td>
                    <td data-col="cara" style="text-align: center;<?php echo !$show_columns['cara_daftar'] ? ' display:none;' : ''; ?>">
                        <span class="badge badge-<?php echo $row['cara_daftar'] == 'e-court' ? 'ecourt' : 'manual'; ?>">
                            <?php echo $row['cara_daftar'] == 'e-court' ? 'E-Court' : 'Manual'; ?>
                        </span>
                    </td>
                    <td data-col="pihak1" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo htmlspecialchars(strip_tags(isset($row['pihak1_text']) ? $row['pihak1_text'] : '-')); ?>
                    </td>
                    <td data-col="pihak2" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo htmlspecialchars(strip_tags(isset($row['pihak2_text']) ? $row['pihak2_text'] : '-')); ?>
                    </td>
                    <td data-col="hakim"><?php echo htmlspecialchars(isset($row['hakim_nama']) && $row['hakim_nama'] != null ? $row['hakim_nama'] : '-'); ?></td>
                    <td data-col="efiling" style="text-align: center; font-size: 12px;<?php echo !$show_columns['efiling_id'] ? ' display:none;' : ''; ?>">
                        <?php echo htmlspecialchars(isset($row['efiling_id']) && $row['efiling_id'] != null ? $row['efiling_id'] : '-'); ?>
                    </td>
                    <td data-col="status" style="text-align: center;">
                        <?php
                        $status_badge = array(
                            'dalam_proses' => 'proses',
                            'putus' => 'putus',
                            'minutasi_selesai' => 'minutasi'
                        );
                        $status_labels = array(
                            'dalam_proses' => 'Dalam Proses',
                            'putus' => 'Putus',
                            'minutasi_selesai' => 'Minutasi Selesai'
                        );

                        $status = $row['status_perkara'];
                        $badge_class = isset($status_badge[$status]) ? $status_badge[$status] : 'manual';
                        $label = isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
                        ?>
                        <span class="badge badge-<?php echo $badge_class; ?>">
                            <?php echo $label; ?>
                        </span>
                    </td>
                    <td data-col="tgl_putusan" style="text-align: center; white-space: nowrap;<?php echo !$show_columns['tanggal_putusan'] ? ' display:none;' : ''; ?>">
                        <?php 
                        echo !empty($row['tanggal_putusan']) 
                            ? date('d/m/Y', strtotime($row['tanggal_putusan'])) 
                            : '-'; 
                        ?>
                    </td>
                    <td data-col="tgl_minutasi" style="text-align: center; white-space: nowrap;<?php echo !$show_columns['tanggal_minutasi'] ? ' display:none;' : ''; ?>">
                        <?php 
                        echo !empty($row['tanggal_minutasi']) 
                            ? date('d/m/Y', strtotime($row['tanggal_minutasi'])) 
                            : '-'; 
                        ?>
                    </td>
                </tr>
                <?php
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="<?php echo getTotalColumns($show_columns); ?>">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3 style="color: #7f8c8d; margin-bottom: 10px;">Tidak Ada Data</h3>
                            <p style="color: #95a5a6;">
                                <?php if (!empty($search)): ?>
                                    Tidak ditemukan hasil pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>"<br>
                                    untuk kategori <strong><?php echo $judul_kategori; ?></strong>
                                    <?php if ($mode == 'single'): ?>
                                        pada tanggal <?php echo formatTanggalIndonesia($tanggal_pilih); ?>
                                    <?php else: ?>
                                        periode <?php echo formatTanggalIndonesia($tanggal_mulai); ?> - <?php echo formatTanggalIndonesia($tanggal_akhir); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Tidak ditemukan perkara kategori <strong><?php echo $judul_kategori; ?></strong><br>
                                    <?php if ($mode == 'single'): ?>
                                        untuk tanggal <?php echo formatTanggalIndonesia($tanggal_pilih); ?>
                                    <?php else: ?>
                                        untuk periode <?php echo formatTanggalIndonesia($tanggal_mulai); ?> - <?php echo formatTanggalIndonesia($tanggal_akhir); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Bottom -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?<?php echo getUrlParams(array('page')); ?>&page=<?php echo $page - 1; ?>">
            <i class="fas fa-chevron-left"></i> Prev
        </a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        
        if ($start > 1): ?>
        <a href="?<?php echo getUrlParams(array('page')); ?>&page=1">1</a>
        <?php if ($start > 2): ?>
        <span style="padding: 8px 12px; color: #6c757d;">...</span>
        <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
        <a href="?<?php echo getUrlParams(array('page')); ?>&page=<?php echo $i; ?>" 
           class="<?php echo $i == $page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>

        <?php if ($end < $total_pages): ?>
        <?php if ($end < $total_pages - 1): ?>
        <span style="padding: 8px 12px; color: #6c757d;">...</span>
        <?php endif; ?>
        <a href="?<?php echo getUrlParams(array('page')); ?>&page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
        <?php endif; ?>

        <?php if ($page < $total_pages): ?>
        <a href="?<?php echo getUrlParams(array('page')); ?>&page=<?php echo $page + 1; ?>">
            Next <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($total_data > 0): ?>
    <!-- Summary Info -->
    <div class="summary-section">
        <h4 style="margin: 0 0 20px 0; color: #2c3e50; display: flex; align-items: center;">
            <i class="fas fa-chart-pie" style="margin-right: 10px; color: #3498db;"></i>
            Ringkasan Data <?php echo $judul_kategori; ?>
        </h4>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Total Perkara</div>
                <div class="summary-value"><?php echo $total_data; ?> perkara</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Halaman</div>
                <div class="summary-value" style="font-size: 16px;"><?php echo $page; ?> dari <?php echo $total_pages; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Periode</div>
                <div class="summary-value" style="font-size: 16px;">
                    <?php if ($mode == 'single'): ?>
                        <?php echo formatTanggalIndonesia($tanggal_pilih); ?>
                    <?php else: ?>
                        <?php echo $jumlah_hari; ?> hari
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Buttons -->
    <div class="export-section">
        <h5>
            <i class="fas fa-download" style="margin-right: 8px;"></i>
            Export Data
        </h5>
        <?php 
        $export_params = "kategori=" . $kategori;
        if ($mode == 'single') {
            $export_params .= "&tanggal=" . $tanggal_pilih;
        } else {
            $export_params .= "&tanggal_mulai=" . $tanggal_mulai . "&tanggal_akhir=" . $tanggal_akhir;
        }
        if (!empty($search)) {
            $export_params .= "&search=" . urlencode($search);
        }
        ?>
        <a href="export_pdf.php?<?php echo $export_params; ?>" class="btn-export btn-export-pdf" target="_blank">
            <i class="fas fa-file-pdf" style="margin-right: 8px;"></i> Export ke PDF
        </a>
        <a href="export_excel.php?<?php echo $export_params; ?>" class="btn-export btn-export-excel">
            <i class="fas fa-file-excel" style="margin-right: 8px;"></i> Export ke Excel
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- External JavaScript -->
<script src="../assets/js/js_detail.js"></script>

</body>
</html>
<?php
mysql_close($connection);
?>