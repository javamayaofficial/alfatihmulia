<?php
/**
 * core/auth.php - Autentikasi & kontrol akses berbasis role
 */
if (!defined('APP_NAME')) { http_response_code(403); exit('Akses ditolak.'); }

class Auth {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public static function attempt($email, $password) {
        $u = DB::one("SELECT * FROM users WHERE email=? AND status='active' LIMIT 1", 's', [$email]);
        if ($u && $u['password_hash'] && password_verify($password, $u['password_hash'])) {
            self::login($u);
            return true;
        }
        return false;
    }

    public static function login($u) {
        self::start();
        session_regenerate_id(true);
        $_SESSION['uid']   = $u['id'];
        $_SESSION['uname'] = $u['name'];
        $_SESSION['role']  = $u['role'];
        $_SESSION['ref']   = $u['referral_code'];
    }

    public static function logout() {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    public static function check() { self::start(); return !empty($_SESSION['uid']); }
    public static function id()    { self::start(); return $_SESSION['uid'] ?? null; }
    public static function role()  { self::start(); return $_SESSION['role'] ?? null; }
    public static function user()  {
        if (!self::check()) return null;
        return DB::one("SELECT * FROM users WHERE id=? LIMIT 1", 'i', [self::id()]);
    }

    /** Apakah user termasuk admin (salah satu role admin) */
    public static function isAdmin() {
        return in_array(self::role(), ['superadmin','admin_program','admin_keuangan'], true);
    }

    /** Guard: wajib login admin, jika tidak redirect ke login */
    public static function requireAdmin($minRole = null) {
        if (!self::isAdmin()) {
            header('Location: ' . url('login', ['next' => 'admin']));
            exit;
        }
        if ($minRole === 'superadmin' && self::role() !== 'superadmin') {
            http_response_code(403);
            exit('Hanya Super Admin yang dapat mengakses halaman ini.');
        }
    }

    /** Guard: wajib login (donatur/relawan/admin) */
    public static function requireLogin() {
        if (!self::check()) { header('Location: ' . url('login')); exit; }
    }
}
