<?php

include '../config/config.php';

function logUpdate($table, $id, $context, $status) {
    $log = date('Y-m-d H:i:s') . " - Table: $table, ID: $id, Context: $context, Status: $status\n";
    file_put_contents('update_log.txt', $log, FILE_APPEND);
}

$error = '';
$success = '';
$data = null;
$context = '';

// Get parameters from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?message=' . urlencode('ID tidak ditemukan') . '&type=error');
    exit();
}
 
$id = clean_input($_GET['id']);
$source_table = isset($_GET['table']) ? clean_input($_GET['table']) : 'pihak';
$perkara_id = isset($_GET['perkara_id']) ? clean_input($_GET['perkara_id']) : null;

// Validate source table
if (!in_array($source_table, ['pihak', 'perkara_pihak1', 'perkara_pihak2'])) {
    $source_table = 'pihak';
}

// Get data based on source table
if ($source_table == 'pihak') {
    $query = "SELECT * FROM pihak WHERE id = '".escape_string($id)."'";
    $context = 'Master Data Pihak';
} else {
    $query = "SELECT pp.*, p.* 
              FROM $source_table pp 
              JOIN pihak p ON pp.pihak_id = p.id 
              WHERE pp.id = '".escape_string($id)."'";
    $context = "Perkara " . ($perkara_id ? "#$perkara_id" : "ID #$id");
}

$result = mysql_query($query);

if (!$result || mysql_num_rows($result) == 0) {
    header('Location: index.php?message=' . urlencode('Data tidak ditemukan') . '&type=error');
    exit();
}

$data = mysql_fetch_assoc($result);
$pihak_id = ($source_table == 'pihak') ? $id : $data['pihak_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = clean_input($_POST['nama']);
    $alamat = clean_input($_POST['alamat']);
    $tempat_lahir = clean_input($_POST['tempat_lahir']);
    $tanggal_lahir = clean_input($_POST['tanggal_lahir']);
    $pekerjaan = clean_input($_POST['pekerjaan']);
    $keterangan = clean_input($_POST['keterangan']);
    
    $tempat_lahir = empty($tempat_lahir) ? NULL : $tempat_lahir;
    $tanggal_lahir = empty($tanggal_lahir) ? NULL : $tanggal_lahir;
    $keterangan = empty($keterangan) ? NULL : $keterangan;
    
    if (empty($nama) || empty($alamat) || empty($pekerjaan)) {
        $error = 'Nama, Alamat, dan Pekerjaan wajib diisi!';
    } else {
        mysql_query("SET AUTOCOMMIT = 0");
        mysql_query("START TRANSACTION");
        
        $success = true;
        $updated_info = [];
        
        // 1. Update master pihak table
        $query_pihak = "UPDATE pihak SET 
                       nama = '".escape_string($nama)."',
                       alamat = '".escape_string($alamat)."',
                       tempat_lahir = ".($tempat_lahir ? "'".escape_string($tempat_lahir)."'" : "NULL").",
                       tanggal_lahir = ".($tanggal_lahir ? "'".escape_string($tanggal_lahir)."'" : "NULL").",
                       pekerjaan = '".escape_string($pekerjaan)."',
                       keterangan = ".($keterangan ? "'".escape_string($keterangan)."'" : "NULL")."
                       WHERE id = '".escape_string($pihak_id)."'";
        
        if (mysql_query($query_pihak)) {
            $affected = mysql_affected_rows();
            if ($affected > 0) {
                $updated_info[] = "Master Data Pihak";
                logUpdate('pihak', $pihak_id, $context, "UPDATED - $affected row(s)");
            }
        } else {
            $success = false;
            $error = "Error updating pihak: " . mysql_error();
        }
        
        // 2. Auto update perkara_pihak1
        if ($success) {
            $pp1_query = "UPDATE perkara_pihak1 SET 
                         nama = '".escape_string($nama)."',
                         alamat = '".escape_string($alamat)."'
                         WHERE pihak_id = '".escape_string($pihak_id)."'";
            
            if (mysql_query($pp1_query)) {
                $affected = mysql_affected_rows();
                if ($affected > 0) {
                    $updated_info[] = "Perkara Pihak1 ($affected records)";
                    logUpdate('perkara_pihak1', $pihak_id, $context, "AUTO UPDATE - $affected row(s)");
                }
            }
        }
        
        // 3. Auto update perkara_pihak2
        if ($success) {
            $pp2_query = "UPDATE perkara_pihak2 SET 
                         nama = '".escape_string($nama)."',
                         alamat = '".escape_string($alamat)."'
                         WHERE pihak_id = '".escape_string($pihak_id)."'";
            
            if (mysql_query($pp2_query)) {
                $affected = mysql_affected_rows();
                if ($affected > 0) {
                    $updated_info[] = "Perkara Pihak2 ($affected records)";
                    logUpdate('perkara_pihak2', $pihak_id, $context, "AUTO UPDATE - $affected row(s)");
                }
            }
        }
        
        if ($success) {
            mysql_query("COMMIT");
            $message = empty($updated_info) ? 'Tidak ada perubahan data' : 'Berhasil mengupdate: ' . implode(', ', $updated_info);
            $redirect_url = $source_table == 'pihak' ? 'index.php' : 'view_perkara.php?id=' . $perkara_id;
            header('Location: ' . $redirect_url . '?message=' . urlencode($message) . '&type=success');
            exit();
        } else {
            mysql_query("ROLLBACK");
        }
        
        mysql_query("SET AUTOCOMMIT = 1");
    }
}

// Format display data
$display_data = array(
    'id' => $data['id'],
    'nama' => $data['nama'],
    'alamat' => $data['alamat'],
    'tempat_lahir' => is_null($data['tempat_lahir']) ? '' : $data['tempat_lahir'],
    'tanggal_lahir' => is_null($data['tanggal_lahir']) ? '' : $data['tanggal_lahir'],
    'pekerjaan' => $data['pekerjaan'],
    'keterangan' => is_null($data['keterangan']) ? '' : $data['keterangan']
);

// Get related records info
$related_tables = [];
$pp1_count = mysql_num_rows(mysql_query("SELECT 1 FROM perkara_pihak1 WHERE pihak_id = '".escape_string($pihak_id)."'"));
$pp2_count = mysql_num_rows(mysql_query("SELECT 1 FROM perkara_pihak2 WHERE pihak_id = '".escape_string($pihak_id)."'"));

$related_tables[] = "pihak (Master Data)";
if ($pp1_count > 0) $related_tables[] = "perkara_pihak1 ($pp1_count records)";
if ($pp2_count > 0) $related_tables[] = "perkara_pihak2 ($pp2_count records)";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Pihak - <?php echo htmlspecialchars($context); ?></title>
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
                        <i class="fas fa-edit"></i>
                        Edit Data Pihak
                    </h2>
                    <div style="margin-top: 5px; opacity: 0.9;"><?php echo htmlspecialchars($context); ?></div>
                </div>
                
                <!-- FORM BODY -->
                <div class="form-body">
                    <!-- INFO SECTION -->
                    <div class="info-section">
                        <h3>Informasi Data</h3>
                        <div class="info-item"><strong>Source:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $source_table))); ?></div>
                        <?php if ($source_table != 'pihak'): ?>
                            <div class="info-item"><strong>Master Pihak ID:</strong> <?php echo htmlspecialchars($pihak_id); ?></div>
                        <?php endif; ?>
                        <div class="info-item"><strong>Data tersimpan di:</strong> <?php echo implode(', ', $related_tables); ?></div>
                    </div>
                    
                    <!-- AUTO UPDATE NOTICE -->
                    <div class="auto-update-notice">
                        <i class="fas fa-info-circle"></i> <strong>Update Otomatis:</strong> Perubahan akan secara otomatis diterapkan ke Master Data Pihak dan semua tabel terkait (perkara_pihak1, perkara_pihak2).
                    </div>
                    
                    <!-- ERROR ALERT -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- FORM -->
                    <form method="POST" id="editForm">
                        <input type="hidden" name="source_table" value="<?php echo htmlspecialchars($source_table); ?>">
                        <input type="hidden" name="perkara_id" value="<?php echo htmlspecialchars($perkara_id); ?>">
                        
                        <!-- FORM SECTION: Data Utama -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-user"></i>
                                Data Utama
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="id">ID</label>
                                    <input type="text" id="id" class="form-input readonly" value="<?php echo htmlspecialchars($display_data['id']); ?>" readonly>
                                    <small class="form-help">ID tidak dapat diubah</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nama">Nama <span class="required">*</span></label>
                                    <input type="text" id="nama" name="nama" class="form-input" value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : htmlspecialchars($display_data['nama']); ?>" required maxlength="255">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="alamat">Alamat <span class="required">*</span></label>
                                    <textarea id="alamat" name="alamat" class="form-textarea" required maxlength="500"><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : htmlspecialchars($display_data['alamat']); ?></textarea>
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
                                    <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-input" value="<?php echo isset($_POST['tempat_lahir']) ? htmlspecialchars($_POST['tempat_lahir']) : htmlspecialchars($display_data['tempat_lahir']); ?>" maxlength="100">
                                    <small class="form-help">Kosongkan jika tidak diketahui</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="tanggal_lahir">Tanggal Lahir <span class="optional">(Opsional)</span></label>
                                    <input type="text" id="tanggal_lahir" name="tanggal_lahir" class="form-input" placeholder="DD/MM/YYYY atau YYYY-MM-DD" value="<?php echo isset($_POST['tanggal_lahir']) ? htmlspecialchars($_POST['tanggal_lahir']) : htmlspecialchars($display_data['tanggal_lahir']); ?>" maxlength="10">
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
                                    <input type="text" id="pekerjaan" name="pekerjaan" class="form-input" value="<?php echo isset($_POST['pekerjaan']) ? htmlspecialchars($_POST['pekerjaan']) : htmlspecialchars($display_data['pekerjaan']); ?>" required maxlength="100">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="keterangan">Keterangan <span class="optional">(Opsional)</span></label>
                                    <textarea id="keterangan" name="keterangan" class="form-textarea" maxlength="1000"><?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : htmlspecialchars($display_data['keterangan']); ?></textarea>
                                    <small class="form-help">Informasi tambahan (opsional)</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FORM ACTIONS -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-submit">
                                <i class="fas fa-save"></i> Update Data
                            </button>
                            <a href="<?php echo $source_table == 'pihak' ? 'index.php' : 'view_perkara.php?id=' . $perkara_id; ?>" class="btn btn-cancel">
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