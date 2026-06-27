<?php
if (!defined('APP_NAME')) { exit; }
$im = impact_data();
$programs = DB::all("SELECT * FROM programs WHERE status='active' ORDER BY created_at DESC LIMIT 6");
$articles = DB::all("SELECT * FROM articles WHERE status='published' ORDER BY created_at DESC LIMIT 3");
$testi    = DB::all("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 6");
$partners = DB::all("SELECT * FROM partners ORDER BY id DESC LIMIT 12");

$hasManualBeneficiaries = isset($im['manual']['penerima_manfaat']);
$beneficiaries = $hasManualBeneficiaries
    ? (int) $im['manual']['penerima_manfaat']['value']
    : (int) $im['derived']['penerima_manfaat_program'];
$donorDisplay = number_format((int) $im['derived']['total_donatur']) . ((int) $im['derived']['total_donatur'] > 0 ? '+' : '');
$relawanDisplay = number_format((int) $im['derived']['total_relawan']) . ((int) $im['derived']['total_relawan'] > 0 ? '+' : '');
$beneficiaryDisplay = number_format((int) $beneficiaries) . ((int) $beneficiaries > 0 ? '+' : '');
$flagshipPrograms = [
    [
        'name' => 'Duta Jejak Baitullah Indonesia',
        'tagline' => 'Syiar Baitullah untuk memperluas semangat ibadah, dakwah, dan pembinaan umat.',
        'items' => ['Kajian Umroh', 'Manasik Gratis', 'Safari Dakwah'],
    ],
    [
        'name' => 'Duta Air Kehidupan Indonesia',
        'tagline' => 'Aksi nyata menghadirkan air bersih untuk masjid, pesantren, dan titik kebutuhan umat.',
        'items' => ['Sedekah Air Masjid', 'Sedekah Air Pesantren', '1.000 Titik Air Berkah Indonesia'],
    ],
    [
        'name' => 'Duta Cahaya Ilmu Indonesia',
        'tagline' => 'Menguatkan generasi melalui pendidikan, Al-Qur\'an, dan pembinaan yang berkelanjutan.',
        'items' => ['Beasiswa Anak Yatim & Dhuafa', 'Wakaf Al-Qur\'an', 'Rumah Qur\'an'],
    ],
];

layout_header('Home');
?>
<section class="hero hero-home">
  <div class="container hero-layout">
    <div class="hero-inner">
      <span class="pill">Yayasan Filantropi Islam Profesional</span>
      <h1>Menjadi Perantara Kebaikan dan <span class="hl">Pahala Jariyah</span> Untuk Umat</h1>
      <p class="lead">Yayasan Al Fatih Mulia Haramain menghadirkan program dakwah, air bersih, pendidikan, dan pemberdayaan yang memudahkan masyarakat berdonasi dengan aman, cepat, dan transparan.</p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="<?= url('donasi') ?>">Donasi Sekarang</a>
        <a class="btn btn-outline btn-lg" href="<?= url('relawan') ?>">Menjadi Relawan</a>
      </div>
      <div class="hero-trust">
        <div><strong><?= $donorDisplay ?></strong><span>Total Donatur</span></div>
        <div><strong><?= $relawanDisplay ?></strong><span>Total Relawan</span></div>
        <div><strong><?= $beneficiaryDisplay ?></strong><span>Penerima Manfaat</span></div>
      </div>
    </div>
    <div class="hero-panel card">
      <span class="pill pill-soft">Komitmen Amanah</span>
      <h3>Program Prioritas Untuk Dampak Bertahap</h3>
      <ul class="check-list">
        <li>Donasi mudah melalui transfer bank, QRIS, dan virtual account</li>
        <li>Laporan penyaluran dana yang terbuka dan terukur</li>
        <li>Penguatan relawan dan kolaborasi secara bertahap sesuai pertumbuhan program</li>
      </ul>
      <a class="btn btn-ghost btn-block" href="<?= url('laporan') ?>">Lihat Laporan Transparansi</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Data Perkembangan Saat Ini</h2>
      <p class="muted">Angka-angka berikut menampilkan capaian yang sudah benar-benar tercatat saat ini, sehingga tetap jujur meski program masih bertumbuh.</p>
    </div>
    <div class="stats-grid impact-counter-grid">
      <div class="stat-card"><span class="stat-ic">🤲</span><b class="counter" data-target="<?= $im['derived']['total_donatur'] ?>">0</b><span>Total Donatur</span></div>
      <div class="stat-card"><span class="stat-ic">🤝</span><b class="counter" data-target="<?= $im['derived']['total_relawan'] ?>">0</b><span>Total Relawan</span></div>
      <div class="stat-card"><span class="stat-ic">❤️</span><b class="counter" data-target="<?= $beneficiaries ?>">0</b><span>Total Penerima Manfaat</span></div>
      <div class="stat-card"><span class="stat-ic">📌</span><b class="counter" data-target="<?= $im['derived']['program_aktif'] ?>">0</b><span>Total Program Berjalan</span></div>
      <div class="stat-card stat-accent"><span class="stat-ic">💧</span><b class="counter" data-target="<?= total_tersalurkan() ?>" data-prefix="Rp ">Rp 0</b><span>Total Dana Tersalurkan</span></div>
    </div>
    <div class="center"><a class="btn btn-ghost" href="<?= url('impact') ?>">Lihat Dashboard Dampak Lengkap</a></div>
  </div>
</section>

<section class="section section-soft">
  <div class="container">
    <div class="section-head">
      <h2>Program Unggulan</h2>
      <p class="muted">Tiga pilar utama ini dirancang untuk menjawab kebutuhan dakwah, kemanusiaan, dan pendidikan umat secara berkelanjutan.</p>
    </div>
    <div class="feature-grid">
      <?php foreach ($flagshipPrograms as $fp): ?>
      <div class="feature-card">
        <span class="tag"><?= e($fp['name']) ?></span>
        <h3><?= e($fp['name']) ?></h3>
        <p class="muted"><?= e($fp['tagline']) ?></p>
        <ul class="mini-list">
          <?php foreach ($fp['items'] as $item): ?>
          <li><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
        <a class="btn btn-outline btn-block" href="<?= url('program') ?>">Lihat Detail Program</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Program Berjalan</h2>
      <p class="muted">Program aktif yang dapat langsung Anda dukung hari ini.</p>
    </div>
    <?php if (!$programs): ?>
      <div class="empty-state"><p>Program baru akan segera hadir. Tim yayasan sedang menyiapkan agenda manfaat berikutnya.</p></div>
    <?php else: ?>
      <div class="card-grid">
        <?php foreach ($programs as $p):
          $pct = $p['target_amount'] > 0 ? min(100, round($p['collected_amount'] / $p['target_amount'] * 100)) : 0; ?>
        <a class="program-card" href="<?= url('program', ['slug' => $p['slug']]) ?>">
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

<?php if ($articles): ?>
<section class="section section-soft">
  <div class="container">
    <div class="section-head">
      <h2>Kegiatan Terbaru</h2>
      <p class="muted">Ikuti kabar lapangan, penyaluran program, dan aktivitas dakwah terbaru dari yayasan.</p>
    </div>
    <div class="card-grid">
      <?php foreach ($articles as $a): ?>
      <a class="news-card" href="<?= url('artikel', ['slug' => $a['slug']]) ?>">
        <div class="news-img" style="background-image:url('<?= $a['image'] ? e(asset('img/'.$a['image'])) : asset('img/placeholder.svg') ?>')"></div>
        <div class="news-body">
          <span class="tag"><?= e($a['category']) ?></span>
          <h3><?= e($a['title']) ?></h3>
          <p class="muted"><?= e(snippet($a['excerpt'] ?: $a['content'], 90)) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($testi): ?>
<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Testimoni Penerima Manfaat</h2>
      <p class="muted">Cerita nyata yang menggambarkan kebermanfaatan program yayasan di tengah masyarakat.</p>
    </div>
    <div class="testi-grid">
      <?php foreach ($testi as $t): ?>
      <div class="testi-card">
        <p>"<?= e($t['message']) ?>"</p>
        <div class="testi-by"><b><?= e($t['name']) ?></b><span><?= e($t['role']) ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container cta-banner">
    <div>
      <span class="pill pill-gold">Gerakan Relawan Nasional</span>
      <h2>Bersama Donatur dan Relawan, Luaskan Jangkauan Kebaikan</h2>
      <p>Gabungkan energi donatur, relawan, dan mitra kolaborasi untuk membangun gerakan kebaikan yang bertumbuh sehat sejak tahap awal.</p>
      <a class="btn btn-primary" href="<?= url('relawan') ?>">Daftar Relawan</a>
      <a class="btn btn-ghost" href="<?= url('kemitraan') ?>">Ajukan Kemitraan</a>
    </div>
  </div>
</section>

<?php if ($partners): ?>
<section class="section section-soft">
  <div class="container">
    <div class="section-head"><h3>Mitra & Kolaborasi</h3><p class="muted">Kepercayaan publik diperkuat oleh sinergi bersama komunitas, lembaga, dan jejaring kebaikan.</p></div>
    <div class="partner-grid">
      <?php foreach ($partners as $pt): ?>
        <div class="partner-card">
          <div class="partner-logo"><?= $pt['logo'] ? '<img src="'.e(asset('img/'.$pt['logo'])).'" alt="'.e($pt['name']).'">' : '<span>'.e($pt['name']).'</span>' ?></div>
          <b><?= e($pt['name']) ?></b>
          <span class="muted"><?= e($pt['category'] ?: 'Mitra') ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container final-cta">
    <span class="pill pill-soft">CTA Donasi</span>
    <h2>Siap Menjadi Bagian Dari Amal Jariyah Umat?</h2>
    <p class="muted">Pilih program, salurkan donasi, dan pantau laporan penyalurannya dengan lebih mudah.</p>
    <div class="hero-cta center">
      <a class="btn btn-primary btn-lg" href="<?= url('donasi') ?>">Donasi Sekarang</a>
      <a class="btn btn-outline btn-lg" href="<?= url('laporan') ?>">Lihat Laporan Penyaluran</a>
    </div>
  </div>
</section>
<?php layout_footer(); ?>
