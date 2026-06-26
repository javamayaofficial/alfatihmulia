<?php
/**
 * core/db.php - Koneksi database + query helper (prepared statement)
 */
if (!defined('APP_NAME')) { http_response_code(403); exit('Akses ditolak.'); }

class DB {
    private static $conn = null;

    public static function conn() {
        if (self::$conn === null) {
            if (function_exists('mysqli_report')) { mysqli_report(MYSQLI_REPORT_OFF); } // PHP 8.1+ compat
            self::$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (self::$conn->connect_errno) {
                http_response_code(500);
                exit('Koneksi database gagal. Periksa config.php Anda.');
            }
            self::$conn->set_charset('utf8mb4');
        }
        return self::$conn;
    }

    /** Jalankan query dengan parameter terikat. $types contoh: 'sii' */
    public static function run($sql, $types = '', $params = []) {
        $stmt = self::conn()->prepare($sql);
        if (!$stmt) { return false; }
        if ($types !== '' && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }

    /** Ambil semua baris sebagai array assoc */
    public static function all($sql, $types = '', $params = []) {
        $stmt = self::run($sql, $types, $params);
        if (!$stmt) return [];
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    /** Ambil satu baris */
    public static function one($sql, $types = '', $params = []) {
        $stmt = self::run($sql, $types, $params);
        if (!$stmt) return null;
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    /** Ambil nilai tunggal (kolom pertama baris pertama) */
    public static function val($sql, $types = '', $params = [], $default = 0) {
        $row = self::one($sql, $types, $params);
        if (!$row) return $default;
        $v = array_values($row)[0];
        return $v === null ? $default : $v;
    }

    /** INSERT dan kembalikan last insert id */
    public static function insert($sql, $types = '', $params = []) {
        $stmt = self::run($sql, $types, $params);
        if (!$stmt) return 0;
        $id = self::conn()->insert_id;
        $stmt->close();
        return $id;
    }
}
