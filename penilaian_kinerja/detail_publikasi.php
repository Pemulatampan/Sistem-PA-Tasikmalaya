<?php
// Include konfigurasi database
include '../config/config.php';

// Parameter
$type = isset($_GET['type']) ? $_GET['type'] : 'putus';
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$triwulan = isset($_GET['triwulan']) ? $_GET['triwulan'] : ceil(date('n') / 3);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

// Hitung bulan awal dan akhir triwulan
$bulan_awal = (($triwulan - 1) * 3) + 1;
$bulan_akhir = $bulan_awal + 2;
$tanggal_awal = "$tahun-" . str_pad($bulan_awal, 2, '0', STR_PAD_LEFT) . "-01";
$tanggal_akhir = date("Y-m-t", strtotime("$tahun-" . str_pad($bulan_akhir, 2, '0', STR_PAD_LEFT) . "-01"));

// Hitung offset
$offset = ($page - 1) * $per_page;

// Konfigurasi berdasarkan type
$config = array();
switch($type) {
    // KINERJA (50%)
    case 'putus':
        $config['title'] = 'Detail Putus';
        $config['desc'] = 'Perkara Yang Sudah Diputus (Bobot 20%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.tanggal_putusan IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pp.tanggal_putusan,
                pp.status_putusan_nama,
                DATEDIFF(pp.tanggal_putusan, p.tanggal_pendaftaran) as durasi_hari
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.tanggal_putusan IS NOT NULL
            ORDER BY pp.tanggal_putusan DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'minutasi':
        $config['title'] = 'Detail Minutasi';
        $config['desc'] = 'Perkara Yang Sudah Diminutasi (Bobot 15%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.tanggal_minutasi IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pp.tanggal_putusan,
                pp.tanggal_minutasi,
                DATEDIFF(pp.tanggal_minutasi, pp.tanggal_putusan) as selisih_hari
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.tanggal_minutasi IS NOT NULL
            ORDER BY pp.tanggal_minutasi DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'publikasi':
        $config['title'] = 'Detail Publikasi Putusan';
        $config['desc'] = 'Perkara Yang Sudah Dipublikasi (Bobot 15%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.amar_putusan_anonimisasi_dok IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pp.tanggal_putusan,
                pp.tanggal_minutasi,
                pp.amar_putusan_anonimisasi_dok as status_publikasi
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.amar_putusan_anonimisasi_dok IS NOT NULL
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'pendaftaran':
        $config['title'] = 'Detail Pendaftaran';
        $config['desc'] = 'Perkara Yang Terdaftar (Bobot 2%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT perkara_id) as total
            FROM perkara
            WHERE tanggal_pendaftaran BETWEEN :awal AND :akhir
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                perkara_id,
                nomor_perkara,
                tanggal_pendaftaran,
                jenis_perkara_nama,
                pihak1_text,
                pihak2_text,
                tahapan_terakhir_text,
                proses_terakhir_text
            FROM perkara
            WHERE tanggal_pendaftaran BETWEEN :awal AND :akhir
            ORDER BY tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'pen_pmh':
        $config['title'] = 'Detail Penetapan PMH';
        $config['desc'] = 'Perkara Dengan Penetapan Mediasi (Bobot 2.5%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_mediasi pm ON p.perkara_id = pm.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pm.penetapan_tanggal_mediasi,
                pm.mediator_text
            FROM perkara p
            JOIN perkara_mediasi pm ON p.perkara_id = pm.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            ORDER BY pm.penetapan_tanggal_mediasi DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'input_pmh':
        $config['title'] = 'Detail Input PMH';
        $config['desc'] = 'Perkara Dengan Data Dukung Mediasi (Bobot 2%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_data_dukung_mediasi pddm ON p.perkara_id = pddm.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text
            FROM perkara p
            JOIN perkara_data_dukung_mediasi pddm ON p.perkara_id = pddm.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'pen_pp':
        $config['title'] = 'Detail Penetapan PP';
        $config['desc'] = 'Perkara Dengan Penetapan Putusan (Bobot 2.5%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.tanggal_putusan IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pp.tanggal_putusan,
                pp.status_putusan_nama
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.tanggal_putusan IS NOT NULL
            ORDER BY pp.tanggal_putusan DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'input_pp':
        $config['title'] = 'Detail Input PP';
        $config['desc'] = 'Perkara Dengan Dokumen Putusan (Bobot 2%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.amar_putusan_dok IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pp.tanggal_putusan,
                pp.amar_putusan_dok as status_dokumen
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.amar_putusan_dok IS NOT NULL
            ORDER BY pp.tanggal_putusan DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'pen_js':
        $config['title'] = 'Detail Penetapan Jadwal Sidang';
        $config['desc'] = 'Perkara Dengan Jadwal Sidang (Bobot 2.5%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                (SELECT pjs2.tanggal_sidang 
                 FROM perkara_jadwal_sidang pjs2 
                 WHERE pjs2.perkara_id = p.perkara_id 
                 ORDER BY pjs2.tanggal_sidang ASC LIMIT 1) as jadwal_pertama,
                (SELECT COUNT(*) 
                 FROM perkara_jadwal_sidang pjs2 
                 WHERE pjs2.perkara_id = p.perkara_id) as jumlah_sidang
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'pen_js_2':
        $config['title'] = 'Detail Penetapan JS (2)';
        $config['desc'] = 'Perkara Dengan Jadwal Sidang (Bobot 2%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                (SELECT pjs2.tanggal_sidang 
                 FROM perkara_jadwal_sidang pjs2 
                 WHERE pjs2.perkara_id = p.perkara_id 
                 ORDER BY pjs2.tanggal_sidang ASC LIMIT 1) as jadwal_pertama,
                (SELECT COUNT(*) 
                 FROM perkara_jadwal_sidang pjs2 
                 WHERE pjs2.perkara_id = p.perkara_id) as jumlah_sidang
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'pen_phs':
        $config['title'] = 'Detail Penetapan PHS';
        $config['desc'] = 'Perkara Dengan Penetapan Hari Sidang (Bobot 2.5%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                (SELECT pjs2.tanggal_sidang 
                 FROM perkara_jadwal_sidang pjs2 
                 WHERE pjs2.perkara_id = p.perkara_id 
                 ORDER BY pjs2.tanggal_sidang ASC LIMIT 1) as jadwal_pertama
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'input_phs':
        $config['title'] = 'Detail Input PHS';
        $config['desc'] = 'Perkara Dengan Input Hari Sidang (Bobot 2%)';
        $config['category'] = 'Kinerja';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                (SELECT pjs2.tanggal_sidang 
                 FROM perkara_jadwal_sidang pjs2 
                 WHERE pjs2.perkara_id = p.perkara_id 
                 ORDER BY pjs2.tanggal_sidang ASC LIMIT 1) as jadwal_pertama
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    // KEPATUHAN / REGISTERM (50%)
    case 'relaas':
        $config['title'] = 'Detail Relaas';
        $config['desc'] = 'Perkara Dengan Relaas (Bobot 2.5%)';
        $config['category'] = 'Kepatuhan';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'mediasi':
        $config['title'] = 'Detail Mediasi';
        $config['desc'] = 'Keberhasilan Mediasi (Bobot 2.5%)';
        $config['category'] = 'Kepatuhan';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT pm.perkara_id) as total
            FROM perkara_mediasi pm
            JOIN perkara p ON pm.perkara_id = p.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pm.mediator_text,
                pm.penetapan_tanggal_mediasi,
                pm.dimulai_mediasi,
                pm.hasil_mediasi,
                CASE 
                    WHEN pm.hasil_mediasi = 'S' THEN 'Berhasil 100%'
                    WHEN pm.hasil_mediasi = 'Y2' THEN 'Berhasil Pencabutan 100%'
                    WHEN pm.hasil_mediasi = 'Y1' THEN 'Berhasil Sebagian 50%'
                    ELSE 'Tidak Berhasil'
                END as status_mediasi
            FROM perkara_mediasi pm
            JOIN perkara p ON pm.perkara_id = p.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            ORDER BY pm.penetapan_tanggal_mediasi DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'saksi':
    $config['title'] = 'Detail Saksi';
    $config['desc'] = 'Perkara Dengan Pembuktian Saksi (Bobot 2.5%)';
    $config['category'] = 'Kepatuhan';
    $config['query_count'] = "
        SELECT COUNT(DISTINCT p.perkara_id) as total
        FROM perkara p
        JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
        WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
        AND pjs.agenda LIKE '%pembuktian%'
    ";
    $config['query_data'] = "
        SELECT DISTINCT
            p.perkara_id,
            p.nomor_perkara,
            p.tanggal_pendaftaran,
            p.jenis_perkara_nama,
            p.pihak1_text,
            p.pihak2_text,
            (SELECT pjs2.tanggal_sidang 
             FROM perkara_jadwal_sidang pjs2 
             WHERE pjs2.perkara_id = p.perkara_id 
             AND pjs2.agenda LIKE '%pembuktian%'
             ORDER BY pjs2.tanggal_sidang ASC LIMIT 1) as tanggal_sidang,
            (SELECT pjs2.agenda 
             FROM perkara_jadwal_sidang pjs2 
             WHERE pjs2.perkara_id = p.perkara_id 
             AND pjs2.agenda LIKE '%pembuktian%'
             ORDER BY pjs2.tanggal_sidang ASC LIMIT 1) as agenda
        FROM perkara p
        WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
        AND EXISTS (
            SELECT 1 FROM perkara_jadwal_sidang pjs 
            WHERE pjs.perkara_id = p.perkara_id 
            AND pjs.agenda LIKE '%pembuktian%'
        )
        ORDER BY tanggal_sidang DESC
        LIMIT :limit OFFSET :offset
    ";
    break;
        
    case 'pbt':
        $config['title'] = 'Detail PBT (Panjar Biaya Tunai)';
        $config['desc'] = 'Perkara Dengan Panjar (Bobot 2.5%)';
        $config['category'] = 'Kepatuhan';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT perkara_id) as total
            FROM (
                SELECT DISTINCT p.perkara_id
                FROM perkara p
                JOIN perkara_biaya pb ON p.perkara_id = pb.perkara_id
                WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
                AND (pb.jenis_transaksi = 1 OR pb.uraian = 'PANJAR')
                
                UNION
                
                SELECT DISTINCT kb.perkara_id
                FROM aps_badilag.keu_biaya kb
                WHERE EXISTS (SELECT 1 FROM perkara p WHERE p.perkara_id = kb.perkara_id 
                              AND p.tanggal_pendaftaran BETWEEN :awal AND :akhir)
                AND (kb.jenis_transaksi = 1 OR kb.uraian = 'PANJAR')
            ) as combined
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                (SELECT SUM(CASE WHEN pb2.uraian = 'PANJAR' THEN pb2.jumlah ELSE 0 END)
                 FROM perkara_biaya pb2
                 WHERE pb2.perkara_id = p.perkara_id) as panjar_sipp,
                (SELECT SUM(CASE WHEN kb2.uraian = 'PANJAR' THEN kb2.jumlah ELSE 0 END)
                 FROM aps_badilag.keu_biaya kb2
                 WHERE kb2.perkara_id = p.perkara_id) as panjar_badilag
            FROM perkara p
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND (
                EXISTS (SELECT 1 FROM perkara_biaya pb 
                        WHERE pb.perkara_id = p.perkara_id 
                        AND (pb.jenis_transaksi = 1 OR pb.uraian = 'PANJAR'))
                OR EXISTS (SELECT 1 FROM aps_badilag.keu_biaya kb 
                           WHERE kb.perkara_id = p.perkara_id 
                           AND (kb.jenis_transaksi = 1 OR kb.uraian = 'PANJAR'))
            )
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'bht':
        $config['title'] = 'Detail BHT (Biaya Hukum Tunai)';
        $config['desc'] = 'Perkara Dengan BHT (Bobot 2.5%)';
        $config['category'] = 'Kepatuhan';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT perkara_id) as total
            FROM (
                SELECT DISTINCT p.perkara_id
                FROM perkara p
                JOIN perkara_biaya pb ON p.perkara_id = pb.perkara_id
                WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
                AND pb.kategori_id = 12
                
                UNION
                
                SELECT DISTINCT kb.perkara_id
                FROM aps_badilag.keu_biaya kb
                WHERE EXISTS (SELECT 1 FROM perkara p WHERE p.perkara_id = kb.perkara_id 
                              AND p.tanggal_pendaftaran BETWEEN :awal AND :akhir)
                AND kb.kategori_id = 12
            ) as combined
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                (SELECT SUM(pb2.jumlah)
                 FROM perkara_biaya pb2
                 WHERE pb2.perkara_id = p.perkara_id
                 AND pb2.kategori_id = 12) as bht_sipp,
                (SELECT SUM(kb2.jumlah)
                 FROM aps_badilag.keu_biaya kb2
                 WHERE kb2.perkara_id = p.perkara_id
                 AND kb2.kategori_id = 12) as bht_badilag
            FROM perkara p
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND (
                EXISTS (SELECT 1 FROM perkara_biaya pb 
                        WHERE pb.perkara_id = p.perkara_id 
                        AND pb.kategori_id = 12)
                OR EXISTS (SELECT 1 FROM aps_badilag.keu_biaya kb 
                           WHERE kb.perkara_id = p.perkara_id 
                           AND kb.kategori_id = 12)
            )
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'sisa_panjar':
        $config['title'] = 'Detail Sisa Panjar';
        $config['desc'] = 'Perkara Dengan Sisa Panjar (Bobot 2.5%)';
        $config['category'] = 'Kepatuhan';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT perkara_id) as total
            FROM (
                SELECT DISTINCT p.perkara_id
                FROM perkara p
                JOIN perkara_biaya pb ON p.perkara_id = pb.perkara_id
                WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
                AND pb.sisa > 0
                
                UNION
                
                SELECT DISTINCT kb.perkara_id
                FROM aps_badilag.keu_biaya kb
                WHERE EXISTS (SELECT 1 FROM perkara p WHERE p.perkara_id = kb.perkara_id 
                              AND p.tanggal_pendaftaran BETWEEN :awal AND :akhir)
                AND kb.sisa > 0
            ) as combined
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                (SELECT MAX(pb2.sisa)
                 FROM perkara_biaya pb2
                 WHERE pb2.perkara_id = p.perkara_id) as sisa_sipp,
                (SELECT MAX(kb2.sisa)
                 FROM aps_badilag.keu_biaya kb2
                 WHERE kb2.perkara_id = p.perkara_id) as sisa_badilag
            FROM perkara p
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND (
                EXISTS (SELECT 1 FROM perkara_biaya pb 
                        WHERE pb.perkara_id = p.perkara_id AND pb.sisa > 0)
                OR EXISTS (SELECT 1 FROM aps_badilag.keu_biaya kb 
                           WHERE kb.perkara_id = p.perkara_id AND kb.sisa > 0)
            )
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'arsip':
        $config['title'] = 'Detail Arsip';
        $config['desc'] = 'Perkara Dengan Dokumen Arsip (Bobot 2.5%)';
        $config['category'] = 'Kepatuhan';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.amar_putusan_dok IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pp.tanggal_putusan,
                pp.amar_putusan_dok as status_arsip
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.amar_putusan_dok IS NOT NULL
            ORDER BY pp.tanggal_putusan DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    // KELENGKAPAN DOKUMEN (10%)
    case 'delegasi':
        $config['title'] = 'Detail Delegasi';
        $config['desc'] = 'Perkara Banding (Bobot 2.5%)';
        $config['category'] = 'Kelengkapan';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_banding_detil pbd ON p.perkara_id = pbd.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pbd.permohonan_banding,
                pbd.tanggal_kirim_salinan_putusan
            FROM perkara p
            JOIN perkara_banding_detil pbd ON p.perkara_id = pbd.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            ORDER BY pbd.permohonan_banding DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'petitum':
        $config['title'] = 'Detail Petitum';
        $config['desc'] = 'Perkara Dengan Status DKB (Bobot 2.5%)';
        $config['category'] = 'Kelengkapan';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.status_putusan_kode IN ('DKB', 'DKBSEBAGIAN')
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pp.tanggal_putusan,
                pp.status_putusan_nama,
                pp.status_putusan_kode
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pp.status_putusan_kode IN ('DKB', 'DKBSEBAGIAN')
            ORDER BY pp.tanggal_putusan DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'relaas_kel':
        $config['title'] = 'Detail Relaas (Kelengkapan)';
        $config['desc'] = 'Kelengkapan Relaas (Bobot 2.5%)';
        $config['category'] = 'Kelengkapan';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'bas':
        $config['title'] = 'Detail BAS (Berita Acara Sidang)';
        $config['desc'] = 'Kelengkapan BAS (Bobot 3%)';
        $config['category'] = 'Kelengkapan';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                (SELECT COUNT(*) 
                 FROM perkara_jadwal_sidang pjs2 
                 WHERE pjs2.perkara_id = p.perkara_id) as jumlah_sidang
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.tanggal_sidang IS NOT NULL
            ORDER BY p.tanggal_pendaftaran DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'ac':
        $config['title'] = 'Detail AC (Akta Cerai)';
        $config['desc'] = 'Perkara Cerai Dengan Putusan (Bobot 2%)';
        $config['category'] = 'Kelengkapan';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND p.jenis_perkara_nama LIKE '%cerai%'
            AND pp.tanggal_putusan IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pp.tanggal_putusan,
                pp.status_putusan_nama
            FROM perkara p
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND p.jenis_perkara_nama LIKE '%cerai%'
            AND pp.tanggal_putusan IS NOT NULL
            ORDER BY pp.tanggal_putusan DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    // KESESUAIAN (-10%)
    case 'sidang_akhir':
        $config['title'] = 'Detail Sidang Akhir Terlambat';
        $config['desc'] = 'Sidang Akhir Lebih Dari 60 Hari (Bobot -2%)';
        $config['category'] = 'Kesesuaian';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.agenda LIKE '%putusan%'
            AND DATEDIFF(pjs.tanggal_sidang, p.tanggal_pendaftaran) > 60
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pjs.tanggal_sidang,
                DATEDIFF(pjs.tanggal_sidang, p.tanggal_pendaftaran) as selisih_hari
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.agenda LIKE '%putusan%'
            AND DATEDIFF(pjs.tanggal_sidang, p.tanggal_pendaftaran) > 60
            ORDER BY selisih_hari DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'sinkron':
        $config['title'] = 'Detail Sinkronisasi Delegasi';
        $config['desc'] = 'Pengiriman Delegasi Lebih Dari 70 Hari (Bobot -4%)';
        $config['category'] = 'Kesesuaian';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_banding_detil pbd ON p.perkara_id = pbd.perkara_id
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pbd.tanggal_kirim_salinan_putusan IS NOT NULL
            AND pp.tanggal_putusan IS NOT NULL
            AND DATEDIFF(pbd.tanggal_kirim_salinan_putusan, pp.tanggal_putusan) > 70
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pp.tanggal_putusan,
                pbd.tanggal_kirim_salinan_putusan,
                DATEDIFF(pbd.tanggal_kirim_salinan_putusan, pp.tanggal_putusan) as selisih_hari
            FROM perkara p
            JOIN perkara_banding_detil pbd ON p.perkara_id = pbd.perkara_id
            JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pbd.tanggal_kirim_salinan_putusan IS NOT NULL
            AND pp.tanggal_putusan IS NOT NULL
            AND DATEDIFF(pbd.tanggal_kirim_salinan_putusan, pp.tanggal_putusan) > 70
            ORDER BY selisih_hari DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'permintaan_delegasi':
        $config['title'] = 'Detail Permintaan Delegasi';
        $config['desc'] = 'Perkara Dengan Permohonan Banding (Bobot -2%)';
        $config['category'] = 'Kesesuaian';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_banding_detil pbd ON p.perkara_id = pbd.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pbd.permohonan_banding IS NOT NULL
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pbd.permohonan_banding
            FROM perkara p
            JOIN perkara_banding_detil pbd ON p.perkara_id = pbd.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pbd.permohonan_banding IS NOT NULL
            ORDER BY pbd.permohonan_banding DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    case 'status_persidangan':
        $config['title'] = 'Detail Status Persidangan';
        $config['desc'] = 'Sidang Yang Ditunda (Bobot -2%)';
        $config['category'] = 'Kesesuaian';
        $config['query_count'] = "
            SELECT COUNT(DISTINCT p.perkara_id) as total
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.ditunda = 1
        ";
        $config['query_data'] = "
            SELECT DISTINCT
                p.perkara_id,
                p.nomor_perkara,
                p.tanggal_pendaftaran,
                p.jenis_perkara_nama,
                p.pihak1_text,
                p.pihak2_text,
                pjs.tanggal_sidang,
                pjs.agenda
            FROM perkara p
            JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
            WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
            AND pjs.ditunda = 1
            ORDER BY pjs.tanggal_sidang DESC
            LIMIT :limit OFFSET :offset
        ";
        break;
        
    default:
        header('Location: index.php');
        exit;
}

// Query total data
$stmt = $pdo_sipp->prepare($config['query_count']);
$stmt->execute(array('awal' => $tanggal_awal, 'akhir' => $tanggal_akhir));
$total_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_data = $total_data['total'];
$total_pages = ceil($total_data / $per_page);

// Query data dengan pagination
$stmt = $pdo_sipp->prepare($config['query_data']);
$stmt->bindValue(':awal', $tanggal_awal, PDO::PARAM_STR);
$stmt->bindValue(':akhir', $tanggal_akhir, PDO::PARAM_STR);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data_perkara = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['title']; ?></title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/penilaian.css">
    <style>
        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .category-kinerja { background: #e3f2fd; color: #1976d2; }
        .category-kepatuhan { background: #f3e5f5; color: #7b1fa2; }
        .category-kelengkapan { background: #fff3e0; color: #f57c00; }
        .category-kesesuaian { background: #ffebee; color: #c62828; }
    </style>
</head>
<body>

<div class="container">
    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <span class="category-badge category-<?php echo strtolower($config['category']); ?>">
                    <?php echo $config['category']; ?>
                </span>
                <h1><?php echo $config['title']; ?></h1>
                <p class="subtitle"><?php echo $config['desc']; ?></p>
                <p style="color: #7f8c8d; margin-top: 5px; font-size: 13px;">
                    Periode: <?php echo date('d/m/Y', strtotime($tanggal_awal)); ?> s/d <?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?>
                </p>
            </div>
            <a href="index.php?tahun=<?php echo $tahun; ?>&triwulan=<?php echo $triwulan; ?>" class="back-btn">Kembali</a>
        </div>
        
        <div class="info-box">
            Total Data: <strong><?php echo number_format($total_data); ?></strong> perkara | 
            Tampilkan: 
            <select onchange="changePerPage(this.value)" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20</option>
                <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
            </select>
            per halaman
        </div>
        
        <?php if (count($data_perkara) > 0): ?>
        <div class="table-section">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Perkara</th>
                            <th>Jenis Perkara</th>
                            <th>Pihak 1</th>
                            <th>Pihak 2</th>
                            <th>Tgl Daftar</th>
                            
                            <?php
                            // Dynamic columns based on type
                            switch($type) {
                                case 'putus':
                                case 'pen_pp':
                                    echo '<th>Tgl Putusan</th>';
                                    echo '<th>Status Putusan</th>';
                                    echo '<th>Durasi (Hari)</th>';
                                    break;
                                case 'minutasi':
                                    echo '<th>Tgl Putusan</th>';
                                    echo '<th>Tgl Minutasi</th>';
                                    echo '<th>Selisih (Hari)</th>';
                                    break;
                                case 'publikasi':
                                    echo '<th>Tgl Putusan</th>';
                                    echo '<th>Tgl Minutasi</th>';
                                    echo '<th>Status</th>';
                                    break;
                                case 'pendaftaran':
                                    echo '<th>Tahapan</th>';
                                    echo '<th>Proses</th>';
                                    break;
                                case 'pen_pmh':
                                    echo '<th>Tgl Penetapan</th>';
                                    echo '<th>Mediator</th>';
                                    break;
                                case 'pen_js':
                                case 'pen_js_2':
                                    echo '<th>Jadwal Pertama</th>';
                                    echo '<th>Jumlah Sidang</th>';
                                    break;
                                case 'mediasi':
                                    echo '<th>Mediator</th>';
                                    echo '<th>Tgl Penetapan</th>';
                                    echo '<th>Tgl Mulai</th>';
                                    echo '<th>Status</th>';
                                    break;
                                case 'saksi':
                                    echo '<th>Tgl Sidang</th>';
                                    echo '<th>Agenda</th>';
                                    break;
                                case 'pbt':
                                    echo '<th>Panjar</th>';
                                    break;
                                case 'bht':
                                    echo '<th>BHT</th>';
                                    break;
                                case 'sisa_panjar':
                                    echo '<th>Sisa</th>';
                                    break;
                                case 'delegasi':
                                case 'permintaan_delegasi':
                                    echo '<th>Tgl Permohonan</th>';
                                    echo '<th>Tgl Kirim</th>';
                                    break;
                                case 'petitum':
                                    echo '<th>Tgl Putusan</th>';
                                    echo '<th>Status</th>';
                                    break;
                                case 'bas':
                                    echo '<th>Jumlah Sidang</th>';
                                    break;
                                case 'ac':
                                    echo '<th>Tgl Putusan</th>';
                                    echo '<th>Status</th>';
                                    break;
                                case 'sidang_akhir':
                                    echo '<th>Tgl Sidang Akhir</th>';
                                    echo '<th>Selisih (Hari)</th>';
                                    break;
                                case 'sinkron':
                                    echo '<th>Tgl Putusan</th>';
                                    echo '<th>Tgl Kirim</th>';
                                    echo '<th>Selisih (Hari)</th>';
                                    break;
                                case 'status_persidangan':
                                    echo '<th>Tgl Sidang</th>';
                                    echo '<th>Agenda</th>';
                                    break;
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $offset + 1;
                        foreach ($data_perkara as $row): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td class="nomor-perkara"><?php echo htmlspecialchars($row['nomor_perkara']); ?></td>
                            <td><?php echo htmlspecialchars($row['jenis_perkara_nama']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars(strip_tags(str_replace('<br />', "\n", $row['pihak1_text'])))); ?></td>
                            <td><?php echo nl2br(htmlspecialchars(strip_tags(str_replace('<br />', "\n", $row['pihak2_text'])))); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_pendaftaran'])); ?></td>
                            
                            <?php
                            // Dynamic data based on type
                            switch($type) {
                                case 'putus':
                                case 'pen_pp':
                                    echo '<td>' . (isset($row['tanggal_putusan']) ? date('d/m/Y', strtotime($row['tanggal_putusan'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['status_putusan_nama']) ? htmlspecialchars($row['status_putusan_nama']) : '-') . '</td>';
                                    echo '<td>' . (isset($row['durasi_hari']) ? $row['durasi_hari'] . ' hari' : '-') . '</td>';
                                    break;
                                case 'minutasi':
                                    echo '<td>' . (isset($row['tanggal_putusan']) ? date('d/m/Y', strtotime($row['tanggal_putusan'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['tanggal_minutasi']) ? date('d/m/Y', strtotime($row['tanggal_minutasi'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['selisih_hari']) ? $row['selisih_hari'] . ' hari' : '-') . '</td>';
                                    break;
                                case 'publikasi':
                                    echo '<td>' . (isset($row['tanggal_putusan']) ? date('d/m/Y', strtotime($row['tanggal_putusan'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['tanggal_minutasi']) ? date('d/m/Y', strtotime($row['tanggal_minutasi'])) : '-') . '</td>';
                                    echo '<td><span class="badge badge-success">Terpublikasi</span></td>';
                                    break;
                                case 'pendaftaran':
                                    echo '<td>' . (isset($row['tahapan_terakhir_text']) ? htmlspecialchars($row['tahapan_terakhir_text']) : '-') . '</td>';
                                    echo '<td>' . (isset($row['proses_terakhir_text']) ? htmlspecialchars($row['proses_terakhir_text']) : '-') . '</td>';
                                    break;
                                case 'pen_pmh':
                                    echo '<td>' . (isset($row['penetapan_tanggal_mediasi']) ? date('d/m/Y', strtotime($row['penetapan_tanggal_mediasi'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['mediator_text']) ? htmlspecialchars($row['mediator_text']) : '-') . '</td>';
                                    break;
                                case 'pen_js':
                                case 'pen_js_2':
                                    echo '<td>' . (isset($row['jadwal_pertama']) ? date('d/m/Y', strtotime($row['jadwal_pertama'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['jumlah_sidang']) ? $row['jumlah_sidang'] . 'x' : '-') . '</td>';
                                    break;
                                case 'mediasi':
                                    echo '<td>' . (isset($row['mediator_text']) ? htmlspecialchars($row['mediator_text']) : '-') . '</td>';
                                    echo '<td>' . (isset($row['penetapan_tanggal_mediasi']) ? date('d/m/Y', strtotime($row['penetapan_tanggal_mediasi'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['dimulai_mediasi']) ? date('d/m/Y', strtotime($row['dimulai_mediasi'])) : '-') . '</td>';
                                    $badge_class = 'badge-success';
                                    if (isset($row['hasil_mediasi'])) {
                                        if ($row['hasil_mediasi'] == 'Y1') {
                                            $badge_class = 'badge-warning';
                                        } elseif ($row['hasil_mediasi'] != 'S' && $row['hasil_mediasi'] != 'Y2') {
                                            $badge_class = 'badge-danger';
                                        }
                                    }
                                    echo '<td><span class="badge ' . $badge_class . '">' . (isset($row['status_mediasi']) ? htmlspecialchars($row['status_mediasi']) : '-') . '</span></td>';
                                    break;
                                case 'saksi':
                                    echo '<td>' . (isset($row['tanggal_sidang']) ? date('d/m/Y', strtotime($row['tanggal_sidang'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['agenda']) ? htmlspecialchars($row['agenda']) : '-') . '</td>';
                                    break;
                                case 'pbt':
                                    $panjar_total = (isset($row['panjar_sipp']) ? $row['panjar_sipp'] : 0) + (isset($row['panjar_badilag']) ? $row['panjar_badilag'] : 0);
                                    echo '<td>Rp ' . number_format($panjar_total, 0, ',', '.') . '</td>';
                                    break;
                                case 'bht':
                                    $bht_total = (isset($row['bht_sipp']) ? $row['bht_sipp'] : 0) + (isset($row['bht_badilag']) ? $row['bht_badilag'] : 0);
                                    echo '<td>Rp ' . number_format($bht_total, 0, ',', '.') . '</td>';
                                    break;
                                case 'sisa_panjar':
                                    $sisa_total = (isset($row['sisa_sipp']) ? $row['sisa_sipp'] : 0) + (isset($row['sisa_badilag']) ? $row['sisa_badilag'] : 0);
                                    echo '<td>Rp ' . number_format($sisa_total, 0, ',', '.') . '</td>';
                                    break;
                                case 'delegasi':
                                case 'permintaan_delegasi':
                                    echo '<td>' . (isset($row['permohonan_banding']) ? date('d/m/Y', strtotime($row['permohonan_banding'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['tanggal_kirim_salinan_putusan']) ? date('d/m/Y', strtotime($row['tanggal_kirim_salinan_putusan'])) : '-') . '</td>';
                                    break;
                                case 'petitum':
                                    echo '<td>' . (isset($row['tanggal_putusan']) ? date('d/m/Y', strtotime($row['tanggal_putusan'])) : '-') . '</td>';
                                    echo '<td><span class="badge badge-info">' . (isset($row['status_putusan_nama']) ? htmlspecialchars($row['status_putusan_nama']) : '-') . '</span></td>';
                                    break;
                                case 'bas':
                                    echo '<td>' . (isset($row['jumlah_sidang']) ? $row['jumlah_sidang'] . 'x' : '-') . '</td>';
                                    break;
                                case 'ac':
                                    echo '<td>' . (isset($row['tanggal_putusan']) ? date('d/m/Y', strtotime($row['tanggal_putusan'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['status_putusan_nama']) ? htmlspecialchars($row['status_putusan_nama']) : '-') . '</td>';
                                    break;
                                case 'sidang_akhir':
                                    echo '<td>' . (isset($row['tanggal_sidang']) ? date('d/m/Y', strtotime($row['tanggal_sidang'])) : '-') . '</td>';
                                    $badge_class = 'badge-danger';
                                    if (isset($row['selisih_hari'])) {
                                        if ($row['selisih_hari'] <= 90) {
                                            $badge_class = 'badge-warning';
                                        }
                                    }
                                    echo '<td><span class="badge ' . $badge_class . '">' . (isset($row['selisih_hari']) ? $row['selisih_hari'] . ' hari' : '-') . '</span></td>';
                                    break;
                                case 'sinkron':
                                    echo '<td>' . (isset($row['tanggal_putusan']) ? date('d/m/Y', strtotime($row['tanggal_putusan'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['tanggal_kirim_salinan_putusan']) ? date('d/m/Y', strtotime($row['tanggal_kirim_salinan_putusan'])) : '-') . '</td>';
                                    $badge_class = 'badge-danger';
                                    if (isset($row['selisih_hari'])) {
                                        if ($row['selisih_hari'] <= 100) {
                                            $badge_class = 'badge-warning';
                                        }
                                    }
                                    echo '<td><span class="badge ' . $badge_class . '">' . (isset($row['selisih_hari']) ? $row['selisih_hari'] . ' hari' : '-') . '</span></td>';
                                    break;
                                case 'status_persidangan':
                                    echo '<td>' . (isset($row['tanggal_sidang']) ? date('d/m/Y', strtotime($row['tanggal_sidang'])) : '-') . '</td>';
                                    echo '<td>' . (isset($row['agenda']) ? htmlspecialchars($row['agenda']) : '-') . '</td>';
                                    break;
                            }
                            ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?type=<?php echo $type; ?>&tahun=<?php echo $tahun; ?>&triwulan=<?php echo $triwulan; ?>&page=<?php echo ($page - 1); ?>&per_page=<?php echo $per_page; ?>" class="page-btn">« Previous</a>
            <?php else: ?>
                <span class="page-btn disabled">« Previous</span>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1) {
                echo '<a href="?type=' . $type . '&tahun=' . $tahun . '&triwulan=' . $triwulan . '&page=1&per_page=' . $per_page . '" class="page-num">1</a>';
                if ($start_page > 2) {
                    echo '<span class="page-dots">...</span>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $page) {
                    echo '<span class="page-num active">' . $i . '</span>';
                } else {
                    echo '<a href="?type=' . $type . '&tahun=' . $tahun . '&triwulan=' . $triwulan . '&page=' . $i . '&per_page=' . $per_page . '" class="page-num">' . $i . '</a>';
                }
            }
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="page-dots">...</span>';
                }
                echo '<a href="?type=' . $type . '&tahun=' . $tahun . '&triwulan=' . $triwulan . '&page=' . $total_pages . '&per_page=' . $per_page . '" class="page-num">' . $total_pages . '</a>';
            }
            ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?type=<?php echo $type; ?>&tahun=<?php echo $tahun; ?>&triwulan=<?php echo $triwulan; ?>&page=<?php echo ($page + 1); ?>&per_page=<?php echo $per_page; ?>" class="page-btn">Next »</a>
            <?php else: ?>
                <span class="page-btn disabled">Next »</span>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        <div class="no-data">
            <div class="no-data-icon">🔭</div>
            <p><strong>Tidak ada data</strong></p>
            <p style="color: #bbb; margin-top: 5px;">Tidak ada data untuk periode ini</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function changePerPage(value) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('per_page', value);
    urlParams.set('page', '1');
    window.location.search = urlParams.toString();
}
</script>

<script src="../assets/js/global.js"></script>
<script src="../assets/js/penilaian.js"></script>

</body>
</html>