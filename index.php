<?php
/**
 * index.php - Front Controller Al Fatih Impact Platform
 * Routing via query-string (?page=) — andal di shared hosting tanpa mod_rewrite.
 */
require_once __DIR__ . '/core/core.php';
require_once __DIR__ . '/core/layout.php';

$page = preg_replace('/[^a-z0-9_\-]/', '', strtolower($_GET['page'] ?? 'home'));

// Aksi tanpa tampilan
if ($page === 'logout') { Auth::logout(); header('Location: ' . url('home')); exit; }

$routes = [
    'home', 'tentang', 'program', 'impact', 'donasi', 'relawan',
    'dokumentasi', 'laporan', 'artikel', 'kemitraan', 'kontak',
    'leaderboard',
    'login', 'register', 'portal',
];

if (!in_array($page, $routes, true)) {
    http_response_code(404);
    $page = '404';
}

$file = __DIR__ . '/modules/' . $page . '.php';
if ($page === '404' || !file_exists($file)) {
    layout_header('Halaman Tidak Ditemukan');
    echo '<div class="container section"><div class="empty-state"><h2>404 — Halaman tidak ditemukan</h2>'
       . '<p class="muted">Mungkin tautannya keliru. Mari kembali ke beranda.</p>'
       . '<a class="btn btn-primary" href="' . url('home') . '">Kembali ke Beranda</a></div></div>';
    layout_footer();
    exit;
}

require $file;
