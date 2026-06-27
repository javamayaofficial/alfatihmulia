<?php
if (!defined('APP_NAME')) { exit; }

$categoryOptions = ['Air Kehidupan', 'Cahaya Ilmu', 'Jejak Baitullah'];
$typeOptions = ['photo' => 'Foto', 'video' => 'Video'];
$statusOptions = ['published' => 'Publish', 'draft' => 'Draft'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';

    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = $id > 0 ? DB::one("SELECT * FROM media_gallery WHERE id=?", 'i', [$id]) : null;
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $mediaType = $_POST['media_type'] ?? 'photo';
        $mediaPath = trim($_POST['media_path'] ?? '');
        $videoUrl = trim($_POST['video_url'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'published';
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $upload = upload_asset_image('media_file', 'gallery', ['webp']);
        if (!$upload['ok']) {
            flash_set($upload['message'], 'err');
            header('Location: ' . admin_url('galeri', $id > 0 ? ['edit' => $id] : []));
            exit;
        }
        if (!empty($upload['file'])) {
            $mediaPath = $upload['file'];
        }

        if ($title !== '' && in_array($mediaType, array_keys($typeOptions), true) && in_array($status, array_keys($statusOptions), true)) {
            if ($id > 0) {
                DB::run(
                    "UPDATE media_gallery SET title=?, category=?, media_type=?, media_path=?, video_url=?, description=?, status=?, sort_order=? WHERE id=?",
                    'sssssssii',
                    [$title, $category ?: null, $mediaType, $mediaPath ?: null, $videoUrl ?: null, $description ?: null, $status, $sortOrder, $id]
                );
                audit('update_gallery_item', $title);
                flash_set('Item galeri diperbarui.');
                if (!empty($existing['media_path']) && $existing['media_path'] !== $mediaPath) {
                    delete_asset_image_if_unused($existing['media_path']);
                }
            } else {
                DB::run(
                    "INSERT INTO media_gallery (title, category, media_type, media_path, video_url, description, status, sort_order) VALUES (?,?,?,?,?,?,?,?)",
                    'sssssssi',
                    [$title, $category ?: null, $mediaType, $mediaPath ?: null, $videoUrl ?: null, $description ?: null, $status, $sortOrder]
                );
                audit('create_gallery_item', $title);
                flash_set('Item galeri ditambahkan.');
            }
        }
    } elseif ($act === 'remove_media') {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = DB::one("SELECT * FROM media_gallery WHERE id=?", 'i', [$id]);
        if ($existing && !empty($existing['media_path'])) {
            DB::run("UPDATE media_gallery SET media_path=NULL WHERE id=?", 'i', [$id]);
            delete_asset_image_if_unused($existing['media_path']);
            audit('remove_gallery_media', $existing['title'] ?? ('#' . $id));
            flash_set('File media galeri dihapus.');
        }
    } elseif ($act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = DB::one("SELECT * FROM media_gallery WHERE id=?", 'i', [$id]);
        DB::run("DELETE FROM media_gallery WHERE id=?", 'i', [$id]);
        if (!empty($existing['media_path'])) {
            delete_asset_image_if_unused($existing['media_path']);
        }
        audit('delete_gallery_item', '#' . $id);
        flash_set('Item galeri dihapus.', 'err');
    }

    header('Location: ' . admin_url('galeri'));
    exit;
}

$edit = isset($_GET['edit']) ? DB::one("SELECT * FROM media_gallery WHERE id=?", 'i', [(int) $_GET['edit']]) : null;
$rows = DB::all("SELECT * FROM media_gallery ORDER BY sort_order ASC, created_at DESC");

admin_layout_header('galeri', 'Kelola Galeri Dokumentasi');
flash_show();
?>
<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h3><?= $edit ? 'Edit Item Galeri' : 'Tambah Item Galeri' ?></h3></div>
    <form method="post" class="form" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <label>Judul</label><input type="text" name="title" value="<?= e($edit['title'] ?? '') ?>" required>
      <div class="grid-2">
        <div>
          <label>Kategori</label>
          <select name="category">
            <option value="">Tanpa kategori</option>
            <?php foreach ($categoryOptions as $option): ?>
            <option value="<?= e($option) ?>" <?= ($edit['category'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Tipe Media</label>
          <select name="media_type">
            <?php foreach ($typeOptions as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($edit['media_type'] ?? 'photo') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <label>Nama File Gambar / Thumbnail</label><input type="text" name="media_path" value="<?= e($edit['media_path'] ?? '') ?>" placeholder="Contoh: galeri-air-01.jpg">
      <label>Upload Gambar / Thumbnail</label><input type="file" name="media_file" accept=".webp">
      <small class="secret-hint">Gunakan gambar horizontal <b>WebP</b>. Rekomendasi terbaik <b>1600 x 900 px</b>, alternatif aman <b>1400 x 900 px</b> atau <b>1200 x 675 px</b>. Jika ini thumbnail video, tetap gunakan rasio <b>16:9</b>.</small>
      <?php if (!empty($edit['media_path'])): ?>
      <div class="upload-preview">
        <img src="<?= e(asset('img/' . $edit['media_path'])) ?>" alt="<?= e($edit['title'] ?? 'Preview galeri') ?>">
        <div class="upload-meta">
          <b>Preview Saat Ini</b>
          <span class="muted"><?= e($edit['media_path']) ?></span>
        </div>
      </div>
      <div class="inline-actions">
        <form method="post" onsubmit="return confirm('Hapus file media ini dari item galeri?')">
          <?= csrf_field() ?>
          <input type="hidden" name="act" value="remove_media">
          <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
          <button class="btn btn-ghost btn-sm">Hapus File Saat Ini</button>
        </form>
      </div>
      <?php endif; ?>
      <label>URL Video (opsional)</label><input type="text" name="video_url" value="<?= e($edit['video_url'] ?? '') ?>" placeholder="https://youtube.com/...">
      <label>Deskripsi Singkat</label><textarea name="description" rows="4"><?= e($edit['description'] ?? '') ?></textarea>
      <div class="grid-2">
        <div>
          <label>Status</label>
          <select name="status">
            <?php foreach ($statusOptions as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($edit['status'] ?? 'published') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Urutan</label><input type="number" name="sort_order" value="<?= e($edit['sort_order'] ?? 0) ?>">
        </div>
      </div>
      <button class="btn btn-primary btn-block"><?= $edit ? 'Simpan Perubahan' : 'Tambah Galeri' ?></button>
      <?php if ($edit): ?><a class="btn btn-ghost btn-block" href="<?= admin_url('galeri') ?>">Batal</a><?php endif; ?>
    </form>
    <p class="note">Saat file diganti atau dihapus dari item ini, sistem akan mencoba membersihkan file lama jika sudah tidak dipakai item lain. Format yang diwajibkan adalah <b>WebP</b>. Ukuran terbaik untuk galeri adalah <b>1600 x 900 px</b> dengan rasio <b>16:9</b>; minimum yang masih layak <b>960 x 540 px</b>.</p>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Daftar Galeri</h3><span class="muted"><?= count($rows) ?> item</span></div>
    <?php if (!$rows): ?><div class="empty-state small"><p>Belum ada item galeri.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Judul</th><th>Kategori</th><th>Tipe</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody><?php foreach ($rows as $row): ?>
        <tr>
          <td>
            <div class="table-media">
              <div class="table-thumb" style="background-image:url('<?= $row['media_path'] ? e(asset('img/' . $row['media_path'])) : asset('img/placeholder.svg') ?>')"></div>
              <div>
                <b><?= e($row['title']) ?></b><br><small class="muted"><?= e(snippet($row['description'] ?: '', 80)) ?></small>
              </div>
            </div>
          </td>
          <td><?= e($row['category'] ?: '-') ?></td>
          <td><?= e(human_label($row['media_type'])) ?></td>
          <td><span class="badge badge-<?= $row['status'] === 'published' ? 'verified' : 'pending' ?>"><?= e(human_label($row['status'])) ?></span></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="<?= admin_url('galeri', ['edit' => $row['id']]) ?>">Edit</a>
            <form method="post" onsubmit="return confirm('Hapus item galeri ini?')">
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
