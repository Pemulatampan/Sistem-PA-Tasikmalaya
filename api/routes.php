<?php
// api/routes.php
// Routing untuk semua endpoint API

// Load database config dulu sebelum controllers
require_once 'config/database.php';

// Load controllers
require_once 'controllers/perkaracontroller.php';
require_once 'controllers/pihakcontroller.php';
require_once 'controllers/penilaiankinerjacontroller.php';
/**
 * Handle routing berdasarkan method dan URI segments
 * 
 * @param string $method - HTTP method (GET, POST, PUT, DELETE)
 * @param array $segments - URI segments (misal: ['perkara', 'rekap'])
 */
function handleRoute($method, $segments) {
    // Ambil resource utama (perkara, penilaian-kinerja, pihak)
    $resource = isset($segments[0]) ? $segments[0] : '';
    
    // Routing untuk PERKARA
    if ($resource === 'perkara') {
        $controller = new PerkaraController();
        
        // GET /perkara/rekap
        if ($method === 'GET' && isset($segments[1]) && $segments[1] === 'rekap') {
            
            // GET /perkara/rekap/statistik
            if (isset($segments[2]) && $segments[2] === 'statistik') {
                $controller->getStatistik();
                return;
            }
            
            // GET /perkara/rekap (default)
            $controller->getRekap();
            return;
        }
        
        // GET /perkara/detail/{id}
        if ($method === 'GET' && isset($segments[1]) && $segments[1] === 'detail' && isset($segments[2])) {
            $controller->getDetail($segments[2]);
            return;
        }
        
        // Route tidak ditemukan
        Response::error('Endpoint tidak ditemukan', 404);
        return;
    }
    
    // Routing untuk PIHAK
    if ($resource === 'pihak') {
        $controller = new PihakController();
        
        // GET /pihak (list)
        if ($method === 'GET' && !isset($segments[1])) {
            $controller->getList();
            return;
        }
        
        // GET /pihak/{id} (detail)
        if ($method === 'GET' && isset($segments[1])) {
            $controller->getDetail($segments[1]);
            return;
        }
        
        // PUT /pihak/{id} (update)
        if ($method === 'PUT' && isset($segments[1])) {
            $controller->update($segments[1]);
            return;
        }
        
        // Method tidak didukung
        Response::error('Method tidak didukung untuk resource pihak', 405);
        return;
    }

    // Routing untuk PENILAIAN KINERJA
    if ($resource === 'penilaian-kinerja') {
        $controller = new PenilaianKinerjaController();
        
        // GET /penilaian-kinerja/summary
        if ($method === 'GET' && isset($segments[1]) && $segments[1] === 'summary') {
            $controller->getSummary();
            return;
        }
        
        // GET /penilaian-kinerja (full data)
        if ($method === 'GET' && !isset($segments[1])) {
            $controller->getData();
            return;
        }
        
        // Route tidak ditemukan
        Response::error('Endpoint tidak ditemukan', 404);
        return;
    }

    // Resource tidak ditemukan
    Response::error('Resource tidak ditemukan', 404);
}
?>