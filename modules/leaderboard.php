<?php
if (!defined('APP_NAME')) { exit; }
$board = leaderboard(50);
$target = (int) setting('umrah_target', '10000000'); // target dana untuk reward relawan tahap awal
layout_header('Leaderboard Relawan');
?>
<section class="page-head gold-head"><div class="container">
  <span class="pill pill-gold">Papan Peringkat Relawan</span>
  <h1>Leaderboard Duta Air Kehidupan</h1>
  <p class="muted">Perkembangan kontribusi relawan yang sudah tercatat secara nyata di sistem.</p>
</div></section>

<section class="section"><div class="container narrow">
  <?php if (!$board || ($board[0]['total'] ?? 0) == 0): ?>
    <div class="empty-state"><p>Belum ada kontribusi tercatat. Jadilah relawan pertama yang menggerakkan kebaikan!</p>
      <a class="btn btn-primary" href="<?= url('relawan') ?>">Daftar Relawan</a></div>
  <?php else: ?>
    <div class="board">
      <?php $rank=0; foreach ($board as $b): $rank++; if($b['total']==0) continue;
        $pct = $target>0 ? min(100, round($b['total']/$target*100)) : 0;
        $medal = $rank==1?'🥇':($rank==2?'🥈':($rank==3?'🥉':$rank)); ?>
      <div class="board-row <?= $rank<=3?'top':'' ?>">
        <div class="rank"><?= $medal ?></div>
        <div class="board-info">
          <b><?= e($b['name']) ?></b>
          <span class="muted"><?= number_format($b['jml_donasi']) ?> donasi dibawa</span>
          <div class="progress mini"><span style="width:<?= $pct ?>%"></span></div>
          <small class="muted"><?= $pct ?>% menuju target reward relawan</small>
        </div>
        <div class="board-total"><?= rupiah($b['total']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="center"><a class="btn btn-primary btn-lg" href="<?= url('relawan') ?>">Ikut Bergabung Sekarang</a></div>
  <?php endif; ?>
</div></section>
<?php layout_footer(); ?>
