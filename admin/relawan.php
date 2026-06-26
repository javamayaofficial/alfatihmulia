<?php
if (!defined('APP_NAME')) { exit; }
$rows = leaderboard(100);
admin_layout_header('relawan', 'Kelola Relawan');
flash_show();
?>
<div class="panel">
  <div class="panel-head"><h3>Relawan & Kontribusi</h3><span class="muted"><?= count($rows) ?> relawan</span></div>
  <?php if (!$rows): ?><div class="empty-state small"><p>Belum ada relawan terdaftar.</p></div>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>#</th><th>Nama</th><th>Kode Referral</th><th>Donasi Dibawa</th><th class="right">Total Dana</th></tr></thead>
    <tbody><?php $i=0; foreach ($rows as $b): $i++; ?>
      <tr><td><?= $i ?></td><td><b><?= e($b['name']) ?></b></td><td><code><?= e($b['referral_code']) ?></code></td>
      <td><?= number_format($b['jml_donasi']) ?>x</td><td class="right"><b><?= rupiah($b['total']) ?></b></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php admin_layout_footer(); ?>
