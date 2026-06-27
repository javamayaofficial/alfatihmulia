<?php
if (!defined('APP_NAME')) { exit; }
$im = impact_data();
$pending = (int) DB::val("SELECT COUNT(*) FROM donations WHERE status='pending'");
$danaPending = (int) DB::val("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='pending'");
$leadPending = (int) DB::val("SELECT COUNT(*) FROM volunteer_leads WHERE status='new'");
$partnershipPending = (int) DB::val("SELECT COUNT(*) FROM partnership_leads WHERE status='new'");
$articlesPublished = (int) DB::val("SELECT COUNT(*) FROM articles WHERE status='published'");
$galleryPublished = (int) DB::val("SELECT COUNT(*) FROM media_gallery WHERE status='published'");
$partnersTotal = (int) DB::val("SELECT COUNT(*) FROM partners");
$orgPublished = (int) DB::val("SELECT COUNT(*) FROM organization_members WHERE status='published'");
$programBeneficiaries = (int) ($im['derived']['penerima_manfaat_program'] ?? 0);
$manualBeneficiaries = 0;
foreach ($im['manual'] as $manualStat) {
    if (($manualStat['label'] ?? '') === 'Penerima Manfaat') {
        $manualBeneficiaries = (int) ($manualStat['value'] ?? 0);
        break;
    }
}
$beneficiaryDifference = $manualBeneficiaries - $programBeneficiaries;
$beneficiarySynced = $beneficiaryDifference === 0;
$docsConfigured = 0;
foreach ([
    'legal_akta_file',
    'legal_sk_file',
    'legal_npwp_file',
    'laporan_doc_bulanan',
    'laporan_doc_tahunan',
    'laporan_doc_penyaluran_program',
    'laporan_doc_audit_keuangan',
] as $docKey) {
    if (trim((string) setting($docKey, '')) !== '') {
        $docsConfigured++;
    }
}
$volunteerRecent = DB::all("SELECT * FROM volunteer_leads ORDER BY created_at DESC LIMIT 5");
$partnershipRecent = DB::all("SELECT * FROM partnership_leads ORDER BY created_at DESC LIMIT 5");
$recent = DB::all("SELECT * FROM donations ORDER BY created_at DESC LIMIT 8");

admin_layout_header('dashboard', 'Dashboard');
flash_show();
?>
<div class="stats-grid">
  <div class="stat-card stat-accent"><span class="stat-ic">💧</span><b><?= rupiah($im['derived']['total_dana']) ?></b><span>Dana Terkumpul (Verified)</span></div>
  <div class="stat-card stat-gold"><span class="stat-ic">⏳</span><b><?= number_format($pending) ?></b><span>Donasi Menunggu Verifikasi</span></div>
  <div class="stat-card"><span class="stat-ic">🤲</span><b><?= number_format($im['derived']['total_donatur']) ?></b><span>Total Donatur</span></div>
  <div class="stat-card"><span class="stat-ic">🙌</span><b><?= number_format($im['derived']['total_relawan']) ?></b><span>Relawan Aktif</span></div>
  <div class="stat-card"><span class="stat-ic">📝</span><b><?= number_format($leadPending) ?></b><span>Lead Relawan Baru</span></div>
  <div class="stat-card"><span class="stat-ic">🤝</span><b><?= number_format($partnershipPending) ?></b><span>Lead Kemitraan Baru</span></div>
</div>

<div class="stats-grid stats-grid-compact">
  <div class="stat-card"><span class="stat-ic">📰</span><b><?= number_format($articlesPublished) ?></b><span>Artikel Publish</span></div>
  <div class="stat-card"><span class="stat-ic">🖼️</span><b><?= number_format($galleryPublished) ?></b><span>Galeri Publish</span></div>
  <div class="stat-card"><span class="stat-ic">🤝</span><b><?= number_format($partnersTotal) ?></b><span>Total Mitra</span></div>
  <div class="stat-card"><span class="stat-ic">🏛️</span><b><?= number_format($orgPublished) ?></b><span>Struktur Publish</span></div>
  <div class="stat-card"><span class="stat-ic">📄</span><b><?= number_format($docsConfigured) ?>/7</b><span>Dokumen Publik Siap</span></div>
  <div class="stat-card"><span class="stat-ic">📋</span><b><?= number_format($im['derived']['program_aktif']) ?></b><span>Program Aktif</span></div>
</div>

<div class="panel">
  <div class="panel-head"><h3>Penerima Manfaat Manual</h3><a class="btn btn-ghost btn-sm" href="<?= admin_url('settings') ?>">Buka Pengaturan</a></div>
  <div class="mini-feed">
    <div class="feed-item">
      <div>
        <b><?= number_format($manualBeneficiaries) ?></b>
        <span class="muted">Angka ini dipakai pada beranda dan dashboard dampak sebagai statistik publik `Penerima Manfaat`.</span>
      </div>
      <div class="feed-meta"><span class="badge badge-verified">Manual</span></div>
    </div>
    <div class="feed-item">
      <div>
        <b><?= number_format($programBeneficiaries) ?></b>
        <span class="muted">Total teknis yang dihitung dari seluruh field `Penerima Manfaat` di data program.</span>
      </div>
      <div class="feed-meta"><span class="badge badge-pending">Program</span></div>
    </div>
    <div class="feed-item">
      <div>
        <b><?= $beneficiarySynced ? 'Sudah Sinkron' : ('Selisih ' . number_format(abs($beneficiaryDifference))) ?></b>
        <span class="muted">
          <?= $beneficiarySynced
              ? 'Angka manual dan total program sudah sama.'
              : ($beneficiaryDifference > 0
                  ? 'Angka manual lebih besar dari total program. Ini cocok jika sebagian dampak belum dipecah per program.'
                  : 'Angka manual lebih kecil dari total program. Sebaiknya cek ulang agar statistik publik tidak tertinggal.'); ?>
        </span>
      </div>
      <div class="feed-meta"><span class="badge badge-<?= $beneficiarySynced ? 'verified' : 'pending' ?>"><?= $beneficiarySynced ? 'Sinkron' : 'Cek' ?></span></div>
    </div>
    <div class="feed-item">
      <div>
        <b>Lokasi Input</b>
        <span class="muted">Panel Admin → Pengaturan → Statistik Dampak Manual → baris `Penerima Manfaat`.</span>
      </div>
    </div>
  </div>
</div>

<?php if ($pending > 0): ?>
<div class="alert alert-info">⏳ Ada <b><?= $pending ?></b> donasi (<?= rupiah($danaPending) ?>) menunggu verifikasi. <a href="<?= admin_url('donasi',['status'=>'pending']) ?>">Verifikasi sekarang →</a></div>
<?php endif; ?>

<?php if ($leadPending > 0): ?>
<div class="alert alert-info">🙌 Ada <b><?= $leadPending ?></b> pendaftaran relawan baru yang perlu ditindaklanjuti. <a href="<?= admin_url('relawan') ?>">Buka kelola relawan →</a></div>
<?php endif; ?>

<?php if ($partnershipPending > 0): ?>
<div class="alert alert-info">🤝 Ada <b><?= $partnershipPending ?></b> pengajuan kemitraan baru yang perlu ditindaklanjuti. <a href="<?= admin_url('mitra') ?>">Buka kelola mitra →</a></div>
<?php endif; ?>

<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h3>Lead Relawan Terbaru</h3><a class="btn btn-ghost btn-sm" href="<?= admin_url('relawan') ?>">Kelola Relawan</a></div>
    <?php if (!$volunteerRecent): ?><div class="empty-state small"><p>Belum ada lead relawan terbaru.</p></div>
    <?php else: ?>
    <div class="mini-feed">
      <?php foreach ($volunteerRecent as $lead): ?>
      <div class="feed-item">
        <div>
          <b><?= e($lead['name']) ?></b>
          <span class="muted"><?= e($lead['city'] ?: 'Kota belum diisi') ?> · <?= e($lead['division'] ?: 'Divisi belum dipilih') ?></span>
        </div>
        <div class="feed-meta">
          <span class="badge badge-<?= $lead['status'] === 'qualified' ? 'verified' : ($lead['status'] === 'rejected' ? 'rejected' : 'pending') ?>"><?= e(human_label($lead['status'])) ?></span>
          <small class="muted"><?= e(date('d/m H:i', strtotime($lead['created_at']))) ?></small>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Lead Kemitraan Terbaru</h3><a class="btn btn-ghost btn-sm" href="<?= admin_url('mitra') ?>">Kelola Mitra</a></div>
    <?php if (!$partnershipRecent): ?><div class="empty-state small"><p>Belum ada lead kemitraan terbaru.</p></div>
    <?php else: ?>
    <div class="mini-feed">
      <?php foreach ($partnershipRecent as $lead): ?>
      <div class="feed-item">
        <div>
          <b><?= e($lead['organization_name']) ?></b>
          <span class="muted"><?= e($lead['contact_name']) ?> · <?= e($lead['partnership_type'] ?: 'Jenis belum dipilih') ?></span>
        </div>
        <div class="feed-meta">
          <span class="badge badge-<?= $lead['status'] === 'approved' ? 'verified' : ($lead['status'] === 'rejected' ? 'rejected' : 'pending') ?>"><?= e(human_label($lead['status'])) ?></span>
          <small class="muted"><?= e(date('d/m H:i', strtotime($lead['created_at']))) ?></small>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h3>Status Konten Publik</h3><a class="btn btn-ghost btn-sm" href="<?= admin_url('konten') ?>">Kelola Konten</a></div>
    <div class="mini-feed">
      <div class="feed-item">
        <div><b>Artikel Publik</b><span class="muted">Konten berita dan artikel untuk edukasi publik</span></div>
        <div class="feed-meta"><span class="badge badge-verified"><?= number_format($articlesPublished) ?> publish</span></div>
      </div>
      <div class="feed-item">
        <div><b>Galeri Dokumentasi</b><span class="muted">Materi visual untuk trust dan pelaporan program</span></div>
        <div class="feed-meta"><span class="badge badge-verified"><?= number_format($galleryPublished) ?> publish</span></div>
      </div>
      <div class="feed-item">
        <div><b>Mitra & Kolaborasi</b><span class="muted">Jejak partner yang tampil di publik</span></div>
        <div class="feed-meta"><span class="badge badge-verified"><?= number_format($partnersTotal) ?> mitra</span></div>
      </div>
      <div class="feed-item">
        <div><b>Struktur Organisasi</b><span class="muted">Anggota yang tampil di halaman Tentang</span></div>
        <div class="feed-meta"><span class="badge badge-<?= $orgPublished > 0 ? 'verified' : 'pending' ?>"><?= number_format($orgPublished) ?> publish</span></div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Status Dokumen Publik</h3><a class="btn btn-ghost btn-sm" href="<?= admin_url('settings') ?>">Buka Pengaturan</a></div>
    <div class="mini-feed">
      <?php foreach ([
        'Akta Yayasan' => 'legal_akta_file',
        'SK Kemenkumham' => 'legal_sk_file',
        'NPWP' => 'legal_npwp_file',
        'Laporan Bulanan' => 'laporan_doc_bulanan',
        'Laporan Tahunan' => 'laporan_doc_tahunan',
        'Laporan Penyaluran' => 'laporan_doc_penyaluran_program',
        'Audit Keuangan' => 'laporan_doc_audit_keuangan',
      ] as $label => $docKey): ?>
      <?php $docReady = trim((string) setting($docKey, '')) !== ''; ?>
      <div class="feed-item">
        <div><b><?= e($label) ?></b><span class="muted"><?= $docReady ? 'Dokumen sudah siap ditampilkan ke publik' : 'Dokumen belum diunggah atau belum ditautkan' ?></span></div>
        <div class="feed-meta"><span class="badge badge-<?= $docReady ? 'verified' : 'pending' ?>"><?= $docReady ? 'Siap' : 'Belum' ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3>Donasi Terbaru</h3><a class="btn btn-ghost btn-sm" href="<?= admin_url('donasi') ?>">Lihat Semua</a></div>
  <?php if (!$recent): ?><div class="empty-state small"><p>Belum ada donasi masuk.</p></div>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Invoice</th><th>Donatur</th><th class="right">Nominal</th><th>Kategori</th><th>Status</th><th>Waktu</th></tr></thead>
    <tbody><?php foreach ($recent as $d): ?>
      <tr><td><?= e($d['invoice']) ?></td><td><?= e($d['is_anonymous']?'Hamba Allah':$d['donor_name']) ?></td>
      <td class="right"><?= rupiah($d['amount']) ?></td><td><?= e(ucfirst($d['category'])) ?></td>
      <td><span class="badge badge-<?= $d['status'] ?>"><?= e($d['status']) ?></span></td>
      <td class="muted"><?= e(date('d/m H:i', strtotime($d['created_at']))) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php admin_layout_footer(); ?>
