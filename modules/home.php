<?php
if (!defined('APP_NAME')) { exit; }
$im = impact_data();
$programs = DB::all("SELECT * FROM programs WHERE status='active' ORDER BY created_at DESC LIMIT 3");
$articles = DB::all("SELECT * FROM articles WHERE status='published' ORDER BY created_at DESC LIMIT 3");
$testi    = DB::all("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 6");
$partners = DB::all("SELECT * FROM partners ORDER BY id DESC LIMIT 12");

layout_header();
?>
<section class="hero">
  <div class="container hero-inner">
    <span class="pill">Platform Filantropi Islam Terpercaya</span>
    <h1>Satu Klik Kebaikan,<br><span class="hl">Sejuta Dampak Nyata</span></h1>
    <p class="lead">Salurkan sedekah, infaq, zakat, dan wakaf Anda dengan amanah. Pantau dampaknya secara langsung — transparan, profesional, dan terukur.</p>
    <div class="hero-cta">
      <a class="btn btn-primary btn-lg" href="<?= url('donasi') ?>">Donasi Sekarang</a>
      <a class="btn btn-outline btn-lg" href="<?= url('relawan') ?>">Jadi Relawan</a>
    </div>
    <div class="hero-trust">
      <div><strong><?= number_format($im['derived']['total_donatur']) ?>+</strong><span>Donatur</span></div>
      <div><strong><?= number_format($im['derived']['total_relawan']) ?>+</strong><span>Relawan</span></div>
      <div><strong><?= number_format($im['derived']['program_aktif']) ?></strong><span>Program Aktif</span></div>
    </div>
  </div>
</section>

<!-- LIVE COUNTER -->
<section class="section">
  <div class="container">
    <div class="section-head"><h2>Dampak yang Terus Berdetak</h2><p class="muted">Angka ini ditarik langsung dari data donasi terverifikasi — bukti transparansi kami.</p></div>
    <div class="stats-grid">
      <div class="stat-card stat-accent"><span class="stat-ic">💧</span><b class="counter" data-target="<?= $im['derived']['total_dana'] ?>" data-prefix="Rp ">Rp 0</b><span>Dana Terkumpul</span></div>
      <div class="stat-card"><span class="stat-ic">🤲</span><b class="counter" data-target="<?= $im['derived']['total_donatur'] ?>">0</b><span>Total Donatur</span></div>
      <div class="stat-card"><span class="stat-ic">🙌</span><b class="counter" data-target="<?= $im['derived']['total_relawan'] ?>">0</b><span>Relawan Aktif</span></div>
      <?php foreach ($im['manual'] as $m): ?>
      <div class="stat-card"><span class="stat-ic"><?= e($m['icon']) ?></span><b class="counter" data-target="<?= (int)$m['value'] ?>">0</b><span><?= e($m['label']) ?></span></div>
      <?php endforeach; ?>
    </div>
    <div class="center"><a class="btn btn-ghost" href="<?= url('impact') ?>">Lihat Dashboard Dampak Lengkap →</a></div>
  </div>
</section>

<!-- PROGRAM -->
<section class="section section-soft">
  <div class="container">
    <div class="section-head"><h2>Program Kebaikan Kami</h2><p class="muted">Pilih program, salurkan kebaikan, lihat progresnya.</p></div>
    <?php if (!$programs): ?>
      <div class="empty-state"><p>Program baru akan segera hadir. Pantau terus kebaikan berikutnya. 🌱</p></div>
    <?php else: ?>
    <div class="card-grid">
      <?php foreach ($programs as $p):
        $pct = $p['target_amount'] > 0 ? min(100, round($p['collected_amount'] / $p['target_amount'] * 100)) : 0; ?>
      <a class="program-card" href="<?= url('program', ['slug'=>$p['slug']]) ?>">
        <div class="program-img" style="background-image:url('<?= $p['image'] ? e(asset('img/'.$p['image'])) : asset('img/placeholder.svg') ?>')"></div>
        <div class="program-body">
          <span class="tag"><?= e($p['category']) ?></span>
          <h3><?= e($p['title']) ?></h3>
          <p class="muted"><?= e(snippet($p['excerpt'] ?: $p['description'], 90)) ?></p>
          <div class="progress"><span style="width:<?= $pct ?>%"></span></div>
          <div class="progress-meta"><b><?= rupiah($p['collected_amount']) ?></b><span>dari <?= rupiah($p['target_amount']) ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="center"><a class="btn btn-outline" href="<?= url('program') ?>">Lihat Semua Program</a></div>
    <?php endif; ?>
  </div>
</section>

<!-- WOW: RELAWAN -->
<section class="section">
  <div class="container cta-banner">
    <div>
      <span class="pill pill-gold">Program Duta Air Kehidupan</span>
      <h2>Jadi Relawan, Raih <em>The Legacy Umrah</em></h2>
      <p>Bagikan link referral pribadimu, kumpulkan kebaikan bersama, naik di papan peringkat nasional, dan melangkah menuju reward Umrah dari yayasan.</p>
      <a class="btn btn-primary" href="<?= url('relawan') ?>">Mulai Jadi Relawan</a>
      <a class="btn btn-ghost" href="<?= url('leaderboard') ?>">Lihat Leaderboard</a>
    </div>
  </div>
</section>

<?php if ($testi): ?>
<section class="section section-soft">
  <div class="container">
    <div class="section-head"><h2>Kata Mereka</h2></div>
    <div class="testi-grid">
      <?php foreach ($testi as $t): ?>
      <div class="testi-card"><p>"<?= e($t['message']) ?>"</p><div class="testi-by"><b><?= e($t['name']) ?></b><span><?= e($t['role']) ?></span></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($articles): ?>
<section class="section">
  <div class="container">
    <div class="section-head"><h2>Berita & Kabar Kebaikan</h2></div>
    <div class="card-grid">
      <?php foreach ($articles as $a): ?>
      <a class="news-card" href="<?= url('artikel', ['slug'=>$a['slug']]) ?>">
        <div class="news-img" style="background-image:url('<?= $a['image'] ? e(asset('img/'.$a['image'])) : asset('img/placeholder.svg') ?>')"></div>
        <div class="news-body"><span class="tag"><?= e($a['category']) ?></span><h3><?= e($a['title']) ?></h3><p class="muted"><?= e(snippet($a['excerpt'] ?: $a['content'], 80)) ?></p></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($partners): ?>
<section class="section section-soft">
  <div class="container">
    <div class="section-head"><h3>Dipercaya oleh Mitra Kami</h3></div>
    <div class="partner-row">
      <?php foreach ($partners as $pt): ?>
        <div class="partner-logo"><?= $pt['logo'] ? '<img src="'.e(asset('img/'.$pt['logo'])).'" alt="'.e($pt['name']).'">' : '<span>'.e($pt['name']).'</span>' ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container final-cta">
    <h2>Kebaikan Anda Hari Ini, Peradaban untuk Esok</h2>
    <p class="muted">Setiap rupiah tercatat, tersalurkan, dan terlaporkan. Mulai sekarang.</p>
    <a class="btn btn-primary btn-lg" href="<?= url('donasi') ?>">Donasi Sekarang</a>
  </div>
</section>
<?php layout_footer(); ?>
