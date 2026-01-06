<?php
// api/config/cors.php
// CORS configuration untuk API

// Allow requests from any origin (untuk development)
// Untuk production, ganti "*" dengan domain spesifik
header('Access-Control-Allow-Origin: *');

// Allow methods
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// Allow headers
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Allow credentials (cookies, authorization headers)
header('Access-Control-Allow-Credentials: true');

// Cache preflight request for 1 hour
header('Access-Control-Max-Age: 3600');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>