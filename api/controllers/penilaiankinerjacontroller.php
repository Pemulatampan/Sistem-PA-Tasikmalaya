<?php
// api/controllers/PenilaianKinerjaController.php
// Controller untuk endpoint Penilaian Kinerja
// Dependencies: Database, Response, Validator, SimpleCache, getDataKinerjaOptimized()

// Load dependencies di luar class
require_once '../config/cache_helper.php';
require_once '../penilaian_kinerja/functions/kinerja_optimized.php';

class PenilaianKinerjaController {
    
    
    /**
     * GET /api/penilaian-kinerja
     * Mendapatkan data penilaian kinerja berdasarkan tahun dan triwulan
     * Menggunakan cache untuk performa optimal
     * 
     * Query params:
     * - tahun (required) : YYYY
     * - triwulan (required) : 1, 2, 3, atau 4
     */
    public function getData() {
        // Get query parameters
        $tahun = isset($_GET['tahun']) ? Validator::sanitize($_GET['tahun']) : '';
        $triwulan = isset($_GET['triwulan']) ? Validator::sanitize($_GET['triwulan']) : '';
        
        // Validasi required fields
        if (empty($tahun) || empty($triwulan)) {
            Response::error('Parameter tahun dan triwulan harus diisi', 400);
        }
        
        // Validasi format tahun (4 digit)
        if (!preg_match('/^\d{4}$/', $tahun)) {
            Response::error('Format tahun tidak valid (gunakan YYYY)', 400);
        }
        
        // Validasi triwulan (1-4)
        if (!in_array($triwulan, array('1', '2', '3', '4'))) {
            Response::error('Triwulan harus antara 1-4', 400);
        }
        
        // Hitung bulan awal dan akhir triwulan
        $bulan_awal = (($triwulan - 1) * 3) + 1;
        $bulan_akhir = $bulan_awal + 2;
        $tanggal_awal = $tahun . "-" . str_pad($bulan_awal, 2, '0', STR_PAD_LEFT) . "-01";
        $tanggal_akhir = date("Y-m-t", strtotime($tahun . "-" . str_pad($bulan_akhir, 2, '0', STR_PAD_LEFT) . "-01"));
        
        // Inisialisasi cache (TTL 1 jam = 3600 detik)
        $cache = new SimpleCache('../assets/cache', 3600);
        $cache_key = "api_kinerja_{$tahun}_{$triwulan}";
        
        // Cek apakah ada parameter untuk clear cache
        if (isset($_GET['clear_cache']) && $_GET['clear_cache'] == '1') {
            $cache->delete($cache_key);
        }
        
        // Ambil data dari cache
        $data_kinerja = $cache->get($cache_key);
        
        if ($data_kinerja === null) {
            // Data belum ada di cache, ambil dari database
            
            // Load function getDataKinerjaOptimized
            require_once '../penilaian_kinerja/functions/kinerja_optimized.php';
            
            // Get PDO connections
            $pdo_sipp = Database::getPDO();
            $pdo_badilag = Database::getPDOBadilag();
            
            // Ambil data dari database
            $data_kinerja = getDataKinerjaOptimized($pdo_sipp, $pdo_badilag, $tanggal_awal, $tanggal_akhir);
            
            // Simpan ke cache
            $cache->set($cache_key, $data_kinerja);
        }
        
        // Response sukses dengan informasi cache
        $cache_info = $cache->getInfo($cache_key);
        
        Response::success('Data penilaian kinerja berhasil diambil', array(
            'periode' => array(
                'tahun' => (int)$tahun,
                'triwulan' => (int)$triwulan,
                'tanggal_awal' => $tanggal_awal,
                'tanggal_akhir' => $tanggal_akhir
            ),
            'cache_info' => array(
                'from_cache' => $cache_info['exists'] && !$cache_info['expired'],
                'age_seconds' => $cache_info['age'],
                'ttl_seconds' => $cache_info['ttl']
            ),
            'data' => $data_kinerja
        ));
    }
    
    /**
     * GET /api/penilaian-kinerja/summary
     * Mendapatkan ringkasan nilai penilaian kinerja
     * 
     * Query params:
     * - tahun (required) : YYYY
     * - triwulan (required) : 1, 2, 3, atau 4
     */
    public function getSummary() {
        // Get query parameters
        $tahun = isset($_GET['tahun']) ? Validator::sanitize($_GET['tahun']) : '';
        $triwulan = isset($_GET['triwulan']) ? Validator::sanitize($_GET['triwulan']) : '';
        
        // Validasi required fields
        if (empty($tahun) || empty($triwulan)) {
            Response::error('Parameter tahun dan triwulan harus diisi', 400);
        }
        
        // Validasi format
        if (!preg_match('/^\d{4}$/', $tahun)) {
            Response::error('Format tahun tidak valid (gunakan YYYY)', 400);
        }
        
        if (!in_array($triwulan, array('1', '2', '3', '4'))) {
            Response::error('Triwulan harus antara 1-4', 400);
        }
        
        // Hitung bulan awal dan akhir triwulan
        $bulan_awal = (($triwulan - 1) * 3) + 1;
        $bulan_akhir = $bulan_awal + 2;
        $tanggal_awal = $tahun . "-" . str_pad($bulan_awal, 2, '0', STR_PAD_LEFT) . "-01";
        $tanggal_akhir = date("Y-m-t", strtotime($tahun . "-" . str_pad($bulan_akhir, 2, '0', STR_PAD_LEFT) . "-01"));
        
        // Ambil data dari cache atau database (sama seperti getData)
        $cache = new SimpleCache('../assets/cache', 3600);
        $cache_key = "api_kinerja_{$tahun}_{$triwulan}";
        $data_kinerja = $cache->get($cache_key);
        
        if ($data_kinerja === null) {
            require_once '../penilaian_kinerja/functions/kinerja_optimized.php';
            $pdo_sipp = Database::getPDO();
            $pdo_badilag = Database::getPDOBadilag();
            $data_kinerja = getDataKinerjaOptimized($pdo_sipp, $pdo_badilag, $tanggal_awal, $tanggal_akhir);
            $cache->set($cache_key, $data_kinerja);
        }
        
        // Return hanya summary nilai
        Response::success('Summary penilaian kinerja berhasil diambil', array(
            'periode' => array(
                'tahun' => (int)$tahun,
                'triwulan' => (int)$triwulan,
                'tanggal_awal' => $tanggal_awal,
                'tanggal_akhir' => $tanggal_akhir
            ),
            'total_perkara' => $data_kinerja['total_perkara'],
            'nilai' => array(
                'kinerja' => $data_kinerja['nilai_kinerja'],
                'kepatuhan' => $data_kinerja['nilai_kepatuhan'],
                'kelengkapan' => $data_kinerja['nilai_kelengkapan'],
                'kesesuaian' => $data_kinerja['nilai_kesesuaian'],
                'akhir' => $data_kinerja['nilai_akhir']
            )
        ));
    }
}
?>