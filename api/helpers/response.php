<?php
// api/helpers/Response.php
// Helper untuk format response JSON yang konsisten

class Response {
    
    /**
     * Send success response
     * 
     * @param string $message - Pesan sukses
     * @param mixed $data - Data yang akan dikembalikan
     * @param int $code - HTTP status code (default: 200)
     */
    public static function success($message, $data = null, $code = 200) {
        http_response_code($code);
        
        $response = array(
            'success' => true,
            'message' => $message,
            'data' => $data
        );
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send error response
     * 
     * @param string $message - Pesan error
     * @param int $code - HTTP status code (default: 400)
     * @param mixed $errors - Detail error (optional)
     */
    public static function error($message, $code = 400, $errors = null) {
        http_response_code($code);
        
        $response = array(
            'success' => false,
            'message' => $message
        );
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send paginated response
     * 
     * @param string $message - Pesan sukses
     * @param array $data - Data array
     * @param int $total - Total records
     * @param int $page - Current page
     * @param int $limit - Records per page
     */
    public static function paginated($message, $data, $total, $page, $limit) {
        http_response_code(200);
        
        $total_pages = ceil($total / $limit);
        
        $response = array(
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => array(
                'total_records' => (int)$total,
                'total_pages' => (int)$total_pages,
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'showing' => count($data)
            )
        );
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
}
?>