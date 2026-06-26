<?php
if (!defined('APP_NAME')) { exit; }
$wa = setting('yayasan_wa',''); $email = setting('yayasan_email',''); $addr = setting('yayasan_alamat','');
layout_header('Kontak');
?>
<section class="page-head"><div class="container"><h1>Hubungi Kami</h1><p class="muted">Kami siap melayani pertanyaan dan kerja sama Anda.</p></div></section>
<section class="section"><div class="container narrow">
  <div class="contact-grid">
    <?php if ($wa): ?><a class="contact-card" target="_blank" href="<?= e(wa_link($wa,'Assalamualaikum, saya ingin bertanya.')) ?>"><span>💬</span><b>WhatsApp</b><em><?= e($wa) ?></em></a><?php endif; ?>
    <?php if ($email): ?><a class="contact-card" href="mailto:<?= e($email) ?>"><span>✉️</span><b>Email</b><em><?= e($email) ?></em></a><?php endif; ?>
    <?php if ($addr): ?><div class="contact-card"><span>📍</span><b>Alamat</b><em><?= e($addr) ?></em></div><?php endif; ?>
  </div>
  <?php if (!$wa && !$email && !$addr): ?><div class="note">ℹ️ Informasi kontak akan tampil setelah admin mengisinya di Pengaturan.</div><?php endif; ?>
</div></section>
<?php layout_footer(); ?>
