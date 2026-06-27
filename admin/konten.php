<?php
if (!defined('APP_NAME')) { exit; }
Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';

    if ($act === 'tentang') {
        foreach ([
            'about_intro',
            'about_legal_intro',
            'about_leadership_intro',
            'about_pembina_list',
            'about_pengawas_list',
            'about_pengurus_list',
        ] as $key) {
            if (isset($_POST[$key])) set_setting($key, trim($_POST[$key]));
        }
        audit('update_public_content', 'Konten halaman Tentang diperbarui');
        flash_set('Konten halaman Tentang berhasil disimpan.');
    } elseif ($act === 'dokumentasi') {
        foreach ([
            'documentation_intro',
            'documentation_filter_intro',
            'documentation_filter_items',
            'documentation_gallery_intro',
            'documentation_photo_desc',
            'documentation_video_desc',
            'documentation_gallery_desc',
        ] as $key) {
            if (isset($_POST[$key])) set_setting($key, trim($_POST[$key]));
        }
        audit('update_public_content', 'Konten halaman Dokumentasi diperbarui');
        flash_set('Konten halaman Dokumentasi berhasil disimpan.');
    } elseif ($act === 'kemitraan') {
        foreach ([
            'partnership_intro',
            'partnership_types',
            'partnership_type_desc',
            'partnership_trace_intro',
            'partnership_form_intro',
            'partnership_form_steps',
        ] as $key) {
            if (isset($_POST[$key])) set_setting($key, trim($_POST[$key]));
        }
        audit('update_public_content', 'Konten halaman Kemitraan diperbarui');
        flash_set('Konten halaman Kemitraan berhasil disimpan.');
    }

    header('Location: ' . admin_url('konten'));
    exit;
}

admin_layout_header('konten', 'Kelola Konten Publik');
flash_show();
?>
<div class="panel">
  <div class="panel-head"><h3>Halaman Tentang Kami</h3></div>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="tentang">
    <label>Kalimat Pembuka Halaman</label>
    <textarea name="about_intro" rows="3"><?= e(setting('about_intro', 'Yayasan Al Fatih Mulia Haramain dibangun untuk mempertemukan niat baik donatur dengan kebutuhan nyata umat.')) ?></textarea>
    <label>Pengantar Legalitas</label>
    <textarea name="about_legal_intro" rows="3"><?= e(setting('about_legal_intro', 'Informasi legalitas berikut ditampilkan untuk membangun trust dan memudahkan publik mengenali identitas resmi yayasan.')) ?></textarea>
    <label>Pengantar Struktur Kepemimpinan</label>
    <textarea name="about_leadership_intro" rows="3"><?= e(setting('about_leadership_intro', 'Susunan kepemimpinan yayasan ditampilkan agar publik memahami struktur amanah dan tata kelola organisasi.')) ?></textarea>
    <p class="note">Untuk mengelola struktur organisasi per orang lengkap dengan foto, gunakan menu `Organisasi`. Isian di bawah ini tetap dipakai sebagai fallback jika data anggota belum diinput.</p>
    <div class="grid-2">
      <div>
        <label>Dewan Pembina</label>
        <textarea name="about_pembina_list" rows="5"><?= e(setting('about_pembina_list', "Ketua Pembina|Riyandi")) ?></textarea>
        <p class="note">Format: `Jabatan|Keterangan`, satu baris per orang.</p>
      </div>
      <div>
        <label>Dewan Pengawas</label>
        <textarea name="about_pengawas_list" rows="5"><?= e(setting('about_pengawas_list', "Ketua Pengawas|Cut Rossy Meutia")) ?></textarea>
        <p class="note">Format: `Jabatan|Keterangan`, satu baris per orang.</p>
      </div>
    </div>
    <label>Pengurus Yayasan</label>
    <textarea name="about_pengurus_list" rows="5"><?= e(setting('about_pengurus_list', "Ketua Yayasan|Yudha Eris Setiawan\nSekretaris|Ari Cipta Robbi\nBendahara|Ichsan Nugraha")) ?></textarea>
    <p class="note">Format: `Jabatan|Keterangan`, satu baris per orang.</p>
    <button class="btn btn-primary">Simpan Konten Tentang</button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h3>Halaman Dokumentasi</h3></div>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="dokumentasi">
    <label>Pengantar Halaman Dokumentasi</label>
    <textarea name="documentation_intro" rows="3"><?= e(setting('documentation_intro', 'Dokumentasi program menjadi bagian penting dari transparansi dan narasi dampak yang ingin dibangun yayasan.')) ?></textarea>
    <label>Pengantar Filter Program</label>
    <textarea name="documentation_filter_intro" rows="3"><?= e(setting('documentation_filter_intro', 'Filter ini disiapkan untuk memudahkan pengunjung menjelajah dokumentasi berdasarkan pilar program utama.')) ?></textarea>
    <label>Daftar Filter Program</label>
    <textarea name="documentation_filter_items" rows="4"><?= e(setting('documentation_filter_items', "Air Kehidupan\nCahaya Ilmu\nJejak Baitullah")) ?></textarea>
    <p class="note">Satu baris untuk satu filter.</p>
    <label>Pengantar Galeri</label>
    <textarea name="documentation_gallery_intro" rows="3"><?= e(setting('documentation_gallery_intro', 'Konten artikel dan kabar lapangan saat ini menjadi sumber dokumentasi publik yang paling siap ditampilkan.')) ?></textarea>
    <div class="grid-2">
      <div><label>Deskripsi Foto Kegiatan</label><textarea name="documentation_photo_desc" rows="3"><?= e(setting('documentation_photo_desc', 'Menampilkan momentum penyaluran program, edukasi lapangan, dan interaksi dengan penerima manfaat.')) ?></textarea></div>
      <div><label>Deskripsi Video Kegiatan</label><textarea name="documentation_video_desc" rows="3"><?= e(setting('documentation_video_desc', 'Siap digunakan untuk menampilkan video singkat lapangan, testimoni, dan laporan dampak program.')) ?></textarea></div>
    </div>
    <label>Deskripsi Galeri Program</label>
    <textarea name="documentation_gallery_desc" rows="3"><?= e(setting('documentation_gallery_desc', 'Ruang visual untuk membangun trust donatur dan menampilkan progres program secara berkala.')) ?></textarea>
    <button class="btn btn-primary">Simpan Konten Dokumentasi</button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h3>Halaman Kemitraan</h3></div>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="kemitraan">
    <label>Pengantar Halaman Kemitraan</label>
    <textarea name="partnership_intro" rows="3"><?= e(setting('partnership_intro', 'Yayasan membuka ruang sinergi dengan lembaga, komunitas, dan institusi yang ingin memperluas dampak kebaikan bersama.')) ?></textarea>
    <label>Jenis Kemitraan</label>
    <textarea name="partnership_types" rows="5"><?= e(setting('partnership_types', "Corporate Partnership\nMasjid Partnership\nKomunitas\nSekolah\nPesantren")) ?></textarea>
    <p class="note">Satu baris untuk satu jenis kemitraan.</p>
    <label>Deskripsi Umum Jenis Kemitraan</label>
    <textarea name="partnership_type_desc" rows="3"><?= e(setting('partnership_type_desc', 'Program kolaborasi dapat diarahkan untuk dukungan program, kampanye publik, penyaluran manfaat, atau agenda sosial bersama.')) ?></textarea>
    <label>Pengantar Jejak Mitra</label>
    <textarea name="partnership_trace_intro" rows="3"><?= e(setting('partnership_trace_intro', 'Daftar mitra yang telah lebih dulu berjalan bersama yayasan.')) ?></textarea>
    <label>Pengantar Form Pengajuan Kerjasama</label>
    <textarea name="partnership_form_intro" rows="3"><?= e(setting('partnership_form_intro', 'Untuk tahap awal, pengajuan kerja sama diarahkan melalui kontak resmi yayasan agar kebutuhan kolaborasi dapat dibahas lebih cepat.')) ?></textarea>
    <label>Langkah Persiapan Pengajuan</label>
    <textarea name="partnership_form_steps" rows="4"><?= e(setting('partnership_form_steps', "Siapkan profil singkat lembaga atau komunitas\nTuliskan bentuk kolaborasi yang diinginkan\nSertakan target program atau wilayah manfaat")) ?></textarea>
    <p class="note">Satu baris untuk satu langkah.</p>
    <button class="btn btn-primary">Simpan Konten Kemitraan</button>
  </form>
</div>
<?php admin_layout_footer(); ?>
