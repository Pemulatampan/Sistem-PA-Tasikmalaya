<?php

function getDataKinerjaOptimized($pdo_sipp, $pdo_badilag, $tanggal_awal, $tanggal_akhir) {
    
    // Query gabungan untuk semua data kinerja sekaligus
    $query = "
    SELECT 
        COUNT(DISTINCT p.perkara_id) as total_perkara,
        COUNT(DISTINCT CASE WHEN pp.tanggal_putusan IS NOT NULL THEN p.perkara_id END) as putus,
        COUNT(DISTINCT CASE WHEN pp.tanggal_minutasi IS NOT NULL THEN p.perkara_id END) as minutasi,
        COUNT(DISTINCT CASE WHEN pp.amar_putusan_anonimisasi_dok IS NOT NULL THEN p.perkara_id END) as publikasi,
        COUNT(DISTINCT CASE WHEN pm.perkara_id IS NOT NULL THEN p.perkara_id END) as pmh,
        COUNT(DISTINCT CASE WHEN pddm.perkara_id IS NOT NULL THEN p.perkara_id END) as input_pmh,
        COUNT(DISTINCT CASE WHEN pp.amar_putusan_dok IS NOT NULL THEN p.perkara_id END) as input_pp,
        COUNT(DISTINCT CASE WHEN pjs.tanggal_sidang IS NOT NULL THEN p.perkara_id END) as pen_js,
        COUNT(DISTINCT CASE WHEN pjs.agenda LIKE '%pembuktian%' THEN p.perkara_id END) as saksi,
        COUNT(DISTINCT CASE WHEN pbd.perkara_id IS NOT NULL THEN p.perkara_id END) as delegasi,
        COUNT(DISTINCT CASE WHEN pp.status_putusan_kode IN ('DKB', 'DKBSEBAGIAN') THEN p.perkara_id END) as petitum,
        COUNT(DISTINCT CASE WHEN p.jenis_perkara_nama LIKE '%cerai%' AND pp.tanggal_putusan IS NOT NULL THEN p.perkara_id END) as ac,
        COUNT(DISTINCT CASE WHEN pjs.agenda LIKE '%putusan%' AND DATEDIFF(pjs.tanggal_sidang, p.tanggal_pendaftaran) > 60 THEN p.perkara_id END) as sidang_akhir_terlambat,
        COUNT(DISTINCT CASE WHEN pjs.ditunda = 1 THEN p.perkara_id END) as status_persidangan
    FROM perkara p
    LEFT JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
    LEFT JOIN perkara_mediasi pm ON p.perkara_id = pm.perkara_id
    LEFT JOIN perkara_data_dukung_mediasi pddm ON p.perkara_id = pddm.perkara_id
    LEFT JOIN perkara_jadwal_sidang pjs ON p.perkara_id = pjs.perkara_id
    LEFT JOIN perkara_banding_detil pbd ON p.perkara_id = pbd.perkara_id
    WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
    ";
    
    $stmt = $pdo_sipp->prepare($query);
    $stmt->execute(['awal' => $tanggal_awal, 'akhir' => $tanggal_akhir]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Query mediasi
    $query_mediasi = "
    SELECT 
        COUNT(DISTINCT CASE WHEN hasil_mediasi = 'S' THEN pm.perkara_id END) as berhasil_akta,
        COUNT(DISTINCT CASE WHEN hasil_mediasi = 'Y2' THEN pm.perkara_id END) as berhasil_pencabutan,
        COUNT(DISTINCT CASE WHEN hasil_mediasi = 'Y1' THEN pm.perkara_id END) as berhasil_sebagian,
        COUNT(DISTINCT pm.perkara_id) as total_mediasi
    FROM perkara_mediasi pm
    JOIN perkara p ON pm.perkara_id = p.perkara_id
    WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
    ";
    $stmt = $pdo_sipp->prepare($query_mediasi);
    $stmt->execute(['awal' => $tanggal_awal, 'akhir' => $tanggal_akhir]);
    $data_mediasi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Query biaya
    $query_biaya = "
    SELECT 
        COUNT(DISTINCT CASE WHEN jenis_transaksi = 1 OR uraian = 'PANJAR' THEN perkara_id END) as pbt,
        COUNT(DISTINCT CASE WHEN kategori_id = 12 THEN perkara_id END) as bht,
        COUNT(DISTINCT CASE WHEN sisa > 0 THEN perkara_id END) as sisa_panjar
    FROM perkara_biaya pb
    WHERE EXISTS (SELECT 1 FROM perkara p WHERE p.perkara_id = pb.perkara_id 
                  AND p.tanggal_pendaftaran BETWEEN :awal AND :akhir)
    ";
    $stmt = $pdo_sipp->prepare($query_biaya);
    $stmt->execute(['awal' => $tanggal_awal, 'akhir' => $tanggal_akhir]);
    $data_biaya = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Query sinkronisasi
    $query_sinkron = "
    SELECT COUNT(DISTINCT p.perkara_id) as sinkron_tidak
    FROM perkara p
    JOIN perkara_banding_detil pbd ON p.perkara_id = pbd.perkara_id
    JOIN perkara_putusan pp ON p.perkara_id = pp.perkara_id
    WHERE p.tanggal_pendaftaran BETWEEN :awal AND :akhir
    AND pbd.tanggal_kirim_salinan_putusan IS NOT NULL
    AND pp.tanggal_putusan IS NOT NULL
    AND DATEDIFF(pbd.tanggal_kirim_salinan_putusan, pp.tanggal_putusan) > 70
    ";
    $stmt = $pdo_sipp->prepare($query_sinkron);
    $stmt->execute(['awal' => $tanggal_awal, 'akhir' => $tanggal_akhir]);
    $sinkron_tidak = $stmt->fetch(PDO::FETCH_ASSOC)['sinkron_tidak'];
    
    // Perhitungan
    $total_mediasi = $data_mediasi['total_mediasi'] ?: 1;
    $persentase_mediasi = (
        ($data_mediasi['berhasil_akta'] * 100) + 
        ($data_mediasi['berhasil_pencabutan'] * 100) + 
        ($data_mediasi['berhasil_sebagian'] * 50)
    ) / $total_mediasi;
    
    $tp = $data['total_perkara'] ?: 1;
    $putus = $data['putus'];
    $pen_js = $data['pen_js'];
    
    // Hitung nilai
    $nilai_kinerja = 
        ($putus / $tp) * 20 +
        ($data['minutasi'] / $tp) * 15 +
        ($data['publikasi'] / $tp) * 15 +
        ($data['total_perkara'] / $tp) * 2 +
        ($data['pmh'] / $tp) * 2.5 +
        ($data['input_pmh'] / $tp) * 2 +
        ($putus / $tp) * 2.5 +
        ($data['input_pp'] / $tp) * 2 +
        ($pen_js / $tp) * 2.5 +
        ($pen_js / $tp) * 2 +
        ($pen_js / $tp) * 2.5 +
        ($pen_js / $tp) * 2;
    
    $nilai_mediasi = ($persentase_mediasi / 100) * 2.5;
    $nilai_kepatuhan = 
        ($pen_js / $tp) * 2.5 +
        $nilai_mediasi +
        ($data['saksi'] / $tp) * 2.5 +
        ($data_biaya['pbt'] / $tp) * 2.5 +
        ($data_biaya['bht'] / $tp) * 2.5 +
        ($data_biaya['sisa_panjar'] / $tp) * 2.5 +
        ($data['input_pp'] / $tp) * 2.5;
    
    $nilai_kelengkapan = 
        ($data['delegasi'] / $tp) * 2.5 +
        ($data['petitum'] / $tp) * 2.5 +
        ($pen_js / $tp) * 2.5 +
        ($pen_js / $tp) * 3 +
        ($data['ac'] / $tp) * 2;
    
    $nilai_kesesuaian = -1 * (
        ($data['sidang_akhir_terlambat'] / $tp) * 2 +
        ($sinkron_tidak / $tp) * 4 +
        ($data['delegasi'] / $tp) * 2 +
        ($data['status_persidangan'] / $tp) * 2
    );
    
    $nilai_akhir = $nilai_kinerja + $nilai_kepatuhan + $nilai_kelengkapan + $nilai_kesesuaian;
    
    return [
        'total_perkara' => $data['total_perkara'],
        'putus' => $putus,
        'minutasi' => $data['minutasi'],
        'publikasi' => $data['publikasi'],
        'pendaftaran' => $data['total_perkara'],
        'pmh' => $data['pmh'],
        'input_pmh' => $data['input_pmh'],
        'pen_pp' => $putus,
        'input_pp' => $data['input_pp'],
        'pen_js' => $pen_js,
        'pen_js_2' => $pen_js,
        'pen_phs' => $pen_js,
        'input_phs' => $pen_js,
        'relaas' => $pen_js,
        'mediasi' => round($persentase_mediasi, 2),
        'saksi' => $data['saksi'],
        'pbt' => $data_biaya['pbt'],
        'bht' => $data_biaya['bht'],
        'sisa_panjar' => $data_biaya['sisa_panjar'],
        'arsip' => $data['input_pp'],
        'delegasi' => $data['delegasi'],
        'petitum' => $data['petitum'],
        'relaas_kel' => $pen_js,
        'bas' => $pen_js,
        'ac' => $data['ac'],
        'sidang_akhir_terlambat' => $data['sidang_akhir_terlambat'],
        'sinkron_tidak' => $sinkron_tidak,
        'permintaan_delegasi' => $data['delegasi'],
        'status_persidangan' => $data['status_persidangan'],
        'nilai_kinerja' => round($nilai_kinerja, 5),
        'nilai_kepatuhan' => round($nilai_kepatuhan, 5),
        'nilai_kelengkapan' => round($nilai_kelengkapan, 5),
        'nilai_kesesuaian' => round($nilai_kesesuaian, 5),
        'nilai_akhir' => round($nilai_akhir, 5)
    ];
}
?>