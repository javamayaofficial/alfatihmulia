<?php
if (!defined('APP_NAME')) { exit; }
layout_header('Tentang Yayasan');
$legal = [
  'Akta Pendirian' => setting('legal_akta',''),
  'SK Kemenkumham' => setting('legal_sk',''),
  'NPWP' => setting('legal_npwp',''),
];
?>
<section class="page-head"><div class="container"><h1>Tentang Yayasan</h1><p class="muted"><?= e(setting('yayasan_name','Yayasan Al Fatih Mulia Haramain')) ?></p></div></section>
<section class="section"><div class="container narrow prose">
  <h2>Profil & Visi</h2>
  <p><?= nl2br(e(setting('yayasan_profil','Yayasan Al Fatih Mulia Haramain hadir sebagai lembaga filantropi Islam yang berkomitmen melayani umat melalui program air bersih, pendidikan, sosial kemanusiaan, wakaf produktif, serta layanan haji dan umrah. Kami percaya bahwa transparansi adalah pondasi kepercayaan.'))) ?></p>
  <h2>Visi</h2>
  <p><?= e(setting('yayasan_visi','Menjadi platform filantropi Islam nasional yang transparan, profesional, amanah, dan berdampak bagi umat.')) ?></p>
  <h2>Misi</h2>
  <p><?= nl2br(e(setting('yayasan_misi',"Meningkatkan kepercayaan publik melalui transparansi.\nMempermudah masyarakat berbuat kebaikan.\nMengelola program umat secara terukur dan berdampak."))) ?></p>

  <h2>Legalitas</h2>
  <div class="legal-grid">
  <?php foreach ($legal as $k=>$v): ?>
    <div class="legal-card"><b><?= e($k) ?></b><span class="muted"><?= $v ? e($v) : 'Tersedia atas permintaan' ?></span></div>
  <?php endforeach; ?>
  </div>
  <p class="note">ℹ️ Dokumen legalitas resmi dapat diunggah admin di Pengaturan dan ditampilkan di sini untuk meningkatkan kepercayaan publik.</p>

  <?php $wa=setting('yayasan_wa',''); if($wa): ?>
  <div class="center"><a class="btn btn-primary" target="_blank" href="<?= e(wa_link($wa,'Assalamualaikum, saya ingin mengenal lebih jauh tentang yayasan.')) ?>">Hubungi Kami via WhatsApp</a></div>
  <?php endif; ?>
</div></section>
<?php layout_footer(); ?>
