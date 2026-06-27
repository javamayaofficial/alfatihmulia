<?php
if (!defined('APP_NAME')) { exit; }
$selectedCategory = trim($_GET['category'] ?? '');
$articles = DB::all("SELECT * FROM articles WHERE status='published' ORDER BY created_at DESC LIMIT 9");
$gallery = $selectedCategory !== ''
    ? DB::all("SELECT * FROM media_gallery WHERE status='published' AND category=? ORDER BY sort_order ASC, created_at DESC", 's', [$selectedCategory])
    : DB::all("SELECT * FROM media_gallery WHERE status='published' ORDER BY sort_order ASC, created_at DESC");
$galleryCategories = DB::all("SELECT category, COUNT(*) AS jml FROM media_gallery WHERE status='published' AND category IS NOT NULL AND category<>'' GROUP BY category ORDER BY category ASC");
$filters = $galleryCategories ? array_column($galleryCategories, 'category') : setting_lines('documentation_filter_items', "Air Kehidupan\nCahaya Ilmu\nJejak Baitullah");
$intro = setting('documentation_intro', 'Dokumentasi program menjadi bagian penting dari transparansi dan narasi dampak yang ingin dibangun yayasan.');
$filterIntro = setting('documentation_filter_intro', 'Filter ini disiapkan untuk memudahkan pengunjung menjelajah dokumentasi berdasarkan pilar program utama.');
$galleryIntro = setting('documentation_gallery_intro', 'Konten artikel dan kabar lapangan saat ini menjadi sumber dokumentasi publik yang paling siap ditampilkan.');
$photoDesc = setting('documentation_photo_desc', 'Menampilkan momentum penyaluran program, edukasi lapangan, dan interaksi dengan penerima manfaat.');
$videoDesc = setting('documentation_video_desc', 'Siap digunakan untuk menampilkan video singkat lapangan, testimoni, dan laporan dampak program.');
$galleryDesc = setting('documentation_gallery_desc', 'Ruang visual untuk membangun trust donatur dan menampilkan progres program secara berkala.');
layout_header('Dokumentasi');
?>
<section class="page-head">
  <div class="container">
    <span class="pill pill-soft">Dokumentasi</span>
    <h1>Foto, Video, dan Galeri Program</h1>
    <p class="muted"><?= e($intro) ?></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Filter Program</h2>
      <p class="muted"><?= e($filterIntro) ?></p>
    </div>
    <div class="chip-row">
      <a class="chip static <?= $selectedCategory === '' ? 'on' : '' ?>" href="<?= url('dokumentasi') ?>">Semua</a>
      <?php foreach ($filters as $filter): ?>
      <a class="chip static <?= $selectedCategory === $filter ? 'on' : '' ?>" href="<?= url('dokumentasi', ['category' => $filter]) ?>"><?= e($filter) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-soft">
  <div class="container">
    <div class="section-head">
      <h2>Galeri Program</h2>
      <p class="muted"><?= e($galleryIntro) ?></p>
    </div>
    <?php if ($gallery): ?>
      <div class="gallery-grid">
        <?php foreach ($gallery as $item): ?>
        <a class="gallery-card" href="<?= $item['media_type'] === 'video' && $item['video_url'] ? e($item['video_url']) : ($item['media_path'] ? e(asset('img/'.$item['media_path'])) : '#') ?>" <?= $item['media_type'] === 'video' || $item['media_path'] ? 'target="_blank" rel="noopener"' : '' ?>>
          <div class="gallery-cover" style="background-image:url('<?= $item['media_path'] ? e(asset('img/'.$item['media_path'])) : asset('img/placeholder.svg') ?>')">
            <span class="tag tag-light"><?= e($item['media_type'] === 'video' ? 'Video' : 'Foto') ?></span>
          </div>
          <div class="gallery-body">
            <span class="tag"><?= e($item['category'] ?: 'Dokumentasi') ?></span>
            <h3><?= e($item['title']) ?></h3>
            <p class="muted"><?= e(snippet($item['description'] ?: '', 110)) ?></p>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php elseif (!$articles): ?>
      <div class="empty-state"><p>Dokumentasi kegiatan akan segera dipublikasikan setelah materi lapangan tersedia.</p></div>
    <?php else: ?>
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
      <div class="center"><p class="note">Galeri khusus dokumentasi belum diisi. Sementara ini halaman mengambil konten dari artikel yang sudah dipublikasikan.</p></div>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container feature-grid">
    <div class="feature-card">
      <h3>Foto Kegiatan</h3>
      <p class="muted"><?= e($photoDesc) ?></p>
    </div>
    <div class="feature-card">
      <h3>Video Kegiatan</h3>
      <p class="muted"><?= e($videoDesc) ?></p>
    </div>
    <div class="feature-card">
      <h3>Galeri Program</h3>
      <p class="muted"><?= e($galleryDesc) ?></p>
    </div>
  </div>
</section>
<?php layout_footer(); ?>
