<?php
// api/config/database.php
// Wrapper untuk koneksi database yang sudah ada di config.php

class Database {
    
    /**
     * Get MySQL legacy connection
     * Digunakan untuk query yang pakai mysql_* functions
     * 
     * @return resource - MySQL connection
     */
    public static function getConnection() {
        global $connection;
        
        if (!$connection) {
            Response::error('Koneksi database gagal', 500);
        }
        
        return $connection;
    }
    
    /**
     * Get PDO connection untuk database SIPP
     * Digunakan untuk query yang pakai PDO
     * 
     * @return PDO
     */
    public static function getPDO() {
        global $pdo_sipp;
        
        if (!$pdo_sipp) {
            Response::error('Koneksi PDO gagal', 500);
        }
        
        return $pdo_sipp;
    }
    
    /**
     * Get PDO connection untuk database Badilag
     * 
     * @return PDO
     */
    public static function getPDOBadilag() {
        global $pdo_badilag;
        
        if (!$pdo_badilag) {
            Response::error('Koneksi PDO Badilag gagal', 500);
        }
        
        return $pdo_badilag;
    }
    
    /**
     * Escape string untuk MySQL legacy
     * 
     * @param string $string
     * @return string
     */
    public static function escape($string) {
        $conn = self::getConnection();
        return mysql_real_escape_string($string, $conn);
    }
    
    /**
     * Execute query dengan MySQL legacy
     * 
     * @param string $query
     * @return resource|bool
     */
    public static function query($query) {
        $conn = self::getConnection();
        $result = mysql_query($query, $conn);
        
        if (!$result) {
            Response::error('Query error: ' . mysql_error($conn), 500);
        }
        
        return $result;
    }
}
?>