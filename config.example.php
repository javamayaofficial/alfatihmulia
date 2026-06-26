<?php
/**
 * config.php - Konfigurasi Al Fatih Impact Platform
 * File ini dibuat & diisi otomatis oleh Installer Wizard (install.php).
 * JANGAN dibagikan ke publik. Sudah dilindungi .htaccess.
 */

// Database
define('DB_HOST', '__DB_HOST__');
define('DB_NAME', '__DB_NAME__');
define('DB_USER', '__DB_USER__');
define('DB_PASS', '__DB_PASS__');

// Aplikasi
define('APP_NAME', 'Al Fatih Impact Platform');
define('APP_INSTALLED', true);

// Base URL otomatis (tanpa hardcode domain)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if (basename($dir) === 'admin') { $dir = dirname($dir); }
define('BASE_URL', rtrim($scheme . '://' . $host . $dir, '/'));

// Keamanan
define('SECRET_KEY', '__SECRET_KEY__');
date_default_timezone_set('Asia/Jakarta');
