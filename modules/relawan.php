<?php
if (!defined('APP_NAME')) { exit; }
$errors = [];
$done = false;
$divisions = [
    'Duta Air Kehidupan',
    'Duta Jejak Baitullah',
    'Duta Cahaya Ilmu',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $profession = trim($_POST['profession'] ?? '');
    $division = trim($_POST['division'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($name === '') $errors[] = 'Nama wajib diisi.';
    if ($city === '') $errors[] = 'Kota wajib diisi.';
    if (strlen(normalize_wa($phone)) < 10) $errors[] = 'Nomor WhatsApp belum valid.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email belum valid.';
    if ($profession === '') $errors[] = 'Profesi wajib diisi.';
    if (!in_array($division, $divisions, true)) $errors[] = 'Divisi relawan belum dipilih.';

    if (!$errors) {
        $normalizedPhone = normalize_wa($phone);
        $leadId = DB::insert(
            "INSERT INTO volunteer_leads (name, city, phone, email, profession, division, note) VALUES (?,?,?,?,?,?,?)",
            'sssssss',
            [$name, $city, $normalizedPhone, $email ?: null, $profession, $division, $note]
        );
        if ($leadId) {
            audit('volunteer_lead_created', $name . ' - ' . $division . ' - ' . $city);
            if ($email !== '') {
                $synced = sync_mailketing_subscriber($email, $name, $normalizedPhone);
                audit('volunteer_lead_mailketing_sync', $synced ? ('Lead #' . $leadId . ' subscriber tersinkron') : ('Lead #' . $leadId . ' subscriber tidak tersinkron'));
            }
            $notified = notify_admin_new_lead('volunteer', [
                'name' => $name,
                'city' => $city,
                'phone' => $normalizedPhone,
                'email' => $email,
                'profession' => $profession,
                'division' => $division,
                'note' => $note,
            ]);
            audit('volunteer_lead_notification', $notified ? 'Notifikasi admin terkirim' : 'Notifikasi admin tidak aktif / gagal');
            $done = true;
        } else {
            $errors[] = 'Pendaftaran belum dapat diproses. Silakan coba lagi.';
        }
    }
}

layout_header('Relawan');
?>
<section class="page-head gold-head"><div class="container">
  <span class="pill pill-gold">Relawan</span>
  <h1>Open Recruitment Relawan</h1>
  <p class="muted">Bergabunglah sebagai relawan awal yang membantu yayasan membangun gerakan kebaikan secara bertahap dan terarah.</p>
</div></section>

<section class="section"><div class="container two-col">
  <div class="benefits">
    <h3>Mengapa Bergabung Menjadi Relawan?</h3>
    <div class="benefit"><span>🌍</span><div><b>Jangkauan Bertahap</b><p class="muted">Ambil bagian dalam gerakan sosial yang sedang tumbuh dan diarahkan agar manfaatnya makin luas dari waktu ke waktu.</p></div></div>
    <div class="benefit"><span>🤝</span><div><b>Kolaborasi Tim</b><p class="muted">Bergerak bersama tim yayasan, donatur, dan mitra untuk eksekusi program yang lebih kuat.</p></div></div>
    <div class="benefit"><span>📚</span><div><b>Penguatan Kapasitas</b><p class="muted">Relawan mendapatkan ruang belajar, keterlibatan program, dan pengalaman kontribusi yang terarah.</p></div></div>
    <div class="benefit"><span>🕌</span><div><b>Amal Jariyah Berkelanjutan</b><p class="muted">Setiap aksi relawan menjadi bagian dari jejak kebaikan yang memperluas manfaat program yayasan.</p></div></div>
    <a class="btn btn-ghost" href="<?= url('leaderboard') ?>">Lihat Leaderboard Relawan</a>
  </div>
  <div class="card form-card">
    <?php if ($done): ?>
      <div class="success-box small">
        <div class="success-ic">✓</div>
        <h2>Pendaftaran Relawan Terkirim</h2>
        <p>Terima kasih telah mendaftar sebagai relawan. Tim yayasan akan menghubungi Anda untuk tahap selanjutnya.</p>
        <a class="btn btn-primary btn-block" href="<?= url('home') ?>">Kembali ke Beranda</a>
      </div>
    <?php else: ?>
      <h3>Form Pendaftaran Relawan</h3>
      <?php if ($errors): ?><div class="alert alert-err"><?php foreach($errors as $er) echo '<div>• '.e($er).'</div>'; ?></div><?php endif; ?>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <label>Nama Lengkap</label><input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required>
        <label>Kota</label><input type="text" name="city" value="<?= e($_POST['city'] ?? '') ?>" placeholder="Contoh: Jakarta" required>
        <label>Nomor WhatsApp</label><input type="tel" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" inputmode="numeric" placeholder="08xxxxxxxxxx" required>
        <label>Email (opsional)</label><input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="contoh@domain.com">
        <label>Profesi</label><input type="text" name="profession" value="<?= e($_POST['profession'] ?? '') ?>" placeholder="Contoh: Guru, Mahasiswa, Karyawan" required>
        <label>Divisi</label>
        <div class="choice-row">
          <?php foreach ($divisions as $division): ?>
          <label class="choice"><input type="radio" name="division" value="<?= e($division) ?>" <?= (($_POST['division'] ?? '') === $division) ? 'checked' : '' ?>><span><?= e($division) ?></span></label>
          <?php endforeach; ?>
        </div>
        <label>Catatan Singkat (opsional)</label><textarea name="note" rows="3" placeholder="Ceritakan pengalaman, minat, atau ketersediaan Anda"><?= e($_POST['note'] ?? '') ?></textarea>
        <button class="btn btn-primary btn-lg btn-block">Kirim Pendaftaran</button>
        <p class="note">Tim kami akan meninjau data Anda dan menghubungi melalui WhatsApp atau email jika diperlukan.</p>
      </form>
    <?php endif; ?>
  </div>
</div></section>
<?php layout_footer(); ?>
