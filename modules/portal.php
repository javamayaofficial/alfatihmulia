<?php
if (!defined('APP_NAME')) { exit; }
Auth::requireLogin();
$u = Auth::user();
if (!$u) { Auth::logout(); header('Location: '.url('login')); exit; }

$isRelawan = $u['role'] === 'relawan';

// Riwayat donasi milik user (cocokkan via phone/email)
$myDonations = DB::all(
    "SELECT * FROM donations WHERE donor_phone=? OR donor_email=? ORDER BY created_at DESC LIMIT 50",
    'ss', [$u['phone'], $u['email']]
);

layout_header($isRelawan ? 'Dashboard Relawan' : 'Dashboard Donatur');
?>
<section class="page-head dash-head"><div class="container">
  <h1>Assalamualaikum, <?= e($u['name']) ?> 👋</h1>
  <p class="muted"><?= $isRelawan ? 'Dashboard Relawan — Duta Air Kehidupan' : 'Dashboard Donatur' ?></p>
</div></section>

<section class="section"><div class="container">
<?php if ($isRelawan):
    $refLink = url('donasi', ['ref'=>$u['referral_code']]);
    $totalRef = (int) DB::val("SELECT COALESCE(SUM(amount),0) FROM donations WHERE referral_code=? AND status='verified'", 's', [$u['referral_code']]);
    $jmlRef   = (int) DB::val("SELECT COUNT(*) FROM donations WHERE referral_code=? AND status='verified'", 's', [$u['referral_code']]);
    $target   = (int) setting('umrah_target','50000000');
    $pct = $target>0 ? min(100, round($totalRef/$target*100)) : 0;
    $rank = (int) DB::val("SELECT COUNT(*)+1 FROM (SELECT u.id, COALESCE(SUM(d.amount),0) t FROM users u LEFT JOIN donations d ON d.referral_code=u.referral_code AND d.status='verified' WHERE u.role='relawan' GROUP BY u.id HAVING t > ?) x", 'i', [$totalRef]);
?>
  <div class="dash-grid">
    <div class="stat-card stat-accent"><span class="stat-ic">💰</span><b><?= rupiah($totalRef) ?></b><span>Dana Dibawa</span></div>
    <div class="stat-card"><span class="stat-ic">🤲</span><b><?= number_format($jmlRef) ?></b><span>Donasi Terbawa</span></div>
    <div class="stat-card stat-gold"><span class="stat-ic">🏆</span><b>#<?= $rank ?></b><span>Peringkat Nasional</span></div>
  </div>

  <div class="card reward-card">
    <h3>🕋 Progress The Legacy Umrah</h3>
    <div class="progress big gold"><span style="width:<?= $pct ?>%"></span></div>
    <div class="raise-meta"><span><?= rupiah($totalRef) ?> / <?= rupiah($target) ?></span><b><?= $pct ?>%</b></div>
    <p class="muted">Terus kumpulkan kebaikan untuk meraih reward Umrah dari yayasan.</p>
  </div>

  <div class="card">
    <h3>🔗 Link Referral Pribadimu</h3>
    <p class="muted">Bagikan link ini. Setiap donasi yang masuk lewat link akan tercatat ke akunmu.</p>
    <div class="copy-row">
      <input type="text" id="refLink" value="<?= e($refLink) ?>" readonly>
      <button class="btn btn-primary" onclick="copyRef()">Salin</button>
    </div>
    <a class="btn btn-outline btn-block" target="_blank" href="https://wa.me/?text=<?= rawurlencode('Yuk berdonasi untuk kebaikan bersama saya! '.$refLink) ?>">Bagikan ke WhatsApp</a>
    <div class="ref-code-box">Kode: <b><?= e($u['referral_code']) ?></b></div>
  </div>
<?php endif; ?>

  <div class="card">
    <h3>Riwayat Donasi Saya</h3>
    <?php if (!$myDonations): ?>
      <div class="empty-state small"><p>Belum ada donasi. Mulai kebaikan pertamamu hari ini. 🤲</p><a class="btn btn-primary" href="<?= url('donasi') ?>">Donasi Sekarang</a></div>
    <?php else: ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Invoice</th><th>Kategori</th><th class="right">Nominal</th><th>Status</th></tr></thead>
        <tbody><?php foreach ($myDonations as $d): ?>
          <tr><td><?= e($d['invoice']) ?></td><td><?= e(ucfirst($d['category'])) ?></td><td class="right"><?= rupiah($d['amount']) ?></td>
          <td><span class="badge badge-<?= $d['status'] ?>"><?= $d['status']==='verified'?'Terverifikasi':($d['status']==='pending'?'Menunggu':'Ditolak') ?></span></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
  </div>
  <div class="center"><a class="btn btn-ghost" href="<?= url('logout') ?>">Keluar</a></div>
</div></section>
<?php layout_footer(); ?>
