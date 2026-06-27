<?php
if (!defined('APP_NAME')) { exit; }
$masuk    = (int) DB::val("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='verified'");
$keluar   = total_tersalurkan();
$saldo    = $masuk - $keluar;
$perkat   = DB::all("SELECT category, COALESCE(SUM(amount),0) total, COUNT(*) jml FROM donations WHERE status='verified' GROUP BY category ORDER BY total DESC");
$bulanan  = DB::all("SELECT DATE_FORMAT(created_at,'%Y-%m') bln, COALESCE(SUM(amount),0) total, COUNT(*) jml
                     FROM donations WHERE status='verified' GROUP BY bln ORDER BY bln DESC LIMIT 12");
$docKeys = [
    'laporan_doc_bulanan' => 'Laporan Bulanan',
    'laporan_doc_tahunan' => 'Laporan Tahunan',
    'laporan_doc_penyaluran_program' => 'Laporan Penyaluran Program',
    'laporan_doc_audit_keuangan' => 'Audit Keuangan',
];
$docs = [];
foreach ($docKeys as $key => $label) {
    $value = trim((string) setting($key, ''));
    if ($value !== '') {
        $docs[] = ['label' => $label, 'url' => $value];
    }
}
$programSummary = DB::all("SELECT title, collected_amount, beneficiaries, status FROM programs ORDER BY collected_amount DESC, created_at DESC LIMIT 12");
$donorList = setting_enabled('laporan_show_donors') ? DB::all("SELECT donor_name, amount, created_at, is_anonymous FROM donations WHERE status='verified' ORDER BY created_at DESC LIMIT 15") : [];
$years = DB::all("SELECT DATE_FORMAT(created_at,'%Y') yr, COALESCE(SUM(amount),0) total, COUNT(*) jml FROM donations WHERE status='verified' GROUP BY yr ORDER BY yr DESC LIMIT 5");
layout_header('Laporan & Transparansi');
?>
<section class="page-head"><div class="container">
  <span class="pill">Akuntabilitas Publik</span>
  <h1>Laporan & Transparansi</h1>
  <p class="muted">Komitmen amanah: setiap dana masuk dan tersalurkan, kami laporkan secara terbuka, bertahap, dan mudah dipahami publik.</p>
</div></section>

<section class="section"><div class="container">
  <div class="stats-grid three">
    <div class="stat-card stat-accent"><span class="stat-ic">⬇️</span><b><?= rupiah($masuk) ?></b><span>Total Penerimaan</span></div>
    <div class="stat-card stat-gold"><span class="stat-ic">⬆️</span><b><?= rupiah($keluar) ?></b><span>Total Penyaluran</span></div>
    <div class="stat-card"><span class="stat-ic">💼</span><b><?= rupiah($saldo) ?></b><span>Saldo Amanah</span></div>
  </div>
</div></section>

<section class="section section-soft"><div class="container">
  <div class="section-head"><h2>Pilar Transparansi Yayasan</h2><p class="muted">Transparansi dibangun melalui ringkasan angka, dokumen resmi, laporan periodik, dan dokumentasi penyaluran program.</p></div>
  <div class="feature-grid">
    <div class="feature-card"><h3>Laporan Bulanan</h3><p class="muted">Memberi gambaran ritme penerimaan donasi dan aktivitas penyaluran dalam periode berjalan.</p></div>
    <div class="feature-card"><h3>Laporan Tahunan</h3><p class="muted">Merangkum akumulasi capaian program, tren pertumbuhan donatur, dan arah gerakan yayasan.</p></div>
    <div class="feature-card"><h3>Laporan Penyaluran</h3><p class="muted">Menjelaskan bagaimana dana diarahkan ke program dan siapa saja penerima manfaatnya.</p></div>
    <div class="feature-card"><h3>Audit Keuangan</h3><p class="muted">Ruang publikasi audit atau dokumen pembuktian akuntabilitas yang siap diunggah yayasan.</p></div>
  </div>
</div></section>

<section class="section section-soft"><div class="container">
  <div class="section-head"><h2>Penerimaan per Kategori</h2></div>
  <?php if ($perkat): ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Kategori</th><th>Jumlah Donasi</th><th class="right">Total</th></tr></thead>
    <tbody><?php foreach ($perkat as $r): ?>
      <tr><td><?= e(human_label($r['category'])) ?></td><td><?= number_format($r['jml']) ?>x</td><td class="right"><?= rupiah($r['total']) ?></td></tr>
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

<?php if ($years): ?>
<section class="section section-soft"><div class="container">
  <div class="section-head"><h2>Laporan Tahunan</h2></div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Tahun</th><th>Jumlah Donasi</th><th class="right">Total</th></tr></thead>
    <tbody><?php foreach ($years as $r): ?>
      <tr><td><?= e($r['yr']) ?></td><td><?= number_format($r['jml']) ?>x</td><td class="right"><?= rupiah($r['total']) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
</div></section>
<?php endif; ?>

<section class="section"><div class="container">
  <div class="section-head"><h2>Laporan Penyaluran Program</h2><p class="muted">Ringkasan ini menunjukkan program yang berjalan, besaran dana tercatat, dan estimasi penerima manfaat.</p></div>
  <?php if ($programSummary): ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Program</th><th>Status</th><th class="right">Dana Tercatat</th><th class="right">Penerima Manfaat</th></tr></thead>
    <tbody><?php foreach ($programSummary as $p): ?>
      <tr>
        <td><b><?= e($p['title']) ?></b></td>
        <td><span class="badge badge-<?= $p['status'] === 'active' ? 'verified' : 'pending' ?>"><?= e(human_label($p['status'])) ?></span></td>
        <td class="right"><?= rupiah($p['collected_amount']) ?></td>
        <td class="right"><?= number_format($p['beneficiaries']) ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php else: ?>
  <div class="empty-state"><p>Ringkasan penyaluran program akan tampil setelah data program tersedia.</p></div>
  <?php endif; ?>
</div></section>

<section class="section section-soft"><div class="container">
  <div class="section-head"><h2>Dokumen Laporan Resmi</h2></div>
  <?php if ($docs): ?>
    <div class="doc-list">
      <?php foreach ($docs as $d): ?>
        <a class="doc-item" href="<?= e($d['url']) ?>" target="_blank" rel="noopener"><span>📄</span><b><?= e($d['label']) ?></b><em>Unduh</em></a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="note">ℹ️ Dokumen laporan PDF resmi (bulanan/tahunan/audit) akan diunggah oleh admin yayasan dan tampil di sini untuk diunduh publik.</div>
  <?php endif; ?>
  <div class="note">Dokumen publik dapat diperbarui sewaktu-waktu agar publik mendapatkan versi laporan terbaru dari yayasan.</div>
  <?php $wa=setting('yayasan_wa',''); if($wa): ?>
  <div class="center"><a class="btn btn-outline" target="_blank" href="<?= e(wa_link($wa,'Saya ingin meminta laporan keuangan yayasan secara lengkap.')) ?>">Minta Laporan Lengkap via WhatsApp</a></div>
  <?php endif; ?>
</div></section>

<?php if ($donorList): ?>
<section class="section"><div class="container">
  <div class="section-head"><h2>Daftar Donatur</h2><p class="muted">Tampilan ini bersifat opsional dan dibatasi untuk menjaga kenyamanan serta privasi donatur.</p></div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Nama</th><th>Waktu</th><th class="right">Nominal</th></tr></thead>
    <tbody><?php foreach ($donorList as $d): ?>
      <tr>
        <td><?= e($d['is_anonymous'] ? 'Hamba Allah' : ($d['donor_name'] ?: 'Donatur')) ?></td>
        <td><?= e(date('d M Y', strtotime($d['created_at']))) ?></td>
        <td class="right"><?= rupiah($d['amount']) ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
</div></section>
<?php endif; ?>
<?php layout_footer(); ?>
