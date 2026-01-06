<?php
include '../config/config.php';

// Cek apakah ID ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?message=' . urlencode('ID tidak ditemukan') . '&type=error');
    exit();
}

$id = clean_input($_GET['id']);

// Cek apakah data ada di aps_badilag.eac_validasi sebelum dihapus
$query = "SELECT * FROM aps_badilag.eac_validasi WHERE perkara_id = '".escape_string($id)."'";
$result = mysql_query($query);

if (!$result || mysql_num_rows($result) == 0) {
    mysql_close($connection);
    header('Location: index.php?message=' . urlencode('Data tidak ditemukan di aps_badilag.eac_validasi') . '&type=error');
    exit();
}

// Start transaction untuk memastikan data integrity
mysql_query("SET AUTOCOMMIT = 0");
mysql_query("START TRANSACTION");

$success = true;

// Hapus hanya dari aps_badilag.eac_validasi
$delete_eac = "DELETE FROM aps_badilag.eac_validasi WHERE perkara_id = '".escape_string($id)."'";
if (mysql_query($delete_eac)) {
    $affected_rows = mysql_affected_rows();
    if ($affected_rows > 0) {
        $success = true;
    } else {
        $success = false;
        $error = "Tidak ada data yang dihapus";
    }
} else {
    $success = false;
    $error = "Error deleting from aps_badilag.eac_validasi: " . mysql_error();
}

// Commit or rollback
if ($success) {
    mysql_query("COMMIT");
    mysql_close($connection);
    
    $message = 'Data berhasil dihapus dari aps_badilag.eac_validasi';
    
    // Log operasi delete untuk audit trail
    $log_message = date('Y-m-d H:i:s') . " - Perkara ID: $id deleted from aps_badilag.eac_validasi (sipp data retained)\n";
    file_put_contents('delete_log.txt', $log_message, FILE_APPEND);
    
    header('Location: index.php?message=' . urlencode($message) . '&type=success');
    exit();
} else {
    mysql_query("ROLLBACK");
    mysql_close($connection);
    header('Location: index.php?message=' . urlencode('Gagal menghapus data: ' . $error) . '&type=error');
    exit();
}
?>