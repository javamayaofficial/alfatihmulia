<?php
if (!defined('APP_NAME')) { exit; }

$programs = DB::all("SELECT id, title FROM programs WHERE status='active' ORDER BY title ASC");
$ref = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['ref'] ?? '');
$selectedCategory = preg_replace('/[^a-z_]/', '', strtolower($_GET['category'] ?? 'sedekah_air_kehidupan'));
$success = null;
$errors = [];
$returnInfo = null;
$categoryOptions = [
    'sedekah_air_kehidupan' => 'Sedekah Air Kehidupan',
    'beasiswa_anak_yatim' => 'Beasiswa Anak Yatim',
    'wakaf_al_quran' => 'Wakaf Al-Qur\'an',
    'syiar_baitullah' => 'Syiar Baitullah',
    'donasi_operasional_dakwah' => 'Donasi Operasional Dakwah',
];
$duitkuActive = duitku_enabled();
$paymentMethods = ['transfer' => 'Transfer Manual'];
if ($duitkuActive) {
    $paymentMethods = ['duitku' => 'Duitku Checkout', 'transfer' => 'Transfer Manual'];
}

$manualAccounts = [];
$primaryBankName = trim((string) setting('payment_bank_primary_name', 'BSI'));
$primaryBankNumber = trim((string) setting('payment_bank_primary_number', ''));
$primaryBankHolder = trim((string) setting('payment_bank_primary_holder', setting('yayasan_name', 'Yayasan Al Fatih')));
if ($primaryBankNumber !== '') {
    $manualAccounts[] = [
        'bank_name' => $primaryBankName !== '' ? $primaryBankName : 'BSI',
        'account_number' => $primaryBankNumber,
        'account_holder' => $primaryBankHolder,
    ];
}
foreach (DB::all("SELECT * FROM bank_accounts WHERE status='published' ORDER BY sort_order ASC, id ASC") as $bankRow) {
    $manualAccounts[] = [
        'bank_name' => $bankRow['bank_name'],
        'account_number' => $bankRow['account_number'],
        'account_holder' => $bankRow['account_holder'],
    ];
}
$qrisFile = trim((string) setting('payment_qris_file', ''));

if (($_GET['duitku_return'] ?? '') === '1') {
    $returnInvoice = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['invoice'] ?? '');
    $returnDonation = $returnInvoice !== '' ? DB::one("SELECT * FROM donations WHERE invoice=?", 's', [$returnInvoice]) : null;
    if ($returnDonation && ($returnDonation['payment_method'] ?? '') === 'duitku') {
        if (($returnDonation['status'] ?? '') === 'pending') {
            $statusResult = duitku_transaction_status($returnInvoice);
            if (($statusResult['ok'] ?? false) && ($statusResult['statusCode'] ?? '') === '00') {
                verify_donation_payment($returnDonation, 'duitku_return');
            } elseif (($statusResult['ok'] ?? false) && !in_array(($statusResult['statusCode'] ?? ''), ['00', '01'], true)) {
                reject_donation_payment($returnDonation, 'duitku_return');
            }
            $returnDonation = DB::one("SELECT * FROM donations WHERE invoice=?", 's', [$returnInvoice]);
        }

        $returnInfo = [
            'invoice' => $returnInvoice,
            'status' => $returnDonation['status'] ?? 'pending',
            'amount' => (int) ($returnDonation['amount'] ?? 0),
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $anon   = isset($_POST['is_anonymous']) ? 1 : 0;
    $name   = trim($_POST['donor_name'] ?? '');
    $phone  = trim($_POST['donor_phone'] ?? '');
    $email  = trim($_POST['donor_email'] ?? '');
    $amount = (int) preg_replace('/[^0-9]/', '', $_POST['amount'] ?? '0');
    $cat    = preg_replace('/[^a-z_]/', '', strtolower($_POST['category'] ?? $selectedCategory));
    $prog   = (int)($_POST['program_id'] ?? 0) ?: null;
    $prayer = trim($_POST['prayer'] ?? '');
    $method = preg_replace('/[^a-z_]/', '', strtolower($_POST['payment_method'] ?? 'transfer'));
    $freq   = ($_POST['frequency'] ?? 'once') === 'monthly' ? 'monthly' : 'once';
    $ref    = preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['referral_code'] ?? '');
    $normalizedPhone = normalize_wa($phone);

    if ($amount < 10000) $errors[] = 'Nominal donasi minimal Rp 10.000.';
    if (!$anon && $name === '') $errors[] = 'Nama wajib diisi (atau pilih donasi anonim).';
    if ($phone === '' || strlen($normalizedPhone) < 10) $errors[] = 'Nomor WhatsApp belum valid.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!isset($paymentMethods[$method])) $errors[] = 'Metode pembayaran tidak tersedia.';
    if ($method === 'duitku' && !$duitkuActive) $errors[] = 'Duitku belum aktif. Silakan pilih transfer manual.';
    if ($method === 'duitku' && $email === '') $errors[] = 'Email wajib diisi untuk pembayaran via Duitku.';
    if ($method === 'transfer' && !$manualAccounts && $qrisFile === '') $errors[] = 'Transfer manual belum siap karena rekening dan QRIS belum diatur admin.';

    if (!$errors) {
        $inv = make_invoice();
        $displayName = $anon ? 'Hamba Allah' : $name;

        if ($method === 'duitku') {
            $gateway = duitku_create_invoice([
                'paymentAmount' => $amount,
                'merchantOrderId' => $inv,
                'productDetails' => 'Donasi ' . human_label($cat) . ' - ' . setting('yayasan_short', setting('yayasan_name', 'Yayasan Al Fatih')),
                'additionalParam' => $cat,
                'merchantUserInfo' => $email,
                'customerVaName' => $displayName,
                'email' => $email,
                'phoneNumber' => $normalizedPhone,
                'itemDetails' => [[
                    'name' => 'Donasi ' . human_label($cat),
                    'price' => $amount,
                    'quantity' => 1,
                ]],
                'customerDetail' => [
                    'firstName' => $displayName,
                    'lastName' => '',
                    'email' => $email,
                    'phoneNumber' => $normalizedPhone,
                ],
                'callbackUrl' => BASE_URL . '/api/webhook-duitku.php',
                'returnUrl' => url('donasi', ['duitku_return' => '1', 'invoice' => $inv]),
                'expiryPeriod' => (int) setting('duitku_expiry_period', '60'),
            ]);
            if (!$gateway['ok']) {
                $errors[] = $gateway['message'];
            } else {
                $created = DB::insert(
                    "INSERT INTO donations (invoice,donor_name,donor_phone,donor_email,is_anonymous,amount,category,program_id,prayer,payment_method,frequency,referral_code,status)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'pending')",
                    'ssssiisissss',
                    [$inv, $anon ? null : $name, $normalizedPhone, $email ?: null, $anon, $amount, $cat, $prog, $prayer ?: null, $method, $freq, $ref ?: null]
                );
                if ($created) {
                    sync_mailketing_subscriber($email, $anon ? '' : $name, $normalizedPhone);
                    send_wa($normalizedPhone, "Assalamualaikum {$displayName}, invoice donasi Anda " . rupiah($amount) . " ({$inv}) berhasil dibuat. Silakan lanjutkan pembayaran melalui halaman Duitku. — " . setting('yayasan_name', 'Yayasan Al Fatih'));
                    header('Location: ' . $gateway['paymentUrl']);
                    exit;
                }
                $errors[] = 'Invoice Duitku berhasil dibuat, tetapi data donasi belum dapat disimpan. Silakan coba lagi.';
            }
        } else {
            DB::insert(
                "INSERT INTO donations (invoice,donor_name,donor_phone,donor_email,is_anonymous,amount,category,program_id,prayer,payment_method,frequency,referral_code,status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'pending')",
                'ssssiisissss',
                [$inv, $anon ? null : $name, $normalizedPhone, $email ?: null, $anon, $amount, $cat, $prog, $prayer ?: null, $method, $freq, $ref ?: null]
            );
            $success = ['invoice' => $inv, 'amount' => $amount, 'method' => $method, 'phone' => $normalizedPhone, 'name' => $displayName];
            sync_mailketing_subscriber($email, $anon ? '' : $name, $normalizedPhone);
            send_wa($normalizedPhone, "Assalamualaikum {$success['name']}, donasi Anda " . rupiah($amount) . " (Invoice {$inv}) telah kami terima dan sedang menunggu verifikasi. Jazakallahu khairan. — " . setting('yayasan_name', 'Yayasan Al Fatih'));
        }
    }
}

layout_header('Donasi');
?>
<section class="section">
  <div class="container narrow">
    <?php if ($returnInfo): ?>
      <div class="alert alert-<?= $returnInfo['status'] === 'verified' ? 'ok' : ($returnInfo['status'] === 'rejected' ? 'err' : 'info') ?>">
        <?php if ($returnInfo['status'] === 'verified'): ?>
          Pembayaran Duitku untuk invoice <b><?= e($returnInfo['invoice']) ?></b> berhasil diverifikasi sebesar <b><?= rupiah($returnInfo['amount']) ?></b>.
        <?php elseif ($returnInfo['status'] === 'rejected'): ?>
          Pembayaran Duitku untuk invoice <b><?= e($returnInfo['invoice']) ?></b> tidak berhasil / kedaluwarsa. Silakan buat donasi baru atau hubungi admin.
        <?php else: ?>
          Invoice <b><?= e($returnInfo['invoice']) ?></b> masih menunggu penyelesaian pembayaran / callback dari Duitku.
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success-box">
        <div class="success-ic">✓</div>
        <h2>Alhamdulillah, donasi Anda tercatat!</h2>
        <p>Invoice <b><?= e($success['invoice']) ?></b> • <?= rupiah($success['amount']) ?></p>
        <?php $wa = setting('yayasan_wa',''); 
          $msg = "Assalamualaikum, saya sudah berdonasi.%0AInvoice: {$success['invoice']}%0ANama: {$success['name']}%0ANominal: ".rupiah($success['amount'])."%0AMohon konfirmasi & info pembayaran.";
        ?>
        <p class="muted">Langkah berikutnya: lakukan transfer ke rekening resmi atau scan QRIS, lalu konfirmasikan pembayaran Anda agar segera diverifikasi.</p>
        <?php if ($manualAccounts || $qrisFile): ?>
        <div class="card donation-note" style="text-align:left">
          <h3>Metode Transfer Manual</h3>
          <?php if ($manualAccounts): ?>
          <div class="doc-list">
            <?php foreach ($manualAccounts as $account): ?>
            <div class="doc-item">
              <div>
                <b><?= e($account['bank_name']) ?></b><br>
                <span class="muted"><?= e($account['account_number']) ?><?= !empty($account['account_holder']) ? ' a.n. ' . e($account['account_holder']) : '' ?></span>
              </div>
              <em>Transfer Manual</em>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <?php if ($qrisFile): ?>
          <div class="upload-preview" style="margin-top:16px">
            <img src="<?= e($qrisFile) ?>" alt="QRIS Pembayaran Yayasan">
            <div class="upload-meta">
              <b>QRIS Pembayaran</b>
              <span class="muted">Scan QRIS resmi yayasan untuk pembayaran cepat.</span>
              <a class="inline-link" href="<?= e($qrisFile) ?>" target="_blank" rel="noopener">Buka QRIS</a>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="hero-cta center">
          <?php if ($wa): ?><a class="btn btn-primary btn-lg" target="_blank" href="https://wa.me/<?= normalize_wa($wa) ?>?text=<?= $msg ?>">Konfirmasi via WhatsApp</a><?php endif; ?>
          <a class="btn btn-outline" href="<?= url('program') ?>">Kembali ke Program</a>
        </div>
      </div>
    <?php else: ?>
      <div class="section-head">
        <span class="pill pill-soft">Donasi</span>
        <h1>Donasi Sekarang</h1>
        <p class="muted">Pilih kategori donasi, tentukan nominal, dan salurkan amanah Anda melalui metode pembayaran yang paling nyaman.</p>
      </div>
      <div class="method-grid">
        <?php foreach ($paymentMethods as $methodKey => $label): ?>
        <div class="method-card">
          <b><?= e($label) ?></b>
          <span class="muted"><?= $methodKey === 'duitku' ? 'Checkout otomatis melalui Duitku untuk QRIS, virtual account, dan kanal pembayaran digital yang tersedia.' : 'Transfer ke rekening resmi yayasan atau scan QRIS manual, lalu konfirmasi ke admin.' ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if ($errors): ?><div class="alert alert-err"><?php foreach($errors as $er) echo '<div>• '.e($er).'</div>'; ?></div><?php endif; ?>
      <?php if (!$duitkuActive): ?><div class="alert alert-info">Duitku belum aktif. Saat ini donatur tetap dapat berdonasi melalui transfer manual dan QRIS resmi yayasan.</div><?php endif; ?>
      <form method="post" class="card form">
        <?= csrf_field() ?>
        <input type="hidden" name="referral_code" value="<?= e($ref) ?>">

        <label>Kategori Donasi</label>
        <div class="choice-row">
          <?php foreach ($categoryOptions as $k => $v): ?>
            <label class="choice"><input type="radio" name="category" value="<?= $k ?>" <?= $k === $selectedCategory ? 'checked' : '' ?>><span><?= $v ?></span></label>
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
          <?php foreach ($paymentMethods as $k => $v): ?>
            <label class="choice"><input type="radio" name="payment_method" value="<?= $k ?>" <?= (($_POST['payment_method'] ?? ($duitkuActive ? 'duitku' : 'transfer')) === $k) ? 'checked' : '' ?>><span><?= $v ?></span></label>
          <?php endforeach; ?>
        </div>

        <label class="check"><input type="checkbox" name="frequency" value="monthly"> Jadikan donasi rutin bulanan</label>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Lanjutkan Donasi</button>
        <p class="note">🔒 Data Anda aman. Pembayaran otomatis ditangani oleh Duitku, sementara transfer manual tetap diverifikasi admin yayasan.</p>
      </form>
      <div class="card donation-note">
        <h3>Laporan Penyaluran Dana</h3>
        <p class="muted">Setelah berdonasi, Anda dapat memantau gambaran transparansi penyaluran melalui halaman laporan dan dokumentasi kegiatan yayasan.</p>
        <div class="hero-cta">
          <a class="btn btn-outline" href="<?= url('laporan') ?>">Lihat Laporan</a>
          <a class="btn btn-ghost" href="<?= url('dokumentasi') ?>">Lihat Dokumentasi</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php layout_footer(); ?>
