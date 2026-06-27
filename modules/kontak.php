<?php
if (!defined('APP_NAME')) { exit; }
$wa = setting('yayasan_wa',''); $email = setting('yayasan_email',''); $addr = setting('yayasan_alamat','');
$mapsLink = setting('google_maps_link', '');
$socials = [
  'Instagram' => setting('social_instagram', '@yayasan.alfatih'),
  'Facebook' => setting('social_facebook', 'Yayasan Al Fatih Mulia Haramain'),
  'YouTube' => setting('social_youtube', 'Al Fatih Mulia Haramain'),
];
layout_header('Kontak');
?>
<section class="page-head"><div class="container"><span class="pill pill-soft">Kontak</span><h1>Hubungi Kami</h1><p class="muted">Kami siap melayani pertanyaan, pengajuan kerja sama, dan kebutuhan informasi program.</p></div></section>
<section class="section"><div class="container narrow">
  <div class="contact-grid">
    <?php if ($wa): ?><a class="contact-card" target="_blank" href="<?= e(wa_link($wa,'Assalamualaikum, saya ingin bertanya.')) ?>"><span>💬</span><b>WhatsApp</b><em><?= e($wa) ?></em></a><?php endif; ?>
    <?php if ($email): ?><a class="contact-card" href="mailto:<?= e($email) ?>"><span>✉️</span><b>Email</b><em><?= e($email) ?></em></a><?php endif; ?>
    <?php if ($addr): ?><div class="contact-card"><span>📍</span><b>Alamat</b><em><?= e($addr) ?></em></div><?php endif; ?>
  </div>
  <?php if (!$wa && !$email && !$addr): ?><div class="note">ℹ️ Informasi kontak akan tampil setelah admin mengisinya di Pengaturan.</div><?php endif; ?>
  <div class="card contact-extras">
    <h3>Google Maps & Media Sosial</h3>
    <p class="muted">Bagian ini disiapkan untuk menampilkan tautan lokasi kantor dan kanal media sosial resmi yayasan.</p>
    <ul class="mini-list compact">
      <?php foreach ($socials as $label => $value): ?>
      <li><b><?= e($label) ?>:</b> <?= e($value) ?></li>
      <?php endforeach; ?>
      <li><b>Google Maps:</b> <?= $mapsLink ? '<a href="'.e($mapsLink).'" target="_blank" rel="noopener">Buka lokasi kantor</a>' : 'Lokasi kantor dapat ditautkan setelah link resmi disiapkan.' ?></li>
    </ul>
  </div>
</div></section>
<?php layout_footer(); ?>
