-- ============================================================================
-- QUERY UNTUK MENAMBAHKAN PEMBAYARAN SERAGAM, PTS, DAN PAS (LENGKAP & BENAR URUTAN)
-- ============================================================================
-- Jalankan queries ini secara BERURUTAN dari atas ke bawah
-- ============================================================================

-- STEP 1: ADD COLUMN invoice_type KE spp_invoices TABLE
-- ============================================================================
ALTER TABLE `spp_invoices` ADD COLUMN `invoice_type` ENUM('spp', 'uniform', 'pts', 'pas') 
NOT NULL DEFAULT 'spp' AFTER `tariff_id` COMMENT 'Type of invoice: spp (monthly), uniform (seragam), pts (penilaian tengah semester), pas (penilaian akhir semester)';

ALTER TABLE `spp_invoices` ADD INDEX `idx_invoice_type` (`invoice_type`);


-- STEP 2: ADD COLUMNS UNTUK BIAYA TAMBAHAN KE spp_tariffs TABLE (PENTING!)
-- ============================================================================
ALTER TABLE `spp_tariffs` ADD COLUMN `uniform_cost` INT UNSIGNED NULL AFTER `amount` 
COMMENT 'Cost of school uniform (Rp)';

ALTER TABLE `spp_tariffs` ADD COLUMN `pts_cost` INT UNSIGNED NULL AFTER `uniform_cost` 
COMMENT 'PTS (Penilaian Tengah Semester) fee (Rp)';

ALTER TABLE `spp_tariffs` ADD COLUMN `pas_cost` INT UNSIGNED NULL AFTER `pts_cost` 
COMMENT 'PAS (Penilaian Akhir Semester) fee (Rp)';


-- STEP 3: VERIFIKASI KOLOM SUDAH DITAMBAHKAN
-- ============================================================================
-- Uncomment baris dibawah untuk verify
-- DESCRIBE spp_tariffs;


-- STEP 4: SET DEFAULT VALUES UNTUK TARIFF YANG ADA (SETELAH KOLOM DITAMBAHKAN)
-- ============================================================================
UPDATE `spp_tariffs` 
SET `uniform_cost` = 500000,  -- Rp 500.000 untuk seragam
    `pts_cost` = 200000,       -- Rp 200.000 untuk PTS
    `pas_cost` = 200000        -- Rp 200.000 untuk PAS
WHERE `is_active` = 1;


-- STEP 5: GENERATE INVOICES UNTUK UNIFORM, PTS, DAN PAS UNTUK SEMUA SISWA AKTIF
-- ============================================================================

-- 5a. UNIFORM INVOICES (Bulan 1 - Orientasi)
INSERT INTO `spp_invoices` 
(`student_id`, `invoice_year`, `invoice_month`, `grade_level_at_invoice`, `tariff_id`, `invoice_type`, `amount_due`, `status`, `created_at`, `updated_at`)
SELECT 
    s.id,
    YEAR(NOW()) AS invoice_year,
    1 AS invoice_month,
    s.current_grade_level,
    st.id AS tariff_id,
    'uniform' AS invoice_type,
    st.uniform_cost AS amount_due,
    'unpaid' AS status,
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


-- 5b. PTS INVOICES (Bulan 5 - Tengah Semester)
INSERT INTO `spp_invoices` 
(`student_id`, `invoice_year`, `invoice_month`, `grade_level_at_invoice`, `tariff_id`, `invoice_type`, `amount_due`, `status`, `created_at`, `updated_at`)
SELECT 
    s.id,
    YEAR(NOW()) AS invoice_year,
    5 AS invoice_month,
    s.current_grade_level,
    st.id AS tariff_id,
    'pts' AS invoice_type,
    st.pts_cost AS amount_due,
    'unpaid' AS status,
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


-- 5c. PAS INVOICES (Bulan 12 - Akhir Semester)
INSERT INTO `spp_invoices` 
(`student_id`, `invoice_year`, `invoice_month`, `grade_level_at_invoice`, `tariff_id`, `invoice_type`, `amount_due`, `status`, `created_at`, `updated_at`)
SELECT 
    s.id,
    YEAR(NOW()) AS invoice_year,
    12 AS invoice_month,
    s.current_grade_level,
    st.id AS tariff_id,
    'pas' AS invoice_type,
    st.pas_cost AS amount_due,
    'unpaid' AS status,
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


-- STEP 6: VERIFIKASI DATA YANG TELAH DITAMBAHKAN
-- ============================================================================

-- Lihat tariff yang sudah diupdate
SELECT 'SPP Tariffs Updated' AS info,
       grade_level, 
       amount AS spp_monthly, 
       uniform_cost, 
       pts_cost, 
       pas_cost 
FROM `spp_tariffs` 
WHERE `is_active` = 1 
ORDER BY grade_level;


-- Lihat invoices yang sudah ditambahkan (tahun 2026)
SELECT 'New Invoices Created' AS info,
       si.invoice_type,
       COUNT(*) AS total_invoices,
       SUM(si.amount_due) AS total_amount
FROM `spp_invoices` si
WHERE si.invoice_year = YEAR(NOW())
    AND si.invoice_type IN ('uniform', 'pts', 'pas')
GROUP BY si.invoice_type
ORDER BY FIELD(si.invoice_type, 'uniform', 'pts', 'pas');


-- Lihat sample invoices untuk first 10 students
SELECT 'Sample Invoices' AS info,
       s.nis,
       s.name,
       si.invoice_type,
       si.amount_due,
       si.status,
       si.invoice_month
FROM `spp_invoices` si
JOIN `students` s ON si.student_id = s.id
WHERE si.invoice_year = YEAR(NOW())
    AND si.invoice_type IN ('uniform', 'pts', 'pas')
LIMIT 10;
