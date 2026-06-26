<?php
if (!defined('APP_NAME')) { exit; }

$programs = DB::all("SELECT id, title FROM programs WHERE status='active' ORDER BY title ASC");
$ref = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['ref'] ?? '');
$success = null; $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $anon   = isset($_POST['is_anonymous']) ? 1 : 0;
    $name   = trim($_POST['donor_name'] ?? '');
    $phone  = trim($_POST['donor_phone'] ?? '');
    $email  = trim($_POST['donor_email'] ?? '');
    $amount = (int) preg_replace('/[^0-9]/', '', $_POST['amount'] ?? '0');
    $cat    = preg_replace('/[^a-z_]/', '', strtolower($_POST['category'] ?? 'sedekah'));
    $prog   = (int)($_POST['program_id'] ?? 0) ?: null;
    $prayer = trim($_POST['prayer'] ?? '');
    $method = preg_replace('/[^a-z_]/', '', strtolower($_POST['payment_method'] ?? 'transfer'));
    $freq   = ($_POST['frequency'] ?? 'once') === 'monthly' ? 'monthly' : 'once';
    $ref    = preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['referral_code'] ?? '');

    if ($amount < 10000) $errors[] = 'Nominal donasi minimal Rp 10.000.';
    if (!$anon && $name === '') $errors[] = 'Nama wajib diisi (atau pilih donasi anonim).';
    if ($phone === '' || strlen(normalize_wa($phone)) < 10) $errors[] = 'Nomor WhatsApp belum valid.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

    if (!$errors) {
        $inv = make_invoice();
        DB::insert(
            "INSERT INTO donations (invoice,donor_name,donor_phone,donor_email,is_anonymous,amount,category,program_id,prayer,payment_method,frequency,referral_code,status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'pending')",
            'ssssiisissss', // invoice,name,phone,email,anon(i),amount(i),cat,program(i),prayer,method,freq,ref
            [$inv, $anon?null:$name, $phone, $email?:null, $anon, $amount, $cat, $prog, $prayer?:null, $method, $freq, $ref?:null]
        );
        $success = ['invoice'=>$inv, 'amount'=>$amount, 'method'=>$method, 'phone'=>$phone, 'name'=>$anon?'Hamba Allah':$name];
        sync_mailketing_subscriber($email, $anon ? '' : $name, $phone);
        // Notifikasi WA (gated): kirim otomatis bila Fonnte aktif
        send_wa($phone, "Assalamualaikum {$success['name']}, donasi Anda " . rupiah($amount) . " (Invoice {$inv}) telah kami terima dan sedang menunggu verifikasi. Jazakallahu khairan. — " . setting('yayasan_name','Yayasan Al Fatih'));
    }
}

layout_header('Donasi');
?>
<section class="section">
  <div class="container narrow">
    <?php if ($success): ?>
      <div class="success-box">
        <div class="success-ic">✓</div>
        <h2>Alhamdulillah, donasi Anda tercatat!</h2>
        <p>Invoice <b><?= e($success['invoice']) ?></b> • <?= rupiah($success['amount']) ?></p>
        <?php $wa = setting('yayasan_wa',''); 
          $msg = "Assalamualaikum, saya sudah berdonasi.%0AInvoice: {$success['invoice']}%0ANama: {$success['name']}%0ANominal: ".rupiah($success['amount'])."%0AMohon konfirmasi & info pembayaran.";
        ?>
        <p class="muted">Langkah terakhir: konfirmasikan pembayaran Anda agar segera diverifikasi.</p>
        <div class="hero-cta center">
          <?php if ($wa): ?><a class="btn btn-primary btn-lg" target="_blank" href="https://wa.me/<?= normalize_wa($wa) ?>?text=<?= $msg ?>">Konfirmasi via WhatsApp</a><?php endif; ?>
          <a class="btn btn-outline" href="<?= url('program') ?>">Kembali ke Program</a>
        </div>
        <?php if (!feature_active('midtrans_server_key') && !feature_active('xendit_key')): ?>
          <p class="note">ℹ️ Pembayaran otomatis (QRIS/VA) aktif setelah yayasan memasukkan kunci payment gateway. Saat ini gunakan transfer manual lalu konfirmasi via WhatsApp di atas.</p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="section-head"><h1>Salurkan Kebaikan Anda</h1><p class="muted">Aman, mudah, dan tercatat amanah.</p></div>
      <?php if ($errors): ?><div class="alert alert-err"><?php foreach($errors as $er) echo '<div>• '.e($er).'</div>'; ?></div><?php endif; ?>
      <form method="post" class="card form">
        <?= csrf_field() ?>
        <input type="hidden" name="referral_code" value="<?= e($ref) ?>">

        <label>Kategori Donasi</label>
        <div class="choice-row">
          <?php foreach (['sedekah'=>'Sedekah','infaq'=>'Infaq','zakat'=>'Zakat','wakaf'=>'Wakaf'] as $k=>$v): ?>
            <label class="choice"><input type="radio" name="category" value="<?= $k ?>" <?= $k==='sedekah'?'checked':'' ?>><span><?= $v ?></span></label>
          <?php endforeach; ?>
        </div>

        <label>Untuk Program (opsional)</label>
        <select name="program_id">
          <option value="">— Donasi Umum —</option>
          <?php foreach ($programs as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?>
        </select>

        <label>Nominal Donasi</label>
        <div class="amount-row">
          <?php foreach ([50000,100000,250000,500000] as $a): ?>
            <button type="button" class="amount-chip" data-amount="<?= $a ?>"><?= rupiah($a) ?></button>
          <?php endforeach; ?>
        </div>
        <input type="text" name="amount" id="amount" inputmode="numeric" placeholder="Nominal lain, mis. 75.000" required>

        <label class="check"><input type="checkbox" name="is_anonymous" id="anon"> Donasi sebagai Hamba Allah (anonim)</label>

        <div id="nameWrap">
          <label>Nama Donatur</label>
          <input type="text" name="donor_name" placeholder="Nama Anda">
        </div>

        <label>Nomor WhatsApp</label>
        <input type="tel" name="donor_phone" inputmode="numeric" placeholder="08xxxxxxxxxx" required>

        <label>Email (opsional)</label>
        <input type="email" name="donor_email" placeholder="email@contoh.com">

        <label>Doa / Pesan (opsional)</label>
        <textarea name="prayer" rows="2" placeholder="Semoga menjadi amal jariyah…"></textarea>

        <label>Metode Pembayaran</label>
        <div class="choice-row">
          <?php foreach (['transfer'=>'Transfer Bank','qris'=>'QRIS','va'=>'Virtual Account'] as $k=>$v): ?>
            <label class="choice"><input type="radio" name="payment_method" value="<?= $k ?>" <?= $k==='transfer'?'checked':'' ?>><span><?= $v ?></span></label>
          <?php endforeach; ?>
        </div>

        <label class="check"><input type="checkbox" name="frequency" value="monthly"> Jadikan donasi rutin bulanan</label>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Lanjutkan Donasi</button>
        <p class="note">🔒 Data Anda aman. Donasi diverifikasi oleh admin yayasan.</p>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php layout_footer(); ?>
