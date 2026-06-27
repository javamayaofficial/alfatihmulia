<?php
if (!defined('APP_NAME')) { exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $existing = $id ? DB::one("SELECT * FROM programs WHERE id=?", 'i', [$id]) : null;
        $title = trim($_POST['title'] ?? '');
        $cat = trim($_POST['category'] ?? 'umum');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $target = (int)preg_replace('/[^0-9]/','',$_POST['target_amount'] ?? '0');
        $benef = (int)($_POST['beneficiaries'] ?? 0);
        $video = trim($_POST['video_url'] ?? '');
        $status = in_array($_POST['status']??'active',['active','draft','closed'])?$_POST['status']:'active';
        $imageUpload = upload_asset_image('program_image_file', 'program', ['webp']);
        if (!$imageUpload['ok']) {
            flash_set($imageUpload['message'], 'err');
            header('Location: '.admin_url('program', $id ? ['edit' => $id] : [])); exit;
        }
        $imageFile = !empty($imageUpload['file']) ? $imageUpload['file'] : trim((string) ($existing['image'] ?? ''));
        if ($title !== '') {
            if ($id) {
                DB::run("UPDATE programs SET title=?,category=?,excerpt=?,description=?,image=?,target_amount=?,beneficiaries=?,video_url=?,status=? WHERE id=?",
                  'sssssiiisi',[$title,$cat,$excerpt,$desc,$imageFile?:null,$target,$benef,$video?:null,$status,$id]);
                if (!empty($imageUpload['file']) && !empty($existing['image']) && $existing['image'] !== $imageUpload['file']) {
                    delete_asset_image_if_unused($existing['image'], ['table' => 'programs', 'id' => $id]);
                }
                audit('update_program', $title);
                flash_set('Program diperbarui.');
            } else {
                $slug = slugify($title);
                DB::run("INSERT INTO programs (slug,title,category,excerpt,description,image,target_amount,beneficiaries,video_url,status) VALUES (?,?,?,?,?,?,?,?,?,?)",
                  'ssssssiiss',[$slug,$title,$cat,$excerpt,$desc,$imageFile?:null,$target,$benef,$video?:null,$status]);
                audit('create_program', $title);
                flash_set('Program baru ditambahkan.');
            }
        }
    } elseif ($act === 'delete') {
        $id=(int)($_POST['id']??0);
        $existing = DB::one("SELECT * FROM programs WHERE id=?", 'i', [$id]);
        DB::run("DELETE FROM programs WHERE id=?", 'i', [$id]);
        if (!empty($existing['image'])) {
            delete_asset_image_if_unused($existing['image'], ['table' => 'programs', 'id' => $id]);
        }
        audit('delete_program', '#'.$id);
        flash_set('Program dihapus.', 'err');
    }
    header('Location: '.admin_url('program')); exit;
}

$edit = null;
if (isset($_GET['edit'])) $edit = DB::one("SELECT * FROM programs WHERE id=?", 'i', [(int)$_GET['edit']]);
$rows = DB::all("SELECT * FROM programs ORDER BY created_at DESC");

admin_layout_header('program', 'Kelola Program');
flash_show();
?>
<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h3><?= $edit?'Edit Program':'Tambah Program' ?></h3></div>
    <form method="post" class="form" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="act" value="save"><input type="hidden" name="id" value="<?= $edit['id']??'' ?>">
      <label>Judul Program</label><input type="text" name="title" value="<?= e($edit['title']??'') ?>" required>
      <label>Kategori</label><input type="text" name="category" value="<?= e($edit['category']??'') ?>" placeholder="mis. Air, Pendidikan, Wakaf">
      <label>Ringkasan Singkat</label><input type="text" name="excerpt" value="<?= e($edit['excerpt']??'') ?>">
      <label>Deskripsi</label><textarea name="description" rows="4"><?= e($edit['description']??'') ?></textarea>
      <label>Visual Program</label>
      <input type="file" name="program_image_file" accept=".webp">
      <small class="secret-hint">Gunakan gambar horizontal <b>WebP</b>. Rekomendasi terbaik <b>1600 x 900 px</b>, alternatif aman <b>1400 x 900 px</b> atau <b>1200 x 675 px</b>. Hindari gambar kotak atau terlalu kecil.</small>
      <?php if (!empty($edit['image'])): ?>
      <div class="upload-preview" style="margin-top:10px">
        <img src="<?= e(asset('img/' . $edit['image'])) ?>" alt="<?= e($edit['title'] ?? 'Visual Program') ?>">
        <div class="upload-meta">
          <b>Visual aktif</b>
          <span class="muted"><?= e($edit['image']) ?></span>
        </div>
      </div>
      <?php endif; ?>
      <label>Target Dana (Rp)</label><input type="text" name="target_amount" inputmode="numeric" value="<?= e($edit['target_amount']??'') ?>">
      <label>Penerima Manfaat</label><input type="number" name="beneficiaries" value="<?= e($edit['beneficiaries']??0) ?>">
      <label>URL Video (embed, opsional)</label><input type="text" name="video_url" value="<?= e($edit['video_url']??'') ?>">
      <label>Status</label>
      <select name="status">
        <?php foreach (['active'=>'Aktif','draft'=>'Draft','closed'=>'Selesai'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= ($edit['status']??'active')===$k?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary btn-block"><?= $edit?'Simpan Perubahan':'Tambah Program' ?></button>
      <?php if ($edit): ?><a class="btn btn-ghost btn-block" href="<?= admin_url('program') ?>">Batal</a><?php endif; ?>
    </form>
    <p class="note">Upload visual program langsung di form ini. Format yang diwajibkan adalah <b>WebP</b> agar website lebih ringan. Ukuran terbaik untuk website adalah <b>1600 x 900 px</b> dengan rasio <b>16:9</b>. Ukuran yang masih bagus: <b>1400 x 900 px</b>, <b>1200 x 675 px</b>, atau minimum <b>960 x 540 px</b>.</p>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Daftar Program</h3></div>
    <?php if (!$rows): ?><div class="empty-state small"><p>Belum ada program. Tambahkan yang pertama.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Program</th><th class="right">Terkumpul</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody><?php foreach ($rows as $p): ?>
        <tr><td>
          <div class="table-media">
            <div class="table-thumb" style="background-image:url('<?= e($p['image'] ? asset('img/'.$p['image']) : asset('img/placeholder.svg')) ?>')"></div>
            <div><b><?= e($p['title']) ?></b><br><small class="muted"><?= e($p['category']) ?></small></div>
          </div>
        </td>
        <td class="right"><?= rupiah($p['collected_amount']) ?><br><small class="muted">dari <?= rupiah($p['target_amount']) ?></small></td>
        <td><span class="badge badge-<?= $p['status']==='active'?'verified':'pending' ?>"><?= e($p['status']) ?></span></td>
        <td class="actions">
          <a class="btn btn-ghost btn-sm" href="<?= admin_url('program',['edit'=>$p['id']]) ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Hapus program ini?')"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn-ghost btn-sm">Hapus</button></form>
        </td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
  </div>
</div>
<?php admin_layout_footer(); ?>
