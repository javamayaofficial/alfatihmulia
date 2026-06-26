<?php
if (!defined('APP_NAME')) { exit; }
$errors = []; $done = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($name === '') $errors[] = 'Nama wajib diisi.';
    if (strlen(normalize_wa($phone)) < 10) $errors[] = 'Nomor WhatsApp belum valid.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if (strlen($pass) < 6) $errors[] = 'Password minimal 6 karakter.';
    if (!$errors && DB::one("SELECT id FROM users WHERE email=?", 's', [$email])) $errors[] = 'Email sudah terdaftar. Silakan masuk.';

    if (!$errors) {
        $code = make_referral_code($name);
        $id = DB::insert("INSERT INTO users (name,email,phone,password_hash,role,referral_code) VALUES (?,?,?,?,'relawan',?)",
            'sssss', [$name, $email, normalize_wa($phone), password_hash($pass, PASSWORD_DEFAULT), $code]);
        if ($id) {
            sync_mailketing_subscriber($email, $name, $phone);
            Auth::login(['id'=>$id,'name'=>$name,'role'=>'relawan','referral_code'=>$code]);
            $done = $code;
        }
    }
}

layout_header('Jadi Relawan');
?>
<section class="page-head gold-head"><div class="container">
  <span class="pill pill-gold">Duta Air Kehidupan Indonesia</span>
  <h1>Jadi Relawan, Raih The Legacy Umrah</h1>
  <p class="muted">Ajak kebaikan lewat link referral pribadimu. Naik di papan peringkat nasional, dan melangkah menuju reward Umrah dari yayasan.</p>
</div></section>

<section class="section"><div class="container two-col">
  <div class="benefits">
    <h3>Kenapa Jadi Relawan?</h3>
    <div class="benefit"><span>🔗</span><div><b>Link Referral Pribadi</b><p class="muted">Setiap donasi lewat linkmu tercatat otomatis ke akunmu.</p></div></div>
    <div class="benefit"><span>🏆</span><div><b>Leaderboard Nasional</b><p class="muted">Bersaing sehat dalam kebaikan bersama relawan se-Indonesia.</p></div></div>
    <div class="benefit"><span>🕋</span><div><b>Progress The Legacy Umrah</b><p class="muted">Kumpulkan kebaikan, capai target, raih reward Umrah.</p></div></div>
    <div class="benefit"><span>📜</span><div><b>Sertifikat Relawan</b><p class="muted">Pengakuan resmi atas kontribusimu.</p></div></div>
    <a class="btn btn-ghost" href="<?= url('leaderboard') ?>">Lihat Leaderboard Saat Ini →</a>
  </div>
  <div class="card form-card">
    <?php if ($done): ?>
      <div class="success-box small">
        <div class="success-ic">✓</div>
        <h2>Selamat datang, Duta Kebaikan!</h2>
        <p>Kode referral pribadimu:</p>
        <div class="ref-code"><?= e($done) ?></div>
        <a class="btn btn-primary btn-block" href="<?= url('portal') ?>">Buka Dashboard Relawan</a>
      </div>
    <?php else: ?>
      <h3>Daftar Relawan</h3>
      <?php if ($errors): ?><div class="alert alert-err"><?php foreach($errors as $er) echo '<div>• '.e($er).'</div>'; ?></div><?php endif; ?>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <label>Nama Lengkap</label><input type="text" name="name" required>
        <label>Nomor WhatsApp</label><input type="tel" name="phone" inputmode="numeric" placeholder="08xxxxxxxxxx" required>
        <label>Email</label><input type="email" name="email" required>
        <label>Password</label><input type="password" name="password" placeholder="Minimal 6 karakter" required>
        <button class="btn btn-primary btn-lg btn-block">Daftar & Mulai Berbagi</button>
        <p class="note">Sudah punya akun? <a href="<?= url('login') ?>">Masuk di sini</a></p>
      </form>
    <?php endif; ?>
  </div>
</div></section>
<?php layout_footer(); ?>
