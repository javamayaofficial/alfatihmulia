<?php
if (!defined('APP_NAME')) { exit; }

$yname = setting('yayasan_name', 'Yayasan Al Fatih Mulia Haramain');
$visi = setting('yayasan_visi', 'Menjadi yayasan nasional yang profesional, kredibel, amanah, dan berdampak luas bagi umat.');
$profil = setting('yayasan_profil', 'Yayasan Al Fatih Mulia Haramain hadir sebagai perantara kebaikan untuk umat dengan fokus pada dakwah, air bersih, pendidikan, dan penguatan kolaborasi sosial. Kami membangun kepercayaan publik melalui pelayanan yang ramah, laporan yang terbuka, dan pengelolaan program yang berorientasi manfaat.');
$misi = preg_split('/\r\n|\r|\n/', trim(setting('yayasan_misi', "Memudahkan masyarakat berdonasi secara aman, mudah, dan terukur.\nMengelola program umat secara profesional dan tepat sasaran.\nMenghadirkan transparansi laporan sebagai pondasi kepercayaan publik.\nMembangun jaringan relawan dan kolaborasi nasional untuk memperluas manfaat.")));
$aboutIntro = setting('about_intro', $yname . ' dibangun untuk mempertemukan niat baik donatur dengan kebutuhan nyata umat.');
$aboutLegalIntro = setting('about_legal_intro', 'Informasi legalitas berikut ditampilkan untuk membangun trust dan memudahkan publik mengenali identitas resmi yayasan.');
$aboutLeadershipIntro = setting('about_leadership_intro', 'Susunan kepemimpinan yayasan ditampilkan agar publik memahami struktur amanah dan tata kelola organisasi.');
$officialAccountText = trim((string) setting('legal_rekening', ''));
if ($officialAccountText === '') {
  $officialBankName = trim((string) setting('payment_bank_primary_name', 'BSI'));
  $officialBankNumber = trim((string) setting('payment_bank_primary_number', ''));
  $officialBankHolder = trim((string) setting('payment_bank_primary_holder', $yname));
  if ($officialBankNumber !== '') {
    $officialAccountText = ($officialBankName !== '' ? $officialBankName . ' ' : '') . $officialBankNumber;
    if ($officialBankHolder !== '') {
      $officialAccountText .= ' a.n. ' . $officialBankHolder;
    }
  } else {
    $officialAccountText = 'Rekening resmi yayasan akan ditampilkan pada halaman donasi dan profil legalitas.';
  }
}
$legal = [
  ['label' => 'Akta Notaris', 'value' => setting('legal_akta', 'Nomor dokumen akan ditampilkan setelah verifikasi internal yayasan.'), 'file' => setting('legal_akta_file', '')],
  ['label' => 'SK Kemenkumham', 'value' => setting('legal_sk', 'Dokumen legal tersedia dan siap dipublikasikan sesuai kebutuhan trust publik.'), 'file' => setting('legal_sk_file', '')],
  ['label' => 'NPWP', 'value' => setting('legal_npwp', 'Informasi NPWP resmi dapat ditampilkan pada tahap publikasi legalitas penuh.'), 'file' => setting('legal_npwp_file', '')],
  ['label' => 'Rekening Resmi', 'value' => $officialAccountText, 'file' => ''],
  ['label' => 'Struktur Organisasi', 'value' => setting('legal_struktur', 'Struktur organisasi inti akan diperbarui secara berkala sesuai keputusan yayasan.'), 'file' => ''],
];
$defaultBoards = [
  'Dewan Pembina' => ["Ketua Pembina|Nama akan ditampilkan setelah finalisasi data resmi yayasan.", "Anggota Pembina|Nama akan ditampilkan setelah finalisasi data resmi yayasan."],
  'Dewan Pengawas' => ["Ketua Pengawas|Nama akan ditampilkan setelah finalisasi data resmi yayasan.", "Anggota Pengawas|Nama akan ditampilkan setelah finalisasi data resmi yayasan."],
  'Pengurus Yayasan' => ["Ketua Yayasan|Nama akan ditampilkan setelah finalisasi data resmi yayasan.", "Sekretaris|Nama akan ditampilkan setelah finalisasi data resmi yayasan.", "Bendahara|Nama akan ditampilkan setelah finalisasi data resmi yayasan.", "Kepala Program|Nama akan ditampilkan setelah finalisasi data resmi yayasan."],
];
$boardSettings = [
  'Dewan Pembina' => setting_lines('about_pembina_list', implode("\n", $defaultBoards['Dewan Pembina'])),
  'Dewan Pengawas' => setting_lines('about_pengawas_list', implode("\n", $defaultBoards['Dewan Pengawas'])),
  'Pengurus Yayasan' => setting_lines('about_pengurus_list', implode("\n", $defaultBoards['Pengurus Yayasan'])),
];
$orgRows = DB::all("SELECT * FROM organization_members WHERE status='published' ORDER BY board_group ASC, sort_order ASC, created_at DESC");
$orgMap = [
  'Dewan Pembina' => [],
  'Dewan Pengawas' => [],
  'Pengurus Yayasan' => [],
];
foreach ($orgRows as $row) {
  if ($row['board_group'] === 'pembina') $orgMap['Dewan Pembina'][] = $row;
  if ($row['board_group'] === 'pengawas') $orgMap['Dewan Pengawas'][] = $row;
  if ($row['board_group'] === 'pengurus') $orgMap['Pengurus Yayasan'][] = $row;
}

layout_header('Tentang Kami');
?>
<section class="page-head">
  <div class="container">
    <span class="pill pill-soft">Tentang Kami</span>
    <h1>Profil Yayasan</h1>
    <p class="muted"><?= e($aboutIntro) ?></p>
  </div>
</section>

<section class="section">
  <div class="container about-grid">
    <div class="card">
      <h2>Profil Yayasan</h2>
      <p class="muted"><?= nl2br(e($profil)) ?></p>
    </div>
    <div class="card">
      <h2>Visi</h2>
      <p class="muted"><?= e($visi) ?></p>
      <h3 class="subhead">Misi</h3>
      <ul class="mini-list">
        <?php foreach ($misi as $item): if (trim($item) === '') continue; ?>
        <li><?= e(trim($item)) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<section class="section section-soft">
  <div class="container">
    <div class="section-head">
      <h2>Legalitas Yayasan</h2>
      <p class="muted"><?= e($aboutLegalIntro) ?></p>
    </div>
    <div class="legal-grid">
      <?php foreach ($legal as $item): ?>
      <div class="legal-card">
        <b><?= e($item['label']) ?></b>
        <span class="muted"><?= e($item['value']) ?></span>
        <?php if (!empty($item['file'])): ?><a class="inline-link" href="<?= e($item['file']) ?>" target="_blank" rel="noopener">Lihat Dokumen</a><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Struktur Kepemimpinan</h2>
      <p class="muted"><?= e($aboutLeadershipIntro) ?></p>
    </div>
    <div class="leadership-grid">
      <?php foreach ($boardSettings as $title => $roles): ?>
      <div class="card">
        <h3><?= e($title) ?></h3>
        <?php if (!empty($orgMap[$title])): ?>
          <div class="org-card-grid">
            <?php foreach ($orgMap[$title] as $member): ?>
            <div class="org-person-card">
              <div class="org-photo" style="background-image:url('<?= $member['photo'] ? e(asset('img/' . $member['photo'])) : asset('img/placeholder.svg') ?>')"></div>
              <b><?= e($member['full_name']) ?></b>
              <span class="org-position"><?= e($member['position']) ?></span>
              <p class="muted"><?= e($member['bio'] ?: 'Profil singkat anggota akan ditampilkan setelah data lengkap diunggah oleh admin.') ?></p>
            </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="role-list">
            <?php foreach ($roles as $role): $parts = array_map('trim', explode('|', $role, 2)); ?>
            <div class="role-card">
              <b><?= e($parts[0] ?? '') ?></b>
              <span class="muted"><?= e($parts[1] ?? 'Nama akan ditampilkan setelah finalisasi data resmi yayasan.') ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php $wa = setting('yayasan_wa', ''); if ($wa): ?>
<section class="section section-soft">
  <div class="container final-cta">
    <h2>Ingin Mengenal Yayasan Lebih Dekat?</h2>
    <p class="muted">Tim kami siap membantu penjelasan program, legalitas, dan peluang kolaborasi dengan lebih lengkap.</p>
    <a class="btn btn-primary btn-lg" target="_blank" href="<?= e(wa_link($wa, 'Assalamualaikum, saya ingin mengenal lebih jauh tentang yayasan.')) ?>">Hubungi Kami via WhatsApp</a>
  </div>
</section>
<?php endif; ?>
<?php layout_footer(); ?>
