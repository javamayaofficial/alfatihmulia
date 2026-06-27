<?php
if (!defined('APP_NAME')) { exit; }
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));
$selectedCategory = trim($_GET['category'] ?? '');

if ($slug !== '') {
    $a = DB::one("SELECT * FROM articles WHERE slug=? AND status='published' LIMIT 1", 's', [$slug]);
    if (!$a) { http_response_code(404); layout_header('Artikel'); echo '<div class="container section"><div class="empty-state"><h2>Artikel tidak ditemukan</h2><a class="btn btn-primary" href="'.url('artikel').'">Berita Lainnya</a></div></div>'; layout_footer(); exit; }
    layout_header($a['title'], $a['excerpt']);
    ?>
    <article class="section"><div class="container narrow">
      <a class="back" href="<?= url('artikel') ?>">← Semua Berita</a>
      <span class="tag"><?= e($a['category']) ?></span>
      <h1 class="article-title"><?= e($a['title']) ?></h1>
      <p class="muted byline">Oleh <?= e($a['author']) ?> • <?= e(date('d M Y', strtotime($a['created_at']))) ?></p>
      <?php if ($a['image']): ?><div class="article-img" style="background-image:url('<?= e(asset('img/'.$a['image'])) ?>')"></div><?php endif; ?>
      <div class="prose"><?= nl2br(e($a['content'])) ?></div>
      <div class="center"><a class="btn btn-primary" href="<?= url('donasi') ?>">Donasi Sekarang</a></div>
    </div></article>
    <?php layout_footer(); return;
}

$categories = DB::all("SELECT category, COUNT(*) jml FROM articles WHERE status='published' GROUP BY category ORDER BY category ASC");
$articles = $selectedCategory !== ''
    ? DB::all("SELECT * FROM articles WHERE status='published' AND category=? ORDER BY created_at DESC LIMIT 30", 's', [$selectedCategory])
    : DB::all("SELECT * FROM articles WHERE status='published' ORDER BY created_at DESC LIMIT 30");
layout_header('Berita & Artikel');
?>
<section class="page-head"><div class="container"><span class="pill pill-soft">Berita & Artikel</span><h1>Berita & Artikel</h1><p class="muted">Cerita dampak, kajian, dan kabar terbaru yayasan dalam kategori dakwah, sedekah, pendidikan, umroh, dan inspirasi kebaikan.</p></div></section>
<?php if ($categories): ?>
<section class="section">
  <div class="container">
    <div class="section-head"><h2>Kategori Artikel</h2><p class="muted">Pilih kategori untuk menjelajah topik yang paling relevan bagi pengunjung.</p></div>
    <div class="chip-row">
      <a class="chip static <?= $selectedCategory === '' ? 'on' : '' ?>" href="<?= url('artikel') ?>">Semua</a>
      <?php foreach ($categories as $cat): ?>
      <a class="chip static <?= $selectedCategory === $cat['category'] ? 'on' : '' ?>" href="<?= url('artikel', ['category' => $cat['category']]) ?>"><?= e($cat['category']) ?> (<?= (int) $cat['jml'] ?>)</a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="section"><div class="container">
  <?php if (!$articles): ?><div class="empty-state"><p>Belum ada artikel. Nantikan kabar kebaikan berikutnya.</p></div>
  <?php else: ?>
  <div class="card-grid">
    <?php foreach ($articles as $a): ?>
    <a class="news-card" href="<?= url('artikel',['slug'=>$a['slug']]) ?>">
      <div class="news-img" style="background-image:url('<?= $a['image']?e(asset('img/'.$a['image'])):asset('img/placeholder.svg') ?>')"></div>
      <div class="news-body"><span class="tag"><?= e($a['category']) ?></span><h3><?= e($a['title']) ?></h3>
        <p class="muted"><?= e(snippet($a['excerpt']?:$a['content'],90)) ?></p>
        <small class="muted"><?= e(date('d M Y', strtotime($a['created_at']))) ?></small>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div></section>
<?php layout_footer(); ?>
