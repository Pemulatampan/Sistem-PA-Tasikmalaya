<?php

ob_start('ob_gzhandler');

include '../config/config.php';
include '../config/cache_helper.php';
include 'functions/kinerja_optimized.php';

$page_title = "Penilaian Kinerja";

// CSS halaman ini
$additional_css = '<link rel="stylesheet" href="../assets/css/penilaian.css?v=' . time() . '">';

include '../includes/header.php';

// Parameter Triwulan
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$triwulan = isset($_GET['triwulan']) ? $_GET['triwulan'] : ceil(date('n') / 3);

// Hitung bulan awal dan akhir triwulan
$bulan_awal = (($triwulan - 1) * 3) + 1;
$bulan_akhir = $bulan_awal + 2;
$tanggal_awal = "$tahun-" . str_pad($bulan_awal, 2, '0', STR_PAD_LEFT) . "-01";
$tanggal_akhir = date("Y-m-t", strtotime("$tahun-" . str_pad($bulan_akhir, 2, '0', STR_PAD_LEFT) . "-01"));

// Inisialisasi cache
$cache = new SimpleCache('cache', 3600);
$cache_key = "kinerja_{$tahun}_{$triwulan}";

// Cek apakah ada parameter untuk clear cache
if (isset($_GET['clear_cache'])) {
    $cache->delete($cache_key);
    header("Location: index.php?tahun=$tahun&triwulan=$triwulan");
    exit;
}

// Ambil data dari cache atau database
$data_kinerja = $cache->get($cache_key);

if ($data_kinerja === null) {
    // Data belum ada di cache, ambil dari database
    $data_kinerja = getDataKinerjaOptimized($pdo_sipp, $pdo_badilag, $tanggal_awal, $tanggal_akhir);
    
    // Simpan ke cache
    $cache->set($cache_key, $data_kinerja);
}
?>

<!-- MULAI KONTEN HALAMAN -->
<div class="page-wrapper">
    <div class="page-header">
        <div>
            <h1>Dashboard Penilaian Kinerja</h1>
            <p class="subtitle">Triwulan <?= $triwulan ?> Tahun <?= $tahun ?></p>
        </div>
        <a href="?tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>&clear_cache=1" 
       style="font-size: 12px; color: #666; text-decoration: none; padding: 5px 10px; border: 1px solid #ddd; border-radius: 4px;">
        🔄 Refresh Data
        </a>
    </div>
    

    <form method="GET" class="filter-form">
        <label>Tahun: 
            <input type="number" name="tahun" value="<?= $tahun ?>" min="2020" max="2030">
        </label>
        <label>Triwulan: 
            <select name="triwulan">
                <option value="1" <?= $triwulan == 1 ? 'selected' : '' ?>>Triwulan 1 (Jan-Mar)</option>
                <option value="2" <?= $triwulan == 2 ? 'selected' : '' ?>>Triwulan 2 (Apr-Jun)</option>
                <option value="3" <?= $triwulan == 3 ? 'selected' : '' ?>>Triwulan 3 (Jul-Sep)</option>
                <option value="4" <?= $triwulan == 4 ? 'selected' : '' ?>>Triwulan 4 (Okt-Des)</option>
            </select>
        </label>
        <button type="submit">Tampilkan</button>
    </form>

    <div class="info-box">
        <strong>Periode:</strong> <?= $tanggal_awal ?> s/d <?= $tanggal_akhir ?> | 
        <strong>Total Perkara:</strong> <?= number_format($data_kinerja['total_perkara']) ?>
    </div>

    <!-- DASHBOARD CARDS SECTION -->
    <div class="cards-grid-compact">
        <!-- KINERJA (50%) -->
        <a href="detail_publikasi.php?type=putus&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Putus</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['putus']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['putus'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=minutasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Minutasi</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['minutasi']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['minutasi'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=publikasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Publikasi</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['publikasi']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['publikasi'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=pendaftaran&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Pendaftaran</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['pendaftaran']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: 100%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=pen_pmh&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Pen. PMH</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['pmh']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['pmh'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=input_pmh&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Input PMH</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['input_pmh']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['input_pmh'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=pen_pp&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Pen. PP</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['pen_pp']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['pen_pp'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=input_pp&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Input PP</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['input_pp']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['input_pp'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=pen_js&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Pen. JS</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['pen_js']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['pen_js'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=pen_js_2&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Pen. JS 2</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['pen_js_2']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['pen_js_2'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=pen_phs&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Pen. PHS</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['pen_phs']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['pen_phs'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=input_phs&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Input PHS</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['input_phs']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['input_phs'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <!-- KEPATUHAN (50%) -->
        <a href="detail_publikasi.php?type=relaas&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Relaas</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['relaas']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['relaas'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=mediasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Mediasi</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['mediasi'])?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['mediasi'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=saksi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Saksi</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['saksi']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['saksi'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=pbt&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">PBT</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['pbt']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['pbt'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=bht&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">BHT</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['bht']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['bht'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=sisa_panjar&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Sisa Panjar</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['sisa_panjar']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['sisa_panjar'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=arsip&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Arsip</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['arsip']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['arsip'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <!-- KELENGKAPAN (10%) -->
        <a href="detail_publikasi.php?type=delegasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Delegasi</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['delegasi']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['delegasi'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=petitum&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Petitum</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['petitum']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['petitum'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=relaas_kel&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Relaas Kel</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['relaas_kel']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['relaas_kel'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=bas&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">BAS</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['bas']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['bas'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=ac&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">AC</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['ac']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['ac'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <!-- KESESUAIAN (-10%) -->
        <a href="detail_publikasi.php?type=sidang_akhir&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Sidang Akhir</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['sidang_akhir_terlambat']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['sidang_akhir_terlambat'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=sinkron&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Sinkron</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['sinkron_tidak']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['sinkron_tidak'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=permintaan_delegasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Permintaan Delegasi</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['permintaan_delegasi']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['permintaan_delegasi'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>

        <a href="detail_publikasi.php?type=status_persidangan&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" class="card-link">
            <div class="dashboard-card-compact">
                <div class="card-title-compact">Status Persidangan</div>
                <div class="card-value-compact"><?= number_format($data_kinerja['status_persidangan']) ?></div>
                <div class="card-bar">
                    <div class="progress-fill-compact" style="width: <?= min(($data_kinerja['status_persidangan'] / $data_kinerja['total_perkara']) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </a>
    </div>

    <!-- SUMMARY SECTION -->
    <div class="summary-section-compact">
        <div class="summary-item">
            <div class="summary-label">Kinerja</div>
            <div class="summary-value"><?= round($data_kinerja['nilai_kinerja'], 2) ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Kepatuhan</div>
            <div class="summary-value"><?= round($data_kinerja['nilai_kepatuhan'], 2) ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Kelengkapan</div>
            <div class="summary-value"><?= round($data_kinerja['nilai_kelengkapan'], 2) ?></div>
        </div>
        <div class="summary-item summary-total">
            <div class="summary-label">Nilai Akhir</div>
            <div class="summary-value"><?= round($data_kinerja['nilai_akhir'], 2) ?></div>
        </div>
    </div>

    <!-- TABLE SECTION -->
    <div class="table-section">
        <h2 class="table-title">Detail Tabel Penilaian</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th colspan="12" class="kinerja-header">Kinerja (50%)</th>
                        <th colspan="7" class="kepatuhan-header">Kepatuhan / Registerm (50%)</th>
                        <th colspan="5" class="kelengkapan-header">Kelengkapan Dokumen (10%)</th>
                        <th colspan="4" class="kesesuaian-header">Kesesuaian (-10%)</th>
                        <th rowspan="3" class="nilai-akhir-header">Nilai<br>Akhir</th>
                    </tr>
                    <tr>
                        <!-- Kinerja -->
                        <th class="kinerja-subheader">Putus<br>(20%)</th>
                        <th class="kinerja-subheader">Minutasi<br>(15%)</th>
                        <th class="kinerja-subheader">Publikasi<br>Putusan<br>(15%)</th>
                        <th class="kinerja-subheader">Pendaftar<br>an (2%)</th>
                        <th class="kinerja-subheader">Pen. PMH<br>(2.5%)</th>
                        <th class="kinerja-subheader">Input<br>PMH<br>(2%)</th>
                        <th class="kinerja-subheader">Pen. PP<br>(2.5%)</th>
                        <th class="kinerja-subheader">Input<br>PP (2%)</th>
                        <th class="kinerja-subheader">Pen. JS<br>(2.5%)</th>
                        <th class="kinerja-subheader">Pen. JS<br>(2%)</th>
                        <th class="kinerja-subheader">Pen.<br>PHS<br>(2.5%)</th>
                        <th class="kinerja-subheader">Input<br>PHS<br>(2%)</th>
                        <!-- Kepatuhan -->
                        <th class="kepatuhan-subheader">Relaas<br>(2.5%)</th>
                        <th class="kepatuhan-subheader">Mediasi<br>(2.5%)</th>
                        <th class="kepatuhan-subheader">Saksi<br>(2.5%)</th>
                        <th class="kepatuhan-subheader">PBT<br>(2.5%)</th>
                        <th class="kepatuhan-subheader">BHT<br>(2.5%)</th>
                        <th class="kepatuhan-subheader">Sisa<br>Panjar<br>(2.5%)</th>
                        <th class="kepatuhan-subheader">Arsip<br>(2.5%)</th>
                        <!-- Kelengkapan -->
                        <th class="kelengkapan-subheader">Delegasi<br>(2.5%)</th>
                        <th class="kelengkapan-subheader">Petitum<br>(2.5%)</th>
                        <th class="kelengkapan-subheader">Relaas<br>(2.5%)</th>
                        <th class="kelengkapan-subheader">BAS<br>(3%)</th>
                        <th class="kelengkapan-subheader">AC (2%)</th>
                        <!-- Kesesuaian -->
                        <th class="kesesuaian-subheader">Sidang<br>Akhir<br>(-2%)</th>
                        <th class="kesesuaian-subheader">Sinkron<br>(-4%)</th>
                        <th class="kesesuaian-subheader">Permint<br>aan<br>Delegasi<br>(-2%)</th>
                        <th class="kesesuaian-subheader">Status<br>Persida<br>ngan<br>(-2%)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Baris 1: Angka Absolut -->
                    <tr>
                        <!-- Kinerja -->
                        <td class="number">
                            <a href="detail_publikasi.php?type=putus&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['putus']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=minutasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['minutasi']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=publikasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['publikasi']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=pendaftaran&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['pendaftaran']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=pen_pmh&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['pmh']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=input_pmh&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['input_pmh']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=pen_pp&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['pen_pp']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=input_pp&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['input_pp']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=pen_js&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['pen_js']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=pen_js_2&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['pen_js_2']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=pen_phs&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['pen_phs']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=input_phs&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['input_phs']) ?>
                            </a>
                        </td>
                        
                        <!-- Kepatuhan -->
                        <td class="number">
                            <a href="detail_publikasi.php?type=relaas&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['relaas']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=mediasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['mediasi']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=saksi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['saksi']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=pbt&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['pbt']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=bht&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['bht']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=sisa_panjar&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['sisa_panjar']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=arsip&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['arsip']) ?>
                            </a>
                        </td>
                        
                        <!-- Kelengkapan -->
                        <td class="number">
                            <a href="detail_publikasi.php?type=delegasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['delegasi']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=petitum&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['petitum']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=relaas_kel&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['relaas_kel']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=bas&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['bas']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=ac&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['ac']) ?>
                            </a>
                        </td>
                        
                        <!-- Kesesuaian -->
                        <td class="number">
                            <a href="detail_publikasi.php?type=sidang_akhir&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['sidang_akhir_terlambat']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=sinkron&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['sinkron_tidak']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=permintaan_delegasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['permintaan_delegasi']) ?>
                            </a>
                        </td>
                        <td class="number">
                            <a href="detail_publikasi.php?type=status_persidangan&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?= number_format($data_kinerja['status_persidangan']) ?>
                            </a>
                        </td>
                        
                        <!-- Nilai Akhir -->
                        <td class="number" rowspan="2"><?= round($data_kinerja['nilai_akhir'], 2) ?></td>
                    </tr>
                    
                    <!-- Baris 2: Persentase Bobot (Kontribusi ke Nilai Akhir) -->
                    <tr class="percentage-row">
                        <!-- Kinerja -->
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=putus&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['putus'] / $data_kinerja['total_perkara']) * 20, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=minutasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['minutasi'] / $data_kinerja['total_perkara']) * 15, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=publikasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['publikasi'] / $data_kinerja['total_perkara']) * 15, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=pendaftaran&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['pendaftaran'] / $data_kinerja['total_perkara']) * 2, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=pen_pmh&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['pmh'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=input_pmh&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['input_pmh'] / $data_kinerja['total_perkara']) * 2, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=pen_pp&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['pen_pp'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=input_pp&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['input_pp'] / $data_kinerja['total_perkara']) * 2, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=pen_js&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['pen_js'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=pen_js_2&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['pen_js_2'] / $data_kinerja['total_perkara']) * 2, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=pen_phs&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['pen_phs'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=input_phs&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['input_phs'] / $data_kinerja['total_perkara']) * 2, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        
                        <!-- Kepatuhan -->
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=relaas&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['relaas'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=mediasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php 
                                // Mediasi: tampilkan persentase bobot
                                $pct = round(($data_kinerja['mediasi'] / 100) * 2.5, 2); 
                                echo $pct < 0.01 ? '0%' : $pct . '%'; 
                                ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=saksi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['saksi'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=pbt&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['pbt'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=bht&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['bht'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=sisa_panjar&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['sisa_panjar'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=arsip&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['arsip'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        
                        <!-- Kelengkapan -->
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=delegasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['delegasi'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=petitum&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['petitum'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=relaas_kel&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['relaas_kel'] / $data_kinerja['total_perkara']) * 2.5, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=bas&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['bas'] / $data_kinerja['total_perkara']) * 3, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=ac&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['ac'] / $data_kinerja['total_perkara']) * 2, 2); echo $pct < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        
                        <!-- Kesesuaian (negatif) -->
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=sidang_akhir&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['sidang_akhir_terlambat'] / $data_kinerja['total_perkara']) * -2, 2); echo abs($pct) < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=sinkron&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['sinkron_tidak'] / $data_kinerja['total_perkara']) * -4, 2); echo abs($pct) < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=permintaan_delegasi&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['permintaan_delegasi'] / $data_kinerja['total_perkara']) * -2, 2); echo abs($pct) < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                        <td class="number percentage">
                            <a href="detail_publikasi.php?type=status_persidangan&tahun=<?= $tahun ?>&triwulan=<?= $triwulan ?>" style="color: inherit; text-decoration: none; display: block;">
                                <?php $pct = round(($data_kinerja['status_persidangan'] / $data_kinerja['total_perkara']) * -2, 2); echo abs($pct) < 0.01 ? '0%' : $pct . '%'; ?>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$additional_js = '<script src="../assets/js/penilaian.js"></script>';
include '../includes/footer.php'; 
ob_end_flush();
?>