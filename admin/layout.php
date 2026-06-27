<?php
if (!defined('APP_NAME')) { http_response_code(403); exit('Akses ditolak.'); }

function admin_layout_header($active = 'dashboard', $title = '') {
    $role = Auth::role();
    $menu = [
        'dashboard' => ['📊','Dashboard', ['superadmin','admin_program','admin_keuangan']],
        'donasi'    => ['💳','Donasi', ['superadmin','admin_keuangan']],
        'program'   => ['📋','Program', ['superadmin','admin_program']],
        'relawan'   => ['🙌','Relawan', ['superadmin','admin_program']],
        'artikel'   => ['📰','Artikel', ['superadmin','admin_program']],
        'galeri'    => ['🖼️','Galeri', ['superadmin','admin_program']],
        'mitra'     => ['🤝','Mitra', ['superadmin','admin_program']],
        'organisasi'=> ['🏛️','Organisasi', ['superadmin','admin_program']],
        'konten'    => ['🧩','Konten Publik', ['superadmin','admin_program']],
        'keuangan'  => ['💼','Keuangan', ['superadmin','admin_keuangan']],
        'user'      => ['👥','Pengguna', ['superadmin']],
        'settings'  => ['⚙️','Pengaturan', ['superadmin']],
        'backup'    => ['💾','Backup', ['superadmin']],
        'audit'     => ['🛡️','Audit Log', ['superadmin']],
    ];
    ?><!DOCTYPE html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title?$title.' — Admin':'Admin Panel') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head><body class="admin-body">
<aside class="sidebar" id="sidebar">
  <div class="side-brand"><?= render_brand_mark('brand-mark', setting('yayasan_short', 'Al Fatih') . ' Logo') ?><b><?= e(setting('yayasan_short', 'Al Fatih')) ?> Admin</b></div>
  <nav class="side-nav">
    <?php foreach ($menu as $key=>$m): if(!in_array($role,$m[2],true)) continue; ?>
      <a href="<?= admin_url($key) ?>" class="<?= $active===$key?'active':'' ?>"><span><?= $m[0] ?></span><?= e($m[1]) ?></a>
    <?php endforeach; ?>
    <a href="<?= url('home') ?>" target="_blank"><span>🌐</span>Lihat Website</a>
    <a href="<?= url('logout') ?>"><span>🚪</span>Keluar</a>
  </nav>
</aside>
<div class="admin-main">
  <header class="admin-top">
    <button class="side-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
    <h1><?= e($title?:ucfirst($active)) ?></h1>
    <span class="admin-user"><?= e($_SESSION['uname']??'Admin') ?> · <?= e(str_replace('_',' ',$role)) ?></span>
  </header>
  <div class="admin-content"><?php
}

function admin_layout_footer() {
    ?></div></div><script src="<?= asset('js/app.js') ?>"></script></body></html><?php
}

function flash_set($msg, $type='ok'){ if(session_status()===PHP_SESSION_NONE)session_start(); $_SESSION['flash']=['m'=>$msg,'t'=>$type]; }
function flash_show(){ if(!empty($_SESSION['flash'])){ $f=$_SESSION['flash']; unset($_SESSION['flash']);
  echo '<div class="alert alert-'.($f['t']==='ok'?'ok':'err').'">'.e($f['m']).'</div>'; } }
