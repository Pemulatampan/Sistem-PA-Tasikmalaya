<?php
// api/helpers/Validator.php
// Helper untuk validasi input

class Validator {
    
    /**
     * Validasi tanggal format Y-m-d
     * 
     * @param string $date - Tanggal yang akan divalidasi
     * @return bool
     */
    public static function isValidDate($date) {
        if (empty($date)) {
            return false;
        }
        
        // Cek format YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        
        // Cek apakah tanggal valid
        $timestamp = strtotime($date);
        return $timestamp !== false && $timestamp > 0;
    }
    
    /**
     * Validasi parameter yang required
     * 
     * @param array $params - Array parameters yang perlu dicek
     * @param array $required - Array nama parameter yang required
     * @return array - Array error messages, kosong jika valid
     */
    public static function required($params, $required) {
        $errors = array();
        
        foreach ($required as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                $errors[$field] = "Field '$field' harus diisi";
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize input string
     * 
     * @param string $input - Input yang akan di-sanitize
     * @return string
     */
    public static function sanitize($input) {
        if (empty($input)) {
            return '';
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Validasi range tanggal
     * 
     * @param string $tanggal_mulai
     * @param string $tanggal_akhir
     * @return array - Array error messages, kosong jika valid
     */
    public static function validateDateRange($tanggal_mulai, $tanggal_akhir) {
        $errors = array();
        
        // Cek format tanggal
        if (!self::isValidDate($tanggal_mulai)) {
            $errors['tanggal_mulai'] = 'Format tanggal_mulai tidak valid (gunakan YYYY-MM-DD)';
        }
        
        if (!self::isValidDate($tanggal_akhir)) {
            $errors['tanggal_akhir'] = 'Format tanggal_akhir tidak valid (gunakan YYYY-MM-DD)';
        }
        
        // Jika format valid, cek logika range
        if (empty($errors)) {
            $start = strtotime($tanggal_mulai);
            $end = strtotime($tanggal_akhir);
            
            if ($start > $end) {
                $errors['tanggal'] = 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir';
            }
        }
        
        return $errors;
    }
    
    /**
     * Validasi ID (harus numeric)
     * 
     * @param mixed $id
     * @return bool
     */
    public static function isValidId($id) {
        return !empty($id) && is_numeric($id) && $id > 0;
    }
}
?>