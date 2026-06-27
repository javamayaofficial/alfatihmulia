-- Reset data awal untuk kondisi yayasan/program masih baru mulai.
-- Aman untuk identitas yayasan: tidak menghapus akun admin, logo, legalitas,
-- rekening, dan pengaturan penting lainnya.

START TRANSACTION;

-- Nolkan capaian program tanpa menghapus daftar program.
UPDATE programs
SET beneficiaries = 0,
    collected_amount = 0;

-- Nolkan statistik manual pada dashboard dampak.
UPDATE impact_stats
SET svalue = 0;

-- Nolkan total dana tersalurkan manual.
UPDATE settings
SET svalue = '0'
WHERE skey IN ('total_tersalurkan');

-- Hapus bukti sosial contoh/demo agar tampilan lebih jujur.
DELETE FROM testimonials;
DELETE FROM partners;

COMMIT;

-- Jika Anda juga ingin menghapus data uji donasi/lead, jalankan blok opsional
-- berikut secara manual setelah backup database:
--
-- START TRANSACTION;
-- DELETE FROM donations;
-- DELETE FROM volunteer_leads;
-- DELETE FROM partnership_leads;
-- UPDATE programs SET beneficiaries = 0, collected_amount = 0;
-- UPDATE impact_stats SET svalue = 0;
-- UPDATE settings SET svalue = '0' WHERE skey IN ('total_tersalurkan');
-- DELETE FROM testimonials;
-- DELETE FROM partners;
-- COMMIT;
