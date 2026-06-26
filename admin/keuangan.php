<?php
if (!defined('APP_NAME')) { exit; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $val=(int)preg_replace('/[^0-9]/','',$_POST['total_tersalurkan']??'0');
    set_setting('total_tersalurkan',(string)$val);
    audit('update_penyaluran', rupiah($val));
    flash_set('Total dana tersalurkan diperbarui.');
    header('Location: '.admin_url('keuangan')); exit;
}
$masuk=(int)DB::val("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='verified'");
$keluar=total_tersalurkan();
$perkat=DB::all("SELECT category, COALESCE(SUM(amount),0) total, COUNT(*) jml FROM donations WHERE status='verified' GROUP BY category ORDER BY total DESC");
admin_layout_header('keuangan','Keuangan & Transparansi');
flash_show();
?>
<div class="stats-grid three">
  <div class="stat-card stat-accent"><span class="stat-ic">⬇️</span><b><?= rupiah($masuk) ?></b><span>Penerimaan</span></div>
  <div class="stat-card stat-gold"><span class="stat-ic">⬆️</span><b><?= rupiah($keluar) ?></b><span>Penyaluran</span></div>
  <div class="stat-card"><span class="stat-ic">💼</span><b><?= rupiah($masuk-$keluar) ?></b><span>Saldo Amanah</span></div>
</div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h3>Catat Dana Tersalurkan</h3></div>
    <form method="post" class="form"><?= csrf_field() ?>
      <label>Total Dana Tersalurkan (Rp)</label>
      <input type="text" name="total_tersalurkan" inputmode="numeric" value="<?= e($keluar) ?>">
      <button class="btn btn-primary btn-block">Simpan</button>
      <p class="note">Angka ini tampil di Dashboard Dampak & Laporan Transparansi publik.</p>
    </form>
  </div>
  <div class="panel"><div class="panel-head"><h3>Penerimaan per Kategori</h3></div>
    <?php if($perkat): ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>Kategori</th><th>Jml</th><th class="right">Total</th></tr></thead><tbody>
    <?php foreach($perkat as $r): ?><tr><td><?= e(ucfirst($r['category'])) ?></td><td><?= number_format($r['jml']) ?>x</td><td class="right"><?= rupiah($r['total']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php else: ?><div class="empty-state small"><p>Belum ada penerimaan.</p></div><?php endif; ?>
  </div>
</div>
<?php admin_layout_footer(); ?>
