<?php
if (!defined('APP_NAME')) { exit; }
$im = impact_data();
$points = DB::all("SELECT pp.*, p.title AS program FROM program_points pp LEFT JOIN programs p ON p.id=pp.program_id ORDER BY pp.id DESC LIMIT 100");
// data grafik 6 bulan terakhir
$chart = DB::all("SELECT DATE_FORMAT(created_at,'%Y-%m') AS bln, COALESCE(SUM(amount),0) AS total
                  FROM donations WHERE status='verified' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                  GROUP BY bln ORDER BY bln ASC");
$mapsKey = setting('google_maps_key','');
layout_header('Dashboard Dampak');
?>
<section class="page-head impact-head"><div class="container">
  <span class="pill">Transparansi Real-Time</span>
  <h1>Dashboard Dampak</h1>
  <p class="muted">Setiap angka di halaman ini ditarik langsung dari data donasi yang telah diverifikasi. Inilah komitmen amanah kami.</p>
</div></section>

<section class="section"><div class="container">
  <div class="stats-grid">
    <div class="stat-card stat-accent"><span class="stat-ic">💧</span><b class="counter" data-target="<?= $im['derived']['total_dana'] ?>" data-prefix="Rp ">Rp 0</b><span>Dana Terkumpul</span></div>
    <div class="stat-card stat-gold"><span class="stat-ic">🎁</span><b class="counter" data-target="<?= total_tersalurkan() ?>" data-prefix="Rp ">Rp 0</b><span>Dana Tersalurkan</span></div>
    <div class="stat-card"><span class="stat-ic">🤲</span><b class="counter" data-target="<?= $im['derived']['total_donatur'] ?>">0</b><span>Total Donatur</span></div>
    <div class="stat-card"><span class="stat-ic">🙌</span><b class="counter" data-target="<?= $im['derived']['total_relawan'] ?>">0</b><span>Relawan Aktif</span></div>
    <div class="stat-card"><span class="stat-ic">📋</span><b class="counter" data-target="<?= $im['derived']['program_aktif'] ?>">0</b><span>Program Aktif</span></div>
    <?php foreach ($im['manual'] as $m): ?>
    <div class="stat-card"><span class="stat-ic"><?= e($m['icon']) ?></span><b class="counter" data-target="<?= (int)$m['value'] ?>">0</b><span><?= e($m['label']) ?></span></div>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section section-soft"><div class="container">
  <div class="section-head"><h2>Tren Donasi 6 Bulan Terakhir</h2></div>
  <?php if ($chart): ?>
    <div class="bar-chart">
      <?php $max = max(array_map(fn($c)=>(int)$c['total'], $chart)) ?: 1;
      foreach ($chart as $c): $h = round((int)$c['total']/$max*100); ?>
        <div class="bar-col"><div class="bar" style="height:<?= max(4,$h) ?>%" title="<?= rupiah($c['total']) ?>"></div><span><?= e(date('M', strtotime($c['bln'].'-01'))) ?></span></div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state"><p>Grafik akan tampil setelah ada donasi terverifikasi.</p></div>
  <?php endif; ?>
</div></section>

<section class="section"><div class="container">
  <div class="section-head"><h2>Peta Sebaran Program</h2><p class="muted">Lokasi nyata kebaikan Anda di seluruh Indonesia.</p></div>
  <?php if ($mapsKey && $points): ?>
    <div id="map" class="map-box" data-points='<?= e(json_encode(array_map(fn($p)=>["name"=>$p["name"],"lat"=>(float)$p["lat"],"lng"=>(float)$p["lng"]], $points))) ?>'></div>
    <script async src="https://maps.googleapis.com/maps/api/js?key=<?= e($mapsKey) ?>&callback=initAIPMap"></script>
  <?php elseif ($points): ?>
    <div class="note">ℹ️ Tampilan peta interaktif aktif setelah yayasan memasukkan Google Maps API Key di Pengaturan. Berikut daftar lokasi program:</div>
    <div class="card-grid points">
      <?php foreach ($points as $pt): ?>
      <div class="point-card"><b><?= e($pt['name']) ?></b><span class="muted"><?= e($pt['province']) ?></span><small><?= e($pt['program']) ?> • <?= number_format($pt['beneficiaries']) ?> penerima</small></div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state"><p>Titik lokasi program akan ditampilkan di sini.</p></div>
  <?php endif; ?>
</div></section>
<?php layout_footer(); ?>
