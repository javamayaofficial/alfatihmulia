<?php
if (!defined('APP_NAME')) { exit; }
// Pendaftaran donatur umum (relawan punya halaman sendiri)
$errors = []; 
if (Auth::check()) { header('Location: '.url('portal')); exit; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??''); $pass=$_POST['password']??'';
    if($name==='') $errors[]='Nama wajib diisi.';
    if($email===''||!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Email tidak valid.';
    if(strlen($pass)<6) $errors[]='Password minimal 6 karakter.';
    if(!$errors && DB::one("SELECT id FROM users WHERE email=?",'s',[$email])) $errors[]='Email sudah terdaftar.';
    if(!$errors){
        $id=DB::insert("INSERT INTO users (name,email,phone,password_hash,role) VALUES (?,?,?,?,'donatur')",
            'ssss',[$name,$email,normalize_wa($phone),password_hash($pass,PASSWORD_DEFAULT)]);
        if($id){
            sync_mailketing_subscriber($email, $name, $phone);
            Auth::login(['id'=>$id,'name'=>$name,'role'=>'donatur','referral_code'=>null]);
            header('Location: '.url('portal')); exit;
        }
    }
}
layout_header('Daftar Donatur');
?>
<section class="section auth-section"><div class="container narrow">
  <div class="card form-card auth-card">
    <div class="auth-head"><span class="brand-mark big">﷽</span><h1>Daftar Akun Donatur</h1><p class="muted">Pantau riwayat & dampak donasi Anda.</p></div>
    <?php if($errors): ?><div class="alert alert-err"><?php foreach($errors as $er) echo '<div>• '.e($er).'</div>'; ?></div><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label>Nama Lengkap</label><input type="text" name="name" required>
      <label>Email</label><input type="email" name="email" required>
      <label>Nomor WhatsApp</label><input type="tel" name="phone" inputmode="numeric" placeholder="08xxxxxxxxxx">
      <label>Password</label><input type="password" name="password" required>
      <button class="btn btn-primary btn-lg btn-block">Daftar</button>
    </form>
    <p class="note">Sudah punya akun? <a href="<?= url('login') ?>">Masuk</a></p>
  </div>
</div></section>
<?php layout_footer(); ?>
