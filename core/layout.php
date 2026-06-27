<?php
if (!defined('APP_NAME')) { http_response_code(403); exit('Akses ditolak.'); }

function layout_header($title = '', $desc = '') {
    $yname = setting('yayasan_name', 'Yayasan Al Fatih Mulia Haramain');
    $pageTitle = $title ? ($title . ' — ' . $yname) : $yname;
    $desc = $desc ?: 'Melayani Umat, Membangun Peradaban. Donasi, relawan, dan dampak nyata dalam satu platform amanah.';
    ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
<header class="nav">
  <div class="container nav-inner">
    <a class="brand" href="<?= url('home') ?>">
      <span class="brand-mark">﷽</span>
      <span class="brand-text"><?= e(setting('yayasan_short','Al Fatih')) ?><small>Yayasan Amanah Nasional</small></span>
    </a>
    <button class="nav-toggle" onclick="document.body.classList.toggle('nav-open')" aria-label="Menu">☰</button>
    <nav class="nav-links">
      <a href="<?= url('home') ?>">Home</a>
      <a href="<?= url('tentang') ?>">Tentang Kami</a>
      <a href="<?= url('program') ?>">Program</a>
      <a href="<?= url('donasi') ?>">Donasi</a>
      <a href="<?= url('relawan') ?>">Relawan</a>
      <a href="<?= url('dokumentasi') ?>">Dokumentasi</a>
      <a href="<?= url('laporan') ?>">Laporan</a>
      <a href="<?= url('artikel') ?>">Artikel</a>
      <a href="<?= url('kemitraan') ?>">Kemitraan</a>
      <a href="<?= url('kontak') ?>">Kontak</a>
      <?php if (Auth::check()): ?>
        <a href="<?= Auth::isAdmin() ? admin_url('dashboard') : url('portal') ?>" class="btn btn-ghost">Dashboard</a>
        <a href="<?= url('logout') ?>" class="btn btn-ghost">Keluar</a>
      <?php else: ?>
        <a href="<?= url('login') ?>" class="btn btn-ghost">Masuk</a>
      <?php endif; ?>
      <a href="<?= url('donasi') ?>" class="btn btn-primary">Donasi Sekarang</a>
    </nav>
  </div>
</header>
<main><?php
}

function layout_footer() {
    $wa = setting('yayasan_wa', '');
    $yname = setting('yayasan_name', 'Yayasan Al Fatih Mulia Haramain');
    $email = setting('yayasan_email', '');
    $address = setting('yayasan_alamat', '');
    ?></main>
<footer class="footer">
  <div class="container footer-grid">
    <div>
      <div class="brand brand-foot"><span class="brand-mark">﷽</span><span class="brand-text"><?= e(setting('yayasan_short','Al Fatih')) ?><small>Yayasan Amanah Nasional</small></span></div>
      <p class="muted"><?= e($yname) ?>. Menjadi perantara kebaikan dan pahala jariyah untuk umat melalui program yang amanah, terukur, dan berkelanjutan.</p>
    </div>
    <div>
      <h4>Menu Utama</h4>
      <a href="<?= url('home') ?>">Home</a>
      <a href="<?= url('tentang') ?>">Tentang Kami</a>
      <a href="<?= url('program') ?>">Program</a>
      <a href="<?= url('donasi') ?>">Donasi</a>
      <a href="<?= url('laporan') ?>">Laporan</a>
      <a href="<?= url('artikel') ?>">Artikel</a>
    </div>
    <div>
      <h4>Hubungi Kami</h4>
      <?php if ($wa): ?><a href="<?= e(wa_link($wa, 'Assalamualaikum, saya ingin bertanya tentang program yayasan.')) ?>">WhatsApp Yayasan</a><?php endif; ?>
      <?php if ($email): ?><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
      <?php if ($address): ?><span class="footer-meta"><?= e($address) ?></span><?php endif; ?>
      <a href="<?= url('kontak') ?>">Kontak & Lokasi</a>
      <a href="<?= url('kemitraan') ?>">Ajukan Kemitraan</a>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>© <?= date('Y') ?> <?= e($yname) ?>. Dibangun dengan amanah.</span>
  </div>
</footer>
<div class="quick-float">
  <a class="quick-float-item quick-float-donate" href="<?= url('donasi') ?>">Donasi Sekarang</a>
  <?php if ($wa): ?><a class="quick-float-item quick-float-wa" href="<?= e(wa_link($wa, 'Assalamualaikum, saya ingin terhubung dengan admin yayasan.')) ?>" target="_blank" rel="noopener">WhatsApp Admin</a><?php endif; ?>
  <a class="quick-float-item quick-float-volunteer" href="<?= url('relawan') ?>">Daftar Relawan</a>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html><?php
}
