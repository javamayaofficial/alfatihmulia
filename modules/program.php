<?php
if (!defined('APP_NAME')) { exit; }
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));

if ($slug !== '') {
    $p = DB::one("SELECT * FROM programs WHERE slug=? LIMIT 1", 's', [$slug]);
    if (!$p) { http_response_code(404); layout_header('Program'); echo '<div class="container section"><div class="empty-state"><h2>Program tidak ditemukan</h2><a class="btn btn-primary" href="'.url('program').'">Lihat Program Lain</a></div></div>'; layout_footer(); exit; }
    $pct = $p['target_amount'] > 0 ? min(100, round($p['collected_amount'] / $p['target_amount'] * 100)) : 0;
    $points = DB::all("SELECT * FROM program_points WHERE program_id=? ORDER BY id DESC", 'i', [$p['id']]);
    layout_header($p['title'], $p['excerpt']);
    ?>
    <section class="program-hero" style="background-image:linear-gradient(180deg,rgba(11,45,30,.15),rgba(11,45,30,.78)),url('<?= $p['image']?e(asset('img/'.$p['image'])):asset('img/placeholder.svg') ?>')">
      <div class="container"><span class="tag tag-light"><?= e($p['category']) ?></span><h1><?= e($p['title']) ?></h1></div>
    </section>
    <section class="section"><div class="container detail-grid">
      <article class="detail-main">
        <?php if ($p['video_url']): ?><div class="video-wrap"><iframe src="<?= e($p['video_url']) ?>" allowfullscreen loading="lazy"></iframe></div><?php endif; ?>
        <div class="prose"><?= nl2br(e($p['description'])) ?></div>
        <?php if ($points): ?>
          <h3>Sebaran Program</h3>
          <ul class="point-list">
            <?php foreach ($points as $pt): ?><li><b><?= e($pt['name']) ?></b><span><?= e($pt['province']) ?> • <?= number_format($pt['beneficiaries']) ?> penerima manfaat</span></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </article>
      <aside class="detail-side">
        <div class="card donate-side">
          <div class="progress big"><span style="width:<?= $pct ?>%"></span></div>
          <div class="raise"><b><?= rupiah($p['collected_amount']) ?></b><span>terkumpul dari <?= rupiah($p['target_amount']) ?></span></div>
          <div class="raise-meta"><span><?= $pct ?>% tercapai</span><span><?= number_format($p['beneficiaries']) ?> penerima manfaat</span></div>
          <a class="btn btn-primary btn-block btn-lg" href="<?= url('donasi', ['program_id'=>$p['id']]) ?>">Donasi untuk Program Ini</a>
          <?php $wa=setting('yayasan_wa',''); if($wa): ?>
          <a class="btn btn-outline btn-block" target="_blank" href="<?= e(wa_link($wa, 'Saya tertarik dengan program "'.$p['title'].'". Mohon informasinya.')) ?>">Tanya via WhatsApp</a>
          <?php endif; ?>
          <a class="btn btn-ghost btn-block" href="https://wa.me/?text=<?= rawurlencode('Mari dukung program '.$p['title'].' di '.url('program',['slug'=>$p['slug']])) ?>" target="_blank">Bagikan ke WhatsApp</a>
        </div>
      </aside>
    </div></section>
    <?php
    layout_footer();
    return;
}

// Daftar semua program
$programs = DB::all("SELECT * FROM programs WHERE status='active' ORDER BY created_at DESC");
layout_header('Program Yayasan');
?>
<section class="page-head"><div class="container"><h1>Program Kebaikan Kami</h1><p class="muted">Setiap program, satu langkah membangun peradaban.</p></div></section>
<section class="section"><div class="container">
  <?php if (!$programs): ?>
    <div class="empty-state"><p>Program baru akan segera hadir. 🌱</p></div>
  <?php else: ?>
  <div class="card-grid">
    <?php foreach ($programs as $p): $pct=$p['target_amount']>0?min(100,round($p['collected_amount']/$p['target_amount']*100)):0; ?>
    <a class="program-card" href="<?= url('program',['slug'=>$p['slug']]) ?>">
      <div class="program-img" style="background-image:url('<?= $p['image']?e(asset('img/'.$p['image'])):asset('img/placeholder.svg') ?>')"></div>
      <div class="program-body"><span class="tag"><?= e($p['category']) ?></span><h3><?= e($p['title']) ?></h3>
        <p class="muted"><?= e(snippet($p['excerpt']?:$p['description'],90)) ?></p>
        <div class="progress"><span style="width:<?= $pct ?>%"></span></div>
        <div class="progress-meta"><b><?= rupiah($p['collected_amount']) ?></b><span><?= $pct ?>%</span></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div></section>
<?php layout_footer(); ?>
