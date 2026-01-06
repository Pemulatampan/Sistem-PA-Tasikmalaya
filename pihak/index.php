<?php
require_once '../config/config.php';
$page_title = "Data Perkara";
include '../includes/header.php';

// Pagination
$limit = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search functionality
$search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = clean_input($_GET['search']);
    $search_query = " AND (p.nama LIKE '%".escape_string($search)."%' 
                     OR p.alamat LIKE '%".escape_string($search)."%' 
                     OR p.pekerjaan LIKE '%".escape_string($search)."%'
                     OR p.keterangan LIKE '%".escape_string($search)."%'
                     OR p.id LIKE '%".escape_string($search)."%'
                     OR p.tempat_lahir LIKE '%".escape_string($search)."%'
                     OR p.tanggal_lahir LIKE '%".escape_string($search)."%')";
} elseif (isset($_POST['search']) && !empty($_POST['search'])) {
    $search = clean_input($_POST['search']);
    $search_query = " AND (p.nama LIKE '%".escape_string($search)."%' 
                     OR p.alamat LIKE '%".escape_string($search)."%' 
                     OR p.pekerjaan LIKE '%".escape_string($search)."%'
                     OR p.keterangan LIKE '%".escape_string($search)."%'
                     OR p.id LIKE '%".escape_string($search)."%'
                     OR p.tempat_lahir LIKE '%".escape_string($search)."%'
                     OR p.tanggal_lahir LIKE '%".escape_string($search)."%')";
} else {
    $search_query = '';
}

// Count total records - hanya yang ada di kedua database
$count_query = "SELECT COUNT(*) as total FROM pihak p 
                WHERE EXISTS (
                    SELECT 1 FROM aps_badilag.eac_validasi ev 
                    WHERE ev.perkara_id = p.id
                )" . $search_query;
$count_result = mysql_query($count_query);
$count_row = mysql_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

// Get records with pagination - hanya yang ada di kedua database
$query = "SELECT p.* FROM pihak p 
          WHERE EXISTS (
              SELECT 1 FROM aps_badilag.eac_validasi ev 
              WHERE ev.perkara_id = p.id
          )" . $search_query . " ORDER BY p.id LIMIT $start, $limit";
$result = mysql_query($query);

if (!$result) {
    die('Query gagal: ' . mysql_error());
}

// Function to display NULL values
function displayValue($value) {
    return is_null($value) || $value === '' ? '<span class="null-value">NULL</span>' : htmlspecialchars($value);
}
?>

<!-- Link ke CSS internal -->
<link rel="stylesheet" href="../assets/css/css_pihak.css?v=<?php echo time(); ?>">

<!-- Content Area dengan Design Sistem Pengadilan -->
<div class="content-wrapper">
    <div class="content-header">
        <div style="margin-bottom: 30px;">
            <h2 style="margin: 0; color: #2c3e50; font-size: 28px; font-weight: 600;">
                <i class="fas fa-users" style="margin-right: 10px; color: #27ae60;"></i>
                Data Pihak
            </h2>
            <p style="margin: 5px 0 0 0; color: #7f8c8d;">Kelola data pihak dalam perkara</p>
        </div>
    </div>
    
    <?php
    if (isset($_GET['message'])) {
        $message = $_GET['message'];
        $type = isset($_GET['type']) ? $_GET['type'] : 'success';
        echo '<div class="alert alert-'.$type.' alert-dismissible">
                <i class="fas fa-'.($type === 'success' ? 'check-circle' : 'exclamation-triangle').'"></i>
                '.htmlspecialchars($message).'
                <button type="button" class="alert-close" onclick="this.parentElement.style.display=\'none\'">×</button>
              </div>';
    }
    ?>
    
    <div class="table-card">
        <div class="table-header">
            <div class="table-controls-left">
                <!--
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Data Pihak
                </a>
                -->
                <div class="entries-control">
                    <span class="control-label">Show</span>
                    <select id="entriesSelect" onchange="changeEntries()" class="form-select-sm">
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                    <span class="control-label">entries</span>
                </div>
            </div> 
            
            <div class="table-controls-right">
                <form method="GET" class="search-form">
                    <input type="hidden" name="entries" value="<?php echo $limit; ?>">
                    <div class="search-input-group">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control search-input" 
                               placeholder="Cari nama, alamat, pekerjaan..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <?php if (!empty($search)): ?>
                        <a href="index.php?entries=<?php echo $limit; ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-head">
                    <tr>
                        <th width="80px">ID</th>
                        <th>Nama</th>
                        <th>Alamat</th>
                        <th width="120px">Tempat Lahir</th>
                        <th width="110px">Tanggal Lahir</th>
                        <th width="120px">Pekerjaan</th>
                        <th width="120px">Keterangan</th>
                        <th width="140px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysql_num_rows($result) > 0) {
                        while ($row = mysql_fetch_assoc($result)) {
                            echo '<tr class="table-row">';
                            echo '<td class="id-cell">' . htmlspecialchars($row['id']) . '</td>';
                            echo '<td class="nama-cell">' . htmlspecialchars($row['nama']) . '</td>';
                            
                            // Alamat with tooltip for long text
                            $alamat = htmlspecialchars($row['alamat']);
                            if (strlen($alamat) > 50) {
                                echo '<td class="alamat-cell" title="' . $alamat . '">' . substr($alamat, 0, 50) . '...</td>';
                            } else {
                                echo '<td class="alamat-cell">' . $alamat . '</td>';
                            }
                            
                            // Display NULL values properly
                            echo '<td>' . displayValue($row['tempat_lahir']) . '</td>';
                            echo '<td>' . displayValue($row['tanggal_lahir']) . '</td>';
                            echo '<td>' . displayValue($row['pekerjaan']) . '</td>';
                            
                            // Keterangan with tooltip for long text
                            $keterangan = $row['keterangan'];
                            if (is_null($keterangan) || $keterangan === '') {
                                echo '<td><span class="null-value">NULL</span></td>';
                            } else {
                                $keterangan_escaped = htmlspecialchars($keterangan);
                                if (strlen($keterangan_escaped) > 30) {
                                    echo '<td title="' . $keterangan_escaped . '">' . substr($keterangan_escaped, 0, 30) . '...</td>';
                                } else {
                                    echo '<td>' . $keterangan_escaped . '</td>';
                                }
                            }
                            
                            echo '<td>
                                    <div class="action-buttons">
                                        <a href="edit.php?id=' . urlencode($row['id']) . '" class="btn-action btn-edit" title="Edit Data">
                                            <i class="fas fa-edit"></i>
                                            <span>Edit</span>
                                        </a>
                                        <!--
                                        <a href="delete.php?id=' . urlencode($row['id']) . '" class="btn-action btn-delete" title="Preview Hapus">
                                           <i class="fas fa-trash"></i> 
                                           <span>Hapus</span>
                                        </a>
                                        -->
                                    </div>
                                  </td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" class="no-data">
                                <div class="no-data-content">
                                    <i class="fas fa-inbox"></i>
                                    <p>Tidak ada data ditemukan</p>
                                </div>
                              </td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="table-footer">
            <div class="table-info">
                <span class="info-text">
                    Showing <?php echo min($start + 1, $total_records); ?> to <?php echo min($start + $limit, $total_records); ?> of <?php echo $total_records; ?> entries
                    <?php if (!empty($search)): ?>
                        <span class="filtered-info">(filtered from total entries)</span>
                    <?php endif; ?>
                </span>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&entries=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page-1); ?>&entries=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                            if ($i == $page):
                        ?>
                            <li class="page-item active">
                                <span class="page-link"><?php echo $i; ?></span>
                            </li>
                        <?php else: ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $i; ?>&entries=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php 
                            endif;
                        endfor; 
                        ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page+1); ?>&entries=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>&entries=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Link ke JavaScript internal -->
<script src="../assets/js/js_pihak.js"></script>

<?php include '../includes/footer.php'; ?>

<?php mysql_close($connection); ?>