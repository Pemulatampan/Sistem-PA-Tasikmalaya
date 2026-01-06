<?php
// api/controllers/PihakController.php
// Controller untuk endpoint Pihak
// Dependencies: Database, Response, Validator (loaded from index.php)

class PihakController {
    
    /**
     * GET /api/pihak
     * Mendapatkan list pihak dengan pagination dan search
     * 
     * Query params:
     * - page (optional, default: 1)
     * - limit (optional, default: 10)
     * - search (optional)
     */
    public function getList() {
        // Get query parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? Validator::sanitize($_GET['search']) : '';
        
        // Validasi pagination
        if ($page < 1) $page = 1;
        if ($limit < 1 || $limit > 100) $limit = 10;
        
        $start = ($page - 1) * $limit;
        
        // Build search query
        $search_query = '';
        if (!empty($search)) {
            $search_escaped = Database::escape($search);
            $search_query = " AND (p.nama LIKE '%$search_escaped%' 
                             OR p.alamat LIKE '%$search_escaped%' 
                             OR p.pekerjaan LIKE '%$search_escaped%'
                             OR p.keterangan LIKE '%$search_escaped%'
                             OR p.id LIKE '%$search_escaped%'
                             OR p.tempat_lahir LIKE '%$search_escaped%'
                             OR p.tanggal_lahir LIKE '%$search_escaped%')";
        }
        
        // Count total records - hanya yang ada di kedua database
        $count_query = "SELECT COUNT(*) as total FROM pihak p 
                        WHERE EXISTS (
                            SELECT 1 FROM aps_badilag.eac_validasi ev 
                            WHERE ev.perkara_id = p.id
                        )" . $search_query;
        
        $count_result = Database::query($count_query);
        $count_row = mysql_fetch_assoc($count_result);
        $total_records = (int)$count_row['total'];
        
        // Get records with pagination
        $query = "SELECT p.* FROM pihak p 
                  WHERE EXISTS (
                      SELECT 1 FROM aps_badilag.eac_validasi ev 
                      WHERE ev.perkara_id = p.id
                  )" . $search_query . " 
                  ORDER BY p.id 
                  LIMIT $start, $limit";
        
        $result = Database::query($query);
        
        // Fetch data
        $data_pihak = array();
        while ($row = mysql_fetch_assoc($result)) {
            $data_pihak[] = $row;
        }
        
        // Response dengan pagination
        Response::paginated(
            'Data pihak berhasil diambil',
            $data_pihak,
            $total_records,
            $page,
            $limit
        );
    }
    
    /**
     * GET /api/pihak/{id}
     * Mendapatkan detail pihak berdasarkan ID
     * 
     * @param string $id - Pihak ID
     */
    public function getDetail($id) {
        // Validasi ID
        if (empty($id)) {
            Response::error('ID pihak harus diisi', 400);
        }
        
        // Escape ID
        $id_escaped = Database::escape($id);
        
        // Query detail pihak
        $query = "SELECT p.* FROM pihak p 
                  WHERE p.id = '$id_escaped'
                  AND EXISTS (
                      SELECT 1 FROM aps_badilag.eac_validasi ev 
                      WHERE ev.perkara_id = p.id
                  )
                  LIMIT 1";
        
        $result = Database::query($query);
        
        // Cek apakah data ditemukan
        if (mysql_num_rows($result) == 0) {
            Response::error('Data pihak tidak ditemukan', 404);
        }
        
        $detail = mysql_fetch_assoc($result);
        
        // Response sukses
        Response::success('Detail pihak berhasil diambil', $detail);
    }
    
    /**
     * PUT /api/pihak/{id}
     * Update data pihak
     * 
     * @param string $id - Pihak ID
     * 
     * Body params (JSON):
     * - nama (optional)
     * - alamat (optional)
     * - tempat_lahir (optional)
     * - tanggal_lahir (optional, format: YYYY-MM-DD)
     * - pekerjaan (optional)
     * - keterangan (optional)
     */
    public function update($id) {
        // Validasi ID
        if (empty($id)) {
            Response::error('ID pihak harus diisi', 400);
        }
        
        // Get JSON input
        $json_input = file_get_contents('php://input');
        $input = json_decode($json_input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON format', 400);
        }
        
        // Validasi: minimal harus ada 1 field yang diupdate
        $allowed_fields = array('nama', 'alamat', 'tempat_lahir', 'tanggal_lahir', 'pekerjaan', 'keterangan');
        $update_fields = array();
        
        foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
                // Validasi khusus untuk tanggal_lahir
                if ($field === 'tanggal_lahir' && !empty($input[$field])) {
                    if (!Validator::isValidDate($input[$field])) {
                        Response::error('Format tanggal_lahir tidak valid (gunakan YYYY-MM-DD)', 400);
                    }
                }
                
                $value = Validator::sanitize($input[$field]);
                $value_escaped = Database::escape($value);
                $update_fields[] = "$field = '$value_escaped'";
            }
        }
        
        if (empty($update_fields)) {
            Response::error('Tidak ada field yang diupdate', 400);
        }
        
        // Escape ID
        $id_escaped = Database::escape($id);
        
        // Cek apakah data exists
        $check_query = "SELECT id FROM pihak WHERE id = '$id_escaped' LIMIT 1";
        $check_result = Database::query($check_query);
        
        if (mysql_num_rows($check_result) == 0) {
            Response::error('Data pihak tidak ditemukan', 404);
        }
        
        // Update query
        $update_query = "UPDATE pihak SET " . implode(', ', $update_fields) . " WHERE id = '$id_escaped'";
        
        Database::query($update_query);
        
        // Get updated data
        $detail_query = "SELECT * FROM pihak WHERE id = '$id_escaped' LIMIT 1";
        $detail_result = Database::query($detail_query);
        $updated_data = mysql_fetch_assoc($detail_result);
        
        // Response sukses
        Response::success('Data pihak berhasil diupdate', $updated_data);
    }
}
?>