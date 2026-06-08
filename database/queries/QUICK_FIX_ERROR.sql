-- ============================================================================
-- QUICK FIX: Error "Unknown column 'uniform_cost' in 'field list'"
-- ============================================================================
-- File ini berisi SEMUA query yang diperlukan dalam satu batch
-- Jalankan di phpMyAdmin, copy-paste SEMUA sekaligus
-- ============================================================================

-- 1. ADD COLUMN ke spp_invoices
ALTER TABLE `spp_invoices` ADD COLUMN `invoice_type` ENUM('spp','uniform','pts','pas') 
NOT NULL DEFAULT 'spp' AFTER `tariff_id`;

-- 2. ADD COLUMN uniform_cost ke spp_tariffs
ALTER TABLE `spp_tariffs` ADD COLUMN `uniform_cost` INT UNSIGNED NULL AFTER `amount`;

-- 3. ADD COLUMN pts_cost ke spp_tariffs
ALTER TABLE `spp_tariffs` ADD COLUMN `pts_cost` INT UNSIGNED NULL AFTER `uniform_cost`;

-- 4. ADD COLUMN pas_cost ke spp_tariffs
ALTER TABLE `spp_tariffs` ADD COLUMN `pas_cost` INT UNSIGNED NULL AFTER `pts_cost`;

-- 5. UPDATE tariff dengan harga default
UPDATE `spp_tariffs` 
SET `uniform_cost` = 500000,
    `pts_cost` = 200000,
    `pas_cost` = 200000
WHERE `is_active` = 1;

-- 5a. FIX UNIQUE KEY agar include invoice_type (CRITICAL!)
-- Drop unique key lama yang tidak include invoice_type
ALTER TABLE `spp_invoices` DROP INDEX IF EXISTS `uq_invoice_student_period`;

-- Create unique key baru yang include invoice_type
ALTER TABLE `spp_invoices` 
ADD UNIQUE KEY `uq_invoice_student_period_type` 
(`student_id`, `invoice_year`, `invoice_month`, `invoice_type`);

-- Sekarang error sudah hilang! Lanjut ke generate invoices...

-- 6. Generate UNIFORM invoices
INSERT IGNORE INTO `spp_invoices` 
(`student_id`, `invoice_year`, `invoice_month`, `grade_level_at_invoice`, `tariff_id`, `invoice_type`, `amount_due`, `status`, `created_at`, `updated_at`)
SELECT 
    s.id, YEAR(NOW()), 1, s.current_grade_level, st.id, 'uniform', st.uniform_cost, 'unpaid', NOW(), NOW()
FROM `students` s
INNER JOIN `spp_tariffs` st ON s.current_grade_level = st.grade_level AND st.is_active = 1
WHERE s.student_status = 'active' AND st.uniform_cost > 0;

-- 7. Generate PTS invoices
INSERT IGNORE INTO `spp_invoices` 
(`student_id`, `invoice_year`, `invoice_month`, `grade_level_at_invoice`, `tariff_id`, `invoice_type`, `amount_due`, `status`, `created_at`, `updated_at`)
SELECT 
    s.id, YEAR(NOW()), 5, s.current_grade_level, st.id, 'pts', st.pts_cost, 'unpaid', NOW(), NOW()
FROM `students` s
INNER JOIN `spp_tariffs` st ON s.current_grade_level = st.grade_level AND st.is_active = 1
WHERE s.student_status = 'active' AND st.pts_cost > 0;

-- 8. Generate PAS invoices
INSERT IGNORE INTO `spp_invoices` 
(`student_id`, `invoice_year`, `invoice_month`, `grade_level_at_invoice`, `tariff_id`, `invoice_type`, `amount_due`, `status`, `created_at`, `updated_at`)
SELECT 
    s.id, YEAR(NOW()), 12, s.current_grade_level, st.id, 'pas', st.pas_cost, 'unpaid', NOW(), NOW()
FROM `students` s
INNER JOIN `spp_tariffs` st ON s.current_grade_level = st.grade_level AND st.is_active = 1
WHERE s.student_status = 'active' AND st.pas_cost > 0;

-- VERIFIKASI
SELECT '✅ BERHASIL!' AS Status;
SELECT invoice_type, COUNT(*) AS total_invoices, SUM(amount_due) AS total_amount FROM `spp_invoices` WHERE invoice_year = YEAR(NOW()) AND invoice_type IN ('uniform','pts','pas') GROUP BY invoice_type;
