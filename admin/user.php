<?php
if (!defined('APP_NAME')) { exit; }
Auth::requireAdmin('superadmin');
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $act=$_POST['act']??'';
    if($act==='save'){
        $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $role=$_POST['role']??'donatur';
        $pass=$_POST['password']??''; $roles=['superadmin','admin_program','admin_keuangan','donatur','relawan'];
        if(!in_array($role,$roles,true)) $role='donatur';
        if($name!=='' && filter_var($email,FILTER_VALIDATE_EMAIL) && strlen($pass)>=6 && !DB::one("SELECT id FROM users WHERE email=?",'s',[$email])){
            $ref=in_array($role,['relawan'])?make_referral_code($name):null;
            DB::run("INSERT INTO users (name,email,password_hash,role,referral_code) VALUES (?,?,?,?,?)",'sssss',[$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role,$ref]);
            audit('create_user',$email.' ('.$role.')'); flash_set('Pengguna ditambahkan.');
        } else flash_set('Gagal: data tidak valid atau email sudah ada.','err');
    } elseif($act==='delete'){
        $id=(int)($_POST['id']??0);
        if($id!==(int)Auth::id()){ DB::run("DELETE FROM users WHERE id=?",'i',[$id]); audit('delete_user','#'.$id); flash_set('Pengguna dihapus.','err'); }
        else flash_set('Tidak bisa menghapus akun sendiri.','err');
    }
    header('Location: '.admin_url('user')); exit;
}
$rows=DB::all("SELECT id,name,email,role,referral_code,created_at FROM users ORDER BY FIELD(role,'superadmin','admin_program','admin_keuangan','relawan','donatur'), id DESC");
admin_layout_header('user','Manajemen Pengguna');
flash_show();
?>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h3>Tambah Pengguna / Admin</h3></div>
    <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="act" value="save">
      <label>Nama</label><input type="text" name="name" required>
      <label>Email</label><input type="email" name="email" required>
      <label>Password</label><input type="password" name="password" placeholder="Min 6 karakter" required>
      <label>Role</label><select name="role">
        <option value="admin_program">Admin Program</option>
        <option value="admin_keuangan">Admin Keuangan</option>
        <option value="superadmin">Super Admin</option>
        <option value="relawan">Relawan</option>
        <option value="donatur">Donatur</option>
      </select>
      <button class="btn btn-primary btn-block">Tambah Pengguna</button>
    </form>
  </div>
  <div class="panel"><div class="panel-head"><h3>Daftar Pengguna</h3></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Nama</th><th>Role</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($rows as $u): ?><tr><td><b><?= e($u['name']) ?></b><br><small class="muted"><?= e($u['email']) ?></small></td>
    <td><span class="badge"><?= e(str_replace('_',' ',$u['role'])) ?></span></td>
    <td class="actions"><?php if($u['id']!=Auth::id()): ?><form method="post" onsubmit="return confirm('Hapus pengguna ini?')"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-ghost btn-sm">Hapus</button></form><?php else: ?><span class="muted">Anda</span><?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
  </div>
</div>
<?php admin_layout_footer(); ?>
