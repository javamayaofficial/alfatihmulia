<?php
if (!defined('APP_NAME')) { exit; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $act=$_POST['act']??'';
    if($act==='save'){
        $id=(int)($_POST['id']??0); $title=trim($_POST['title']??''); $cat=trim($_POST['category']??'Berita');
        $excerpt=trim($_POST['excerpt']??''); $content=trim($_POST['content']??''); $author=trim($_POST['author']??'Admin Yayasan');
        $status=in_array($_POST['status']??'published',['published','draft'])?$_POST['status']:'published';
        if($title!==''){
          if($id){ DB::run("UPDATE articles SET title=?,category=?,excerpt=?,content=?,author=?,status=? WHERE id=?",'ssssssi',[$title,$cat,$excerpt,$content,$author,$status,$id]); flash_set('Artikel diperbarui.'); }
          else { $slug=slugify($title); DB::run("INSERT INTO articles (slug,title,category,excerpt,content,author,status) VALUES (?,?,?,?,?,?,?)",'sssssss',[$slug,$title,$cat,$excerpt,$content,$author,$status]); flash_set('Artikel ditambahkan.'); }
          audit('save_artikel',$title);
        }
    } elseif($act==='delete'){ $id=(int)($_POST['id']??0); DB::run("DELETE FROM articles WHERE id=?",'i',[$id]); audit('delete_artikel','#'.$id); flash_set('Artikel dihapus.','err'); }
    header('Location: '.admin_url('artikel')); exit;
}
$edit=isset($_GET['edit'])?DB::one("SELECT * FROM articles WHERE id=?",'i',[(int)$_GET['edit']]):null;
$rows=DB::all("SELECT * FROM articles ORDER BY created_at DESC");
admin_layout_header('artikel','Kelola Artikel');
flash_show();
?>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h3><?= $edit?'Edit Artikel':'Tulis Artikel' ?></h3></div>
    <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="act" value="save"><input type="hidden" name="id" value="<?= $edit['id']??'' ?>">
      <label>Judul</label><input type="text" name="title" value="<?= e($edit['title']??'') ?>" required>
      <label>Kategori</label><input type="text" name="category" value="<?= e($edit['category']??'Berita') ?>">
      <label>Ringkasan</label><input type="text" name="excerpt" value="<?= e($edit['excerpt']??'') ?>">
      <label>Isi Artikel</label><textarea name="content" rows="6"><?= e($edit['content']??'') ?></textarea>
      <label>Penulis</label><input type="text" name="author" value="<?= e($edit['author']??'Admin Yayasan') ?>">
      <label>Status</label><select name="status"><option value="published" <?= ($edit['status']??'')==='published'?'selected':'' ?>>Publish</option><option value="draft" <?= ($edit['status']??'')==='draft'?'selected':'' ?>>Draft</option></select>
      <button class="btn btn-primary btn-block"><?= $edit?'Simpan':'Terbitkan' ?></button>
      <?php if($edit): ?><a class="btn btn-ghost btn-block" href="<?= admin_url('artikel') ?>">Batal</a><?php endif; ?>
    </form>
  </div>
  <div class="panel"><div class="panel-head"><h3>Daftar Artikel</h3></div>
    <?php if(!$rows): ?><div class="empty-state small"><p>Belum ada artikel.</p></div>
    <?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Judul</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($rows as $a): ?><tr><td><b><?= e($a['title']) ?></b><br><small class="muted"><?= e($a['category']) ?> · <?= e(date('d/m/y',strtotime($a['created_at']))) ?></small></td>
    <td><span class="badge badge-<?= $a['status']==='published'?'verified':'pending' ?>"><?= e($a['status']) ?></span></td>
    <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= admin_url('artikel',['edit'=>$a['id']]) ?>">Edit</a>
    <form method="post" onsubmit="return confirm('Hapus artikel?')"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-ghost btn-sm">Hapus</button></form></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </div>
</div>
<?php admin_layout_footer(); ?>
