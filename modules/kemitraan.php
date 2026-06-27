<?php
if (!defined('APP_NAME')) { exit; }
$wa = setting('yayasan_wa', '');
$partners = DB::all("SELECT * FROM partners ORDER BY id DESC");
$partnershipTypes = setting_lines('partnership_types', "Corporate Partnership\nMasjid Partnership\nKomunitas\nSekolah\nPesantren");
$intro = setting('partnership_intro', 'Yayasan membuka ruang sinergi dengan lembaga, komunitas, dan institusi yang ingin memperluas dampak kebaikan bersama.');
$typeDesc = setting('partnership_type_desc', 'Program kolaborasi dapat diarahkan untuk dukungan program, kampanye publik, penyaluran manfaat, atau agenda sosial bersama.');
$traceIntro = setting('partnership_trace_intro', 'Daftar mitra yang telah lebih dulu berjalan bersama yayasan.');
$formIntro = setting('partnership_form_intro', 'Untuk tahap awal, pengajuan kerja sama diarahkan melalui kontak resmi yayasan agar kebutuhan kolaborasi dapat dibahas lebih cepat.');
$formSteps = setting_lines('partnership_form_steps', "Siapkan profil singkat lembaga atau komunitas\nTuliskan bentuk kolaborasi yang diinginkan\nSertakan target program atau wilayah manfaat");
$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $organization = trim($_POST['organization_name'] ?? '');
    $contactName = trim($_POST['contact_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $type = trim($_POST['partnership_type'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($organization === '') $errors[] = 'Nama lembaga atau komunitas wajib diisi.';
    if ($contactName === '') $errors[] = 'Nama PIC wajib diisi.';
    if (strlen(normalize_wa($phone)) < 10) $errors[] = 'Nomor WhatsApp PIC belum valid.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email PIC tidak valid.';
    if ($type === '') $errors[] = 'Jenis kemitraan wajib dipilih.';
    if ($message === '') $errors[] = 'Rencana kerja sama singkat wajib diisi.';

    if (!$errors) {
        $normalizedPhone = normalize_wa($phone);
        $saved = DB::insert(
            "INSERT INTO partnership_leads (organization_name, contact_name, phone, email, partnership_type, message) VALUES (?,?,?,?,?,?)",
            'ssssss',
            [$organization, $contactName, $normalizedPhone, $email ?: null, $type, $message]
        );
        if ($saved) {
            audit('partnership_lead_created', $organization . ' - ' . $type);
            $notified = notify_admin_new_lead('partnership', [
                'organization_name' => $organization,
                'contact_name' => $contactName,
                'phone' => $normalizedPhone,
                'email' => $email,
                'partnership_type' => $type,
                'message' => $message,
            ]);
            audit('partnership_lead_notification', $notified ? 'Notifikasi admin terkirim' : 'Notifikasi admin tidak aktif / gagal');
            $done = true;
        } else {
            $errors[] = 'Pengajuan belum dapat diproses. Silakan coba lagi.';
        }
    }
}
layout_header('Kemitraan');
?>
<section class="page-head">
  <div class="container">
    <span class="pill pill-soft">Kemitraan</span>
    <h1>Mitra & Kolaborasi</h1>
    <p class="muted"><?= e($intro) ?></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Skema Kolaborasi</h2>
      <p class="muted">Pola kemitraan ini disusun agar calon mitra mudah memilih bentuk kerja sama yang paling relevan dengan kebutuhan yayasan dan umat.</p>
    </div>
    <div class="feature-grid">
      <?php foreach ($partnershipTypes as $type): ?>
      <div class="feature-card">
        <h3><?= e($type) ?></h3>
        <p class="muted"><?= e($typeDesc) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($partners): ?>
<section class="section section-soft">
  <div class="container">
    <div class="section-head">
      <h2>Jejak Mitra</h2>
      <p class="muted"><?= e($traceIntro) ?></p>
    </div>
    <div class="partner-grid">
      <?php foreach ($partners as $pt): ?>
      <div class="partner-card">
        <div class="partner-logo"><?= $pt['logo'] ? '<img src="'.e(asset('img/'.$pt['logo'])).'" alt="'.e($pt['name']).'">' : '<span>'.e($pt['name']).'</span>' ?></div>
        <b><?= e($pt['name']) ?></b>
        <span class="muted"><?= e($pt['category'] ?: 'Mitra') ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container narrow">
    <div class="card partnership-form">
      <h2>Form Pengajuan Kerjasama</h2>
      <?php if ($done): ?>
      <div class="success-box small">
        <div class="success-ic">✓</div>
        <h3>Pengajuan Kerja Sama Terkirim</h3>
        <p>Terima kasih. Tim yayasan akan meninjau dan menghubungi PIC melalui WhatsApp atau email yang Anda cantumkan.</p>
        <a class="btn btn-primary btn-block" href="<?= url('kemitraan') ?>">Kirim Pengajuan Lain</a>
      </div>
      <?php else: ?>
      <p class="muted"><?= e($formIntro) ?></p>
      <ul class="mini-list compact">
        <?php foreach ($formSteps as $step): ?>
        <li><?= e($step) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($errors): ?><div class="alert alert-err"><?php foreach ($errors as $error) echo '<div>• ' . e($error) . '</div>'; ?></div><?php endif; ?>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <label>Nama Lembaga / Komunitas</label><input type="text" name="organization_name" value="<?= e($_POST['organization_name'] ?? '') ?>" required>
        <label>Nama PIC</label><input type="text" name="contact_name" value="<?= e($_POST['contact_name'] ?? '') ?>" required>
        <div class="grid-2">
          <div><label>WhatsApp PIC</label><input type="tel" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" inputmode="numeric" placeholder="08xxxxxxxxxx" required></div>
          <div><label>Email PIC (opsional)</label><input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="email@contoh.com"></div>
        </div>
        <label>Jenis Kemitraan</label>
        <select name="partnership_type" required>
          <option value="">Pilih jenis kemitraan</option>
          <?php foreach ($partnershipTypes as $type): ?>
          <option value="<?= e($type) ?>" <?= (($_POST['partnership_type'] ?? '') === $type) ? 'selected' : '' ?>><?= e($type) ?></option>
          <?php endforeach; ?>
        </select>
        <label>Rencana Kerja Sama Singkat</label><textarea name="message" rows="4" placeholder="Jelaskan bentuk kolaborasi, target program, atau kebutuhan yang ingin dibahas" required><?= e($_POST['message'] ?? '') ?></textarea>
        <button class="btn btn-primary btn-block">Kirim Pengajuan</button>
        <div class="hero-cta">
          <?php if ($wa): ?><a class="btn btn-ghost" target="_blank" href="<?= e(wa_link($wa, 'Assalamualaikum, kami ingin follow up pengajuan kerja sama dengan Yayasan Al Fatih Mulia Haramain.')) ?>">Hubungi via WhatsApp</a><?php endif; ?>
          <a class="btn btn-outline" href="<?= url('kontak') ?>">Lihat Kontak Resmi</a>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php layout_footer(); ?>
