========================================================
  AL FATIH IMPACT PLATFORM (AIP) — VERSI PRO
  "Melayani Umat, Membangun Peradaban"
========================================================

Terima kasih! Ini adalah platform digital yayasan lengkap:
donasi online, portal relawan + leaderboard, dashboard
dampak real-time, laporan transparansi, dan panel admin.

Panduan ini ditulis untuk Anda yang TIDAK perlu paham coding.
Cukup bisa membuka cPanel & File Manager. Ikuti langkah demi
langkah di bawah ini.

--------------------------------------------------------
YANG ANDA BUTUHKAN
--------------------------------------------------------
1. Hosting dengan cPanel (PHP 7.4+ dan MySQL) — hampir semua
   hosting Indonesia sudah mendukung.
2. File "al-fatih-impact.zip" (yang ini).
3. Waktu sekitar 10 menit.

--------------------------------------------------------
LANGKAH 1 — UPLOAD FILE
--------------------------------------------------------
1. Login ke cPanel hosting Anda.
2. Buka "File Manager".
3. Masuk ke folder "public_html".
   (Jika ingin dipasang di subdomain/subfolder, masuk ke
    folder tersebut.)
4. Klik "Upload", lalu pilih file "al-fatih-impact.zip".
5. Setelah selesai upload, klik kanan file ZIP -> "Extract".
6. Pastikan file "index.php" dan "install.php" berada
   langsung di dalam public_html (bukan di dalam subfolder
   "al-fatih-impact"). Jika ter-extract ke subfolder,
   pindahkan semua isinya ke public_html.

--------------------------------------------------------
LANGKAH 2 — BUAT DATABASE
--------------------------------------------------------
1. Kembali ke halaman utama cPanel.
2. Buka menu "MySQL Databases".
3. Pada "Create New Database", ketik nama database
   (mis. "aip"), lalu klik Create. Catat nama lengkapnya
   (biasanya jadi "namauser_aip").
4. Scroll ke "MySQL Users" -> buat user baru + password.
   CATAT user dan password ini.
5. Scroll ke "Add User To Database" -> pilih user & database
   yang baru dibuat -> klik Add -> centang "ALL PRIVILEGES"
   -> Make Changes.

--------------------------------------------------------
LANGKAH 3 — JALANKAN INSTALLER (OTOMATIS)
--------------------------------------------------------
1. Buka browser, ketik alamat:
   https://domainanda.com/install.php
   (ganti "domainanda.com" dengan domain Anda)
2. Wizard akan tampil. Ikuti 1 halaman saja:
   - Cek Persyaratan: pastikan semua tanda hijau (OK).
   - Database: isi nama DB, user, password (dari Langkah 2).
   - Akun Admin: isi nama, email, dan password untuk login
     Anda sebagai pengelola.
   - Profil Yayasan: nama yayasan + nomor WhatsApp.
3. Klik "Jalankan Instalasi".
4. Muncul tulisan "Alhamdulillah, Instalasi Berhasil!".

Installer otomatis membuat semua tabel, akun admin, dan
beberapa data contoh (program, dll) agar tampilan langsung
terisi.

--------------------------------------------------------
LANGKAH 4 — AMANKAN INSTALLER (PENTING!)
--------------------------------------------------------
Setelah instalasi berhasil, kembali ke File Manager dan
HAPUS file "install.php" — atau ganti namanya menjadi
"install.lock". Ini mencegah orang lain menjalankan ulang
installer. (Panel admin juga akan mengingatkan Anda.)

--------------------------------------------------------
LANGKAH 5 — LOGIN & MULAI
--------------------------------------------------------
1. Buka: https://domainanda.com/admin
2. Login dengan email & password admin (dari Langkah 3).
3. Mulai isi: Program, Artikel, dan angka Statistik Dampak
   (Titik Air, Penerima Manfaat, dll) di menu Pengaturan.

--------------------------------------------------------
MENGAKTIFKAN FITUR LANJUTAN (OPSIONAL)
--------------------------------------------------------
Beberapa fitur sudah TERPASANG dan akan AKTIF otomatis
begitu Anda memasukkan "kunci API" di menu:
  Admin -> Pengaturan -> Kunci API

  • Duitku             -> isi Merchant Code + API Key,
                          aktifkan mode production bila
                          kredensial live, lalu set webhook ke
                          /api/webhook-duitku.php
  • Transfer Manual    -> isi rekening utama yayasan,
                          tambah rekening lain bila perlu,
                          dan unggah QRIS resmi di menu
                          Pengaturan -> Pembayaran Manual
  • Mailketing         -> isi API Token + List ID agar email
                          donatur/relawan otomatis masuk ke
                          list marketing.
  • Fonnte (WhatsApp)  -> isi Token dan bila perlu Device ID
                          untuk notifikasi WhatsApp otomatis.
  • Google Maps        -> peta sebaran program interaktif.

SEBELUM kunci Duitku diisi, platform tetap berjalan penuh
dengan alur manual: donatur transfer ke rekening/QRIS lalu
konfirmasi via WhatsApp, dan admin memverifikasi donasi dari
panel. Jadi Anda bisa langsung beroperasi hari ini.

--------------------------------------------------------
STRUKTUR ROLE ADMIN
--------------------------------------------------------
  • Super Admin     : akses penuh (termasuk user & settings).
  • Admin Program   : kelola program, relawan, artikel.
  • Admin Keuangan  : verifikasi donasi & catat penyaluran.
Tambah admin baru di menu "Pengguna" (khusus Super Admin).

--------------------------------------------------------
JIKA ADA MASALAH
--------------------------------------------------------
• Halaman putih / error koneksi:
  -> Cek kembali nama DB, user, password di installer.
     Jika perlu, hapus config.php lalu jalankan install.php
     lagi.
• Tampilan berantakan:
  -> Pastikan folder "assets" ikut ter-upload lengkap.
• Tombol tidak jalan:
  -> Pastikan file di folder "assets/js" ada.

--------------------------------------------------------
BACKUP & RESTORE DATA (PENTING)
--------------------------------------------------------
Lindungi data yayasan Anda secara berkala:
1. Login admin -> menu "Backup".
2. Klik "Buat Backup Sekarang" -> sistem membuat file .sql
   berisi seluruh data (program, donasi, relawan, dll).
3. Klik "Unduh" untuk menyimpan file backup ke komputer Anda.
4. Untuk memulihkan: menu Backup -> bagian "Restore",
   pilih file .sql -> Restore. (Sistem otomatis membuat
   backup cadangan sebelum menimpa data.)

Disarankan backup minimal seminggu sekali. File backup
tersimpan aman di folder "storage/backups".

--------------------------------------------------------
CARA GANTI NOMOR WHATSAPP & DATA YAYASAN
--------------------------------------------------------
Semua diubah tanpa menyentuh kode:
  Admin -> Pengaturan -> Profil Yayasan
  (nama, WhatsApp, email, alamat, visi-misi, legalitas).
Nomor WhatsApp yang Anda isi otomatis dipakai di semua
tombol WhatsApp di website.

========================================================
  Dibangun dengan amanah oleh Java Maya Studio.
  Dream Big — Build Mini — Launch Fast.
========================================================
