<?php
// api/index.php
// Entry point untuk semua API requests

// Set error reporting
// Suppress deprecated mysql warnings karena pakai PHP 5.6
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0); // Matikan display errors untuk production

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');

// Load config dari parent folder
require_once '../config/config.php';

// Load API helpers
require_once 'helpers/response.php';
require_once 'helpers/validator.php';

// Load config
require_once 'config/cors.php';
require_once 'config/database.php';

// Load routes
require_once 'routes.php';

// Get request method dan URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remove query string dari URI
$uri = strtok($uri, '?');

// Remove base path (api/)
// Sesuaikan dengan nama folder project kamu
$base_path = '/Sistem%20Pengadilan%20Agama1/api';
$uri = str_replace($base_path, '', $uri);

// Remove trailing slash
$uri = rtrim($uri, '/');

// Split URI menjadi segments
$segments = explode('/', trim($uri, '/'));

// Handle empty URI
if (empty($segments[0])) {
    Response::success('API is running', array(
        'version' => '1.0',
        'endpoints' => array(
            'GET /perkara/rekap' => 'Get rekap perkara',
            'GET /perkara/rekap/statistik' => 'Get statistik perkara',
            'GET /perkara/detail/{id}' => 'Get detail perkara'
        )
    ));
    exit;
}

// Handle OPTIONS request (CORS preflight)
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Route request
try {
    handleRoute($method, $segments);
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
?>