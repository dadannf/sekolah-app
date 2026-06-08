-- ============================================================================
-- SCRIPT SETUP PEMBAYARAN SERAGAM, PTS, PAS - RUNNABLE DI phpMyAdmin
-- ============================================================================
-- PENTING: Jalankan queries ini SATU PER SATU, DARI ATAS KE BAWAH
-- Tunggu query sebelumnya selesai sebelum menjalankan query berikutnya
-- ============================================================================

-- ===========================================================================
-- STEP 1: ADD COLUMN invoice_type KE spp_invoices TABLE
-- ===========================================================================
-- Deskripsi: Menambahkan kolom untuk membedakan jenis invoice
-- Status: HARUS dijalankan terlebih dahulu
-- Waktu: ~1 detik
-- ===========================================================================

ALTER TABLE `spp_invoices` ADD COLUMN `invoice_type` ENUM('spp','uniform','pts','pas') 
NOT NULL DEFAULT 'spp' AFTER `tariff_id`;

-- Jika ada error tentang duplicate column, berarti sudah ada
-- Lanjut ke STEP 2

-- ===========================================================================
-- STEP 2: ADD COLUMN uniform_cost KE spp_tariffs TABLE
-- ===========================================================================
-- Deskripsi: Kolom untuk harga seragam per kelas
-- Status: HARUS dijalankan setelah STEP 1
-- Waktu: ~1 detik
-- ===========================================================================

ALTER TABLE `spp_tariffs` ADD COLUMN `uniform_cost` INT UNSIGNED NULL AFTER `amount`;

-- Jika ada error tentang duplicate column, berarti sudah ada
-- Lanjut ke STEP 3

-- ===========================================================================
-- STEP 3: ADD COLUMN pts_cost KE spp_tariffs TABLE
-- ===========================================================================
-- Deskripsi: Kolom untuk harga PTS per kelas
-- Status: HARUS dijalankan setelah STEP 2
-- Waktu: ~1 detik
-- ===========================================================================

ALTER TABLE `spp_tariffs` ADD COLUMN `pts_cost` INT UNSIGNED NULL AFTER `uniform_cost`;

-- Jika ada error tentang duplicate column, berarti sudah ada
-- Lanjut ke STEP 4

-- ===========================================================================
-- STEP 4: ADD COLUMN pas_cost KE spp_tariffs TABLE
-- ===========================================================================
-- Deskripsi: Kolom untuk harga PAS per kelas
-- Status: HARUS dijalankan setelah STEP 3
-- Waktu: ~1 detik
-- ===========================================================================

ALTER TABLE `spp_tariffs` ADD COLUMN `pas_cost` INT UNSIGNED NULL AFTER `pts_cost`;

-- Jika ada error tentang duplicate column, berarti sudah ada
-- Lanjut ke STEP 5

-- ===========================================================================
-- STEP 5: UPDATE TARIFF DENGAN DEFAULT HARGA
-- ===========================================================================
-- Deskripsi: Set harga default untuk semua kelas aktif
-- Harga:
--   - Seragam: Rp 500.000
--   - PTS: Rp 200.000
--   - PAS: Rp 200.000
-- Status: HARUS dijalankan setelah STEP 4 (kolom sudah ada)
-- Waktu: ~1 detik
-- ===========================================================================

UPDATE `spp_tariffs` 
SET `uniform_cost` = 500000,
    `pts_cost` = 200000,
    `pas_cost` = 200000
WHERE `is_active` = 1;

-- Verifikasi dengan query:
-- SELECT grade_level, amount, uniform_cost, pts_cost, pas_cost FROM `spp_tariffs` WHERE is_active = 1;

-- ===========================================================================
-- STEP 6: GENERATE UNIFORM INVOICES
-- ===========================================================================
-- Deskripsi: Buat invoice seragam untuk semua siswa aktif (Bulan 1)
-- Status: BISA dijalankan setelah STEP 5
-- Waktu: ~2-5 detik (tergantung jumlah siswa)
-- ===========================================================================

INSERT INTO `spp_invoices` 
(`student_id`, `invoice_year`, `invoice_month`, `grade_level_at_invoice`, `tariff_id`, `invoice_type`, `amount_due`, `status`, `created_at`, `updated_at`)
SELECT 
    s.id,
    YEAR(NOW()),
    1 AS invoice_month,
    s.current_grade_level,
    st.id,
    'uniform',
    st.uniform_cost,
    'unpaid',
    NOW(),
    NOW()
FROM `students` s
INNER JOIN `spp_tariffs` st ON s.current_grade_level = st.grade_level AND st.is_active = 1
WHERE s.student_status = 'active'
    AND st.uniform_cost > 0
    AND NOT EXISTS (
        SELECT 1 FROM `spp_invoices` si 
        WHERE si.student_id = s.id 
        AND si.invoice_year = YEAR(NOW())
        AND si.invoice_type = 'uniform'
    );

-- ===========================================================================
-- STEP 7: GENERATE PTS INVOICES
-- ===========================================================================
-- Deskripsi: Buat invoice PTS untuk semua siswa aktif (Bulan 5)
-- Status: BISA dijalankan setelah STEP 5
-- Waktu: ~2-5 detik (tergantung jumlah siswa)
-- ===========================================================================

INSERT INTO `spp_invoices` 
(`student_id`, `invoice_year`, `invoice_month`, `grade_level_at_invoice`, `tariff_id`, `invoice_type`, `amount_due`, `status`, `created_at`, `updated_at`)
SELECT 
    s.id,
    YEAR(NOW()),
    5 AS invoice_month,
    s.current_grade_level,
    st.id,
    'pts',
    st.pts_cost,
    'unpaid',
    NOW(),
    NOW()
FROM `students` s
INNER JOIN `spp_tariffs` st ON s.current_grade_level = st.grade_level AND st.is_active = 1
WHERE s.student_status = 'active'
    AND st.pts_cost > 0
    AND NOT EXISTS (
        SELECT 1 FROM `spp_invoices` si 
        WHERE si.student_id = s.id 
        AND si.invoice_year = YEAR(NOW())
        AND si.invoice_type = 'pts'
    );

-- ===========================================================================
-- STEP 8: GENERATE PAS INVOICES
-- ===========================================================================
-- Deskripsi: Buat invoice PAS untuk semua siswa aktif (Bulan 12)
-- Status: BISA dijalankan setelah STEP 5
-- Waktu: ~2-5 detik (tergantung jumlah siswa)
-- ===========================================================================

INSERT INTO `spp_invoices` 
(`student_id`, `invoice_year`, `invoice_month`, `grade_level_at_invoice`, `tariff_id`, `invoice_type`, `amount_due`, `status`, `created_at`, `updated_at`)
SELECT 
    s.id,
    YEAR(NOW()),
    12 AS invoice_month,
    s.current_grade_level,
    st.id,
    'pas',
    st.pas_cost,
    'unpaid',
    NOW(),
    NOW()
FROM `students` s
INNER JOIN `spp_tariffs` st ON s.current_grade_level = st.grade_level AND st.is_active = 1
WHERE s.student_status = 'active'
    AND st.pas_cost > 0
    AND NOT EXISTS (
        SELECT 1 FROM `spp_invoices` si 
        WHERE si.student_id = s.id 
        AND si.invoice_year = YEAR(NOW())
        AND si.invoice_type = 'pas'
    );

-- ===========================================================================
-- VERIFIKASI: Jalankan queries dibawah SETELAH semua STEP selesai
-- ===========================================================================

-- Cek tariff sudah diupdate
SELECT '✓ Tariff Updated' AS Status, grade_level, amount, uniform_cost, pts_cost, pas_cost FROM `spp_tariffs` WHERE is_active = 1;

-- Cek invoices yang sudah dibuat
SELECT '✓ Invoices Created' AS Status, invoice_type, COUNT(*) AS total, SUM(amount_due) AS total_amount FROM `spp_invoices` WHERE invoice_year = YEAR(NOW()) AND invoice_type IN ('uniform','pts','pas') GROUP BY invoice_type;

-- Cek sample invoices untuk satu student
SELECT '✓ Sample per Student' AS Status, s.nis, s.name, si.invoice_type, si.amount_due, si.status FROM `spp_invoices` si JOIN `students` s ON si.student_id = s.id WHERE si.invoice_year = YEAR(NOW()) AND si.invoice_type IN ('uniform','pts','pas') LIMIT 5;
