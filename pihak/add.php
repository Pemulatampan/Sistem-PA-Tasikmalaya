<?php
include '../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id            = clean_input($_POST['id']);
    $nama          = clean_input($_POST['nama']);
    $alamat        = clean_input($_POST['alamat']);
    $tempat_lahir  = clean_input($_POST['tempat_lahir']);
    $tanggal_lahir = clean_input($_POST['tanggal_lahir']);
    $pekerjaan     = clean_input($_POST['pekerjaan']);
    $keterangan    = clean_input($_POST['keterangan']);
    
    $insert_to_pp1 = isset($_POST['insert_perkara_pihak1']);
    $insert_to_pp2 = isset($_POST['insert_perkara_pihak2']);
    
    $tempat_lahir  = empty($tempat_lahir)  ? NULL : $tempat_lahir;
    $tanggal_lahir = empty($tanggal_lahir) ? NULL : $tanggal_lahir;
    $keterangan    = empty($keterangan)    ? NULL : $keterangan;

    if (empty($id) || empty($nama) || empty($alamat) || empty($pekerjaan)) {
        $error = 'ID, Nama, Alamat, dan Pekerjaan wajib diisi!';
    } else {
        $check_pihak = mysql_query("SELECT id FROM pihak WHERE id='".escape_string($id)."'");

        if (!$check_pihak) {
            $error = 'Error checking ID: ' . mysql_error();
        } elseif (mysql_num_rows($check_pihak) > 0) {
            $error = 'ID sudah ada di tabel pihak!';
        } else {
            mysql_query("SET AUTOCOMMIT=0");
            mysql_query("START TRANSACTION");
            $success = true;

            $sql_pihak = "INSERT INTO pihak (id, nama, alamat, tempat_lahir, tanggal_lahir, pekerjaan, keterangan) 
                          VALUES ('".escape_string($id)."',
                                  '".escape_string($nama)."',
                                  '".escape_string($alamat)."',
                                  ".($tempat_lahir ? "'".escape_string($tempat_lahir)."'" : "NULL").",
                                  ".($tanggal_lahir ? "'".escape_string($tanggal_lahir)."'" : "NULL").",
                                  '".escape_string($pekerjaan)."',
                                  ".($keterangan ? "'".escape_string($keterangan)."'" : "NULL").")";
            if (!mysql_query($sql_pihak)) {
                $success = false;
                $error = "Error insert pihak: " . mysql_error();
            }

            if ($success && $insert_to_pp1) {
                $sql_pp1 = "INSERT INTO perkara_pihak1 (perkara_id, urutan, pihak_id, jenis_pihak_id, nama, alamat, keterangan) 
                            VALUES (NULL, NULL, '".escape_string($id)."', NULL,
                                    '".escape_string($nama)."',
                                    '".escape_string($alamat)."',
                                    ".($keterangan ? "'".escape_string($keterangan)."'" : "NULL").")";
                if (!mysql_query($sql_pp1)) {
                    $success = false;
                    $error = "Error insert perkara_pihak1: " . mysql_error();
                }
            }

            if ($success && $insert_to_pp2) {
                $sql_pp2 = "INSERT INTO perkara_pihak2 (perkara_id, urutan, pihak_id, jenis_pihak_id, nama, alamat, keterangan) 
                            VALUES (NULL, NULL, '".escape_string($id)."', NULL,
                                    '".escape_string($nama)."',
                                    '".escape_string($alamat)."',
                                    ".($keterangan ? "'".escape_string($keterangan)."'" : "NULL").")";
                if (!mysql_query($sql_pp2)) {
                    $success = false;
                    $error = "Error insert perkara_pihak2: " . mysql_error();
                }
            }

            if ($success) {
                mysql_query("COMMIT");
                $message = 'Data berhasil ditambahkan ke tabel pihak';
                if ($insert_to_pp1 || $insert_to_pp2) {
                    $added_tables = array();
                    if ($insert_to_pp1) $added_tables[] = 'perkara_pihak1';
                    if ($insert_to_pp2) $added_tables[] = 'perkara_pihak2';
                    $message .= ' dan ' . implode(', ', $added_tables);
                }
                header('Location: index.php?message=' . urlencode($message) . '&type=success');
                exit();
            } else {
                mysql_query("ROLLBACK");
            }
            
            mysql_query("SET AUTOCOMMIT=1");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Pihak</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/css_pihak.css">
</head>
<body>
    <div class="form-page-wrapper">
        <div class="form-page-container">
            <!-- FORM CARD WRAPPER -->
            <div class="form-card">
                <!-- FORM HEADER -->
                <div class="form-header">
                    <h2>
                        <i class="fas fa-plus-circle"></i>
                        Tambah Data Pihak
                    </h2>
                    <div style="margin-top: 5px; opacity: 0.9;">Master Data Pihak</div>
                </div>
                
                <!-- FORM BODY -->
                <div class="form-body">
                    <!-- ERROR ALERT -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error" style="margin-bottom: 25px; padding: 15px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 8px; color: #991b1b;">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- FORM -->
                    <form method="POST" id="addForm">
                        <!-- FORM SECTION: Data Utama -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-user"></i>
                                Data Utama
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="id">ID <span class="required">*</span></label>
                                    <input type="text" id="id" name="id" class="form-input" value="<?php echo isset($_POST['id']) ? htmlspecialchars($_POST['id']) : ''; ?>" required maxlength="50">
                                    <small class="form-help">ID unik untuk tabel pihak</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nama">Nama <span class="required">*</span></label>
                                    <input type="text" id="nama" name="nama" class="form-input" value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" required maxlength="255">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="alamat">Alamat <span class="required">*</span></label>
                                    <textarea id="alamat" name="alamat" class="form-textarea" required maxlength="500"><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FORM SECTION: Data Kelahiran -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-calendar"></i>
                                Data Kelahiran
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="tempat_lahir">Tempat Lahir <span class="optional">(Opsional)</span></label>
                                    <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-input" value="<?php echo isset($_POST['tempat_lahir']) ? htmlspecialchars($_POST['tempat_lahir']) : ''; ?>" maxlength="100">
                                    <small class="form-help">Kosongkan jika tidak diketahui</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="tanggal_lahir">Tanggal Lahir <span class="optional">(Opsional)</span></label>
                                    <input type="text" id="tanggal_lahir" name="tanggal_lahir" class="form-input" placeholder="DD/MM/YYYY atau YYYY-MM-DD" value="<?php echo isset($_POST['tanggal_lahir']) ? htmlspecialchars($_POST['tanggal_lahir']) : ''; ?>" maxlength="10">
                                    <small class="form-help">Format: DD/MM/YYYY atau kosongkan</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FORM SECTION: Data Lainnya -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-briefcase"></i>
                                Data Lainnya
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="pekerjaan">Pekerjaan <span class="required">*</span></label>
                                    <input type="text" id="pekerjaan" name="pekerjaan" class="form-input" value="<?php echo isset($_POST['pekerjaan']) ? htmlspecialchars($_POST['pekerjaan']) : ''; ?>" required maxlength="100">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="keterangan">Keterangan <span class="optional">(Opsional)</span></label>
                                    <textarea id="keterangan" name="keterangan" class="form-textarea" maxlength="1000"><?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ''; ?></textarea>
                                    <small class="form-help">Informasi tambahan (opsional)</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FORM SECTION: Opsi Tabel Terkait -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-database"></i>
                                Opsi Tabel Terkait
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Tambahkan juga ke tabel:</label>
                                    <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 10px;">
                                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal; color: #2c3e50;">
                                            <input type="checkbox" id="insert_perkara_pihak1" name="insert_perkara_pihak1" style="width: auto;" <?php echo isset($_POST['insert_perkara_pihak1']) ? 'checked' : ''; ?>>
                                            <span>perkara_pihak1 (menggunakan pihak_id referensi)</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal; color: #2c3e50;">
                                            <input type="checkbox" id="insert_perkara_pihak2" name="insert_perkara_pihak2" style="width: auto;" <?php echo isset($_POST['insert_perkara_pihak2']) ? 'checked' : ''; ?>>
                                            <span>perkara_pihak2 (menggunakan pihak_id referensi)</span>
                                        </label>
                                    </div>
                                    <small class="form-help">Data akan diinsert dengan pihak_id = ID yang dimasukkan di atas</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FORM ACTIONS -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-submit">
                                <i class="fas fa-save"></i> Simpan Data
                            </button>
                            <a href="index.php" class="btn btn-cancel">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/js_pihak.js"></script>
</body>
</html>

<?php mysql_close($connection); ?>