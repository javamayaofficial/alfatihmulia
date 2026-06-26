<?php
if (!defined('APP_NAME')) { exit; }

// Aksi verifikasi / tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['act'] ?? '';
    $d = DB::one("SELECT * FROM donations WHERE id=?", 'i', [$id]);
    if ($d) {
        if ($act === 'verify' && $d['status'] !== 'verified') {
            DB::run("UPDATE donations SET status='verified', verified_at=NOW() WHERE id=?", 'i', [$id]);
            // update collected_amount program (anti-double via cek status sebelumnya 'verified')
            if ($d['program_id']) {
                DB::run("UPDATE programs SET collected_amount = collected_amount + ? WHERE id=?", 'ii', [(int)$d['amount'], (int)$d['program_id']]);
            }
            audit('verify_donasi', $d['invoice'].' '.rupiah($d['amount']));
            // notif WA donatur (gated)
            if ($d['donor_phone']) send_wa($d['donor_phone'], "Alhamdulillah, donasi Anda ".rupiah($d['amount'])." (".$d['invoice'].") telah DIVERIFIKASI. Jazakallahu khairan. — ".setting('yayasan_name','Yayasan Al Fatih'));
            flash_set('Donasi '.$d['invoice'].' berhasil diverifikasi.');
        } elseif ($act === 'reject') {
            if ($d['status'] === 'verified' && $d['program_id']) {
                DB::run("UPDATE programs SET collected_amount = GREATEST(0, collected_amount - ?) WHERE id=?", 'ii', [(int)$d['amount'], (int)$d['program_id']]);
            }
            DB::run("UPDATE donations SET status='rejected' WHERE id=?", 'i', [$id]);
            audit('reject_donasi', $d['invoice']);
            flash_set('Donasi '.$d['invoice'].' ditandai ditolak.', 'err');
        }
    }
    header('Location: ' . admin_url('donasi')); exit;
}

$filter = preg_replace('/[^a-z]/', '', $_GET['status'] ?? '');
$where = in_array($filter, ['pending','verified','rejected'], true) ? "WHERE status='$filter'" : '';
$rows = DB::all("SELECT d.*, p.title AS program FROM donations d LEFT JOIN programs p ON p.id=d.program_id $where ORDER BY d.created_at DESC LIMIT 200");

admin_layout_header('donasi', 'Kelola Donasi');
flash_show();
?>
<div class="filter-row">
  <a class="chip <?= $filter===''?'on':'' ?>" href="<?= admin_url('donasi') ?>">Semua</a>
  <a class="chip <?= $filter==='pending'?'on':'' ?>" href="<?= admin_url('donasi',['status'=>'pending']) ?>">Menunggu</a>
  <a class="chip <?= $filter==='verified'?'on':'' ?>" href="<?= admin_url('donasi',['status'=>'verified']) ?>">Terverifikasi</a>
  <a class="chip <?= $filter==='rejected'?'on':'' ?>" href="<?= admin_url('donasi',['status'=>'rejected']) ?>">Ditolak</a>
</div>
<div class="panel">
<?php if (!$rows): ?><div class="empty-state small"><p>Tidak ada data donasi pada filter ini.</p></div>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Invoice</th><th>Donatur</th><th>WA</th><th class="right">Nominal</th><th>Kategori</th><th>Program</th><th>Referral</th><th>Status</th><th>Aksi</th></tr></thead>
  <tbody><?php foreach ($rows as $d): ?>
    <tr>
      <td><?= e($d['invoice']) ?><br><small class="muted"><?= e(date('d/m/y H:i', strtotime($d['created_at']))) ?></small></td>
      <td><?= e($d['is_anonymous']?'Hamba Allah':($d['donor_name']?:'-')) ?></td>
      <td><?php if($d['donor_phone']): ?><a target="_blank" href="<?= e(wa_link($d['donor_phone'],'Assalamualaikum, terkait donasi '.$d['invoice'])) ?>">Chat</a><?php else: ?>-<?php endif; ?></td>
      <td class="right"><b><?= rupiah($d['amount']) ?></b></td>
      <td><?= e(ucfirst($d['category'])) ?></td>
      <td><?= e($d['program']?:'Umum') ?></td>
      <td><?= e($d['referral_code']?:'-') ?></td>
      <td><span class="badge badge-<?= $d['status'] ?>"><?= e($d['status']) ?></span></td>
      <td class="actions">
        <?php if ($d['status'] !== 'verified'): ?>
        <form method="post" onsubmit="return confirm('Verifikasi donasi ini?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $d['id'] ?>"><input type="hidden" name="act" value="verify"><button class="btn btn-primary btn-sm">✓ Verifikasi</button></form>
        <?php endif; ?>
        <?php if ($d['status'] !== 'rejected'): ?>
        <form method="post" onsubmit="return confirm('Tolak donasi ini?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $d['id'] ?>"><input type="hidden" name="act" value="reject"><button class="btn btn-ghost btn-sm">✕ Tolak</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?></tbody>
</table></div>
<?php endif; ?>
</div>
<?php admin_layout_footer(); ?>
