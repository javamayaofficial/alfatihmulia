<?php
if (!defined('APP_NAME')) { exit; }
Auth::requireAdmin('superadmin');

@mkdir(BACKUP_DIR, 0755, true);

/** Buat dump SQL seluruh tabel (murni PHP, tanpa mysqldump/terminal) */
function aip_backup_db() {
    $conn = DB::conn();
    $tables = [];
    $res = $conn->query("SHOW TABLES");
    while ($row = $res->fetch_row()) { $tables[] = $row[0]; }

    $out = "-- Al Fatih Impact Platform - Backup\n-- Tanggal: " . date('Y-m-d H:i:s') . "\nSET NAMES utf8mb4;\nSET foreign_key_checks=0;\n\n";
    foreach ($tables as $t) {
        $create = $conn->query("SHOW CREATE TABLE `$t`")->fetch_row();
        $out .= "DROP TABLE IF EXISTS `$t`;\n" . $create[1] . ";\n\n";
        $rows = $conn->query("SELECT * FROM `$t`");
        while ($r = $rows->fetch_assoc()) {
            $cols = array_map(fn($c)=>"`$c`", array_keys($r));
            $vals = array_map(function($v) use ($conn){
                return $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'";
            }, array_values($r));
            $out .= "INSERT INTO `$t` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
        }
        $out .= "\n";
    }
    $out .= "SET foreign_key_checks=1;\n";
    $fname = 'backup-' . date('Ymd-His') . '.sql';
    file_put_contents(BACKUP_DIR . '/' . $fname, $out);
    return $fname;
}

/** Jalankan file SQL (restore) */
function aip_restore_db($path) {
    $sql = file_get_contents($path);
    if ($sql === false) return false;
    $conn = DB::conn();
    return $conn->multi_query($sql) ? (function() use ($conn){ while($conn->more_results() && $conn->next_result()){} return true; })() : false;
}

// ---- Aksi ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    if ($act === 'backup') {
        $f = aip_backup_db();
        audit('backup_db', $f);
        flash_set('Backup berhasil dibuat: ' . $f);
    } elseif ($act === 'delete') {
        $f = basename($_POST['file'] ?? '');
        if ($f && file_exists(BACKUP_DIR.'/'.$f)) { @unlink(BACKUP_DIR.'/'.$f); audit('delete_backup',$f); flash_set('Backup dihapus.', 'err'); }
    } elseif ($act === 'restore') {
        if (!empty($_FILES['sqlfile']['tmp_name']) && is_uploaded_file($_FILES['sqlfile']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['sqlfile']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'sql') { flash_set('File harus berekstensi .sql', 'err'); }
            else {
                // backup otomatis sebelum restore (jaga-jaga)
                $auto = aip_backup_db();
                if (aip_restore_db($_FILES['sqlfile']['tmp_name'])) { audit('restore_db', $_FILES['sqlfile']['name']); flash_set('Restore berhasil. (Backup otomatis sebelum restore: '.$auto.')'); }
                else { flash_set('Restore gagal. Periksa file SQL Anda.', 'err'); }
            }
        } else { flash_set('Pilih file .sql terlebih dahulu.', 'err'); }
    }
    header('Location: ' . admin_url('backup')); exit;
}

// Unduh backup
if (isset($_GET['download'])) {
    $f = basename($_GET['download']);
    $path = BACKUP_DIR . '/' . $f;
    if (file_exists($path) && pathinfo($f, PATHINFO_EXTENSION) === 'sql') {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $f . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
    http_response_code(404); exit('File tidak ditemukan.');
}

$backups = [];
if (is_dir(BACKUP_DIR)) {
    foreach (array_diff(scandir(BACKUP_DIR), ['.','..','.htaccess','index.html']) as $f) {
        if (pathinfo($f, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = ['name'=>$f, 'size'=>filesize(BACKUP_DIR.'/'.$f), 'time'=>filemtime(BACKUP_DIR.'/'.$f)];
        }
    }
    usort($backups, fn($a,$b)=>$b['time']-$a['time']);
}

admin_layout_header('backup', 'Backup & Restore');
flash_show();
?>
<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h3>💾 Buat Backup</h3></div>
    <p class="muted">Simpan salinan seluruh database (program, donasi, relawan, pengaturan) ke dalam satu file <code>.sql</code> yang bisa Anda unduh.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="backup">
      <button class="btn btn-primary btn-block">Buat Backup Sekarang</button>
    </form>
    <p class="note">Disarankan backup rutin (mis. tiap pekan) dan sebelum melakukan perubahan besar.</p>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>♻️ Restore dari File</h3></div>
    <p class="muted">Pulihkan data dari file backup <code>.sql</code>. Sistem otomatis membuat backup cadangan sebelum restore.</p>
    <form method="post" enctype="multipart/form-data" onsubmit="return confirm('Restore akan menimpa data saat ini. Lanjutkan?')">
      <?= csrf_field() ?><input type="hidden" name="act" value="restore">
      <label>Pilih file .sql</label>
      <input type="file" name="sqlfile" accept=".sql" required>
      <button class="btn btn-outline btn-block">Restore Database</button>
    </form>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3>Daftar Backup Tersimpan</h3><span class="muted"><?= count($backups) ?> file</span></div>
  <?php if (!$backups): ?>
    <div class="empty-state small"><p>Belum ada backup. Buat backup pertama Anda di atas. 💾</p></div>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Nama File</th><th>Ukuran</th><th>Tanggal</th><th>Aksi</th></tr></thead>
    <tbody><?php foreach ($backups as $b): ?>
      <tr>
        <td><code><?= e($b['name']) ?></code></td>
        <td><?= number_format($b['size']/1024, 1) ?> KB</td>
        <td class="muted"><?= e(date('d/m/Y H:i', $b['time'])) ?></td>
        <td class="actions">
          <a class="btn btn-primary btn-sm" href="<?= admin_url('backup',['download'=>$b['name']]) ?>">Unduh</a>
          <form method="post" onsubmit="return confirm('Hapus file backup ini?')"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="file" value="<?= e($b['name']) ?>"><button class="btn btn-ghost btn-sm">Hapus</button></form>
        </td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php admin_layout_footer(); ?>
