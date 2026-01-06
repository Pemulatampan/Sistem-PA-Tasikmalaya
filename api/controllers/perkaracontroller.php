<?php
// api/controllers/PerkaraController.php
// Controller untuk endpoint Perkara
// Dependencies: Database, Response, Validator (loaded from index.php)

class PerkaraController {
    
    /**
     * GET /perkara/rekap
     * Mendapatkan rekap perkara berdasarkan range tanggal
     * 
     * Query params:
     * - tanggal_mulai (required) : YYYY-MM-DD
     * - tanggal_akhir (required) : YYYY-MM-DD
     */
    public function getRekap() {
        // Get query parameters
        $tanggal_mulai = isset($_GET['tanggal_mulai']) ? Validator::sanitize($_GET['tanggal_mulai']) : '';
        $tanggal_akhir = isset($_GET['tanggal_akhir']) ? Validator::sanitize($_GET['tanggal_akhir']) : '';
        
        // Validasi required fields
        if (empty($tanggal_mulai) || empty($tanggal_akhir)) {
            Response::error('Parameter tanggal_mulai dan tanggal_akhir harus diisi', 400);
        }
        
        // Validasi format dan range tanggal
        $errors = Validator::validateDateRange($tanggal_mulai, $tanggal_akhir);
        if (!empty($errors)) {
            Response::error('Validasi gagal', 400, $errors);
        }
        
        // Escape untuk SQL
        $tanggal_mulai_escaped = Database::escape($tanggal_mulai);
        $tanggal_akhir_escaped = Database::escape($tanggal_akhir);
        
        // Query rekap (dari dokumen yang kamu kirim)
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
            WHERE p.tanggal_pendaftaran BETWEEN '$tanggal_mulai_escaped' 
                  AND '$tanggal_akhir_escaped'
            ORDER BY p.tanggal_pendaftaran DESC, p.nomor_perkara
        ";
        
        // Execute query
        $result = Database::query($sql_rekap);
        
        // Fetch data
        $data_rekap = array();
        while ($row = mysql_fetch_assoc($result)) {
            $data_rekap[] = $row;
        }
        
        // Response sukses
        Response::success('Data rekap berhasil diambil', array(
            'periode' => array(
                'tanggal_mulai' => $tanggal_mulai,
                'tanggal_akhir' => $tanggal_akhir,
                'jumlah_hari' => floor((strtotime($tanggal_akhir) - strtotime($tanggal_mulai)) / (60 * 60 * 24)) + 1
            ),
            'total_records' => count($data_rekap),
            'data' => $data_rekap
        ));
    }
    
    /**
     * GET /perkara/rekap/statistik
     * Mendapatkan statistik rekap (E-Court, Manual, Putus, Minutasi)
     * 
     * Query params:
     * - tanggal_mulai (required) : YYYY-MM-DD
     * - tanggal_akhir (required) : YYYY-MM-DD
     */
    public function getStatistik() {
        // Get query parameters
        $tanggal_mulai = isset($_GET['tanggal_mulai']) ? Validator::sanitize($_GET['tanggal_mulai']) : '';
        $tanggal_akhir = isset($_GET['tanggal_akhir']) ? Validator::sanitize($_GET['tanggal_akhir']) : '';
        
        // Validasi required fields
        if (empty($tanggal_mulai) || empty($tanggal_akhir)) {
            Response::error('Parameter tanggal_mulai dan tanggal_akhir harus diisi', 400);
        }
        
        // Validasi format dan range tanggal
        $errors = Validator::validateDateRange($tanggal_mulai, $tanggal_akhir);
        if (!empty($errors)) {
            Response::error('Validasi gagal', 400, $errors);
        }
        
        // Escape untuk SQL
        $tanggal_mulai_escaped = Database::escape($tanggal_mulai);
        $tanggal_akhir_escaped = Database::escape($tanggal_akhir);
        
        // Query untuk hitung statistik
        $sql_stats = "
            SELECT 
                COUNT(DISTINCT p.perkara_id) as total_perkara,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT pe2.efiling_id 
                          FROM perkara_efiling_id pe2 
                          WHERE pe2.perkara_id = p.perkara_id 
                          LIMIT 1) IS NOT NULL 
                         AND (SELECT pe2.efiling_id 
                              FROM perkara_efiling_id pe2 
                              WHERE pe2.perkara_id = p.perkara_id 
                              LIMIT 1) != '' 
                    THEN p.perkara_id 
                END) as e_court,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT pe2.efiling_id 
                          FROM perkara_efiling_id pe2 
                          WHERE pe2.perkara_id = p.perkara_id 
                          LIMIT 1) IS NULL 
                         OR (SELECT pe2.efiling_id 
                             FROM perkara_efiling_id pe2 
                             WHERE pe2.perkara_id = p.perkara_id 
                             LIMIT 1) = '' 
                    THEN p.perkara_id 
                END) as manual,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT pp2.tanggal_putusan 
                          FROM perkara_putusan pp2 
                          WHERE pp2.perkara_id = p.perkara_id 
                          LIMIT 1) IS NOT NULL 
                         AND (SELECT pp2.tanggal_putusan 
                              FROM perkara_putusan pp2 
                              WHERE pp2.perkara_id = p.perkara_id 
                              LIMIT 1) != '' 
                    THEN p.perkara_id 
                END) as putus,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT pp2.tanggal_minutasi 
                          FROM perkara_putusan pp2 
                          WHERE pp2.perkara_id = p.perkara_id 
                          LIMIT 1) IS NOT NULL 
                         AND (SELECT pp2.tanggal_minutasi 
                              FROM perkara_putusan pp2 
                              WHERE pp2.perkara_id = p.perkara_id 
                              LIMIT 1) != '' 
                    THEN p.perkara_id 
                END) as minutasi
            FROM perkara p
            WHERE p.tanggal_pendaftaran BETWEEN '$tanggal_mulai_escaped' 
                  AND '$tanggal_akhir_escaped'
        ";
        
        $result = Database::query($sql_stats);
        $stats = mysql_fetch_assoc($result);
        
        // Hitung dalam proses
        $dalam_proses = (int)$stats['total_perkara'] - (int)$stats['putus'] - (int)$stats['minutasi'];
        
        // Response sukses
        Response::success('Statistik berhasil diambil', array(
            'periode' => array(
                'tanggal_mulai' => $tanggal_mulai,
                'tanggal_akhir' => $tanggal_akhir,
                'jumlah_hari' => floor((strtotime($tanggal_akhir) - strtotime($tanggal_mulai)) / (60 * 60 * 24)) + 1
            ),
            'statistik' => array(
                'total_perkara' => (int)$stats['total_perkara'],
                'cara_daftar' => array(
                    'e_court' => (int)$stats['e_court'],
                    'manual' => (int)$stats['manual']
                ),
                'status' => array(
                    'putus' => (int)$stats['putus'],
                    'minutasi' => (int)$stats['minutasi'],
                    'dalam_proses' => $dalam_proses
                )
            )
        ));
    }
    
    /**
     * GET /perkara/detail/{id}
     * Mendapatkan detail perkara berdasarkan ID
     * 
     * @param string $id - Perkara ID
     */
    public function getDetail($id) {
        // Validasi ID
        if (!Validator::isValidId($id)) {
            Response::error('ID perkara tidak valid', 400);
        }
        
        // Escape ID
        $id_escaped = Database::escape($id);
        
        // Query detail perkara
        $sql_detail = "
            SELECT 
                p.*,
                (SELECT pe2.efiling_id 
                 FROM perkara_efiling_id pe2 
                 WHERE pe2.perkara_id = p.perkara_id 
                 LIMIT 1) as efiling_id,
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
                (SELECT pp2.amar_putusan 
                 FROM perkara_putusan pp2 
                 WHERE pp2.perkara_id = p.perkara_id 
                 LIMIT 1) as amar_putusan
            FROM perkara p
            WHERE p.perkara_id = '$id_escaped'
            LIMIT 1
        ";
        
        $result = Database::query($sql_detail);
        
        // Cek apakah data ditemukan
        if (mysql_num_rows($result) == 0) {
            Response::error('Data perkara tidak ditemukan', 404);
        }
        
        $detail = mysql_fetch_assoc($result);
        
        // Response sukses
        Response::success('Detail perkara berhasil diambil', $detail);
    }
}