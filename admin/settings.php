<?php
if (!defined('APP_NAME')) { exit; }
Auth::requireAdmin('superadmin');

$legalDocKeys = [
    'legal_akta_file' => 'Dokumen Akta',
    'legal_sk_file' => 'Dokumen SK',
    'legal_npwp_file' => 'Dokumen NPWP',
];
$reportDocKeys = [
    'laporan_doc_bulanan' => 'Laporan Bulanan',
    'laporan_doc_tahunan' => 'Laporan Tahunan',
    'laporan_doc_penyaluran_program' => 'Laporan Penyaluran Program',
    'laporan_doc_audit_keuangan' => 'Audit Keuangan',
];
$allDocKeys = $legalDocKeys + $reportDocKeys;
$bulkFormId = 'settingsBulkForm';
$flashSuccess = function ($message) {
    flash_set($message, 'ok', ['sound' => 'save-success']);
};

$saveApiSettings = function () {
    $apiKeys = [
        'duitku_merchant_code',
        'duitku_api_key',
        'mailketing_api_token',
        'mailketing_list_id',
        'fonnte_token',
        'fonnte_sender',
        'google_maps_key',
    ];
    foreach ($apiKeys as $k) {
        if (isset($_POST[$k])) set_setting($k, trim($_POST[$k]));
    }
    set_setting('duitku_is_production', isset($_POST['duitku_is_production']) ? '1' : '0');
    set_setting('mailketing_auto_sync', isset($_POST['mailketing_auto_sync']) ? '1' : '0');
};

$saveProfileSettings = function () {
    $legalUploads = [
        'legal_akta_file' => 'legal-akta',
        'legal_sk_file' => 'legal-sk',
        'legal_npwp_file' => 'legal-npwp',
    ];
    foreach (['yayasan_name','yayasan_short','yayasan_wa','yayasan_email','yayasan_alamat',
              'yayasan_profil','yayasan_visi','yayasan_misi','legal_akta','legal_sk','legal_npwp',
              'legal_rekening','legal_struktur','google_maps_link',
              'social_instagram','social_facebook','social_youtube','umrah_target'] as $k) {
        if (isset($_POST[$k])) set_setting($k, trim($_POST[$k]));
    }
    foreach ($legalUploads as $field => $prefix) {
        $upload = upload_public_file($field, $prefix);
        if (!$upload['ok']) {
            return $upload;
        }
        if (!empty($upload['url'])) {
            $oldUrl = setting($field, '');
            if ($oldUrl !== '' && $oldUrl !== $upload['url']) {
                delete_public_upload_by_url($oldUrl);
            }
            set_setting($field, $upload['url']);
        }
    }
    $logoUpload = upload_asset_image('yayasan_logo_file', 'yayasan-logo', ['webp']);
    if (!$logoUpload['ok']) {
        return $logoUpload;
    }
    if (!empty($logoUpload['file'])) {
        $oldLogo = trim((string) setting('yayasan_logo', ''));
        set_setting('yayasan_logo', $logoUpload['file']);
        if ($oldLogo !== '' && $oldLogo !== $logoUpload['file']) {
            delete_asset_image_if_unused($oldLogo);
        }
    }
    return ['ok' => true];
};

$saveImpactSettings = function () {
    $keys = $_POST['ikey'] ?? [];
    $labels = $_POST['ilabel'] ?? [];
    $vals = $_POST['ival'] ?? [];
    $icons = $_POST['iicon'] ?? [];
    foreach ($keys as $i => $kk) {
        $kk = preg_replace('/[^a-z0-9_]/', '', strtolower($kk));
        if ($kk === '') continue;
        DB::run(
            "INSERT INTO impact_stats (skey,label,svalue,icon,sort) VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE label=VALUES(label),svalue=VALUES(svalue),icon=VALUES(icon)",
            'ssisi',
            [$kk, trim($labels[$i] ?? $kk), (int) ($vals[$i] ?? 0), trim($icons[$i] ?? 'star'), $i]
        );
    }
    return ['ok' => true];
};

$saveReportSettings = function () {
    foreach (['laporan_doc_bulanan','laporan_doc_tahunan','laporan_doc_penyaluran_program','laporan_doc_audit_keuangan'] as $k) {
        if (isset($_POST[$k])) set_setting($k, trim($_POST[$k]));
    }
    $reportUploads = [
        'laporan_doc_bulanan_file' => 'laporan-bulanan',
        'laporan_doc_tahunan_file' => 'laporan-tahunan',
        'laporan_doc_penyaluran_program_file' => 'laporan-penyaluran',
        'laporan_doc_audit_keuangan_file' => 'laporan-audit',
    ];
    $mapping = [
        'laporan_doc_bulanan_file' => 'laporan_doc_bulanan',
        'laporan_doc_tahunan_file' => 'laporan_doc_tahunan',
        'laporan_doc_penyaluran_program_file' => 'laporan_doc_penyaluran_program',
        'laporan_doc_audit_keuangan_file' => 'laporan_doc_audit_keuangan',
    ];
    foreach ($reportUploads as $field => $prefix) {
        $upload = upload_public_file($field, $prefix);
        if (!$upload['ok']) {
            return $upload;
        }
        if (!empty($upload['url'])) {
            $oldUrl = setting($mapping[$field], '');
            if ($oldUrl !== '' && $oldUrl !== $upload['url']) {
                delete_public_upload_by_url($oldUrl);
            }
            set_setting($mapping[$field], $upload['url']);
        }
    }
    set_setting('laporan_show_donors', isset($_POST['laporan_show_donors']) ? '1' : '0');
    return ['ok' => true];
};

$savePaymentSettings = function () {
    foreach (['payment_bank_primary_name', 'payment_bank_primary_number', 'payment_bank_primary_holder'] as $k) {
        if (isset($_POST[$k])) set_setting($k, trim($_POST[$k]));
    }
    $upload = upload_public_file('payment_qris_upload', 'payment-qris', ['webp']);
    if (!$upload['ok']) {
        return $upload;
    }
    if (!empty($upload['url'])) {
        $oldUrl = setting('payment_qris_file', '');
        if ($oldUrl !== '' && $oldUrl !== $upload['url']) {
            delete_public_upload_by_url($oldUrl);
        }
        set_setting('payment_qris_file', $upload['url']);
    }
    return ['ok' => true];
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    if ($act === 'settings_bulk') {
        $provider = trim((string) ($_POST['test_provider'] ?? ''));
        if ($provider !== '') {
            $saveApiSettings();
            if ($provider === 'duitku') {
                $test = test_duitku_connection($_POST['duitku_merchant_code'] ?? '', $_POST['duitku_api_key'] ?? '', isset($_POST['duitku_is_production']));
                audit('test_api_duitku', $test['message']);
                flash_set($test['message'], $test['ok'] ? 'ok' : 'err');
            } elseif ($provider === 'mailketing') {
                $test = test_mailketing_connection($_POST['mailketing_api_token'] ?? '', $_POST['mailketing_list_id'] ?? '');
                audit('test_api_mailketing', $test['message']);
                flash_set($test['message'], $test['ok'] ? 'ok' : 'err');
            } elseif ($provider === 'fonnte') {
                $test = test_fonnte_connection($_POST['fonnte_token'] ?? '', setting('yayasan_wa', ''));
                audit('test_api_fonnte', $test['message']);
                flash_set($test['message'], $test['ok'] ? 'ok' : 'err');
            }
        } else {
            foreach ([$saveProfileSettings, $savePaymentSettings, $saveImpactSettings, $saveReportSettings] as $saveSection) {
                $result = $saveSection();
                if (!($result['ok'] ?? false)) {
                    flash_set($result['message'] ?? 'Perubahan gagal disimpan.', 'err');
                    header('Location: '.admin_url('settings')); exit;
                }
            }
            $saveApiSettings();
            audit('update_settings_bulk', 'Semua pengaturan utama diperbarui');
            $flashSuccess('Semua pengaturan berhasil disimpan.');
        }
    } elseif ($act === 'profil') {
        $result = $saveProfileSettings();
        if (!($result['ok'] ?? false)) {
            flash_set($result['message'] ?? 'Perubahan gagal disimpan.', 'err');
            header('Location: '.admin_url('settings')); exit;
        }
        audit('update_profil','Profil yayasan diperbarui');
        $flashSuccess('Profil yayasan disimpan.');
    } elseif ($act === 'api') {
        $saveApiSettings();
        $provider = $_POST['test_provider'] ?? '';
        if ($provider === 'duitku') {
            $test = test_duitku_connection($_POST['duitku_merchant_code'] ?? '', $_POST['duitku_api_key'] ?? '', isset($_POST['duitku_is_production']));
            audit('test_api_duitku', $test['message']);
            flash_set($test['message'], $test['ok'] ? 'ok' : 'err');
        } elseif ($provider === 'mailketing') {
            $test = test_mailketing_connection($_POST['mailketing_api_token'] ?? '', $_POST['mailketing_list_id'] ?? '');
            audit('test_api_mailketing', $test['message']);
            flash_set($test['message'], $test['ok'] ? 'ok' : 'err');
        } elseif ($provider === 'fonnte') {
            $test = test_fonnte_connection($_POST['fonnte_token'] ?? '', setting('yayasan_wa', ''));
            audit('test_api_fonnte', $test['message']);
            flash_set($test['message'], $test['ok'] ? 'ok' : 'err');
        } else {
            audit('update_api','Kunci API diperbarui');
            $flashSuccess('Kunci API disimpan. Fitur terkait kini aktif.');
        }
    } elseif ($act === 'impact') {
        $saveImpactSettings();
        audit('update_impact','Statistik dampak diperbarui');
        $flashSuccess('Statistik dampak disimpan.');
    } elseif ($act === 'laporan') {
        $result = $saveReportSettings();
        if (!($result['ok'] ?? false)) {
            flash_set($result['message'] ?? 'Perubahan gagal disimpan.', 'err');
            header('Location: '.admin_url('settings')); exit;
        }
        audit('update_laporan_settings','Pengaturan laporan publik diperbarui');
        $flashSuccess('Pengaturan laporan publik disimpan.');
    } elseif ($act === 'payments') {
        $result = $savePaymentSettings();
        if (!($result['ok'] ?? false)) {
            flash_set($result['message'] ?? 'Perubahan gagal disimpan.', 'err');
            header('Location: '.admin_url('settings')); exit;
        }
        audit('update_payment_settings', 'Pengaturan pembayaran manual diperbarui');
        $flashSuccess('Pengaturan pembayaran manual disimpan.');
    } elseif ($act === 'save_bank_account') {
        $id = (int) ($_POST['bank_id'] ?? 0);
        $bankName = trim((string) ($_POST['bank_name'] ?? ''));
        $accountNumber = trim((string) ($_POST['account_number'] ?? ''));
        $accountHolder = trim((string) ($_POST['account_holder'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
        if ($bankName === '' || $accountNumber === '') {
            flash_set('Nama bank dan nomor rekening wajib diisi.', 'err');
            header('Location: '.admin_url('settings', $id > 0 ? ['edit_bank' => $id] : [])); exit;
        }
        if ($id > 0) {
            DB::run("UPDATE bank_accounts SET bank_name=?, account_number=?, account_holder=?, sort_order=?, status=? WHERE id=?", 'sssisi', [$bankName, $accountNumber, $accountHolder ?: null, $sortOrder, $status, $id]);
            audit('update_bank_account', $bankName . ' #' . $id);
            $flashSuccess('Rekening tambahan diperbarui.');
        } else {
            DB::run("INSERT INTO bank_accounts (bank_name, account_number, account_holder, sort_order, status) VALUES (?,?,?,?,?)", 'sssis', [$bankName, $accountNumber, $accountHolder ?: null, $sortOrder, $status]);
            audit('create_bank_account', $bankName . ' ' . $accountNumber);
            $flashSuccess('Rekening tambahan ditambahkan.');
        }
    } elseif ($act === 'delete_bank_account') {
        $id = (int) ($_POST['bank_id'] ?? 0);
        $existingBank = DB::one("SELECT * FROM bank_accounts WHERE id=?", 'i', [$id]);
        if ($existingBank) {
            DB::run("DELETE FROM bank_accounts WHERE id=?", 'i', [$id]);
            audit('delete_bank_account', ($existingBank['bank_name'] ?? 'Bank') . ' #' . $id);
            $flashSuccess('Rekening tambahan dihapus.');
        }
    } elseif ($act === 'remove_payment_qris') {
        $currentUrl = setting('payment_qris_file', '');
        if ($currentUrl !== '') {
            delete_public_upload_by_url($currentUrl);
            set_setting('payment_qris_file', '');
            audit('remove_payment_qris', 'QRIS pembayaran dihapus');
            $flashSuccess('File QRIS pembayaran dihapus.');
        } else {
            flash_set('File QRIS belum tersedia.', 'err');
        }
    } elseif ($act === 'remove_doc') {
        $docKey = trim((string) ($_POST['doc_key'] ?? ''));
        if (!isset($allDocKeys[$docKey])) {
            flash_set('Dokumen yang dipilih tidak valid.', 'err');
            header('Location: '.admin_url('settings')); exit;
        }
        $currentUrl = setting($docKey, '');
        if ($currentUrl !== '') {
            delete_public_upload_by_url($currentUrl);
            set_setting($docKey, '');
            audit('remove_public_document', $docKey);
            $flashSuccess($allDocKeys[$docKey] . ' berhasil dihapus.');
        } else {
            flash_set('Dokumen belum tersedia untuk dihapus.', 'err');
        }
    } elseif ($act === 'remove_yayasan_logo') {
        $currentLogo = trim((string) setting('yayasan_logo', ''));
        if ($currentLogo !== '') {
            set_setting('yayasan_logo', '');
            delete_asset_image_if_unused($currentLogo);
            audit('remove_yayasan_logo', 'Logo yayasan dihapus');
            $flashSuccess('Logo yayasan dihapus.');
        } else {
            flash_set('Logo yayasan belum tersedia.', 'err');
        }
    }
    header('Location: '.admin_url('settings')); exit;
}

$stats = DB::all("SELECT * FROM impact_stats ORDER BY sort ASC");
$bankEdit = isset($_GET['edit_bank']) ? DB::one("SELECT * FROM bank_accounts WHERE id=?", 'i', [(int) $_GET['edit_bank']]) : null;
$bankRows = DB::all("SELECT * FROM bank_accounts ORDER BY sort_order ASC, id DESC");
$primaryBankNumber = trim((string) setting('payment_bank_primary_number', '7362699503'));
$publishedExtraAccounts = 0;
foreach ($bankRows as $bankRow) {
    if (($bankRow['status'] ?? '') === 'published') {
        $publishedExtraAccounts++;
    }
}
$manualPaymentReady = $primaryBankNumber !== '' || $publishedExtraAccounts > 0;
$qrisReady = trim((string) setting('payment_qris_file', '')) !== '';
$duitkuActive = feature_active('duitku_merchant_code') && feature_active('duitku_api_key');
$mailketingActive = feature_active('mailketing_api_token') && feature_active('mailketing_list_id');
$duitkuWebhook = BASE_URL . '/api/webhook-duitku.php';
$duitkuModeLabel = setting('duitku_is_production', '0') === '1' ? 'Production' : 'Sandbox';
$currentLogo = trim((string) setting('yayasan_logo', ''));
admin_layout_header('settings', 'Pengaturan');
flash_show();
$renderDocPreview = function ($settingKey, $title) {
    $url = trim((string) setting($settingKey, ''));
    if ($url === '') {
        return '';
    }
    $path = parse_url($url, PHP_URL_PATH);
    $ext = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
    $isPdf = $ext === 'pdf';
    ob_start();
    ?>
    <div class="doc-preview-card">
      <div class="doc-preview-head">
        <b><?= e($title) ?></b>
        <span class="badge badge-pending"><?= e(strtoupper($ext ?: 'FILE')) ?></span>
      </div>
      <div class="doc-preview-body">
        <?php if ($isImage): ?>
        <img src="<?= e($url) ?>" alt="<?= e($title) ?>">
        <?php elseif ($isPdf): ?>
        <iframe src="<?= e($url) ?>" title="<?= e($title) ?>" loading="lazy"></iframe>
        <?php else: ?>
        <div class="doc-preview-placeholder">
          <span>Dokumen tersedia untuk ditinjau</span>
        </div>
        <?php endif; ?>
      </div>
      <div class="doc-preview-actions">
        <a class="btn btn-ghost btn-sm" href="<?= e($url) ?>" target="_blank" rel="noopener">Buka Dokumen</a>
        <form method="post" onsubmit="return confirm('Hapus dokumen ini?')">
          <?= csrf_field() ?>
          <input type="hidden" name="act" value="remove_doc">
          <input type="hidden" name="doc_key" value="<?= e($settingKey) ?>">
          <button class="btn btn-ghost btn-sm">Hapus Dokumen</button>
        </form>
      </div>
    </div>
    <?php
    return ob_get_clean();
};
$renderSecretInput = function ($name, $value, $placeholder = '', $formId = '') {
    $inputId = 'secret-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
    $preview = trim((string) $value);
    $formAttr = $formId !== '' ? ' form="' . e($formId) . '"' : '';
    if ($preview !== '') {
        $length = strlen($preview);
        if ($length <= 8) {
            $preview = substr($preview, 0, 2) . str_repeat('*', max(1, $length - 4)) . substr($preview, -2);
        } else {
            $preview = substr($preview, 0, 4) . str_repeat('*', max(4, $length - 8)) . substr($preview, -4);
        }
    }
    ob_start();
    ?>
    <div class="secret-input-row">
      <div class="secret-input-stack">
        <input type="password" id="<?= e($inputId) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" placeholder="<?= e($placeholder) ?>" autocomplete="off" data-secret-input<?= $formAttr ?>>
        <?php if ($preview !== ''): ?><small class="secret-hint">Tersimpan: <code><?= e($preview) ?></code></small><?php endif; ?>
      </div>
      <div class="secret-actions">
        <button class="btn btn-ghost btn-sm secret-toggle" type="button" data-target="<?= e($inputId) ?>" data-show-label="Lihat" data-hide-label="Sembunyikan">Lihat</button>
        <button class="btn btn-ghost btn-sm secret-copy" type="button" data-copy-target="<?= e($inputId) ?>" data-default-label="Salin" data-copied-label="Tersalin">Salin</button>
      </div>
    </div>
    <?php
    return ob_get_clean();
};
?>
<div class="panel">
  <div class="panel-head"><h3>Status Pembayaran</h3></div>
  <div class="stats-grid stats-grid-compact">
    <div class="stat-card <?= $duitkuActive ? 'stat-accent' : '' ?>">
      <div class="stat-ic">D</div>
      <b><?= e($duitkuModeLabel) ?></b>
      <span>Duitku <?= $duitkuActive ? 'aktif dan siap dites' : 'belum aktif' ?></span>
    </div>
    <div class="stat-card <?= $manualPaymentReady ? 'stat-accent' : '' ?>">
      <div class="stat-ic">R</div>
      <b><?= $manualPaymentReady ? 'Siap' : 'Belum' ?></b>
      <span><?= $manualPaymentReady ? 'Transfer manual tersedia' : 'Isi rekening utama atau rekening tambahan' ?></span>
    </div>
    <div class="stat-card <?= $qrisReady ? 'stat-accent' : '' ?>">
      <div class="stat-ic">Q</div>
      <b><?= $qrisReady ? 'Tersedia' : 'Belum Ada' ?></b>
      <span><?= $qrisReady ? 'QRIS sudah diunggah' : 'Unggah gambar QRIS resmi yayasan' ?></span>
    </div>
    <div class="stat-card stat-gold">
      <div class="stat-ic">W</div>
      <b><?= (int) $publishedExtraAccounts ?></b>
      <span>Rekening tambahan yang tampil</span>
    </div>
  </div>
  <div class="alert alert-info">
    <b>Webhook Duitku:</b> <code><?= e($duitkuWebhook) ?></code><br>
    <span class="muted">Mode saat ini: <b><?= e($duitkuModeLabel) ?></b>. Gunakan tombol test koneksi di bawah untuk memastikan kredensial tetap valid sebelum transaksi live.</span>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3>Checklist Uji Pembayaran</h3></div>
  <p class="muted">Gunakan panduan singkat ini sebelum website dipakai live, agar alur `Duitku`, transfer manual, dan verifikasi admin bisa dipastikan berjalan normal.</p>
  <div class="grid-2">
    <div class="card">
      <h3>Uji Duitku</h3>
      <ul class="mini-list compact">
        <li>Buka halaman donasi publik lalu pilih metode <b>Duitku Checkout</b>.</li>
        <li>Isi nominal, nama, WhatsApp, dan email lalu lanjutkan checkout.</li>
        <li>Pastikan diarahkan ke halaman pembayaran Duitku tanpa error.</li>
        <li>Selesaikan pembayaran lalu pastikan kembali ke website dengan status berhasil atau menunggu callback.</li>
      </ul>
      <div class="hero-cta">
        <a class="btn btn-outline btn-sm" href="<?= url('donasi') ?>" target="_blank" rel="noopener">Buka Halaman Donasi</a>
        <a class="btn btn-ghost btn-sm" href="<?= admin_url('donasi') ?>">Cek Donasi di Admin</a>
      </div>
    </div>
    <div class="card">
      <h3>Uji Transfer Manual</h3>
      <ul class="mini-list compact">
        <li>Pilih metode <b>Transfer Manual</b> di halaman donasi publik.</li>
        <li>Pastikan rekening utama, rekening tambahan, dan QRIS tampil sesuai pengaturan.</li>
        <li>Lakukan simulasi submit donasi lalu cek invoice tercatat di admin.</li>
        <li>Verifikasi manual dari admin dan pastikan status berubah menjadi <b>verified</b>.</li>
      </ul>
      <div class="hero-cta">
        <a class="btn btn-outline btn-sm" href="<?= url('donasi') ?>" target="_blank" rel="noopener">Tes Transfer Manual</a>
        <a class="btn btn-ghost btn-sm" href="<?= admin_url('donasi', ['status' => 'pending']) ?>">Donasi Pending</a>
      </div>
    </div>
  </div>
  <div class="alert alert-info">
    <b>Target akhir:</b> donatur bisa checkout tanpa error, status donasi masuk ke admin, dan pembayaran sukses berubah menjadi <code>verified</code> baik dari callback `Duitku` maupun verifikasi manual admin.
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3>Profil Yayasan</h3></div>
  <div class="alert alert-info">Semua perubahan pada profil, API, pembayaran, statistik, dan laporan disimpan melalui satu tombol utama di bagian paling bawah halaman ini.</div>
  <div class="form settings-bulk-section">
    <div class="grid-2">
      <div><label>Nama Yayasan</label><input form="<?= e($bulkFormId) ?>" type="text" name="yayasan_name" value="<?= e(setting('yayasan_name')) ?>"></div>
      <div><label>Nama Pendek (Brand)</label><input form="<?= e($bulkFormId) ?>" type="text" name="yayasan_short" value="<?= e(setting('yayasan_short','Al Fatih')) ?>"></div>
      <div><label>Nomor WhatsApp Yayasan</label><input form="<?= e($bulkFormId) ?>" type="text" name="yayasan_wa" value="<?= e(setting('yayasan_wa')) ?>" placeholder="08xxxxxxxxxx"></div>
      <div><label>Email</label><input form="<?= e($bulkFormId) ?>" type="email" name="yayasan_email" value="<?= e(setting('yayasan_email')) ?>"></div>
    </div>
    <label>Alamat</label><input form="<?= e($bulkFormId) ?>" type="text" name="yayasan_alamat" value="<?= e(setting('yayasan_alamat')) ?>">
    <label>Profil Singkat</label><textarea form="<?= e($bulkFormId) ?>" name="yayasan_profil" rows="3"><?= e(setting('yayasan_profil')) ?></textarea>
    <label>Visi</label><input form="<?= e($bulkFormId) ?>" type="text" name="yayasan_visi" value="<?= e(setting('yayasan_visi')) ?>">
    <label>Misi (satu baris per misi)</label><textarea form="<?= e($bulkFormId) ?>" name="yayasan_misi" rows="3"><?= e(setting('yayasan_misi')) ?></textarea>
    <div class="grid-2">
      <div><label>Akta Pendirian</label><input form="<?= e($bulkFormId) ?>" type="text" name="legal_akta" value="<?= e(setting('legal_akta', 'Akta Notaris Nomor 18 tanggal 11 Juni 2026 oleh BENNY NUR CHANIAGO, S.H., M.Kn.')) ?>"></div>
      <div><label>SK Kemenkumham</label><input form="<?= e($bulkFormId) ?>" type="text" name="legal_sk" value="<?= e(setting('legal_sk', 'Keputusan Menteri Hukum RI Nomor AHU-0014152.AH.01.04.Tahun 2026 tanggal 12 Juni 2026.')) ?>"></div>
      <div><label>NPWP</label><input form="<?= e($bulkFormId) ?>" type="text" name="legal_npwp" value="<?= e(setting('legal_npwp')) ?>" placeholder="Isi nomor NPWP yayasan bila sudah tersedia"></div>
      <div>
        <label>Rekening Resmi</label><input form="<?= e($bulkFormId) ?>" type="text" name="legal_rekening" value="<?= e(setting('legal_rekening')) ?>" placeholder="Contoh: BSI 123456 a.n Yayasan">
        <small class="secret-hint">Kosongkan bila ingin mengikuti rekening utama pada panel pembayaran manual.</small>
      </div>
      <div><label>Struktur Organisasi</label><input form="<?= e($bulkFormId) ?>" type="text" name="legal_struktur" value="<?= e(setting('legal_struktur', 'Pembina: Riyandi. Pengawas: Cut Rossy Meutia. Pengurus: Yudha Eris Setiawan, Ari Cipta Robbi, dan Ichsan Nugraha.')) ?>" placeholder="Keterangan singkat struktur organisasi"></div>
      <div><label>Target Dana Reward Umrah (Rp)</label><input form="<?= e($bulkFormId) ?>" type="text" name="umrah_target" inputmode="numeric" value="<?= e(setting('umrah_target','50000000')) ?>"></div>
      <div><label>Link Google Maps</label><input form="<?= e($bulkFormId) ?>" type="text" name="google_maps_link" value="<?= e(setting('google_maps_link')) ?>" placeholder="https://maps.google.com/..."></div>
      <div><label>Instagram</label><input form="<?= e($bulkFormId) ?>" type="text" name="social_instagram" value="<?= e(setting('social_instagram')) ?>" placeholder="@yayasan"></div>
      <div><label>Facebook</label><input form="<?= e($bulkFormId) ?>" type="text" name="social_facebook" value="<?= e(setting('social_facebook')) ?>" placeholder="Halaman Facebook Yayasan"></div>
      <div><label>YouTube</label><input form="<?= e($bulkFormId) ?>" type="text" name="social_youtube" value="<?= e(setting('social_youtube')) ?>" placeholder="Channel YouTube Yayasan"></div>
    </div>
    <div class="grid-2">
      <div>
        <label>Upload Logo Yayasan</label><input form="<?= e($bulkFormId) ?>" type="file" name="yayasan_logo_file" accept=".webp">
        <small class="secret-hint">Gunakan logo horizontal <b>WebP transparan</b>. Rekomendasi terbaik <b>1200 x 320 px</b>, alternatif aman <b>1000 x 280 px</b> atau <b>900 x 260 px</b>.</small>
        <?php if ($currentLogo): ?>
        <div class="upload-preview upload-preview-logo">
          <img src="<?= e(asset('img/' . $currentLogo)) ?>" alt="Logo Yayasan">
          <div class="upload-meta">
            <b>Logo Yayasan Aktif</b>
            <span class="muted"><?= e($currentLogo) ?></span>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="card">
        <h3>Catatan Logo</h3>
        <ul class="mini-list compact">
          <li>Logo otomatis tampil di header, footer, panel admin, halaman login, dan register.</li>
          <li>Ukuran terbaik untuk website: <b>1200 x 320 px</b> dengan rasio horizontal sekitar <b>3.75 : 1</b>.</li>
          <li>Ukuran yang masih bagus: <b>1000 x 280 px</b>, <b>900 x 260 px</b>, atau minimum <b>600 x 180 px</b>.</li>
          <li>Gunakan file <b>WebP transparan</b> agar ringan di website, dan hindari logo kotak seperti <b>300 x 300 px</b> atau <b>500 x 500 px</b> untuk header.</li>
          <li>Jika logo asli berbentuk lambang tinggi, sebaiknya buat versi khusus website: lambang di kiri dan tulisan yayasan di kanan dalam satu file horizontal.</li>
          <li>Simpan profil setelah memilih file logo baru.</li>
        </ul>
        <?php if ($currentLogo): ?>
        <div class="inline-actions">
          <form method="post" onsubmit="return confirm('Hapus logo yayasan ini?')">
            <?= csrf_field() ?>
            <input type="hidden" name="act" value="remove_yayasan_logo">
            <button class="btn btn-ghost btn-sm">Hapus Logo</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="grid-2">
      <div>
        <label>Upload Dokumen Akta</label><input form="<?= e($bulkFormId) ?>" type="file" name="legal_akta_file" accept=".pdf,.jpg,.jpeg,.png,.webp">
        <?php if (setting('legal_akta_file')): ?><a class="inline-link" href="<?= e(setting('legal_akta_file')) ?>" target="_blank" rel="noopener">Lihat dokumen saat ini</a><?php endif; ?>
        <?= $renderDocPreview('legal_akta_file', 'Dokumen Akta') ?>
      </div>
      <div>
        <label>Upload Dokumen SK</label><input form="<?= e($bulkFormId) ?>" type="file" name="legal_sk_file" accept=".pdf,.jpg,.jpeg,.png,.webp">
        <?php if (setting('legal_sk_file')): ?><a class="inline-link" href="<?= e(setting('legal_sk_file')) ?>" target="_blank" rel="noopener">Lihat dokumen saat ini</a><?php endif; ?>
        <?= $renderDocPreview('legal_sk_file', 'Dokumen SK') ?>
      </div>
      <div>
        <label>Upload Dokumen NPWP</label><input form="<?= e($bulkFormId) ?>" type="file" name="legal_npwp_file" accept=".pdf,.jpg,.jpeg,.png,.webp">
        <?php if (setting('legal_npwp_file')): ?><a class="inline-link" href="<?= e(setting('legal_npwp_file')) ?>" target="_blank" rel="noopener">Lihat dokumen saat ini</a><?php endif; ?>
        <?= $renderDocPreview('legal_npwp_file', 'Dokumen NPWP') ?>
      </div>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3>🔑 Kunci API (Aktivasi Fitur)</h3></div>
  <div class="alert alert-info">Isi kredensial integrasi di bawah ini agar payment gateway, email marketing, dan notifikasi WhatsApp bisa aktif dari panel admin. Gunakan tombol test untuk memastikan koneksi API normal sebelum dipakai live.</div>
  <div class="form settings-bulk-section">
    <div class="alert alert-info">
      <b>Webhook Duitku:</b> <code><?= e($duitkuWebhook) ?></code><br>
      <span class="muted">Daftarkan URL callback ini di project `Duitku` agar status pembayaran otomatis masuk ke sistem.</span>
    </div>

    <label>Duitku Merchant Code <?= $duitkuActive?'<span class="badge badge-verified">Aktif</span>':'<span class="badge badge-pending">Belum aktif</span>' ?></label>
    <?= $renderSecretInput('duitku_merchant_code', setting('duitku_merchant_code'), 'Merchant Code project Duitku', $bulkFormId) ?>
    <label>Duitku API Key</label>
    <?= $renderSecretInput('duitku_api_key', setting('duitku_api_key'), 'API Key project Duitku', $bulkFormId) ?>
    <label class="check"><input form="<?= e($bulkFormId) ?>" type="checkbox" name="duitku_is_production" value="1" <?= setting('duitku_is_production','0') === '1' ? 'checked' : '' ?>> Gunakan mode production Duitku</label>
    <div class="hero-cta">
      <button class="btn" type="submit" form="<?= e($bulkFormId) ?>" name="test_provider" value="duitku">Test Koneksi Duitku</button>
    </div>

    <label>Mailketing API Token <?= $mailketingActive?'<span class="badge badge-verified">Aktif</span>':'<span class="badge badge-pending">Belum aktif</span>' ?></label>
    <?= $renderSecretInput('mailketing_api_token', setting('mailketing_api_token'), 'API token dari menu integration Mailketing', $bulkFormId) ?>
    <label>Mailketing List ID</label>
    <input form="<?= e($bulkFormId) ?>" type="text" name="mailketing_list_id" value="<?= e(setting('mailketing_list_id')) ?>" placeholder="Contoh: 1">
    <label class="check"><input form="<?= e($bulkFormId) ?>" type="checkbox" name="mailketing_auto_sync" value="1" <?= setting('mailketing_auto_sync','1') === '1' ? 'checked' : '' ?>> Sinkronkan email donatur & relawan otomatis ke Mailketing</label>
    <div class="hero-cta">
      <button class="btn" type="submit" form="<?= e($bulkFormId) ?>" name="test_provider" value="mailketing">Test Mailketing</button>
    </div>

    <label>Fonnte Token (WhatsApp API) <?= feature_active('fonnte_token')?'<span class="badge badge-verified">Aktif</span>':'<span class="badge badge-pending">Belum aktif</span>' ?></label>
    <?= $renderSecretInput('fonnte_token', setting('fonnte_token'), 'Notifikasi WA otomatis & OTP', $bulkFormId) ?>
    <label>Fonnte Device / Sender ID</label>
    <input form="<?= e($bulkFormId) ?>" type="text" name="fonnte_sender" value="<?= e(setting('fonnte_sender')) ?>" placeholder="Opsional, isi jika akun Fonnte Anda memakai device tertentu">
    <div class="hero-cta">
      <button class="btn" type="submit" form="<?= e($bulkFormId) ?>" name="test_provider" value="fonnte">Test Fonnte</button>
    </div>

    <label>Google Maps API Key <?= feature_active('google_maps_key')?'<span class="badge badge-verified">Aktif</span>':'<span class="badge badge-pending">Belum aktif</span>' ?></label>
    <input form="<?= e($bulkFormId) ?>" type="text" name="google_maps_key" value="<?= e(setting('google_maps_key')) ?>" placeholder="Peta sebaran interaktif">
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3>Pembayaran Manual</h3></div>
  <p class="muted">Atur rekening utama BSI, unggah QRIS resmi, lalu tambahkan rekening lain bila yayasan memiliki opsi transfer tambahan.</p>
  <div class="form settings-bulk-section">
    <div class="grid-2">
      <div><label>Nama Bank Utama</label><input form="<?= e($bulkFormId) ?>" type="text" name="payment_bank_primary_name" value="<?= e(setting('payment_bank_primary_name', 'BSI KCP Bandung')) ?>" placeholder="Contoh: BSI"></div>
      <div><label>Nomor Rekening Utama</label><input form="<?= e($bulkFormId) ?>" type="text" name="payment_bank_primary_number" value="<?= e(setting('payment_bank_primary_number', '7362699503')) ?>" placeholder="Contoh: 1234567890"></div>
      <div><label>Nama Pemilik Rekening</label><input form="<?= e($bulkFormId) ?>" type="text" name="payment_bank_primary_holder" value="<?= e(setting('payment_bank_primary_holder', 'Yayasan Alfatih Mulia')) ?>" placeholder="Contoh: Yayasan Al Fatih Mulia Haramain"></div>
    </div>
    <div class="grid-2">
      <div>
        <label>Upload QRIS</label><input form="<?= e($bulkFormId) ?>" type="file" name="payment_qris_upload" accept=".webp">
        <small class="secret-hint">Gunakan file <b>WebP</b> persegi. Rekomendasi terbaik <b>1200 x 1200 px</b>, alternatif aman <b>1080 x 1080 px</b> atau <b>900 x 900 px</b>.</small>
        <?php if (setting('payment_qris_file')): ?><a class="inline-link" href="<?= e(setting('payment_qris_file')) ?>" target="_blank" rel="noopener">Lihat QRIS saat ini</a><?php endif; ?>
        <?= $renderDocPreview('payment_qris_file', 'QRIS Pembayaran') ?>
      </div>
      <div class="card">
        <h3>Catatan</h3>
        <ul class="mini-list compact">
          <li>Rekening utama akan tampil pertama pada halaman donasi.</li>
          <li>QRIS akan ditampilkan sebagai opsi scan untuk donatur transfer manual.</li>
          <li>Gunakan file <b>WebP</b> persegi agar ringan dimuat dan tetap tajam saat dibuka.</li>
          <li>Rekening tambahan dapat diatur di panel bawah.</li>
        </ul>
      </div>
    </div>
  </div>
  <?php if (setting('payment_qris_file')): ?>
  <div class="inline-actions">
    <form method="post" onsubmit="return confirm('Hapus file QRIS ini?')">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="remove_payment_qris">
      <button class="btn btn-ghost btn-sm">Hapus QRIS</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h3><?= $bankEdit ? 'Edit Rekening Tambahan' : 'Tambah Rekening Tambahan' ?></h3></div>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="save_bank_account">
      <input type="hidden" name="bank_id" value="<?= (int) ($bankEdit['id'] ?? 0) ?>">
      <label>Nama Bank</label><input type="text" name="bank_name" value="<?= e($bankEdit['bank_name'] ?? '') ?>" placeholder="Contoh: BCA">
      <label>Nomor Rekening</label><input type="text" name="account_number" value="<?= e($bankEdit['account_number'] ?? '') ?>" placeholder="Contoh: 1234567890">
      <label>Nama Pemilik (opsional)</label><input type="text" name="account_holder" value="<?= e($bankEdit['account_holder'] ?? '') ?>" placeholder="Contoh: Yayasan Al Fatih">
      <div class="grid-2">
        <div><label>Urutan Tampil</label><input type="number" name="sort_order" value="<?= (int) ($bankEdit['sort_order'] ?? 0) ?>"></div>
        <div><label>Status</label><select name="status"><option value="published" <?= ($bankEdit['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Tampilkan</option><option value="draft" <?= ($bankEdit['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Sembunyikan</option></select></div>
      </div>
      <button class="btn btn-primary btn-block"><?= $bankEdit ? 'Simpan Perubahan' : 'Tambah Rekening' ?></button>
      <?php if ($bankEdit): ?><a class="btn btn-ghost btn-block" href="<?= admin_url('settings') ?>">Batal</a><?php endif; ?>
    </form>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>Daftar Rekening Tambahan</h3><span class="muted"><?= count($bankRows) ?> rekening</span></div>
    <?php if (!$bankRows): ?><div class="empty-state small"><p>Belum ada rekening tambahan.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Bank</th><th>Nomor Rekening</th><th>Pemilik</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody><?php foreach ($bankRows as $bank): ?>
        <tr>
          <td><b><?= e($bank['bank_name']) ?></b><br><small class="muted">Urutan: <?= (int) $bank['sort_order'] ?></small></td>
          <td><?= e($bank['account_number']) ?></td>
          <td><?= e($bank['account_holder'] ?: '-') ?></td>
          <td><span class="badge badge-<?= $bank['status'] === 'published' ? 'verified' : 'pending' ?>"><?= e($bank['status'] === 'published' ? 'Tampil' : 'Draft') ?></span></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="<?= admin_url('settings', ['edit_bank' => $bank['id']]) ?>">Edit</a>
            <form method="post" onsubmit="return confirm('Hapus rekening tambahan ini?')">
              <?= csrf_field() ?>
              <input type="hidden" name="act" value="delete_bank_account">
              <input type="hidden" name="bank_id" value="<?= (int) $bank['id'] ?>">
              <button class="btn btn-ghost btn-sm">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3>Statistik Dampak Manual</h3></div>
  <p class="muted">Counter yang tidak terderivasi otomatis (mis. Titik Air, Pesantren Binaan, Penerima Manfaat). Tampil di Dashboard Dampak.</p>
  <div class="form settings-bulk-section">
    <div id="statRows">
      <?php
      $defaults = $stats ?: [
        ['skey'=>'titik_air','label'=>'Titik Air','svalue'=>0,'icon'=>'💧'],
        ['skey'=>'pesantren','label'=>'Pesantren Binaan','svalue'=>0,'icon'=>'🕌'],
        ['skey'=>'rumah_quran','label'=>'Rumah Quran','svalue'=>0,'icon'=>'📖'],
        ['skey'=>'penerima_manfaat','label'=>'Penerima Manfaat','svalue'=>0,'icon'=>'❤️'],
        ['skey'=>'jamaah','label'=>'Jamaah Haji & Umrah','svalue'=>0,'icon'=>'🕋'],
      ];
      foreach ($defaults as $s): ?>
        <div class="stat-edit-row">
          <input form="<?= e($bulkFormId) ?>" type="text" name="ikey[]" value="<?= e($s['skey']) ?>" placeholder="kunci" class="w-key">
          <input form="<?= e($bulkFormId) ?>" type="text" name="ilabel[]" value="<?= e($s['label']) ?>" placeholder="Label">
          <input form="<?= e($bulkFormId) ?>" type="text" name="iicon[]" value="<?= e($s['icon']) ?>" placeholder="Ikon" class="w-icon">
          <input form="<?= e($bulkFormId) ?>" type="number" name="ival[]" value="<?= (int)$s['svalue'] ?>" placeholder="Nilai" class="w-val">
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3>Dokumen Laporan Publik</h3></div>
  <p class="muted">Masukkan link dokumen yang ingin ditampilkan pada halaman laporan publik, misalnya Google Drive atau URL file PDF.</p>
  <div class="form settings-bulk-section">
    <div class="grid-2">
      <div>
        <label>Laporan Bulanan</label><input form="<?= e($bulkFormId) ?>" type="text" name="laporan_doc_bulanan" value="<?= e(setting('laporan_doc_bulanan')) ?>" placeholder="https://...">
        <input form="<?= e($bulkFormId) ?>" type="file" name="laporan_doc_bulanan_file" accept=".pdf,.jpg,.jpeg,.png,.webp">
        <?php if (setting('laporan_doc_bulanan')): ?><a class="inline-link" href="<?= e(setting('laporan_doc_bulanan')) ?>" target="_blank" rel="noopener">Lihat dokumen saat ini</a><?php endif; ?>
        <?= $renderDocPreview('laporan_doc_bulanan', 'Laporan Bulanan') ?>
      </div>
      <div>
        <label>Laporan Tahunan</label><input form="<?= e($bulkFormId) ?>" type="text" name="laporan_doc_tahunan" value="<?= e(setting('laporan_doc_tahunan')) ?>" placeholder="https://...">
        <input form="<?= e($bulkFormId) ?>" type="file" name="laporan_doc_tahunan_file" accept=".pdf,.jpg,.jpeg,.png,.webp">
        <?php if (setting('laporan_doc_tahunan')): ?><a class="inline-link" href="<?= e(setting('laporan_doc_tahunan')) ?>" target="_blank" rel="noopener">Lihat dokumen saat ini</a><?php endif; ?>
        <?= $renderDocPreview('laporan_doc_tahunan', 'Laporan Tahunan') ?>
      </div>
      <div>
        <label>Laporan Penyaluran Program</label><input form="<?= e($bulkFormId) ?>" type="text" name="laporan_doc_penyaluran_program" value="<?= e(setting('laporan_doc_penyaluran_program')) ?>" placeholder="https://...">
        <input form="<?= e($bulkFormId) ?>" type="file" name="laporan_doc_penyaluran_program_file" accept=".pdf,.jpg,.jpeg,.png,.webp">
        <?php if (setting('laporan_doc_penyaluran_program')): ?><a class="inline-link" href="<?= e(setting('laporan_doc_penyaluran_program')) ?>" target="_blank" rel="noopener">Lihat dokumen saat ini</a><?php endif; ?>
        <?= $renderDocPreview('laporan_doc_penyaluran_program', 'Laporan Penyaluran Program') ?>
      </div>
      <div>
        <label>Audit Keuangan</label><input form="<?= e($bulkFormId) ?>" type="text" name="laporan_doc_audit_keuangan" value="<?= e(setting('laporan_doc_audit_keuangan')) ?>" placeholder="https://...">
        <input form="<?= e($bulkFormId) ?>" type="file" name="laporan_doc_audit_keuangan_file" accept=".pdf,.jpg,.jpeg,.png,.webp">
        <?php if (setting('laporan_doc_audit_keuangan')): ?><a class="inline-link" href="<?= e(setting('laporan_doc_audit_keuangan')) ?>" target="_blank" rel="noopener">Lihat dokumen saat ini</a><?php endif; ?>
        <?= $renderDocPreview('laporan_doc_audit_keuangan', 'Audit Keuangan') ?>
      </div>
    </div>
    <label class="check"><input form="<?= e($bulkFormId) ?>" type="checkbox" name="laporan_show_donors" value="1" <?= setting('laporan_show_donors','0') === '1' ? 'checked' : '' ?>> Tampilkan daftar donatur publik secara terbatas pada halaman laporan</label>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h3>🔒 Keamanan Installer</h3></div>
  <?php $hasInstaller = file_exists(__DIR__.'/../install.php'); ?>
  <?php if ($hasInstaller): ?>
    <div class="alert alert-err">⚠️ File <code>install.php</code> masih ada. Demi keamanan, hapus atau ganti namanya menjadi <code>install.lock</code> via File Manager cPanel.</div>
  <?php else: ?>
    <div class="alert alert-ok">✓ Installer sudah diamankan. Bagus!</div>
  <?php endif; ?>
</div>

<div class="settings-save-bar">
  <div>
    <b>Simpan Semua Perubahan</b>
    <p>Gunakan tombol ini setelah selesai mengubah pengaturan di atas. Sistem akan menyimpan seluruh panel utama sekaligus.</p>
  </div>
  <button class="btn btn-primary btn-lg" type="submit" form="<?= e($bulkFormId) ?>">Simpan Perubahan</button>
</div>

<form id="<?= e($bulkFormId) ?>" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="act" value="settings_bulk">
</form>
<script>
document.addEventListener('click', function (event) {
  var button = event.target.closest('[data-target]');
  if (button) {
    var input = document.getElementById(button.getAttribute('data-target'));
    if (!input) return;
    var showing = input.getAttribute('type') === 'text';
    input.setAttribute('type', showing ? 'password' : 'text');
    button.textContent = showing ? (button.getAttribute('data-show-label') || 'Lihat') : (button.getAttribute('data-hide-label') || 'Sembunyikan');
    return;
  }

  var copyButton = event.target.closest('[data-copy-target]');
  if (!copyButton) return;
  var copyInput = document.getElementById(copyButton.getAttribute('data-copy-target'));
  if (!copyInput) return;

  var resetLabel = function () {
    copyButton.textContent = copyButton.getAttribute('data-default-label') || 'Salin';
  };
  var copiedLabel = copyButton.getAttribute('data-copied-label') || 'Tersalin';

  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(copyInput.value).then(function () {
      copyButton.textContent = copiedLabel;
      window.setTimeout(resetLabel, 1500);
    }, resetLabel);
    return;
  }

  var previousType = copyInput.getAttribute('type');
  copyInput.setAttribute('type', 'text');
  copyInput.focus();
  copyInput.select();
  try {
    document.execCommand('copy');
    copyButton.textContent = copiedLabel;
    window.setTimeout(resetLabel, 1500);
  } catch (error) {
    resetLabel();
  }
  copyInput.setAttribute('type', previousType);
});
</script>
<?php admin_layout_footer(); ?>
