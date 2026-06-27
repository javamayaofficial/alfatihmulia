<?php
/**
 * admin/index.php - Router Panel Admin (query-string ?r=)
 */
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/layout.php';

Auth::requireAdmin();

$r = preg_replace('/[^a-z0-9_\-]/', '', strtolower($_GET['r'] ?? 'dashboard'));
$pages = ['dashboard','donasi','program','relawan','artikel','galeri','mitra','organisasi','konten','keuangan','user','settings','backup','audit'];
if (!in_array($r, $pages, true)) $r = 'dashboard';

$file = __DIR__ . '/' . $r . '.php';
if (!file_exists($file)) { $file = __DIR__ . '/dashboard.php'; }
require $file;
