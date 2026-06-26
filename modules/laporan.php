<?php
if (!defined('APP_NAME')) { exit; }
$masuk    = (int) DB::val("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='verified'");
$keluar   = total_tersalurkan();
$saldo    = $masuk - $keluar;
$perkat   = DB::all("SELECT category, COALESCE(SUM(amount),0) total, COUNT(*) jml FROM donations WHERE status='verified' GROUP BY category ORDER BY total DESC");
$bulanan  = DB::all("SELECT DATE_FORMAT(created_at,'%Y-%m') bln, COALESCE(SUM(amount),0) total, COUNT(*) jml
                     FROM donations WHERE status='verified' GROUP BY bln ORDER BY bln DESC LIMIT 12");
$docs     = DB::all("SELECT * FROM settings WHERE skey LIKE 'laporan_doc_%'");
layout_header('Laporan & Transparansi');
?>
<section class="page-head"><div class="container">
  <span class="pill">Akuntabilitas Publik</span>
  <h1>Laporan & Transparansi</h1>
  <p class="muted">Komitmen amanah: setiap dana masuk dan tersalurkan, kami laporkan terbuka.</p>
</div></section>

<section class="section"><div class="container">
  <div class="stats-grid three">
    <div class="stat-card stat-accent"><span class="stat-ic">⬇️</span><b><?= rupiah($masuk) ?></b><span>Total Penerimaan</span></div>
    <div class="stat-card stat-gold"><span class="stat-ic">⬆️</span><b><?= rupiah($keluar) ?></b><span>Total Penyaluran</span></div>
    <div class="stat-card"><span class="stat-ic">💼</span><b><?= rupiah($saldo) ?></b><span>Saldo Amanah</span></div>
  </div>
</div></section>

<section class="section section-soft"><div class="container">
  <div class="section-head"><h2>Penerimaan per Kategori</h2></div>
  <?php if ($perkat): ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Kategori</th><th>Jumlah Donasi</th><th class="right">Total</th></tr></thead>
    <tbody><?php foreach ($perkat as $r): ?>
      <tr><td><?= e(ucfirst($r['category'])) ?></td><td><?= number_format($r['jml']) ?>x</td><td class="right"><?= rupiah($r['total']) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php else: ?><div class="empty-state"><p>Laporan akan tersedia setelah ada donasi terverifikasi.</p></div><?php endif; ?>
</div></section>

<?php if ($bulanan): ?>
<section class="section"><div class="container">
  <div class="section-head"><h2>Rekap Bulanan</h2></div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Bulan</th><th>Jumlah Donasi</th><th class="right">Total</th></tr></thead>
    <tbody><?php foreach ($bulanan as $r): ?>
      <tr><td><?= e(date('F Y', strtotime($r['bln'].'-01'))) ?></td><td><?= number_format($r['jml']) ?>x</td><td class="right"><?= rupiah($r['total']) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
</div></section>
<?php endif; ?>

<section class="section section-soft"><div class="container">
  <div class="section-head"><h2>Dokumen Laporan Resmi</h2></div>
  <?php if ($docs): ?>
    <div class="doc-list">
      <?php foreach ($docs as $d): ?>
        <a class="doc-item" href="<?= e($d['svalue']) ?>" target="_blank"><span>📄</span><b><?= e(str_replace('laporan_doc_','',$d['skey'])) ?></b><em>Unduh</em></a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="note">ℹ️ Dokumen laporan PDF resmi (bulanan/tahunan/audit) akan diunggah oleh admin yayasan dan tampil di sini untuk diunduh publik.</div>
  <?php endif; ?>
  <?php $wa=setting('yayasan_wa',''); if($wa): ?>
  <div class="center"><a class="btn btn-outline" target="_blank" href="<?= e(wa_link($wa,'Saya ingin meminta laporan keuangan yayasan secara lengkap.')) ?>">Minta Laporan Lengkap via WhatsApp</a></div>
  <?php endif; ?>
</div></section>
<?php layout_footer(); ?>
