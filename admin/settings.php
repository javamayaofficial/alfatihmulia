<?php
if (!defined('APP_NAME')) { exit; }
Auth::requireAdmin('superadmin');

$saveApiSettings = function () {
    $apiKeys = [
        'midtrans_merchant_id',
        'midtrans_client_key',
        'midtrans_server_key',
        'xendit_key',
        'xendit_callback_token',
        'mailketing_api_token',
        'mailketing_list_id',
        'fonnte_token',
        'fonnte_sender',
        'google_maps_key',
    ];
    foreach ($apiKeys as $k) {
        if (isset($_POST[$k])) set_setting($k, trim($_POST[$k]));
    }
    set_setting('midtrans_is_production', isset($_POST['midtrans_is_production']) ? '1' : '0');
    set_setting('mailketing_auto_sync', isset($_POST['mailketing_auto_sync']) ? '1' : '0');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    if ($act === 'profil') {
        foreach (['yayasan_name','yayasan_short','yayasan_wa','yayasan_email','yayasan_alamat',
                  'yayasan_profil','yayasan_visi','yayasan_misi','legal_akta','legal_sk','legal_npwp',
                  'umrah_target'] as $k) {
            if (isset($_POST[$k])) set_setting($k, trim($_POST[$k]));
        }
        audit('update_profil','Profil yayasan diperbarui');
        flash_set('Profil yayasan disimpan.');
    } elseif ($act === 'api') {
        $saveApiSettings();
        $provider = $_POST['test_provider'] ?? '';
        if ($provider === 'midtrans') {
            $test = test_midtrans_connection($_POST['midtrans_server_key'] ?? '', isset($_POST['midtrans_is_production']));
            audit('test_api_midtrans', $test['message']);
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
            flash_set('Kunci API disimpan. Fitur terkait kini aktif.');
        }
    } elseif ($act === 'impact') {
        $keys=$_POST['ikey']??[]; $labels=$_POST['ilabel']??[]; $vals=$_POST['ival']??[]; $icons=$_POST['iicon']??[];
        foreach ($keys as $i=>$kk) {
            $kk=preg_replace('/[^a-z0-9_]/','',strtolower($kk)); if($kk==='') continue;
            DB::run("INSERT INTO impact_stats (skey,label,svalue,icon,sort) VALUES (?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE label=VALUES(label),svalue=VALUES(svalue),icon=VALUES(icon)",
                'ssisi',[$kk,trim($labels[$i]??$kk),(int)($vals[$i]??0),trim($icons[$i]??'star'),$i]);
        }
        audit('update_impact','Statistik dampak diperbarui');
        flash_set('Statistik dampak disimpan.');
    }
    header('Location: '.admin_url('settings')); exit;
}

$stats = DB::all("SELECT * FROM impact_stats ORDER BY sort ASC");
admin_layout_header('settings', 'Pengaturan');
flash_show();
?>
<div class="panel">
  <div class="panel-head"><h3>Profil Yayasan</h3></div>
  <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="act" value="profil">
    <div class="grid-2">
      <div><label>Nama Yayasan</label><input type="text" name="yayasan_name" value="<?= e(setting('yayasan_name')) ?>"></div>
      <div><label>Nama Pendek (Brand)</label><input type="text" name="yayasan_short" value="<?= e(setting('yayasan_short','Al Fatih')) ?>"></div>
      <div><label>Nomor WhatsApp Yayasan</label><input type="text" name="yayasan_wa" value="<?= e(setting('yayasan_wa')) ?>" placeholder="08xxxxxxxxxx"></div>
      <div><label>Email</label><input type="email" name="yayasan_email" value="<?= e(setting('yayasan_email')) ?>"></div>
    </div>
    <label>Alamat</label><input type="text" name="yayasan_alamat" value="<?= e(setting('yayasan_alamat')) ?>">
    <label>Profil Singkat</label><textarea name="yayasan_profil" rows="3"><?= e(setting('yayasan_profil')) ?></textarea>
    <label>Visi</label><input type="text" name="yayasan_visi" value="<?= e(setting('yayasan_visi')) ?>">
    <label>Misi (satu baris per misi)</label><textarea name="yayasan_misi" rows="3"><?= e(setting('yayasan_misi')) ?></textarea>
    <div class="grid-2">
      <div><label>Akta Pendirian</label><input type="text" name="legal_akta" value="<?= e(setting('legal_akta')) ?>"></div>
      <div><label>SK Kemenkumham</label><input type="text" name="legal_sk" value="<?= e(setting('legal_sk')) ?>"></div>
      <div><label>NPWP</label><input type="text" name="legal_npwp" value="<?= e(setting('legal_npwp')) ?>"></div>
      <div><label>Target Dana Reward Umrah (Rp)</label><input type="text" name="umrah_target" inputmode="numeric" value="<?= e(setting('umrah_target','50000000')) ?>"></div>
    </div>
    <button class="btn btn-primary">Simpan Profil</button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h3>🔑 Kunci API (Aktivasi Fitur)</h3></div>
  <div class="alert alert-info">Isi kredensial integrasi di bawah ini agar payment gateway, email marketing, dan notifikasi WhatsApp bisa aktif dari panel admin. Gunakan tombol test untuk memastikan koneksi API normal sebelum dipakai live.</div>
  <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="act" value="api">
    <?php
      $midtransActive = feature_active('midtrans_client_key') && feature_active('midtrans_server_key');
      $mailketingActive = feature_active('mailketing_api_token') && feature_active('mailketing_list_id');
      $midtransWebhook = BASE_URL . '/api/webhook-midtrans.php';
      $xenditWebhook = BASE_URL . '/api/webhook-xendit.php';
    ?>

    <div class="alert alert-info">
      <b>Webhook Midtrans:</b> <code><?= e($midtransWebhook) ?></code><br>
      <b>Webhook Xendit:</b> <code><?= e($xenditWebhook) ?></code>
    </div>

    <label>Midtrans Merchant ID <?= $midtransActive?'<span class="badge badge-verified">Aktif</span>':'<span class="badge badge-pending">Belum aktif</span>' ?></label>
    <input type="text" name="midtrans_merchant_id" value="<?= e(setting('midtrans_merchant_id')) ?>" placeholder="Merchant ID Midtrans">
    <label>Midtrans Client Key</label>
    <input type="text" name="midtrans_client_key" value="<?= e(setting('midtrans_client_key')) ?>" placeholder="Dipakai untuk Snap / pembayaran client-side">
    <label>Midtrans Server Key</label>
    <input type="text" name="midtrans_server_key" value="<?= e(setting('midtrans_server_key')) ?>" placeholder="Dipakai untuk webhook / transaksi server-side">
    <label class="check"><input type="checkbox" name="midtrans_is_production" value="1" <?= setting('midtrans_is_production','0') === '1' ? 'checked' : '' ?>> Gunakan mode production Midtrans</label>
    <div class="hero-cta">
      <button class="btn" type="submit" name="test_provider" value="midtrans">Test Midtrans</button>
    </div>

    <label>Xendit API Key <?= feature_active('xendit_key')?'<span class="badge badge-verified">Aktif</span>':'<span class="badge badge-pending">Belum aktif</span>' ?></label>
    <input type="text" name="xendit_key" value="<?= e(setting('xendit_key')) ?>" placeholder="Secret key Xendit">
    <label>Xendit Callback Token</label>
    <input type="text" name="xendit_callback_token" value="<?= e(setting('xendit_callback_token')) ?>" placeholder="Token verifikasi callback">

    <label>Mailketing API Token <?= $mailketingActive?'<span class="badge badge-verified">Aktif</span>':'<span class="badge badge-pending">Belum aktif</span>' ?></label>
    <input type="text" name="mailketing_api_token" value="<?= e(setting('mailketing_api_token')) ?>" placeholder="API token dari menu integration Mailketing">
    <label>Mailketing List ID</label>
    <input type="text" name="mailketing_list_id" value="<?= e(setting('mailketing_list_id')) ?>" placeholder="Contoh: 1">
    <label class="check"><input type="checkbox" name="mailketing_auto_sync" value="1" <?= setting('mailketing_auto_sync','1') === '1' ? 'checked' : '' ?>> Sinkronkan email donatur & relawan otomatis ke Mailketing</label>
    <div class="hero-cta">
      <button class="btn" type="submit" name="test_provider" value="mailketing">Test Mailketing</button>
    </div>

    <label>Fonnte Token (WhatsApp API) <?= feature_active('fonnte_token')?'<span class="badge badge-verified">Aktif</span>':'<span class="badge badge-pending">Belum aktif</span>' ?></label>
    <input type="text" name="fonnte_token" value="<?= e(setting('fonnte_token')) ?>" placeholder="Notifikasi WA otomatis & OTP">
    <label>Fonnte Device / Sender ID</label>
    <input type="text" name="fonnte_sender" value="<?= e(setting('fonnte_sender')) ?>" placeholder="Opsional, isi jika akun Fonnte Anda memakai device tertentu">
    <div class="hero-cta">
      <button class="btn" type="submit" name="test_provider" value="fonnte">Test Fonnte</button>
    </div>

    <label>Google Maps API Key <?= feature_active('google_maps_key')?'<span class="badge badge-verified">Aktif</span>':'<span class="badge badge-pending">Belum aktif</span>' ?></label>
    <input type="text" name="google_maps_key" value="<?= e(setting('google_maps_key')) ?>" placeholder="Peta sebaran interaktif">
    <button class="btn btn-primary">Simpan Kunci API</button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h3>Statistik Dampak Manual</h3></div>
  <p class="muted">Counter yang tidak terderivasi otomatis (mis. Titik Air, Pesantren Binaan, Penerima Manfaat). Tampil di Dashboard Dampak.</p>
  <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="act" value="impact">
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
          <input type="text" name="ikey[]" value="<?= e($s['skey']) ?>" placeholder="kunci" class="w-key">
          <input type="text" name="ilabel[]" value="<?= e($s['label']) ?>" placeholder="Label">
          <input type="text" name="iicon[]" value="<?= e($s['icon']) ?>" placeholder="Ikon" class="w-icon">
          <input type="number" name="ival[]" value="<?= (int)$s['svalue'] ?>" placeholder="Nilai" class="w-val">
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary">Simpan Statistik</button>
  </form>
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
<?php admin_layout_footer(); ?>
