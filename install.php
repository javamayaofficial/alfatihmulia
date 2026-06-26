<?php
/**
 * install.php - Installer Wizard Al Fatih Impact Platform
 * Tanpa terminal. Cukup buka di browser: https://domainanda.com/install.php
 */
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
if (function_exists('mysqli_report')) { mysqli_report(MYSQLI_REPORT_OFF); } // kompatibilitas PHP 8.1+ shared hosting

$root = __DIR__;
$configFile = $root . '/config.php';
$step = (int)($_GET['step'] ?? 1);

// Jika sudah terinstal, kunci installer
if (file_exists($configFile)) {
    $c = file_get_contents($configFile);
    if (strpos($c, '__DB_HOST__') === false) {
        // sudah terisi
        $alreadyDone = true;
    }
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$errors = [];
$success = false;

// ---- STEP 2: Proses instalasi ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'install') {
    $dbhost = trim($_POST['db_host'] ?? 'localhost');
    $dbname = trim($_POST['db_name'] ?? '');
    $dbuser = trim($_POST['db_user'] ?? '');
    $dbpass = $_POST['db_pass'] ?? '';
    $adminName = trim($_POST['admin_name'] ?? '');
    $adminEmail= trim($_POST['admin_email'] ?? '');
    $adminPass = $_POST['admin_pass'] ?? '';
    $yName     = trim($_POST['yayasan_name'] ?? '');
    $yWa       = trim($_POST['yayasan_wa'] ?? '');

    if ($dbname==='' || $dbuser==='') $errors[] = 'Nama database dan user database wajib diisi.';
    if ($adminName==='' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPass)<6)
        $errors[] = 'Data admin tidak lengkap (email valid & password minimal 6 karakter).';

    // Koneksi DB
    if (!$errors) {
        $conn = @new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        if ($conn->connect_errno) {
            $errors[] = 'Gagal koneksi database: ' . h($conn->connect_error) . '. Periksa kembali nama DB, user, dan password.';
        } else {
            $conn->set_charset('utf8mb4');
            // Jalankan schema.sql
            $sql = file_get_contents($root . '/schema.sql');
            if ($sql === false) {
                $errors[] = 'File schema.sql tidak ditemukan.';
            } else {
                if (!$conn->multi_query($sql)) {
                    $errors[] = 'Gagal membuat tabel: ' . h($conn->error);
                } else {
                    while ($conn->more_results() && $conn->next_result()) { /* flush */ }

                    // Buat akun admin
                    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                    $code = 'AIP-ADMIN-' . rand(1000,9999);
                    $stmt = $conn->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'superadmin')");
                    $stmt->bind_param('sss', $adminName, $adminEmail, $hash);
                    $stmt->execute(); $stmt->close();

                    // Seed pengaturan dasar
                    $seedSettings = [
                        'yayasan_name'  => $yName ?: 'Yayasan Al Fatih Mulia Haramain',
                        'yayasan_short' => 'Al Fatih',
                        'yayasan_wa'    => $yWa,
                        'yayasan_email' => $adminEmail,
                        'yayasan_visi'  => 'Menjadi platform filantropi Islam nasional yang transparan, profesional, amanah, dan berdampak bagi umat.',
                        'umrah_target'  => '50000000',
                        'total_tersalurkan' => '0',
                    ];
                    $ss = $conn->prepare("INSERT INTO settings (skey,svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)");
                    foreach ($seedSettings as $k=>$v){ $ss->bind_param('ss',$k,$v); $ss->execute(); }
                    $ss->close();

                    // Seed impact stats default
                    $stats = [['titik_air','Titik Air','💧'],['pesantren','Pesantren Binaan','🕌'],
                              ['rumah_quran','Rumah Quran','📖'],['penerima_manfaat','Penerima Manfaat','❤️'],
                              ['jamaah','Jamaah Haji & Umrah','🕋']];
                    $st = $conn->prepare("INSERT INTO impact_stats (skey,label,svalue,icon,sort) VALUES (?,?,0,?,?) ON DUPLICATE KEY UPDATE label=VALUES(label)");
                    $i=0; foreach($stats as $s){ $st->bind_param('sssi',$s[0],$s[1],$s[2],$i); $st->execute(); $i++; } $st->close();

                    // Seed contoh program
                    $prog = $conn->prepare("INSERT INTO programs (slug,title,category,excerpt,description,target_amount,beneficiaries,status) VALUES (?,?,?,?,?,?,?,'active')");
                    $samples = [
                      ['sedekah-air-kehidupan','Sedekah Air Kehidupan','Air','Wujudkan sumber air bersih untuk saudara kita di daerah kekeringan.','Program pembangunan sumur dan sarana air bersih bagi masyarakat di wilayah yang kesulitan air. Setiap tetes adalah amal jariyah yang mengalir.',100000000,5000],
                      ['rumah-quran','Rumah Quran Al Fatih','Pendidikan','Bangun generasi penghafal Quran melalui rumah tahfizh.','Mendukung operasional dan beasiswa santri di Rumah Quran binaan yayasan.',75000000,300],
                      ['wakaf-produktif','Wakaf Produktif','Wakaf','Investasi akhirat yang manfaatnya terus mengalir.','Wakaf produktif yang dikelola amanah untuk membiayai program sosial & dakwah berkelanjutan.',200000000,10000],
                    ];
                    foreach($samples as $p){ $prog->bind_param('sssssii',$p[0],$p[1],$p[2],$p[3],$p[4],$p[5],$p[6]); $prog->execute(); } $prog->close();

                    // Seed testimoni & mitra contoh
                    $conn->query("INSERT INTO testimonials (name,role,message) VALUES
                      ('Bapak Ahmad','Donatur Rutin','Saya tenang berdonasi di sini karena laporannya transparan dan jelas.'),
                      ('Siti Aminah','Relawan','Jadi relawan di sini membuat saya merasa kontribusi kecil pun sangat berarti.')");
                    $conn->query("INSERT INTO partners (name,category) VALUES ('Masjid Al Fatih','Masjid Mitra'),('Pesantren Haramain','Pesantren Mitra')");

                    // Generate config.php
                    $tpl = file_get_contents($root . '/config.php');
                    $secret = bin2hex(random_bytes(16));
                    $tpl = str_replace(
                        ['__DB_HOST__','__DB_NAME__','__DB_USER__','__DB_PASS__','__SECRET_KEY__'],
                        [addslashes($dbhost),addslashes($dbname),addslashes($dbuser),addslashes($dbpass),$secret],
                        $tpl
                    );
                    if (@file_put_contents($configFile, $tpl) === false) {
                        $errors[] = 'Gagal menulis config.php. Pastikan folder dapat ditulis (izin 755/644).';
                    } else {
                        $success = true;
                        $_SESSION['install_done'] = true;
                    }
                    $conn->close();
                }
            }
        }
    }
    if (!$success && !$errors) $errors[] = 'Terjadi kesalahan tak terduga saat instalasi.';
}

// Cek requirement
$req = [
    'PHP versi ≥ 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'Ekstensi MySQLi' => extension_loaded('mysqli'),
    'Ekstensi mbstring' => extension_loaded('mbstring'),
    'config.php dapat ditulis' => is_writable($root) || (file_exists($configFile) && is_writable($configFile)),
    'Folder uploads/ dapat ditulis' => is_writable($root.'/uploads') || !file_exists($root.'/uploads'),
    'Folder storage/ dapat ditulis' => is_writable($root.'/storage') || !file_exists($root.'/storage'),
];
$allOk = !in_array(false, $req, true);
?><!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installer — Al Fatih Impact Platform</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:linear-gradient(160deg,#063e27,#0B7A4B);min-height:100vh;color:#1F2A24;padding:30px 16px}
.wiz{max-width:620px;margin:0 auto;background:#fff;border-radius:24px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden}
.wiz-head{background:#06301e;color:#fff;padding:30px;text-align:center}
.wiz-head .m{font-size:34px;margin-bottom:8px}
.wiz-head h1{font-family:'Poppins';font-size:22px}
.wiz-head p{opacity:.85;font-size:14px;margin-top:4px}
.wiz-body{padding:30px}
h2{font-family:'Poppins';font-size:18px;margin-bottom:16px}
.req{list-style:none;margin-bottom:20px}
.req li{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eee;font-size:14px}
.ok{color:#1B9E5A;font-weight:600}.no{color:#D14343;font-weight:600}
label{display:block;font-weight:600;font-size:13px;margin:14px 0 6px}
input{width:100%;padding:12px 14px;border:1.5px solid #e7ecea;border-radius:12px;font-size:15px;font-family:'Inter'}
input:focus{outline:0;border-color:#0B7A4B;box-shadow:0 0 0 3px rgba(11,122,75,.12)}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{display:block;width:100%;background:#0B7A4B;color:#fff;border:0;border-radius:999px;padding:14px;font-size:15px;font-weight:600;cursor:pointer;margin-top:22px;text-align:center}
.btn:hover{background:#085c39}
.btn.gold{background:#C9A227}
.alert{padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:14px}
.err{background:#fdecec;color:#a32424;border:1px solid #f5c6c6}
.note{font-size:12.5px;color:#6b7a72;margin-top:8px}
.sec{background:#f5f7f6;border-radius:14px;padding:16px;margin-bottom:8px}
.sec h3{font-size:14px;margin-bottom:6px;color:#0B7A4B}
.done{text-align:center}
.done .big{font-size:54px;margin-bottom:10px}
.steps{display:flex;gap:6px;justify-content:center;margin-top:14px}
.steps i{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.3)}
.steps i.on{background:#C9A227}
</style></head>
<body><div class="wiz">
<div class="wiz-head"><div class="m">&#xFDFD;</div><h1>Al Fatih Impact Platform</h1><p>Installer Wizard — Tanpa Terminal</p>
<div class="steps"><i class="<?= !$success?'on':'' ?>"></i><i class="<?= $success?'on':'' ?>"></i></div></div>
<div class="wiz-body">

<?php if (!empty($alreadyDone) && !$success): ?>
  <div class="done">
    <div class="big">🔒</div>
    <h2>Aplikasi Sudah Terinstal</h2>
    <p class="note">Demi keamanan, segera hapus file <b>install.php</b> via File Manager cPanel.</p>
    <a class="btn" href="admin/index.php">Masuk ke Panel Admin</a>
  </div>

<?php elseif ($success): ?>
  <div class="done">
    <div class="big">🎉</div>
    <h2>Alhamdulillah, Instalasi Berhasil!</h2>
    <p class="note">Platform Anda siap digunakan. Langkah keamanan terakhir:</p>
    <div class="sec" style="text-align:left;margin-top:14px">
      <h3>⚠️ Wajib: Amankan Installer</h3>
      <p style="font-size:13.5px">Buka <b>File Manager cPanel</b> → hapus file <b>install.php</b> (atau ganti nama menjadi <b>install.lock</b>). Ini mencegah orang lain menjalankan ulang installer.</p>
    </div>
    <a class="btn" href="admin/index.php">Login ke Panel Admin →</a>
    <a class="btn gold" href="index.php">Lihat Website</a>
  </div>

<?php else: ?>
  <?php if ($errors): ?><div class="alert err"><?php foreach($errors as $e) echo '<div>• '.h($e).'</div>'; ?></div><?php endif; ?>

  <h2>Langkah 1 — Cek Persyaratan Server</h2>
  <ul class="req">
    <?php foreach ($req as $k=>$v): ?>
      <li><span><?= h($k) ?></span><span class="<?= $v?'ok':'no' ?>"><?= $v?'✓ OK':'✕ Belum' ?></span></li>
    <?php endforeach; ?>
  </ul>
  <?php if (!$allOk): ?>
    <div class="alert err">Beberapa persyaratan belum terpenuhi. Hubungi penyedia hosting Anda, atau set izin folder ke 755.</div>
  <?php endif; ?>

  <form method="post" action="install.php">
    <input type="hidden" name="do" value="install">
    <div class="sec">
      <h3>🗄️ Database (buat dulu di cPanel → MySQL Databases)</h3>
      <label>Host Database</label>
      <input name="db_host" value="localhost" required>
      <div class="row">
        <div><label>Nama Database</label><input name="db_name" placeholder="user_aip" required></div>
        <div><label>User Database</label><input name="db_user" placeholder="user_aip" required></div>
      </div>
      <label>Password Database</label>
      <input type="password" name="db_pass" placeholder="Password database">
    </div>

    <div class="sec">
      <h3>👤 Akun Super Admin</h3>
      <label>Nama Lengkap</label><input name="admin_name" placeholder="Nama Anda" required>
      <div class="row">
        <div><label>Email Login</label><input type="email" name="admin_email" required></div>
        <div><label>Password</label><input type="password" name="admin_pass" placeholder="Min 6 karakter" required></div>
      </div>
    </div>

    <div class="sec">
      <h3>🕌 Profil Yayasan (bisa diubah nanti)</h3>
      <label>Nama Yayasan</label><input name="yayasan_name" value="Yayasan Al Fatih Mulia Haramain">
      <label>Nomor WhatsApp Yayasan</label><input name="yayasan_wa" placeholder="08xxxxxxxxxx">
    </div>

    <button class="btn" type="submit" <?= !$allOk?'disabled style=opacity:.5':'' ?>>🚀 Jalankan Instalasi</button>
    <p class="note">Installer akan membuat tabel, akun admin, dan data contoh secara otomatis.</p>
  </form>
<?php endif; ?>

</div></div></body></html>
