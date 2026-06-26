<?php
if (!defined('APP_NAME')) { http_response_code(403); exit('Akses ditolak.'); }

function layout_header($title = '', $desc = '') {
    $yname = setting('yayasan_name', 'Al Fatih Impact Platform');
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
      <span class="brand-text"><?= e(setting('yayasan_short','Al Fatih')) ?><small>Impact Platform</small></span>
    </a>
    <button class="nav-toggle" onclick="document.body.classList.toggle('nav-open')" aria-label="Menu">☰</button>
    <nav class="nav-links">
      <a href="<?= url('program') ?>">Program</a>
      <a href="<?= url('impact') ?>">Dampak</a>
      <a href="<?= url('relawan') ?>">Relawan</a>
      <a href="<?= url('laporan') ?>">Transparansi</a>
      <a href="<?= url('artikel') ?>">Berita</a>
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
    ?></main>
<footer class="footer">
  <div class="container footer-grid">
    <div>
      <div class="brand brand-foot"><span class="brand-mark">﷽</span><span class="brand-text"><?= e(setting('yayasan_short','Al Fatih')) ?><small>Impact Platform</small></span></div>
      <p class="muted"><?= e($yname) ?>. Melayani Umat, Membangun Peradaban.</p>
    </div>
    <div>
      <h4>Tautan</h4>
      <a href="<?= url('tentang') ?>">Tentang Yayasan</a>
      <a href="<?= url('program') ?>">Program</a>
      <a href="<?= url('laporan') ?>">Laporan Transparansi</a>
      <a href="<?= url('relawan') ?>">Jadi Relawan</a>
    </div>
    <div>
      <h4>Hubungi Kami</h4>
      <?php if ($wa): ?><a href="<?= e(wa_link($wa, 'Assalamualaikum, saya ingin bertanya tentang program yayasan.')) ?>">WhatsApp Yayasan</a><?php endif; ?>
      <a href="<?= url('kontak') ?>">Kontak & Lokasi</a>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>© <?= date('Y') ?> <?= e($yname) ?>. Dibangun dengan amanah.</span>
  </div>
</footer>
<?php if ($wa): ?>
<a class="wa-float" href="<?= e(wa_link($wa, 'Assalamualaikum, saya ingin bertanya tentang yayasan.')) ?>" target="_blank" rel="noopener" aria-label="Chat WhatsApp">
  <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.8 14.06c-.24.68-1.4 1.3-1.94 1.38-.5.07-1.13.1-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.79-4.17-4.94-4.36-.14-.19-1.18-1.57-1.18-2.99 0-1.42.75-2.12 1.01-2.41.27-.29.58-.36.78-.36.19 0 .39 0 .56.01.18.01.42-.07.66.5.24.59.82 2.03.89 2.18.07.14.12.31.02.5-.09.19-.14.31-.27.48-.14.17-.29.37-.41.5-.14.14-.28.29-.12.57.16.27.71 1.17 1.53 1.9 1.05.94 1.94 1.23 2.21 1.37.27.14.43.12.59-.07.16-.19.68-.79.86-1.06.18-.27.36-.22.61-.13.25.09 1.59.75 1.86.89.27.14.45.21.52.32.07.12.07.68-.17 1.36z"/></svg>
</a>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html><?php
}
