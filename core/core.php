<?php
/**
 * core/core.php - Bootstrap utama aplikasi
 */
$cfg = __DIR__ . '/../config.php';
if (!file_exists($cfg)) {
    header('Location: install.php');
    exit;
}
require_once $cfg;
if (!defined('APP_INSTALLED') || !APP_INSTALLED) {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '.') . '/install.php');
    exit;
}

// Path penyimpanan (backup & log)
define('STORAGE_DIR', __DIR__ . '/../storage');
define('BACKUP_DIR', STORAGE_DIR . '/backups');
define('LOG_DIR', STORAGE_DIR . '/logs');

// Error logging ke file (tidak menampilkan error ke publik)
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
if (is_dir(LOG_DIR) && is_writable(LOG_DIR)) { @ini_set('error_log', LOG_DIR . '/error.log'); }
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

// ---- Session hardening (sebelum session_start) ----
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.cookie_httponly', '1');
    if (PHP_VERSION_ID >= 70300) {
        @session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax','secure'=>$secure]);
    } else {
        @session_set_cookie_params(0, '/; samesite=Lax', '', $secure, true);
    }
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

Auth::start();

function ensure_runtime_schema() {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    DB::conn()->query(
        "CREATE TABLE IF NOT EXISTS volunteer_leads (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            city VARCHAR(120) DEFAULT NULL,
            phone VARCHAR(25) NOT NULL,
            email VARCHAR(150) DEFAULT NULL,
            profession VARCHAR(120) DEFAULT NULL,
            division VARCHAR(120) DEFAULT NULL,
            note VARCHAR(500) DEFAULT NULL,
            status ENUM('new','contacted','qualified','rejected') NOT NULL DEFAULT 'new',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    DB::conn()->query(
        "CREATE TABLE IF NOT EXISTS media_gallery (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(180) NOT NULL,
            category VARCHAR(80) DEFAULT NULL,
            media_type ENUM('photo','video') NOT NULL DEFAULT 'photo',
            media_path VARCHAR(255) DEFAULT NULL,
            video_url VARCHAR(255) DEFAULT NULL,
            description TEXT,
            status ENUM('published','draft') NOT NULL DEFAULT 'published',
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    DB::conn()->query(
        "CREATE TABLE IF NOT EXISTS partnership_leads (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            organization_name VARCHAR(180) NOT NULL,
            contact_name VARCHAR(120) NOT NULL,
            phone VARCHAR(25) NOT NULL,
            email VARCHAR(150) DEFAULT NULL,
            partnership_type VARCHAR(120) DEFAULT NULL,
            message TEXT,
            status ENUM('new','contacted','negotiation','approved','rejected') NOT NULL DEFAULT 'new',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    DB::conn()->query(
        "CREATE TABLE IF NOT EXISTS organization_members (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            full_name VARCHAR(160) NOT NULL,
            position VARCHAR(160) NOT NULL,
            board_group ENUM('pembina','pengawas','pengurus') NOT NULL DEFAULT 'pengurus',
            photo VARCHAR(255) DEFAULT NULL,
            bio TEXT,
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('published','draft') NOT NULL DEFAULT 'published',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_group (board_group),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    DB::conn()->query(
        "CREATE TABLE IF NOT EXISTS bank_accounts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            bank_name VARCHAR(120) NOT NULL,
            account_number VARCHAR(80) NOT NULL,
            account_holder VARCHAR(160) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('published','draft') NOT NULL DEFAULT 'published',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $volunteerEmailExists = (int) DB::val(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'volunteer_leads' AND COLUMN_NAME = 'email'"
    );
    if ($volunteerEmailExists === 0) {
        DB::conn()->query("ALTER TABLE volunteer_leads ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER phone");
    }
}

ensure_runtime_schema();

/**
 * Statistik dampak gabungan: sebagian DIDERIVASI dari donasi (real-time),
 * sebagian dari tabel impact_stats (input manual admin).
 */
function impact_data() {
    $derived = [
        'total_dana'      => (int) DB::val("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='verified'"),
        'total_donatur'   => (int) DB::val("SELECT COUNT(DISTINCT COALESCE(donor_phone, CONCAT('id',id))) FROM donations WHERE status='verified'"),
        'total_relawan'   => (int) DB::val("SELECT COUNT(*) FROM users WHERE role='relawan' AND status='active'"),
        'program_aktif'   => (int) DB::val("SELECT COUNT(*) FROM programs WHERE status='active'"),
        'penerima_manfaat_program' => (int) DB::val("SELECT COALESCE(SUM(beneficiaries),0) FROM programs"),
    ];
    $manual = [];
    foreach (DB::all("SELECT * FROM impact_stats ORDER BY sort ASC") as $r) {
        $manual[$r['skey']] = ['label' => $r['label'], 'value' => (int)$r['svalue'], 'icon' => $r['icon']];
    }
    return ['derived' => $derived, 'manual' => $manual];
}

/** Hitung dana tersalurkan (collected per program yang sudah ditandai tersalurkan via setting) */
function total_tersalurkan() {
    return (int) setting('total_tersalurkan', '0');
}

/** Leaderboard relawan berdasarkan donasi terverifikasi yang membawa referral */
function leaderboard($limit = 20) {
    return DB::all(
        "SELECT u.id, u.name, u.referral_code,
                COUNT(d.id) AS jml_donasi,
                COALESCE(SUM(d.amount),0) AS total
         FROM users u
         LEFT JOIN donations d ON d.referral_code = u.referral_code AND d.status='verified'
         WHERE u.role='relawan'
         GROUP BY u.id, u.name, u.referral_code
         ORDER BY total DESC, jml_donasi DESC
         LIMIT " . (int)$limit
    );
}
