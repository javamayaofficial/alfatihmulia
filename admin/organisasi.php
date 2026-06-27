<?php
if (!defined('APP_NAME')) { exit; }

$groupOptions = [
    'pembina' => 'Dewan Pembina',
    'pengawas' => 'Dewan Pengawas',
    'pengurus' => 'Pengurus Yayasan',
];
$statusOptions = [
    'published' => 'Publish',
    'draft' => 'Draft',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';

    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = $id > 0 ? DB::one("SELECT * FROM organization_members WHERE id=?", 'i', [$id]) : null;
        $fullName = trim($_POST['full_name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $boardGroup = trim($_POST['board_group'] ?? 'pengurus');
        $photo = trim($_POST['photo'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = trim($_POST['status'] ?? 'published');

        $upload = upload_asset_image('photo_file', 'org');
        if (!$upload['ok']) {
            flash_set($upload['message'], 'err');
            header('Location: ' . admin_url('organisasi', $id > 0 ? ['edit' => $id] : []));
            exit;
        }
        if (!empty($upload['file'])) {
            $photo = $upload['file'];
        }

        if ($fullName !== '' && $position !== '' && isset($groupOptions[$boardGroup]) && isset($statusOptions[$status])) {
            if ($id > 0) {
                DB::run(
                    "UPDATE organization_members SET full_name=?, position=?, board_group=?, photo=?, bio=?, sort_order=?, status=? WHERE id=?",
                    'sssssisi',
                    [$fullName, $position, $boardGroup, $photo ?: null, $bio ?: null, $sortOrder, $status, $id]
                );
                audit('update_organization_member', $fullName);
                flash_set('Data struktur organisasi diperbarui.');
                if (!empty($existing['photo']) && $existing['photo'] !== $photo) {
                    delete_asset_image_if_unused($existing['photo']);
                }
            } else {
                DB::run(
                    "INSERT INTO organization_members (full_name, position, board_group, photo, bio, sort_order, status) VALUES (?,?,?,?,?,?,?)",
                    'sssssis',
                    [$fullName, $position, $boardGroup, $photo ?: null, $bio ?: null, $sortOrder, $status]
                );
                audit('create_organization_member', $fullName);
                flash_set('Anggota struktur organisasi ditambahkan.');
            }
        } else {
            flash_set('Nama, jabatan, kelompok, dan status wajib diisi dengan benar.', 'err');
        }
    } elseif ($act === 'remove_photo') {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = DB::one("SELECT * FROM organization_members WHERE id=?", 'i', [$id]);
        if ($existing && !empty($existing['photo'])) {
            DB::run("UPDATE organization_members SET photo=NULL WHERE id=?", 'i', [$id]);
            delete_asset_image_if_unused($existing['photo']);
            audit('remove_organization_photo', $existing['full_name'] ?? ('#' . $id));
            flash_set('Foto anggota organisasi dihapus.');
        }
    } elseif ($act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = DB::one("SELECT * FROM organization_members WHERE id=?", 'i', [$id]);
        DB::run("DELETE FROM organization_members WHERE id=?", 'i', [$id]);
        if (!empty($existing['photo'])) {
            delete_asset_image_if_unused($existing['photo']);
        }
        audit('delete_organization_member', '#' . $id);
        flash_set('Anggota struktur organisasi dihapus.', 'err');
    }

    header('Location: ' . admin_url('organisasi'));
    exit;
}

$edit = isset($_GET['edit']) ? DB::one("SELECT * FROM organization_members WHERE id=?", 'i', [(int) $_GET['edit']]) : null;
$rows = DB::all("SELECT * FROM organization_members ORDER BY board_group ASC, sort_order ASC, created_at DESC");

admin_layout_header('organisasi', 'Kelola Struktur Organisasi');
flash_show();
?>
<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h3><?= $edit ? 'Edit Anggota Organisasi' : 'Tambah Anggota Organisasi' ?></h3></div>
    <form method="post" class="form" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Nama Lengkap</label><input type="text" name="full_name" value="<?= e($edit['full_name'] ?? '') ?>" required>
      <label>Jabatan</label><input type="text" name="position" value="<?= e($edit['position'] ?? '') ?>" placeholder="Contoh: Ketua Yayasan" required>
      <div class="grid-2">
        <div>
          <label>Kelompok</label>
          <select name="board_group">
            <?php foreach ($groupOptions as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($edit['board_group'] ?? 'pengurus') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Status</label>
          <select name="status">
            <?php foreach ($statusOptions as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($edit['status'] ?? 'published') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <label>Nama File Foto (opsional)</label><input type="text" name="photo" value="<?= e($edit['photo'] ?? '') ?>" placeholder="Contoh: ketua-yayasan.jpg">
      <label>Upload Foto</label><input type="file" name="photo_file" accept=".jpg,.jpeg,.png,.webp,.gif,.svg">
      <?php if (!empty($edit['photo'])): ?>
      <div class="upload-preview">
        <img src="<?= e(asset('img/' . $edit['photo'])) ?>" alt="<?= e($edit['full_name'] ?? 'Preview foto organisasi') ?>">
        <div class="upload-meta">
          <b>Preview Foto Saat Ini</b>
          <span class="muted"><?= e($edit['photo']) ?></span>
        </div>
      </div>
      <div class="inline-actions">
        <form method="post" onsubmit="return confirm('Hapus foto anggota ini?')">
          <?= csrf_field() ?>
          <input type="hidden" name="act" value="remove_photo">
          <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
          <button class="btn btn-ghost btn-sm">Hapus Foto Saat Ini</button>
        </form>
      </div>
      <?php endif; ?>
      <label>Bio / Keterangan Singkat</label><textarea name="bio" rows="4" placeholder="Contoh: Fokus pada pengembangan program sosial dan tata kelola yayasan."><?= e($edit['bio'] ?? '') ?></textarea>
      <label>Urutan Tampil</label><input type="number" name="sort_order" value="<?= e($edit['sort_order'] ?? 0) ?>">
      <button class="btn btn-primary btn-block"><?= $edit ? 'Simpan Perubahan' : 'Tambah Anggota' ?></button>
      <?php if ($edit): ?><a class="btn btn-ghost btn-block" href="<?= admin_url('organisasi') ?>">Batal</a><?php endif; ?>
    </form>
    <p class="note">Saat foto diganti atau dihapus, sistem akan mencoba membersihkan file lama jika sudah tidak dipakai konten lain.</p>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Daftar Struktur Organisasi</h3><span class="muted"><?= count($rows) ?> anggota</span></div>
    <?php if (!$rows): ?><div class="empty-state small"><p>Belum ada data struktur organisasi.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Nama</th><th>Kelompok</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody><?php foreach ($rows as $row): ?>
        <tr>
          <td>
            <div class="table-media">
              <div class="table-thumb" style="background-image:url('<?= $row['photo'] ? e(asset('img/' . $row['photo'])) : asset('img/placeholder.svg') ?>')"></div>
              <div>
                <b><?= e($row['full_name']) ?></b><br>
                <small class="muted"><?= e($row['position']) ?></small>
              </div>
            </div>
          </td>
          <td><?= e($groupOptions[$row['board_group']] ?? $row['board_group']) ?></td>
          <td><span class="badge badge-<?= $row['status'] === 'published' ? 'verified' : 'pending' ?>"><?= e(human_label($row['status'])) ?></span></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="<?= admin_url('organisasi', ['edit' => $row['id']]) ?>">Edit</a>
            <form method="post" onsubmit="return confirm('Hapus anggota ini?')">
              <?= csrf_field() ?>
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <button class="btn btn-ghost btn-sm">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
  </div>
</div>
<?php admin_layout_footer(); ?>
