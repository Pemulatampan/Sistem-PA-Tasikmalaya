<?php

class SimpleCache {
    private $cache_dir;
    private $ttl;
    
    public function __construct($cache_dir, $ttl = 3600) {
        // Konversi path relatif jadi absolut
        if (substr($cache_dir, 0, 1) !== '/') {
            $cache_dir = dirname(__FILE__) . '/../' . $cache_dir;
        }
        
        $this->cache_dir = $cache_dir;
        $this->ttl = $ttl;
        
        // Buat folder cache jika belum ada
        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, 0755, true);
        }
    }
    
    // Ambil data dari cache
    public function get($key) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        // Cek apakah cache expired
        if (time() - filemtime($file) > $this->ttl) {
            @unlink($file);
            return null;
        }
        
        $data = @file_get_contents($file);
        if ($data === false) {
            return null;
        }
        
        return json_decode($data, true);
    }
    
    // Simpan data ke cache
    public function set($key, $data) {
        $file = $this->getCacheFile($key);
        $json = json_encode($data);
        
        if ($json === false) {
            return false;
        }
        
        return @file_put_contents($file, $json) !== false;
    }
    
    // Hapus cache tertentu
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return false;
    }
    
    // Hapus semua cache
    public function clear() {
        $pattern = $this->cache_dir . '/*.cache';
        $files = glob($pattern);
        
        if ($files === false) {
            return false;
        }
        
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }
    
    // Generate nama file cache
    private function getCacheFile($key) {
        $safe_key = md5($key);
        return $this->cache_dir . '/' . $safe_key . '.cache';
    }
    
    // Cek apakah cache exists dan masih valid
    public function has($key) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return false;
        }
        
        return (time() - filemtime($file)) <= $this->ttl;
    }
    
    // Get cache info
    public function getInfo($key) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return array(
                'exists' => false,
                'expired' => true
            );
        }
        
        $age = time() - filemtime($file);
        
        return array(
            'exists' => true,
            'expired' => $age > $this->ttl,
            'age' => $age,
            'ttl' => $this->ttl,
            'file' => $file,
            'size' => filesize($file)
        );
    }
}
?>