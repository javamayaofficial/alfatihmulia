<?php
if (!defined('APP_NAME')) { exit; }
$im = impact_data();
$pending = (int) DB::val("SELECT COUNT(*) FROM donations WHERE status='pending'");
$danaPending = (int) DB::val("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='pending'");
$recent = DB::all("SELECT * FROM donations ORDER BY created_at DESC LIMIT 8");

admin_layout_header('dashboard', 'Dashboard');
flash_show();
?>
<div class="stats-grid">
  <div class="stat-card stat-accent"><span class="stat-ic">💧</span><b><?= rupiah($im['derived']['total_dana']) ?></b><span>Dana Terkumpul (Verified)</span></div>
  <div class="stat-card stat-gold"><span class="stat-ic">⏳</span><b><?= number_format($pending) ?></b><span>Donasi Menunggu Verifikasi</span></div>
  <div class="stat-card"><span class="stat-ic">🤲</span><b><?= number_format($im['derived']['total_donatur']) ?></b><span>Total Donatur</span></div>
  <div class="stat-card"><span class="stat-ic">🙌</span><b><?= number_format($im['derived']['total_relawan']) ?></b><span>Relawan Aktif</span></div>
</div>

<?php if ($pending > 0): ?>
<div class="alert alert-info">⏳ Ada <b><?= $pending ?></b> donasi (<?= rupiah($danaPending) ?>) menunggu verifikasi. <a href="<?= admin_url('donasi',['status'=>'pending']) ?>">Verifikasi sekarang →</a></div>
<?php endif; ?>

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
