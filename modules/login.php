<?php
if (!defined('APP_NAME')) { exit; }
$errors = [];
if (Auth::check()) { header('Location: ' . (Auth::isAdmin() ? admin_url('dashboard') : url('portal'))); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (Auth::attempt($email, $pass)) {
        audit('login', 'User masuk: ' . $email);
        header('Location: ' . (Auth::isAdmin() ? admin_url('dashboard') : url('portal')));
        exit;
    }
    $errors[] = 'Email atau password salah.';
}
layout_header('Masuk');
?>
<section class="section auth-section"><div class="container narrow">
  <div class="card form-card auth-card">
    <div class="auth-head"><span class="brand-mark big">﷽</span><h1>Masuk ke Akun</h1><p class="muted">Portal Donatur & Relawan</p></div>
    <?php if ($errors): ?><div class="alert alert-err"><?php foreach($errors as $er) echo '<div>• '.e($er).'</div>'; ?></div><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label>Email</label><input type="email" name="email" required autofocus>
      <label>Password</label><input type="password" name="password" required>
      <button class="btn btn-primary btn-lg btn-block">Masuk</button>
    </form>
    <p class="note">Belum punya akun relawan? <a href="<?= url('relawan') ?>">Daftar di sini</a></p>
    <?php if (!feature_active('fonnte_token')): ?>
      <p class="note muted">ℹ️ Login via WhatsApp OTP akan aktif setelah yayasan mengaktifkan WhatsApp API.</p>
    <?php endif; ?>
  </div>
</div></section>
<?php layout_footer(); ?>
